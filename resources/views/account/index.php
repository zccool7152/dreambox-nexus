<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$displayName = ($user['display_preference'] === 'username' && !empty($user['username'])) ? $user['username'] : $user['name'];

// Compute initials
$initials = '';
$nameParts = explode(' ', trim($user['name']));
if (!empty($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1 && !empty($nameParts[count($nameParts) - 1])) {
    $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
}
if ($initials === '') $initials = 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings // Dreambox Nexus</title>
    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --border-light: #e2e8f0;
            --border-focus: #0284c7;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-subtle: #94a3b8;
            --primary: #0284c7;
            --primary-hover: #0369a1;
            --success: #10b981;
            --danger: #ef4444;
            --radius-lg: 16px;
            --radius-md: 10px;
            --radius-sm: 6px;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.025);
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-page);
            color: var(--text-main);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .account-container {
            max-width: 1100px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        /* Hero Profile Card */
        .profile-hero {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .profile-hero-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .hero-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #ffffff;
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            border: 3px solid #ffffff;
        }

        .hero-meta h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-main);
            line-height: 1.2;
        }

        .hero-meta p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .hero-badges {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .pill-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 9999px;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .badge-role {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .badge-group {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .badge-2fa {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Grid Layout */
        .account-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 860px) {
            .account-grid { grid-template-columns: 1fr; }
        }

        .account-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 1.5rem;
        }

        .card-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            background: #f0f9ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--text-main);
        }

        .card-header p {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.4rem;
        }

        .form-input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            color: var(--text-main);
            background: #ffffff;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .form-input[readonly] {
            background: #f8fafc;
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .input-hint {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
            display: block;
        }

        /* Radio Card Group (Display Preference) */
        .radio-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.4rem;
        }

        .radio-card {
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 0.75rem 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            background: #ffffff;
            position: relative;
        }

        .radio-card input {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
        }

        .radio-card.selected {
            border-color: var(--primary);
            background: #f0f9ff;
        }

        .radio-card-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .radio-card-sub {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* Buttons */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.7rem 1.4rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35);
        }

        /* Footer */
        .account-footer {
            text-align: center;
            padding: 2rem 0;
            color: var(--text-subtle);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

    <!-- Include Universal Top Bar -->
    <?php include dirname(__DIR__) . '/partials/topbar.php'; ?>

    <main class="account-container">
        <!-- Success / Error Notifications -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Hero Card -->
        <section class="profile-hero">
            <div class="profile-hero-left">
                <div class="hero-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="hero-meta">
                    <h1><?= htmlspecialchars($displayName) ?></h1>
                    <p><?= htmlspecialchars($user['email']) ?> • Member since <?= htmlspecialchars(substr($user['created_at'] ?? '', 0, 10)) ?></p>
                    <div class="hero-badges">
                        <?php foreach ((array)($user['roles'] ?? ['Member']) as $r): ?>
                            <span class="pill-badge badge-role"><?= htmlspecialchars($r) ?></span>
                        <?php endforeach; ?>
                        <?php foreach ((array)($user['groups'] ?? []) as $g): ?>
                            <span class="pill-badge badge-group"><?= htmlspecialchars($g) ?></span>
                        <?php endforeach; ?>
                        <?php if (!empty($user['is_2fa_enabled'])): ?>
                            <span class="pill-badge badge-2fa">2FA Protected</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
                <a href="<?= $appBase ?>/auth/2fa/setup/" class="btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.8rem; background: #ffffff; color: var(--primary); border: 1px solid var(--border-light); box-shadow: none;">
                    <?= !empty($user['is_2fa_enabled']) ? 'Manage 2FA' : 'Enable 2FA' ?>
                </a>
            </div>
        </section>

        <!-- Account Grid: Profile Details & Password Rotation -->
        <div class="account-grid">
            <!-- 1. Profile Information -->
            <section class="account-card">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div>
                        <h2>Profile & Identity</h2>
                        <p>Update your name, unique username, and preferred display format.</p>
                    </div>
                </div>

                <form method="POST" action="<?= $appBase ?>/account/profile">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="form-group">
                        <label class="form-label" for="inputName">Full Name</label>
                        <input type="text" id="inputName" name="name" class="form-input" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputUsername">Username</label>
                        <input type="text" id="inputUsername" name="username" class="form-input" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="e.g. jdoe" pattern="[a-z0-9_\-\.]{3,32}">
                        <span class="input-hint">3 to 32 lowercase alphanumeric characters, dots, and underscores.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Display Preference in Top Bar</label>
                        <span class="input-hint" style="margin-bottom:0.4rem;">Choose whether your Full Name or Username appears in the top navigation bar.</span>
                        <div class="radio-card-grid">
                            <label class="radio-card <?= ($user['display_preference'] ?? 'name') === 'name' ? 'selected' : '' ?>" id="cardPrefName">
                                <input type="radio" name="display_preference" value="name" <?= ($user['display_preference'] ?? 'name') === 'name' ? 'checked' : '' ?>>
                                <span class="radio-card-title">Full Name</span>
                                <span class="radio-card-sub"><?= htmlspecialchars($user['name']) ?></span>
                            </label>

                            <label class="radio-card <?= ($user['display_preference'] ?? 'name') === 'username' ? 'selected' : '' ?>" id="cardPrefUser">
                                <input type="radio" name="display_preference" value="username" <?= ($user['display_preference'] ?? 'name') === 'username' ? 'checked' : '' ?>>
                                <span class="radio-card-title">Username</span>
                                <span class="radio-card-sub"><?= !empty($user['username']) ? htmlspecialchars($user['username']) : '(Not set)' ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <input type="email" id="inputEmail" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        <span class="input-hint">Email is managed by your system administrator.</span>
                    </div>

                    <button type="submit" class="btn-primary">
                        <span>Save Profile Changes</span>
                    </button>
                </form>
            </section>

            <!-- 2. Password Security (Argon2id) -->
            <section class="account-card">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <div>
                        <h2>Password Security</h2>
                        <p>Rotate your credentials using memory-hardened Argon2id encryption.</p>
                    </div>
                </div>

                <form method="POST" action="<?= $appBase ?>/account/password">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="form-group">
                        <label class="form-label" for="inputCurrentPass">Current Password</label>
                        <input type="password" id="inputCurrentPass" name="current_password" class="form-input" placeholder="••••••••••••" required autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputNewPass">New Password</label>
                        <input type="password" id="inputNewPass" name="new_password" class="form-input" placeholder="••••••••••••" minlength="8" required autocomplete="new-password">
                        <span class="input-hint">Minimum 8 characters with letters, numbers, and symbols.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputConfirmPass">Confirm New Password</label>
                        <input type="password" id="inputConfirmPass" name="new_password_confirmation" class="form-input" placeholder="••••••••••••" minlength="8" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-primary" style="background:#0f172a;">
                        <span>Update Password</span>
                    </button>
                </form>
            </section>
        </div>

        <footer class="account-footer">
            Dreambox Nexus Core v0.1 Alpha • Protected under PCI-DSS Req 8.3 & HIPAA §164.312
        </footer>
    </main>

    <script nonce="<?= $nonce ?? '' ?>">
    document.querySelectorAll('input[name="display_preference"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
            radio.closest('.radio-card').classList.add('selected');
        });
    });
    </script>
</body>
</html>
