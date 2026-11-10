<?php
declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Crypto\Hasher;
use Nexus\Database\Database;
use Nexus\Events\AuditLogger;
use Nexus\Security\Csrf;
use Nexus\Security\Session;
use Nexus\View\View;

class AccountController
{
    private Auth $auth;
    private UserRepository $userRepo;
    private Hasher $hasher;
    private ?Database $db;
    private View $view;
    private Csrf $csrf;
    private Session $session;

    public function __construct(
        Auth $auth,
        UserRepository $userRepo,
        Hasher $hasher,
        ?Database $db,
        View $view,
        Csrf $csrf,
        Session $session
    ) {
        $this->auth = $auth;
        $this->userRepo = $userRepo;
        $this->hasher = $hasher;
        $this->db = $db;
        $this->view = $view;
        $this->csrf = $csrf;
        $this->session = $session;
    }

    public function show(Request $request): Response
    {
        $sessionUser = $this->auth->user($request);
        if ($sessionUser === null) {
            if ($request->wantsJson()) {
                return Response::error('Authentication required.', [], 401, [], $request->getTraceId());
            }
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        $user = $this->userRepo->findById($sessionUser['id']) ?? $sessionUser;
        $appBase = Request::getAppBase();

        if ($request->wantsJson()) {
            return Response::json([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'username' => $user['username'] ?? '',
                    'display_preference' => $user['display_preference'] ?? 'name',
                    'roles' => $user['roles'] ?? ['Member'],
                    'groups' => $user['groups'] ?? [],
                    'avatar_url' => $user['avatar_url'] ?? '',
                    'is_2fa_enabled' => !empty($user['is_2fa_enabled']),
                    'created_at' => $user['created_at'] ?? '',
                ]
            ], 'User profile retrieved.', 200, [], $request->getTraceId());
        }

        $html = $this->view->render('account.index', [
            'appBase' => $appBase,
            'user' => $user,
            'csrfToken' => $this->csrf->generateToken($this->session->getId()),
            'success' => $this->session->getFlash('account_success'),
            'error' => $this->session->getFlash('account_error'),
            'traceId' => $request->getTraceId(),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function updateProfile(Request $request): Response
    {
        $sessionUser = $this->auth->user($request);
        if ($sessionUser === null) {
            return Response::error('Authentication required.', [], 401, [], $request->getTraceId());
        }

        // Verify CSRF for web forms
        if (!$request->wantsJson()) {
            $token = $request->getString('csrf_token');
            if (!$this->csrf->validateToken($token, $this->session->getId())) {
                $this->session->flash('account_error', 'Invalid security token (CSRF). Please retry.');
                return Response::redirect(Request::getAppBase() . '/account/');
            }
        }

        $name = trim($request->getString('name'));
        $username = strtolower(trim($request->getString('username')));
        $displayPreference = trim($request->getString('display_preference', 'name'));
        $avatarUrl = trim($request->getString('avatar_url', ''));

        if ($name === '') {
            if ($request->wantsJson()) {
                return Response::error('Validation failure.', ['name' => 'Name cannot be empty.'], 422, [], $request->getTraceId());
            }
            $this->session->flash('account_error', 'Name cannot be empty.');
            return Response::redirect(Request::getAppBase() . '/account/');
        }

        if ($username !== '' && !preg_match('/^[a-z0-9_\-\.]{3,32}$/', $username)) {
            $msg = 'Username must be between 3 and 32 characters, consisting of lowercase letters, numbers, dots, hyphens, and underscores.';
            if ($request->wantsJson()) {
                return Response::error('Validation failure.', ['username' => $msg], 422, [], $request->getTraceId());
            }
            $this->session->flash('account_error', $msg);
            return Response::redirect(Request::getAppBase() . '/account/');
        }

        if (!in_array($displayPreference, ['name', 'username'], true)) {
            $displayPreference = 'name';
        }

        // Check username uniqueness if changed
        if ($username !== '') {
            $existing = $this->userRepo->findByUsername($username);
            if ($existing !== null && $existing['id'] !== $sessionUser['id']) {
                $msg = "Username '{$username}' is already taken by another account.";
                if ($request->wantsJson()) {
                    return Response::error('Validation failure.', ['username' => $msg], 422, [], $request->getTraceId());
                }
                $this->session->flash('account_error', $msg);
                return Response::redirect(Request::getAppBase() . '/account/');
            }
        }

        $updateFields = [
            'name' => $name,
            'username' => $username,
            'display_preference' => $displayPreference,
        ];
        if ($avatarUrl !== '') {
            $updateFields['avatar_url'] = $avatarUrl;
        }

        $this->userRepo->update($sessionUser['id'], $updateFields);
        $this->auth->updateSessionUser($updateFields);

        // Record Audit Log
        if ($this->db !== null) {
            $auditLogger = new AuditLogger($this->db);
            $auditLogger->record(
                'account.profile_updated',
                $sessionUser['id'],
                $request->getClientIp(),
                $request->getUserAgent(),
                ['updated_fields' => array_keys($updateFields)]
            );
        }

        if ($request->wantsJson()) {
            return Response::json([
                'updated' => true,
                'user' => $this->userRepo->findById($sessionUser['id']),
            ], 'Profile updated successfully.', 200, [], $request->getTraceId());
        }

        $this->session->flash('account_success', 'Your profile details and display preferences have been updated.');
        return Response::redirect(Request::getAppBase() . '/account/');
    }

    public function changePassword(Request $request): Response
    {
        $sessionUser = $this->auth->user($request);
        if ($sessionUser === null) {
            return Response::error('Authentication required.', [], 401, [], $request->getTraceId());
        }

        if (!$request->wantsJson()) {
            $token = $request->getString('csrf_token');
            if (!$this->csrf->validateToken($token, $this->session->getId())) {
                $this->session->flash('account_error', 'Invalid security token (CSRF). Please retry.');
                return Response::redirect(Request::getAppBase() . '/account/');
            }
        }

        $currentPassword = $request->getString('current_password');
        $newPassword = $request->getString('new_password');
        $confirmPassword = $request->getString('new_password_confirmation', $request->getString('confirm_password'));

        $fullUser = $this->userRepo->findById($sessionUser['id']);
        if ($fullUser === null) {
            return Response::error('User identity not found.', [], 404, [], $request->getTraceId());
        }

        // Verify current password
        $currentValid = false;
        if (!empty($fullUser['password_hash'])) {
            $currentValid = $this->hasher->verify($currentPassword, $fullUser['password_hash']);
        }
        if (!$currentValid) {
            $msg = 'Current password does not match our records.';
            if ($request->wantsJson()) {
                return Response::error('Validation failure.', ['current_password' => $msg], 401, [], $request->getTraceId());
            }
            $this->session->flash('account_error', $msg);
            return Response::redirect(Request::getAppBase() . '/account/');
        }

        // Validate new password
        if (strlen($newPassword) < 8) {
            $msg = 'New password must be at least 8 characters long.';
            if ($request->wantsJson()) {
                return Response::error('Validation failure.', ['new_password' => $msg], 422, [], $request->getTraceId());
            }
            $this->session->flash('account_error', $msg);
            return Response::redirect(Request::getAppBase() . '/account/');
        }

        if ($newPassword !== $confirmPassword) {
            $msg = 'New password and confirmation do not match.';
            if ($request->wantsJson()) {
                return Response::error('Validation failure.', ['new_password_confirmation' => $msg], 422, [], $request->getTraceId());
            }
            $this->session->flash('account_error', $msg);
            return Response::redirect(Request::getAppBase() . '/account/');
        }

        // Update password with Argon2id hash
        $newHash = $this->hasher->hash($newPassword);
        $this->userRepo->update($sessionUser['id'], [
            'password_hash' => $newHash,
            'must_change_password' => 0,
        ]);

        if ($this->db !== null) {
            $auditLogger = new AuditLogger($this->db);
            $auditLogger->record(
                'account.password_changed',
                $sessionUser['id'],
                $request->getClientIp(),
                $request->getUserAgent(),
                ['algorithm' => 'Argon2id']
            );
        }

        if ($request->wantsJson()) {
            return Response::json(['password_changed' => true], 'Password updated successfully.', 200, [], $request->getTraceId());
        }

        $this->session->flash('account_success', 'Your password has been changed successfully.');
        return Response::redirect(Request::getAppBase() . '/account/');
    }
}
