<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // IAM Administration Console</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
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
            <a href="<?= $appBase ?>/auth/logout/" class="nav-link" style="color:var(--neon-rose);margin-left:auto;">🚪 Logout</a>
        </nav>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

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
                                <form method="POST" action="<?= $appBase ?>/admin/users/" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="_action" value="update">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                    <button type="submit" class="btn-table">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php if ($u['is_2fa_enabled']): ?>
                                    <form method="POST" action="<?= $appBase ?>/admin/users/" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="_action" value="update">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                        <input type="hidden" name="reset_2fa" value="1">
                                        <button type="submit" class="btn-table" title="Reset Google Authenticator">
                                            Reset 2FA
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($u['id'] !== $currentUser['id']): ?>
                                    <form method="POST" action="<?= $appBase ?>/admin/users/" style="display:inline;" onsubmit="return confirm('Permanently revoke this identity?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
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
            <form method="POST" action="<?= $appBase ?>/admin/users/">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="_action" value="create">
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
