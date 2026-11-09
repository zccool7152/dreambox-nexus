<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // IAM Administration Console</title>
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
            --neon-amber: #f59e0b;
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

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

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

        .brand-area {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .user-pill {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.4rem 0.8rem;
            border-radius: 9999px;
            font-size: 0.75rem;
        }

        .user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--neon-cyan);
            color: #06090e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
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

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .btn-action {
            padding: 0.5rem 0.9rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-family: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-blue));
            color: #06090e;
        }

        .btn-primary:hover {
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.3);
            transform: translateY(-1px);
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

        tr:hover td {
            background: rgba(0, 242, 254, 0.02);
        }

        .badge {
            display: inline-block;
            font-size: 0.65rem;
            padding: 0.18rem 0.45rem;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-active { background: rgba(16, 185, 129, 0.15); color: var(--neon-emerald); border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-suspended { background: rgba(244, 63, 94, 0.15); color: var(--neon-rose); border: 1px solid rgba(244, 63, 94, 0.3); }
        .badge-role { background: rgba(0, 242, 254, 0.1); color: var(--neon-cyan); border: 1px solid rgba(0, 242, 254, 0.3); }
        .badge-provider { background: rgba(255, 255, 255, 0.08); color: var(--text-main); }
        .badge-2fa-on { background: rgba(16, 185, 129, 0.15); color: var(--neon-emerald); }
        .badge-2fa-off { background: rgba(245, 158, 11, 0.15); color: var(--neon-amber); }

        .btn-table {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.68rem;
            cursor: pointer;
            font-family: inherit;
            margin-right: 0.3rem;
        }

        .btn-table:hover {
            border-color: var(--neon-cyan);
            color: var(--neon-cyan);
        }

        /* Provision Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: #0d1321;
            border: 1px solid var(--border-glow);
            border-radius: 12px;
            max-width: 440px;
            width: 100%;
            padding: 1.8rem;
            box-shadow: 0 0 30px rgba(0, 242, 254, 0.2);
        }

        .form-input {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.8rem;
            margin-bottom: 0.8rem;
            outline: none;
        }

        .form-input:focus { border-color: var(--neon-cyan); }
        label { font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <header class="admin-header">
            <div class="brand-area">
                <span class="brand-badge">CORE v0.1 ALPHA</span>
                <div>
                    <h1 style="font-size:1.15rem;letter-spacing:0.05em;">CIA Triad // IAM Management Console</h1>
                    <span style="font-size:0.7rem;color:var(--text-muted);">Confidentiality // Integrity // Availability</span>
                </div>
            </div>

            <div class="user-pill">
                <div class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></div>
                <span><?= htmlspecialchars($currentUser['email']) ?></span>
                <span class="badge badge-role"><?= htmlspecialchars(implode(', ', (array)($currentUser['roles'] ?? ['Member']))) ?></span>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="<?= $appBase ?>/admin/users" class="nav-link active">👥 Users</a>
            <a href="<?= $appBase ?>/admin/roles" class="nav-link">🛡️ Roles & Permissions</a>
            <a href="<?= $appBase ?>/admin/groups" class="nav-link">🏛️ Groups</a>
            <a href="<?= $appBase ?>/admin/audit" class="nav-link">📜 Cryptographic Audit Trail</a>
            <a href="<?= $appBase ?>/compliance/" class="nav-link">⚖️ Compliance Matrix</a>
            <a href="<?= $appBase ?>/health/" class="nav-link">💖 Health Probe</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="nav-link">🔒 SecOps Cockpit</a>
            <a href="<?= $appBase ?>/auth/logout" class="nav-link" style="color:var(--neon-rose);margin-left:auto;">🚪 Logout</a>
        </nav>

        <div class="section-header">
            <div>
                <h2 style="font-size:1rem;margin-bottom:0.2rem;">Identity Directory & Governance</h2>
                <span style="font-size:0.72rem;color:var(--text-muted);"><?= count($users) ?> registered identities under active policy governance</span>
            </div>
            <button class="btn-action btn-primary" id="btnOpenModal">
                + Provision Identity
            </button>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Identity</th>
                        <th>Auth Provider</th>
                        <th>Roles</th>
                        <th>Groups</th>
                        <th>2FA Status</th>
                        <th>Account State</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;color:var(--text-main);"><?= htmlspecialchars($u['name']) ?></div>
                                <div style="font-size:0.68rem;color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td>
                                <span class="badge badge-provider"><?= strtoupper($u['auth_provider']) ?></span>
                            </td>
                            <td>
                                <?php foreach ((array)$u['roles'] as $r): ?>
                                    <span class="badge badge-role"><?= htmlspecialchars($r) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if (!empty($u['groups'])): ?>
                                    <?php foreach ((array)$u['groups'] as $g): ?>
                                        <span class="badge" style="background:rgba(79,172,254,0.15);color:var(--neon-blue);"><?= htmlspecialchars($g) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:0.68rem;">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_2fa_enabled']): ?>
                                    <span class="badge badge-2fa-on">ACTIVE (TOTP)</span>
                                <?php else: ?>
                                    <span class="badge badge-2fa-off">PENDING</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge badge-active">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge badge-suspended">SUSPENDED</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="<?= $appBase ?>/admin/users/<?= urlencode($u['id']) ?>/update" style="display:inline;">
                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                    <button type="submit" class="btn-table">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php if ($u['is_2fa_enabled']): ?>
                                    <form method="POST" action="<?= $appBase ?>/admin/users/<?= urlencode($u['id']) ?>/update" style="display:inline;">
                                        <input type="hidden" name="reset_2fa" value="1">
                                        <button type="submit" class="btn-table" title="Reset Google Authenticator">
                                            Reset 2FA
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($u['id'] !== $currentUser['id']): ?>
                                    <form method="POST" action="<?= $appBase ?>/admin/users/<?= urlencode($u['id']) ?>/delete" style="display:inline;" onsubmit="return confirm('Permanently revoke this identity?');">
                                        <button type="submit" class="btn-table" style="color:var(--neon-rose);">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Provision Identity Modal -->
    <div id="provisionModal" class="modal">
        <div class="modal-content">
            <h3 style="font-size:1.1rem;margin-bottom:1rem;color:var(--neon-cyan);">+ Provision Identity</h3>
            <form method="POST" action="<?= $appBase ?>/admin/users">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Alex Vance" required>

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="alex@company.com" required>

                <label for="role">Assigned Role</label>
                <select id="role" name="role" class="form-input" style="background:#090e17;">
                    <?php foreach ($roles as $rName => $rMeta): ?>
                        <option value="<?= htmlspecialchars($rName) ?>"><?= htmlspecialchars($rName) ?> (<?= htmlspecialchars($rMeta['description']) ?>)</option>
                    <?php endforeach; ?>
                </select>

                <label for="password">Initial Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Enter secure initial password" required autocomplete="new-password">

                <div style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="submit" class="btn-action btn-primary" style="flex:1;">Provision</button>
                    <button type="button" class="btn-action btn-table" id="btnCloseModal" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script nonce="<?= $nonce ?? '' ?>">
        const modal = document.getElementById('provisionModal');
        document.getElementById('btnOpenModal').addEventListener('click', () => modal.style.display = 'flex');
        document.getElementById('btnCloseModal').addEventListener('click', () => modal.style.display = 'none');
    </script>
</body>
</html>
