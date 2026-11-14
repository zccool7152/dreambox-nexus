<?php
declare(strict_types=1);

namespace Nexus\Admin;

use Nexus\Auth\Auth;
use Nexus\Auth\Rbac\GroupManager;
use Nexus\Auth\Rbac\RoleManager;
use Nexus\Auth\UserRepository;
use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Database\Database;
use Nexus\Events\AuditLogger;
use Nexus\Security\Csrf;
use Nexus\View\View;

class AdminController
{
    private Auth $auth;
    private UserRepository $userRepo;
    private RoleManager $roleMgr;
    private GroupManager $groupMgr;
    private ?Database $db;
    private View $view;
    private Csrf $csrf;
    private ?\Nexus\Security\Session $session;

    public function __construct(
        Auth $auth,
        UserRepository $userRepo,
        RoleManager $roleMgr,
        GroupManager $groupMgr,
        ?Database $db,
        View $view,
        Csrf $csrf,
        ?\Nexus\Security\Session $session = null
    ) {
        $this->auth = $auth;
        $this->userRepo = $userRepo;
        $this->roleMgr = $roleMgr;
        $this->groupMgr = $groupMgr;
        $this->db = $db;
        $this->view = $view;
        $this->csrf = $csrf;
        $this->session = $session;
    }

    private function authenticateAdmin(Request $request, string $requiredPermission = 'users.view'): ?array
    {
        $user = $this->auth->user($request);
        if ($user === null) {
            return null;
        }

        if (!$this->roleMgr->userHasPermission($user, $requiredPermission)) {
            return null;
        }

        return $user;
    }

    private function verifyCsrf(Request $request): ?Response
    {
        if ($this->session !== null) {
            $this->session->start();
        }
        $sessionId = $this->session ? $this->session->getId() : '';
        if (!$this->csrf->verifyRequest($request, $sessionId)) {
            if ($request->wantsJson()) {
                return Response::error('CSRF token validation failed.', 419, 'CSRF_INVALID');
            }
            if ($this->session !== null) {
                $this->session->flash('admin_error', 'Invalid security token (CSRF). Please retry.');
            }
            return Response::redirect(Request::getAppBase() . '/admin/users/');
        }
        return null;
    }

    public function dashboard(Request $request): Response
    {
        return $this->users($request);
    }

    public function users(Request $request): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'users.view');
        if ($currentUser === null) {
            if ($request->wantsJson()) {
                return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
            }
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        if (!empty($currentUser['must_change_password'])) {
            if ($request->wantsJson()) {
                return Response::error('First-time authentication: password rotation required.', 403, 'PASSWORD_CHANGE_REQUIRED');
            }
            return Response::redirect(Request::getAppBase() . '/auth/password/change/');
        }

        $users = $this->userRepo->all();
        $roles = $this->roleMgr->getRoles();
        $groups = $this->groupMgr->getGroups();

        if ($request->wantsJson()) {
            return Response::json([
                'users' => $users,
                'total' => count($users),
            ], 200, 'User directory retrieved.');
        }

        if ($this->session !== null) {
            $this->session->start();
        }

        $appBase = Request::getAppBase();
        $html = $this->view->render('admin.users', [
            'appBase' => $appBase,
            'currentUser' => $currentUser,
            'users' => $users,
            'roles' => $roles,
            'groups' => $groups,
            'csrfToken' => $this->csrf->generate($this->session?->getId()),
            'nonce' => \Nexus\Security\Headers::getNonce(),
            'error' => $this->session ? $this->session->getFlash('admin_error') : null,
            'success' => $this->session ? $this->session->getFlash('admin_success') : null,
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function createUser(Request $request): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'users.manage');
        if ($currentUser === null) {
            return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
        }

        $csrfErr = $this->verifyCsrf($request);
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $email = strtolower(trim($request->getString('email', '')));
        $name = trim($request->getString('name', ''));
        $role = $request->getString('role', 'Member');
        $password = $request->getString('password', '');
        $mailAccess = !empty($request->get('mail_access'));
        $appBase = Request::getAppBase();

        if (empty($email) || empty($name)) {
            if (!$request->wantsJson()) {
                if ($this->session !== null) $this->session->flash('admin_error', 'Email and name are required.');
                return Response::redirect($appBase . '/admin/users/');
            }
            return Response::error('Email and name are required.', 422);
        }

        if (empty($password)) {
            if (!$request->wantsJson()) {
                if ($this->session !== null) $this->session->flash('admin_error', 'A secure initial password is required.');
                return Response::redirect($appBase . '/admin/users/');
            }
            return Response::error('A secure initial password is required.', 422);
        }

        $existing = $this->userRepo->findByEmail($email);
        if ($existing !== null) {
            if (!$request->wantsJson()) {
                if ($this->session !== null) $this->session->flash('admin_error', 'A user with this email address already exists.');
                return Response::redirect($appBase . '/admin/users/');
            }
            return Response::error('A user with this email address already exists.', 409);
        }

        $newUser = $this->userRepo->create([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'auth_provider' => 'email',
            'roles' => [$role],
            'status' => 'active',
            'is_2fa_enabled' => false,
            'mail_access' => $mailAccess,
        ]);

        $this->recordAudit(
            $request,
            $currentUser['id'],
            'USER_CREATE',
            'users',
            $newUser['id'],
            null,
            ['email' => $email, 'role' => $role]
        );

        if ($request->wantsJson()) {
            return Response::json($newUser, 201, 'User successfully provisioned.');
        }

        if ($this->session !== null) {
            $this->session->flash('admin_success', "User identity {$newUser['email']} successfully provisioned.");
        }

        return Response::redirect($appBase . '/admin/users/');
    }

    public function updateUser(Request $request, string $id): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'users.manage');
        if ($currentUser === null) {
            return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
        }

        $csrfErr = $this->verifyCsrf($request);
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $appBase = Request::getAppBase();
        $user = $this->userRepo->findById($id);
        if ($user === null) {
            if (!$request->wantsJson()) {
                if ($this->session !== null) $this->session->flash('admin_error', 'User identity not found.');
                return Response::redirect($appBase . '/admin/users/');
            }
            return Response::error('User identity not found.', 404);
        }

        $fields = [];
        if ($request->has('status')) {
            $status = $request->getString('status');
            if ($status !== '') {
                $fields['status'] = $status;
            }
        }

        if ($request->has('role')) {
            $role = $request->getString('role');
            if ($role !== '') {
                $fields['roles'] = [$role];
            }
        }

        if ($request->has('reset_2fa')) {
            $fields['is_2fa_enabled'] = 0;
            $fields['totp_secret'] = null;
            $fields['recovery_codes'] = [];
        }

        if ($request->has('toggle_mail_access')) {
            $fields['mail_access'] = $request->getString('toggle_mail_access') === '1';
        }

        if (!empty($fields)) {
            $this->userRepo->update($id, $fields);
            $this->recordAudit(
                $request,
                $currentUser['id'],
                'USER_UPDATE',
                'users',
                $id,
                $user,
                $fields
            );
        }

        if ($request->wantsJson()) {
            return Response::json($this->userRepo->findById($id), 200, 'User updated.');
        }

        if ($this->session !== null) {
            $msg = 'User identity updated.';
            if (isset($fields['status'])) {
                $msg = "User {$user['email']} status changed to {$fields['status']}.";
            } elseif (isset($fields['is_2fa_enabled']) && $fields['is_2fa_enabled'] === 0) {
                $msg = "Two-factor authentication reset for {$user['email']}.";
            } elseif (array_key_exists('mail_access', $fields)) {
                $msg = $fields['mail_access']
                    ? "Webmail access granted to {$user['email']}."
                    : "Webmail access revoked for {$user['email']}.";
            }
            $this->session->flash('admin_success', $msg);
        }

        return Response::redirect($appBase . '/admin/users/');
    }

    public function deleteUser(Request $request, string $id): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'users.manage');
        if ($currentUser === null) {
            return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
        }

        $csrfErr = $this->verifyCsrf($request);
        if ($csrfErr !== null) {
            return $csrfErr;
        }

        $appBase = Request::getAppBase();

        if ($id === $currentUser['id']) {
            if (!$request->wantsJson()) {
                if ($this->session !== null) {
                    $this->session->flash('admin_error', 'You cannot delete your own administrative identity.');
                }
                return Response::redirect($appBase . '/admin/users/');
            }
            return Response::error('You cannot delete your own administrative identity.', 400);
        }

        $user = $this->userRepo->findById($id);
        if ($user === null) {
            if (!$request->wantsJson()) {
                if ($this->session !== null) {
                    $this->session->flash('admin_error', 'User identity not found.');
                }
                return Response::redirect($appBase . '/admin/users/');
            }
            return Response::error('User identity not found.', 404);
        }

        $this->userRepo->delete($id);

        $this->recordAudit(
            $request,
            $currentUser['id'],
            'USER_DELETE',
            'users',
            $id,
            $user,
            null
        );

        if ($request->wantsJson()) {
            return Response::json([], 200, 'User identity deleted.');
        }

        if ($this->session !== null) {
            $this->session->flash('admin_success', "User identity {$user['email']} successfully deleted.");
        }

        return Response::redirect($appBase . '/admin/users/');
    }

    public function roles(Request $request): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'roles.view');
        if ($currentUser === null) {
            if ($request->wantsJson()) {
                return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
            }
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        if (!empty($currentUser['must_change_password'])) {
            return Response::redirect(Request::getAppBase() . '/auth/password/change/');
        }

        $roles = $this->roleMgr->getRoles();
        $permissions = $this->roleMgr->getPermissions();

        if ($request->wantsJson()) {
            return Response::json([
                'roles' => $roles,
                'permissions' => $permissions,
            ], 200, 'Roles and permissions retrieved.');
        }

        if ($this->session !== null) {
            $this->session->start();
        }
        $html = $this->view->render('admin.roles', [
            'appBase' => Request::getAppBase(),
            'currentUser' => $currentUser,
            'roles' => $roles,
            'permissions' => $permissions,
            'csrfToken' => $this->csrf->generate($this->session?->getId()),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function groups(Request $request): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'groups.view');
        if ($currentUser === null) {
            if ($request->wantsJson()) {
                return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
            }
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        if (!empty($currentUser['must_change_password'])) {
            return Response::redirect(Request::getAppBase() . '/auth/password/change/');
        }

        $groups = $this->groupMgr->getGroups();
        $roles = $this->roleMgr->getRoles();

        if ($request->wantsJson()) {
            return Response::json([
                'groups' => $groups,
            ], 200, 'Identity groups retrieved.');
        }

        if ($this->session !== null) {
            $this->session->start();
        }
        $html = $this->view->render('admin.groups', [
            'appBase' => Request::getAppBase(),
            'currentUser' => $currentUser,
            'groups' => $groups,
            'roles' => $roles,
            'csrfToken' => $this->csrf->generate($this->session?->getId()),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function audit(Request $request): Response
    {
        $currentUser = $this->authenticateAdmin($request, 'audit.view');
        if ($currentUser === null) {
            if ($request->wantsJson()) {
                return Response::error('Unauthorized or insufficient permissions.', 403, 'FORBIDDEN');
            }
            return Response::redirect(Request::getAppBase() . '/auth/login/');
        }

        if (!empty($currentUser['must_change_password'])) {
            return Response::redirect(Request::getAppBase() . '/auth/password/change/');
        }

        $logs = $this->fetchAuditLogs();

        if ($request->wantsJson()) {
            return Response::json([
                'logs' => $logs,
                'chain_intact' => $this->verifyChain($logs),
            ], 200, 'Audit trail retrieved.');
        }

        if ($this->session !== null) {
            $this->session->start();
        }
        $html = $this->view->render('admin.audit', [
            'appBase' => Request::getAppBase(),
            'currentUser' => $currentUser,
            'logs' => $logs,
            'chainIntact' => $this->verifyChain($logs),
            'csrfToken' => $this->csrf->generate($this->session?->getId()),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function recordAudit(
        Request $request,
        string $userId,
        string $action,
        string $resource,
        ?string $resourceId,
        ?array $before,
        ?array $after
    ): void {
        $auditLogger = new AuditLogger($this->db);
        $auditLogger->log(
            $request->getTraceId(),
            $userId,
            $action,
            $resource,
            $resourceId,
            $request->getClientIp(),
            $request->getUserAgent(),
            $before,
            $after
        );
    }

    private function fetchAuditLogs(int $limit = 50): array
    {
        $auditLogger = new AuditLogger($this->db);
        return $auditLogger->getRecentLogs($limit);
    }

    private function verifyChain(array $logs): bool
    {
        $auditLogger = new AuditLogger($this->db);
        $status = $auditLogger->verifyChain();
        return (bool)($status['intact'] ?? false);
    }
}
