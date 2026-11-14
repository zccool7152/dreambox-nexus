<?php
declare(strict_types=1);

namespace Nexus\Auth\Rbac;

use Nexus\Database\Database;

class RoleManager
{
    private ?Database $db;

    private array $defaultRoles = [
        'SuperAdmin' => [
            'description' => 'Unrestricted access to all cryptographic, IAM, and compliance systems.',
            'permissions' => ['*'],
        ],
        'SecurityOfficer' => [
            'description' => 'SecOps administration, MFA resets, account lockouts, and audit monitoring.',
            'permissions' => ['users.manage', 'users.view', 'roles.view', 'groups.view', 'audit.view', 'compliance.view', 'mail.access'],
        ],
        'ComplianceAuditor' => [
            'description' => 'Read-only access to regulatory compliance matrices, audit logs, and reports.',
            'permissions' => ['users.view', 'audit.view', 'compliance.view', 'compliance.export'],
        ],
        'Developer' => [
            'description' => 'API contract explorer, telemetry monitoring, and queue job management.',
            'permissions' => ['routes.view', 'telemetry.view', 'jobs.manage'],
        ],
        'Member' => [
            'description' => 'Standard authenticated identity.',
            'permissions' => ['profile.manage'],
        ],
    ];

    private array $defaultPermissions = [
        'users.manage' => 'Create, edit, suspend, or delete user identities.',
        'users.view' => 'View user list and account details.',
        'roles.manage' => 'Create and modify RBAC roles and permissions.',
        'roles.view' => 'View role hierarchy and assigned permissions.',
        'groups.manage' => 'Create and configure organizational identity groups.',
        'groups.view' => 'View organizational identity groups and members.',
        'audit.view' => 'Inspect immutable cryptographic audit trails.',
        'audit.export' => 'Export signed audit compliance packages.',
        'compliance.manage' => 'Execute compliance verification runs.',
        'compliance.view' => 'View regulatory framework breakdown HUD.',
        'routes.view' => 'Inspect active route registry.',
        'telemetry.view' => 'View SecOps vital telemetry.',
        'jobs.manage' => 'Dispatch and retry asynchronous jobs.',
        'mail.access' => 'Provision and access Dreambox Nexus Webmail client and ephemeral reader jail.',
        'profile.manage' => 'Update personal credentials and MFA tokens.',
    ];

    public function __construct(?Database $db = null)
    {
        $this->db = $db;
    }

    public function getRoles(): array
    {
        if ($this->db !== null && $this->db->isAlive() && $this->db->getDriver() === 'mongodb') {
            $docs = $this->db->mongoFind('roles', []);
            if (!empty($docs)) {
                $roles = [];
                foreach ($docs as $d) {
                    $roles[$d['name']] = [
                        'name' => $d['name'],
                        'description' => $d['description'] ?? '',
                        'permissions' => (array)($d['permissions'] ?? []),
                        'is_system' => !empty($d['is_system']),
                    ];
                }
                return $roles;
            }
        }

        $formatted = [];
        foreach ($this->defaultRoles as $name => $meta) {
            $formatted[$name] = [
                'name' => $name,
                'description' => $meta['description'],
                'permissions' => $meta['permissions'],
                'is_system' => true,
            ];
        }
        return $formatted;
    }

    public function getPermissions(): array
    {
        return $this->defaultPermissions;
    }

    public function getRolePermissions(string $role): array
    {
        $roles = $this->getRoles();
        return $roles[$role]['permissions'] ?? [];
    }

    public function userHasPermission(array $user, string $permission): bool
    {
        if ($permission === 'mail.access' && !empty($user['mail_access'])) {
            return true;
        }

        $roles = (array)($user['roles'] ?? ['Member']);
        if (in_array('SuperAdmin', $roles, true)) {
            return true;
        }

        foreach ($roles as $rName) {
            $perms = $this->getRolePermissions($rName);
            if (in_array('*', $perms, true) || in_array($permission, $perms, true)) {
                return true;
            }
        }

        return false;
    }

    public function createRole(string $name, string $description, array $permissions): bool
    {
        $name = trim($name);
        if ($this->db !== null && $this->db->isAlive() && $this->db->getDriver() === 'mongodb') {
            $this->db->mongoInsert('roles', [
                'name' => $name,
                'description' => $description,
                'permissions' => $permissions,
                'is_system' => false,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        }

        $this->defaultRoles[$name] = [
            'description' => $description,
            'permissions' => $permissions,
        ];
        return true;
    }
}

