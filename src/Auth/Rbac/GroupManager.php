<?php
declare(strict_types=1);

namespace Nexus\Auth\Rbac;

use Nexus\Database\Database;

class GroupManager
{
    private ?Database $db;

    private array $defaultGroups = [
        'SecOps' => [
            'description' => 'Security Operations & Incident Response Team',
            'roles' => ['SecurityOfficer'],
            'members' => ['admin@localhost'],
        ],
        'Engineering' => [
            'description' => 'Core Platform & API Engineering Group',
            'roles' => ['Developer'],
            'members' => [],
        ],
        'ComplianceAuditors' => [
            'description' => 'External & Internal Regulatory Auditors',
            'roles' => ['ComplianceAuditor'],
            'members' => [],
        ],
        'Operations' => [
            'description' => 'Infrastructure & Systems Administration',
            'roles' => ['Member'],
            'members' => [],
        ],
    ];

    public function __construct(?Database $db = null)
    {
        $this->db = $db;
    }

    public function getGroups(): array
    {
        if ($this->db !== null && $this->db->isAlive() && $this->db->getDriver() === 'mongodb') {
            $docs = $this->db->mongoFind('groups', []);
            if (!empty($docs)) {
                $groups = [];
                foreach ($docs as $d) {
                    $groups[$d['name']] = [
                        'name' => $d['name'],
                        'description' => $d['description'] ?? '',
                        'roles' => (array)($d['roles'] ?? []),
                        'members' => (array)($d['members'] ?? []),
                    ];
                }
                return $groups;
            }
        }

        return $this->defaultGroups;
    }

    public function createGroup(string $name, string $description, array $roles = []): bool
    {
        $name = trim($name);
        if ($this->db !== null && $this->db->isAlive() && $this->db->getDriver() === 'mongodb') {
            $this->db->mongoInsert('groups', [
                'name' => $name,
                'description' => $description,
                'roles' => $roles,
                'members' => [],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        }

        $this->defaultGroups[$name] = [
            'description' => $description,
            'roles' => $roles,
            'members' => [],
        ];
        return true;
    }
}

