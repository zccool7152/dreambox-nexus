<?php
declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Auth\OAuth\OAuthManager;
use Nexus\Auth\Rbac\RoleManager;
use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Crypto\Hasher;
use Nexus\Security\Csrf;
use Nexus\Security\Session;
use Nexus\View\QrCodeGenerator;
use Nexus\View\View;

class AuthController
{
    private Auth $auth;
    private UserRepository $userRepo;
    private Mfa $mfa;
    private AccountLockout $lockout;
    private OAuthManager $oauth;
    private RoleManager $rbac;
    private View $view;
    private Session $session;
    private Csrf $csrf;

    public function __construct(
        Auth $auth,
        UserRepository $userRepo,
        Mfa $mfa,
        AccountLockout $lockout,
        OAuthManager $oauth,
        RoleManager $rbac,
        View $view,
        Session $session,
        Csrf $csrf
    ) {
        $this->auth = $auth;
        $this->userRepo = $userRepo;
        $this->mfa = $mfa;
        $this->lockout = $lockout;
        $this->oauth = $oauth;
        $this->rbac = $rbac;
        $this->view = $view;
        $this->session = $session;
        $this->csrf = $csrf;
    }

    private function verifyCsrf(Request $request, string $redirectPath, string $flashKey = 'auth_error'): ?Response
    {
        $this->session->start();
        if (!$this->csrf->verifyRequest($request, $this->session->getId())) {
            if ($request->wantsJson()) {
                return Response::error('CSRF token validation failed.', 419, 'CSRF_INVALID');
            }
            $this->session->flash($flashKey, 'Invalid security token (CSRF). Please retry.');
            return Response::redirect(Request::getAppBase() . $redirectPath);
        }
        return null;
    }

    public function showLogin(Request $request): Response
    {
        if ($this->auth->check($request)) {
            return Response::redirect(Request::getAppBase() . '/admin/');
        }

        $this->session->start();
        $appBase = Request::getAppBase();
        $csrfToken = $this->csrf->generate($this->session->getId());
        $html = $this->view->render('auth.login', [
            'appBase' => $appBase,
            'csrfToken' => $csrfToken,
            'nonce' => \Nexus\Security\Headers::getNonce(),
            'error' => $this->session->getFlash('auth_error'),
            'success' => $this->session->getFlash('auth_success'),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function login(Request $request): Response
    {
        $csrfErr = $this->verifyCsrf($request, '/auth/login/', 'auth_error');
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $email = strtolower(trim($request->getString('email', '')));
        $password = $request->getString('password', '');
        $ip = $request->getClientIp();
        $appBase = Request::getAppBase();

        if (empty($email) || empty($password)) {
            if (!$request->wantsJson()) {
                $this->session->flash('auth_error', 'Email and password are required.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('Email and password are required.', 422, 'VALIDATION_ERROR');
        }

        // PCI-DSS Req 8.3.4 Account Lockout Check
        if ($this->lockout->isLocked($email)) {
            $rem = $this->lockout->getLockoutRemainingSeconds($email);
            $mins = ceil($rem / 60);
            $msg = "Account is locked due to repeated failed login attempts. Try again in {$mins} minutes.";
            if (!$request->wantsJson()) {
                $this->session->flash('auth_error', $msg);
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error($msg, 423, 'ACCOUNT_LOCKED');
        }

        $user = $this->userRepo->findByEmail($email);
        $hasher = new Hasher();

        $validPassword = false;
        if ($user !== null && !empty($user['password_hash'])) {
            $validPassword = $hasher->verify($password, $user['password_hash']);
        }

        if (!$validPassword) {
            $this->lockout->recordFailedAttempt($email, $ip);
            if (!$request->wantsJson()) {
                $this->session->flash('auth_error', 'Invalid email or password.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('Invalid credentials provided.', 401, 'INVALID_CREDENTIALS');
        }

        if ($user['status'] === 'suspended') {
            if (!$request->wantsJson()) {
                $this->session->flash('auth_error', 'This account has been administratively suspended.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('This account has been administratively suspended.', 403, 'ACCOUNT_SUSPENDED');
        }

        $this->lockout->resetAttempts($email);

        // 2FA Enforcement for Email logins
        $this->session->start();
        $this->session->set('pending_auth_user_id', $user['id']);

        if (empty($user['is_2fa_enabled']) || empty($user['totp_secret'])) {
            // First time email login: Mandate Google Authenticator 2FA Setup
            if (!$request->wantsJson()) {
                return Response::redirect($appBase . '/auth/2fa/setup/');
            }
            return Response::json([
                'requires_2fa_setup' => true,
                'redirect' => $appBase . '/auth/2fa/setup/',
            ], 200, 'Two-factor authentication enrollment required.');
        }

        // Established email login: Prompt for 6-digit TOTP
        if (!$request->wantsJson()) {
            return Response::redirect($appBase . '/auth/2fa/');
        }
        return Response::json([
            'requires_2fa' => true,
            'redirect' => $appBase . '/auth/2fa/',
        ], 200, 'Two-factor authentication challenge required.');
    }

    public function showSetup2fa(Request $request): Response
    {
        $this->session->start();
        $userId = $this->session->get('pending_auth_user_id');
        if (empty($userId)) {
            return Response::redirect(Request::getAppBase() . '/auth/login');
        }

        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            return Response::redirect(Request::getAppBase() . '/auth/login');
        }

        $secret = $this->session->get('pending_totp_secret');
        if (empty($secret)) {
            $secret = $this->mfa->generateSecret();
            $this->session->set('pending_totp_secret', $secret);
        }

        $appName = 'Dreambox Nexus';
        $otpUri = $this->mfa->getProvisioningUri($user['email'], $secret, $appName);
        $qrSvg = QrCodeGenerator::svg($otpUri, 220);

        $html = $this->view->render('auth.setup_2fa', [
            'appBase' => Request::getAppBase(),
            'user' => $user,
            'secret' => $secret,
            'qrSvg' => $qrSvg,
            'csrfToken' => $this->csrf->generate($this->session->getId()),
            'error' => $this->session->getFlash('mfa_error'),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function processSetup2fa(Request $request): Response
    {
        $csrfErr = $this->verifyCsrf($request, '/auth/2fa/setup/', 'mfa_error');
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $userId = $this->session->get('pending_auth_user_id');
        $secret = $this->session->get('pending_totp_secret');
        $code = trim($request->getString('code', ''));
        $appBase = Request::getAppBase();

        if (empty($userId) || empty($secret)) {
            if (!$request->wantsJson()) {
                $this->session->flash('mfa_error', 'Session expired. Please log in again.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('Session expired. Please log in again.', 401);
        }

        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            if (!$request->wantsJson()) {
                $this->session->flash('auth_error', 'User identity not found.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('User identity not found.', 404);
        }

        if ($this->lockout->isLocked($user['email'])) {
            $rem = $this->lockout->getLockoutRemainingSeconds($user['email']);
            $mins = ceil($rem / 60);
            $msg = "Account is locked due to repeated failed verification attempts. Try again in {$mins} minutes.";
            if (!$request->wantsJson()) {
                $this->session->flash('mfa_error', $msg);
                return Response::redirect($appBase . '/auth/2fa/setup/');
            }
            return Response::error($msg, 423, 'ACCOUNT_LOCKED');
        }

        $isReplay = $this->session->get('last_verified_totp_code') === $code;
        if (empty($code) || $isReplay || !$this->mfa->verifyCode($secret, $code)) {
            $this->lockout->recordFailedAttempt($user['email'], $request->getClientIp());
            if (!$request->wantsJson()) {
                $this->session->flash('mfa_error', 'Invalid verification code from Google Authenticator.');
                return Response::redirect($appBase . '/auth/2fa/setup/');
            }
            return Response::error('Invalid verification code from Google Authenticator.', 422);
        }

        $this->lockout->resetAttempts($user['email']);
        $this->session->set('last_verified_totp_code', $code);

        $recoveryCodes = $this->mfa->generateRecoveryCodes(8);
        $this->userRepo->update($userId, [
            'is_2fa_enabled' => 1,
            'totp_secret' => $secret,
            'recovery_codes' => $recoveryCodes,
        ]);

        $user = $this->userRepo->findById($userId);
        $this->session->remove('pending_auth_user_id');
        $this->session->remove('pending_totp_secret');

        $mustChange = !empty($user['must_change_password']);
        $this->auth->loginWithSession(
            $user['id'],
            $user['email'],
            $user['name'],
            $user['roles'] ?? ['Member'],
            $user['avatar_url'] ?? '',
            $user['auth_provider'] ?? 'email',
            $mustChange,
            $user['username'] ?? '',
            $user['display_preference'] ?? 'name',
            $user['groups'] ?? []
        );

        $redirectUrl = $mustChange ? ($appBase . '/auth/password/change/') : ($appBase . '/admin/');

        if (!$request->wantsJson()) {
            return Response::redirect($redirectUrl);
        }

        return Response::json([
            'authenticated' => true,
            'recovery_codes' => $recoveryCodes,
            'must_change_password' => $mustChange,
            'redirect' => $redirectUrl,
        ], 200, 'Two-factor authentication successfully enabled.');
    }

    public function showTotp(Request $request): Response
    {
        $this->session->start();
        $userId = $this->session->get('pending_auth_user_id');
        if (empty($userId)) {
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        $html = $this->view->render('auth.totp', [
            'appBase' => Request::getAppBase(),
            'user' => $user,
            'csrfToken' => $this->csrf->generate($this->session->getId()),
            'error' => $this->session->getFlash('mfa_error'),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function verifyTotp(Request $request): Response
    {
        $csrfErr = $this->verifyCsrf($request, '/auth/2fa/', 'mfa_error');
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $userId = $this->session->get('pending_auth_user_id');
        $code = trim($request->getString('code', ''));
        $appBase = Request::getAppBase();

        if (empty($userId)) {
            if (!$request->wantsJson()) {
                $this->session->flash('mfa_error', 'Authentication challenge session expired.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('Authentication challenge session expired.', 401);
        }

        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            if (!$request->wantsJson()) {
                $this->session->flash('auth_error', 'User identity not found.');
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('User identity not found.', 404);
        }

        if ($this->lockout->isLocked($user['email'])) {
            $rem = $this->lockout->getLockoutRemainingSeconds($user['email']);
            $mins = ceil($rem / 60);
            $msg = "Account is locked due to repeated failed verification attempts. Try again in {$mins} minutes.";
            if (!$request->wantsJson()) {
                $this->session->flash('mfa_error', $msg);
                return Response::redirect($appBase . '/auth/2fa/');
            }
            return Response::error($msg, 423, 'ACCOUNT_LOCKED');
        }

        $secret = $user['totp_secret'] ?? '';
        $verified = false;
        $isReplay = $this->session->get('last_verified_totp_code') === $code;

        if (!empty($secret) && !$isReplay && $this->mfa->verifyCode($secret, $code)) {
            $verified = true;
            $this->session->set('last_verified_totp_code', $code);
        } else {
            // Check emergency recovery codes
            $recCodes = $user['recovery_codes'] ?? [];
            if (in_array($code, $recCodes, true)) {
                $verified = true;
                // Invalidate the used recovery code
                $recCodes = array_values(array_diff($recCodes, [$code]));
                $this->userRepo->update($user['id'], ['recovery_codes' => $recCodes]);
            }
        }

        if (!$verified) {
            $this->lockout->recordFailedAttempt($user['email'], $request->getClientIp());
            if (!$request->wantsJson()) {
                $this->session->flash('mfa_error', 'Invalid Google Authenticator verification code.');
                return Response::redirect($appBase . '/auth/2fa/');
            }
            return Response::error('Invalid Google Authenticator verification code.', 401);
        }

        $this->lockout->resetAttempts($user['email']);

        $this->session->remove('pending_auth_user_id');
        $mustChange = !empty($user['must_change_password']);
        $this->auth->loginWithSession(
            $user['id'],
            $user['email'],
            $user['name'],
            $user['roles'] ?? ['Member'],
            $user['avatar_url'] ?? '',
            $user['auth_provider'] ?? 'email',
            $mustChange,
            $user['username'] ?? '',
            $user['display_preference'] ?? 'name',
            $user['groups'] ?? []
        );

        $redirectUrl = $mustChange ? ($appBase . '/auth/password/change/') : ($appBase . '/admin/');

        if (!$request->wantsJson()) {
            return Response::redirect($redirectUrl);
        }

        return Response::json([
            'authenticated' => true,
            'must_change_password' => $mustChange,
            'redirect' => $redirectUrl,
        ], 200, 'Authentication complete.');
    }

    public function showChangePassword(Request $request): Response
    {
        $user = $this->auth->user($request);
        if ($user === null) {
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        $this->session->start();
        $html = $this->view->render('auth.change_password', [
            'appBase' => Request::getAppBase(),
            'user' => $user,
            'csrfToken' => $this->csrf->generate($this->session->getId()),
            'error' => $this->session->getFlash('pwd_error'),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function changePassword(Request $request): Response
    {
        $csrfErr = $this->verifyCsrf($request, '/auth/password/change/', 'pwd_error');
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $user = $this->auth->user($request);
        $appBase = Request::getAppBase();

        if ($user === null) {
            if (!$request->wantsJson()) {
                return Response::redirect($appBase . '/auth/login/');
            }
            return Response::error('Unauthorized session.', 401);
        }

        $currentPassword = $request->getString('current_password', '');
        $newPassword = $request->getString('new_password', '');
        $confirmPassword = $request->getString('confirm_password', '');

        $err = null;
        if (empty($currentPassword) || empty($newPassword)) {
            $err = 'Current password and new password are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $err = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 8) {
            $err = 'New password must be at least 8 characters long.';
        } elseif ($newPassword === $currentPassword) {
            $err = 'New password cannot be identical to the temporary initial password.';
        }

        if ($err !== null) {
            if (!$request->wantsJson()) {
                $this->session->flash('pwd_error', $err);
                return Response::redirect($appBase . '/auth/password/change/');
            }
            return Response::error($err, 422);
        }

        $fullUser = $this->userRepo->findById($user['id']);
        $hasher = new Hasher();
        if ($fullUser === null || empty($fullUser['password_hash']) || !$hasher->verify($currentPassword, $fullUser['password_hash'])) {
            $err = 'Current password verification failed.';
            if (!$request->wantsJson()) {
                $this->session->flash('pwd_error', $err);
                return Response::redirect($appBase . '/auth/password/change/');
            }
            return Response::error($err, 401);
        }

        $this->userRepo->update($user['id'], [
            'password_hash' => $hasher->hash($newPassword),
            'must_change_password' => 0,
        ]);

        $this->session->set('must_change_password', false);

        if (!$request->wantsJson()) {
            return Response::redirect($appBase . '/admin/');
        }

        return Response::json([
            'success' => true,
            'redirect' => $appBase . '/admin/',
        ], 200, 'Password successfully updated.');
    }

    public function redirectToOAuth(Request $request, string $provider): Response
    {
        try {
            $state = $this->oauth->generateState();
            $oauthProvider = $this->oauth->getProvider($provider);
            $url = $oauthProvider->getAuthorizationUrl($state);
            return Response::redirect($url);
        } catch (\Throwable $e) {
            $this->session->flash('auth_error', $e->getMessage());
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }
    }

    public function handleOAuthCallback(Request $request, string $provider): Response
    {
        $error = $request->getString('error', '');
        if ($error !== '') {
            $errorDesc = $request->getString('error_description', $error);
            $this->session->flash('auth_error', 'OAuth authorization was cancelled or declined: ' . $errorDesc);
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        $code = $request->getString('code', '');
        $state = $request->getString('state', '');

        if (!$this->oauth->validateState($state)) {
            error_log("[OAuth Debug] State mismatch on {$provider} callback. Inbound state: '{$state}', Session state: '" . ($this->session->get('oauth_state') ?? 'NULL') . "'");
            $this->session->flash('auth_error', 'Invalid OAuth state parameter (CSRF detected). Please try again.');
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        try {
            $oauthProvider = $this->oauth->getProvider($provider);
            $profile = $oauthProvider->handleCallback($code);

            // Find or provision user
            $user = $this->userRepo->findByOAuth($profile['provider'], $profile['provider_id']);
            if ($user === null) {
                // Check if account with same email already exists
                $user = $this->userRepo->findByEmail($profile['email']);
                if ($user !== null) {
                    $this->userRepo->update($user['id'], [
                        'auth_provider' => $profile['provider'],
                        'provider_id' => $profile['provider_id'],
                        'avatar_url' => $profile['avatar_url'],
                    ]);
                    $user = $this->userRepo->findById($user['id']);
                } else {
                    $emailParts = explode('@', $profile['email']);
                    $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $emailParts[0] ?? 'user'));
                    $user = $this->userRepo->create([
                        'email' => $profile['email'],
                        'name' => $profile['name'],
                        'username' => $baseUsername !== '' ? $baseUsername : 'oauth_user',
                        'display_preference' => 'name',
                        'auth_provider' => $profile['provider'],
                        'provider_id' => $profile['provider_id'],
                        'avatar_url' => $profile['avatar_url'],
                        'status' => 'active',
                        'roles' => ['Member'],
                        'is_2fa_enabled' => false,
                    ]);
                }
            }

            if ($user['status'] === 'suspended') {
                $this->session->flash('auth_error', 'Your account has been administratively suspended.');
                return Response::redirect(Request::getAppBase() . '/auth/login/');
            }

            if (!empty($user['is_2fa_enabled'])) {
                $this->session->start();
                $this->session->set('pending_auth_user_id', $user['id']);
                return Response::redirect(Request::getAppBase() . '/auth/2fa/');
            }

            $mustChange = !empty($user['must_change_password']);
            $this->auth->loginWithSession(
                $user['id'],
                $user['email'],
                $user['name'],
                $user['roles'] ?? ['Member'],
                $user['avatar_url'] ?? '',
                $user['auth_provider'] ?? 'email',
                $mustChange,
                $user['username'] ?? '',
                $user['display_preference'] ?? 'name',
                $user['groups'] ?? []
            );

            $roles = (array)($user['roles'] ?? ['Member']);
            $isAdmin = in_array('SuperAdmin', $roles, true) || in_array('Admin', $roles, true) || in_array('SecurityOfficer', $roles, true);
            $destination = $mustChange ? '/auth/password/change/' : ($isAdmin ? '/admin/' : '/account/');

            return Response::redirect(Request::getAppBase() . $destination);
        } catch (\Throwable $e) {
            $this->session->flash('auth_error', 'OAuth authentication failed: ' . $e->getMessage());
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        if ($request->wantsJson()) {
            return Response::json([], 200, 'Successfully logged out.');
        }
        return Response::redirect(Request::getAppBase() . '/auth/login/');
    }
}
