<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Identity Groups Governance</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/logos/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/logos/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/logos/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/logos/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
    <div class="container">
        <header class="admin-header">
            <div>
                <span class="brand-badge">CORE v0.1 ALPHA</span>
                <h1 class="h4 mt-1 mb-0">Organizational Identity Groups</h1>
                <span class="small text-muted">Relationship-Based Access Control (ReBAC) & Role Inheritance</span>
            </div>
            <div>
                <span class="badge-role">Active Identity: <?= htmlspecialchars($currentUser['email']) ?></span>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="<?= $appBase ?>/admin/users" class="nav-link">👥 Users</a>
            <a href="<?= $appBase ?>/admin/roles" class="nav-link">🛡️ Roles & Permissions</a>
            <a href="<?= $appBase ?>/admin/groups" class="nav-link active">🏛️ Groups</a>
            <a href="<?= $appBase ?>/admin/audit" class="nav-link">📜 Cryptographic Audit Trail</a>
            <a href="<?= $appBase ?>/compliance/" class="nav-link">⚖️ Compliance Matrix</a>
            <a href="<?= $appBase ?>/health/" class="nav-link">💖 Health Probe</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="nav-link">🔒 SecOps Cockpit</a>
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link text-danger ms-auto">🚪 Logout</a>
        </nav>

        <div class="grid-groups">
            <?php foreach ($groups as $gName => $gMeta): ?>
                <div class="group-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h3 class="h5 text-cyan mb-0"><?= htmlspecialchars($gName) ?></h3>
                        <span class="small text-muted">
                            <?= count((array)($gMeta['members'] ?? [])) ?> Members
                        </span>
                    </div>

                    <p class="small text-muted mb-3">
                        <?= htmlspecialchars($gMeta['description'] ?? '') ?>
                    </p>

                    <div class="mb-2">
                        <span class="small text-muted text-uppercase d-block mb-1">Inherited Roles:</span>
                        <?php foreach ((array)($gMeta['roles'] ?? []) as $r): ?>
                            <span class="badge-role"><?= htmlspecialchars($r) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <span class="small text-muted text-uppercase d-block mb-1">Group Members:</span>
                        <?php if (!empty($gMeta['members'])): ?>
                            <?php foreach ((array)$gMeta['members'] as $m): ?>
                                <span class="badge-member"><?= htmlspecialchars($m) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="small text-muted">No assigned members</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

