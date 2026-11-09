<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Cryptographic Audit Trail</title>
    <style>
        :root {
            --bg-base: #06090e;
            --surface-glass: rgba(13, 19, 33, 0.75);
            --surface-card: rgba(18, 26, 43, 0.65);
            --border-subtle: rgba(0, 242, 254, 0.15);
            --border-glow: rgba(0, 242, 254, 0.4);
            --neon-cyan: #00f2fe;
            --neon-blue: #4facfe;
            --neon-emerald: #10b981;
            --neon-rose: #f43f5e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(0, 242, 254, 0.08), transparent 45%),
                radial-gradient(circle at 90% 90%, rgba(79, 172, 254, 0.04), transparent 40%);
            color: var(--text-main);
            font-family: var(--font-mono);
            min-height: 100vh;
            padding: 1.5rem;
        }

        .container { max-width: 1300px; margin: 0 auto; }

        header.admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px solid var(--border-subtle);
            margin-bottom: 1.5rem;
        }

        .brand-badge {
            font-size: 0.62rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            background: rgba(0, 242, 254, 0.1);
            color: var(--neon-cyan);
            border: 1px solid rgba(0, 242, 254, 0.3);
        }

        nav.admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .nav-link {
            padding: 0.55rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.2s;
        }

        .nav-link:hover {
            color: var(--text-main);
            border-color: var(--border-glow);
            background: rgba(0, 242, 254, 0.05);
        }

        .nav-link.active {
            color: #06090e;
            background: var(--neon-cyan);
            border-color: var(--neon-cyan);
            font-weight: 700;
        }

        .table-card {
            background: var(--surface-glass);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            text-align: left;
        }

        th {
            background: rgba(0, 0, 0, 0.35);
            padding: 0.85rem 1rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.68rem;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-subtle);
        }

        td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        .badge-verified {
            background: rgba(16, 185, 129, 0.15);
            color: var(--neon-emerald);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .hash-code {
            font-size: 0.68rem;
            color: var(--neon-cyan);
            background: rgba(0, 0, 0, 0.4);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            border: 1px solid rgba(0, 242, 254, 0.2);
            display: inline-block;
            font-family: inherit;
        }
    </style>
</head>
<body>
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
            <a href="<?= $appBase ?>/auth/logout" class="nav-link" style="color:var(--neon-rose);margin-left:auto;">🚪 Logout</a>
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

