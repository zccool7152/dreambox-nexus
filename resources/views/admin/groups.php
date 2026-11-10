<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Identity Groups Governance</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
    <div class="container">
        <header class="admin-header">
            <div>
                <span class="brand-badge">CORE v0.1 ALPHA</span>
                <h1 style="font-size:1.15rem;margin-top:0.3rem;">Organizational Identity Groups</h1>
                <span style="font-size:0.7rem;color:var(--text-muted);">Relationship-Based Access Control (ReBAC) & Role Inheritance</span>
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
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link" style="color:var(--neon-rose);margin-left:auto;">🚪 Logout</a>
        </nav>

        <div class="grid-groups">
            <?php foreach ($groups as $gName => $gMeta): ?>
                <div class="group-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.6rem;">
                        <h3 style="font-size:1rem;color:var(--neon-cyan);"><?= htmlspecialchars($gName) ?></h3>
                        <span style="font-size:0.65rem;color:var(--text-muted);">
                            <?= count((array)($gMeta['members'] ?? [])) ?> Members
                        </span>
                    </div>

                    <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.4;">
                        <?= htmlspecialchars($gMeta['description'] ?? '') ?>
                    </p>

                    <div style="margin-bottom:0.8rem;">
                        <span style="font-size:0.68rem;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.05em;display:block;margin-bottom:0.3rem;">Inherited Roles:</span>
                        <?php foreach ((array)($gMeta['roles'] ?? []) as $r): ?>
                            <span class="badge-role"><?= htmlspecialchars($r) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <span style="font-size:0.68rem;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.05em;display:block;margin-bottom:0.3rem;">Group Members:</span>
                        <?php if (!empty($gMeta['members'])): ?>
                            <?php foreach ((array)$gMeta['members'] as $m): ?>
                                <span class="badge-member"><?= htmlspecialchars($m) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="font-size:0.7rem;color:var(--text-muted);">No assigned members</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

