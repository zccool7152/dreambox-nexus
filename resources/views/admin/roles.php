<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // RBAC Roles & Permissions</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
    <div class="container">
        <header class="admin-header">
            <div>
                <span class="brand-badge">CORE v0.1 ALPHA</span>
                <h1 class="h4 mt-1 mb-0">RBAC Roles & Permissions Matrix</h1>
                <span class="small text-muted">Declarative Authorization // Least Privilege Enforcement</span>
            </div>
            <div>
                <span class="badge-role">Active Identity: <?= htmlspecialchars($currentUser['email']) ?></span>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="<?= $appBase ?>/admin/users" class="nav-link">👥 Users</a>
            <a href="<?= $appBase ?>/admin/roles" class="nav-link active">🛡️ Roles & Permissions</a>
            <a href="<?= $appBase ?>/admin/groups" class="nav-link">🏛️ Groups</a>
            <a href="<?= $appBase ?>/admin/audit" class="nav-link">📜 Cryptographic Audit Trail</a>
            <a href="<?= $appBase ?>/compliance/" class="nav-link">⚖️ Compliance Matrix</a>
            <a href="<?= $appBase ?>/health/" class="nav-link">💖 Health Probe</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="nav-link">🔒 SecOps Cockpit</a>
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link text-danger ms-auto">🚪 Logout</a>
        </nav>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th class="table-scope-col">Granular Permission Scope</th>
                        <th>Description</th>
                        <?php foreach ($roles as $rName => $rMeta): ?>
                            <th class="text-center"><?= htmlspecialchars($rName) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $pName => $pDesc): ?>
                        <tr>
                            <td>
                                <code class="text-cyan fw-semibold"><?= htmlspecialchars($pName) ?></code>
                            </td>
                            <td class="text-muted small">
                                <?= htmlspecialchars($pDesc) ?>
                            </td>
                            <?php foreach ($roles as $rName => $rMeta): ?>
                                <?php
                                    $has = in_array('*', $rMeta['permissions'], true) || in_array($pName, $rMeta['permissions'], true);
                                ?>
                                <td class="text-center">
                                    <?php if ($has): ?>
                                        <span class="check-yes">✔ GRANTED</span>
                                    <?php else: ?>
                                        <span class="check-no">✕</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

