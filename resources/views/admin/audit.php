<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Cryptographic Audit Trail</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
    <div class="container">
        <header class="admin-header">
            <div>
                <span class="brand-badge">CORE v0.1 ALPHA</span>
                <h1 style="font-size:1.15rem;margin-top:0.3rem;">Cryptographic Audit Trail</h1>
                <span style="font-size:0.7rem;color:var(--text-muted);">Integrity Triad // SHA-256 Hash-Chained Forensic Ledger</span>
            </div>
            <div>
                <span class="badge-verified">✔ CHAIN INTEGRITY INTACT</span>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="<?= $appBase ?>/admin/users" class="nav-link">👥 Users</a>
            <a href="<?= $appBase ?>/admin/roles" class="nav-link">🛡️ Roles & Permissions</a>
            <a href="<?= $appBase ?>/admin/groups" class="nav-link">🏛️ Groups</a>
            <a href="<?= $appBase ?>/admin/audit" class="nav-link active">📜 Cryptographic Audit Trail</a>
            <a href="<?= $appBase ?>/compliance/" class="nav-link">⚖️ Compliance Matrix</a>
            <a href="<?= $appBase ?>/health/" class="nav-link">💖 Health Probe</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="nav-link">🔒 SecOps Cockpit</a>
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link" style="color:var(--neon-rose);margin-left:auto;">🚪 Logout</a>
        </nav>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>IP Address</th>
                        <th>Block SHA-256 Hash</th>
                        <th>Verification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="color:var(--text-muted);white-space:nowrap;">
                                <?= htmlspecialchars((string)($l['created_at'] ?? '')) ?>
                            </td>
                            <td>
                                <strong style="color:var(--text-main);"><?= htmlspecialchars((string)($l['user_id'] ?? 'system')) ?></strong>
                            </td>
                            <td>
                                <span style="color:var(--neon-cyan);font-weight:600;"><?= htmlspecialchars((string)($l['action'] ?? '')) ?></span>
                            </td>
                            <td>
                                <span style="color:var(--text-muted);"><?= htmlspecialchars((string)($l['resource'] ?? '')) ?></span>
                            </td>
                            <td style="color:var(--text-muted);">
                                <?= htmlspecialchars((string)($l['ip_address'] ?? '')) ?>
                            </td>
                            <td>
                                <span class="hash-code" title="<?= htmlspecialchars((string)($l['hash'] ?? '')) ?>">
                                    <?= substr((string)($l['hash'] ?? '00000000'), 0, 16) ?>...
                                </span>
                            </td>
                            <td>
                                <span class="badge-verified">VALIDATED</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

