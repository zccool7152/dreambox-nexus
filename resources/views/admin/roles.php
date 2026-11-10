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
                <h1 style="font-size:1.15rem;margin-top:0.3rem;">RBAC Roles & Permissions Matrix</h1>
                <span style="font-size:0.7rem;color:var(--text-muted);">Declarative Authorization // Least Privilege Enforcement</span>
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
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link" style="color:var(--neon-rose);margin-left:auto;">🚪 Logout</a>
        </nav>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th style="min-width:240px;">Granular Permission Scope</th>
                        <th>Description</th>
                        <?php foreach ($roles as $rName => $rMeta): ?>
                            <th style="text-align:center;"><?= htmlspecialchars($rName) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $pName => $pDesc): ?>
                        <tr>
                            <td>
                                <code style="color:var(--neon-cyan);font-weight:600;"><?= htmlspecialchars($pName) ?></code>
                            </td>
                            <td style="color:var(--text-muted);font-size:0.73rem;">
                                <?= htmlspecialchars($pDesc) ?>
                            </td>
                            <?php foreach ($roles as $rName => $rMeta): ?>
                                <?php
                                    $has = in_array('*', $rMeta['permissions'], true) || in_array($pName, $rMeta['permissions'], true);
                                ?>
                                <td style="text-align:center;">
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

