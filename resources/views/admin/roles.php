<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // RBAC Roles & Permissions</title>
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
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
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

        .check-yes { color: var(--neon-emerald); font-weight: 700; }
        .check-no { color: rgba(255, 255, 255, 0.15); }

        .badge-role {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            background: rgba(0, 242, 254, 0.1);
            color: var(--neon-cyan);
            border: 1px solid rgba(0, 242, 254, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
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

