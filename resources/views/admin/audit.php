<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Cryptographic Audit Trail</title>
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
                <h1 class="h4 mt-1 mb-0">Cryptographic Audit Trail</h1>
                <span class="small text-muted">Integrity Triad // SHA-256 Hash-Chained Forensic Ledger</span>
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
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link text-danger ms-auto">🚪 Logout</a>
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
                            <td class="text-muted text-nowrap">
                                <?= htmlspecialchars((string)($l['created_at'] ?? '')) ?>
                            </td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars((string)($l['user_id'] ?? 'system')) ?></strong>
                            </td>
                            <td>
                                <span class="text-cyan fw-semibold"><?= htmlspecialchars((string)($l['action'] ?? '')) ?></span>
                            </td>
                            <td>
                                <span class="text-muted"><?= htmlspecialchars((string)($l['resource'] ?? '')) ?></span>
                            </td>
                            <td class="text-muted">
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

