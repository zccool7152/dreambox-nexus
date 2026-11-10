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
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
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

