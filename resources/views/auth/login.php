<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // CIA Authentication Hub</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
    <div class="auth-container">
        <div class="auth-header">
            <span class="brand-badge">CIA IAM GATEWAY v0.1 ALPHA</span>
            <h1>Identity Verification</h1>
            <p>Confidentiality // Integrity // Availability</p>
        </div>

        <div id="alertBox" class="alert alert-error"></div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error d-block"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- OAuth 2.0 Identity Providers -->
        <div class="oauth-stack">
            <a href="<?= $appBase ?>/auth/oauth/google" class="btn-oauth btn-google">
                <svg viewBox="0 0 24 24">
                    <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                    <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.7-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                    <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.8s.2-2.1.4-2.8L1.9 6.3C.7 8.7 0 10.3 0 12s.7 3.3 1.9 5.7l3.7-2.9z"/>
                    <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.2L1.9 16C3.7 19.7 7.5 23 12 23z"/>
                </svg>
                Continue with Google
            </a>

            <a href="<?= $appBase ?>/auth/oauth/github" class="btn-oauth btn-github">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                </svg>
                Continue with GitHub
            </a>
        </div>

        <div class="divider">
            <span>Or authenticate via Email</span>
        </div>

        <!-- Email & Password Authenticator -->
        <form id="emailLoginForm" method="POST" action="<?= $appBase ?>/auth/login/">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="email">Identity / Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="identity@domain.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">
                Verify Credentials →
            </button>
        </form>

        <div class="footer-note">
            <span>🔒 Protected by PCI-DSS Req 8.3 & RFC 6238 TOTP Engine.</span><br>
            <a href="<?= $appBase ?>/">← Return to Application Gateway</a>
        </div>
    </div>

    <script nonce="<?= $nonce ?? '' ?>">
        const form = document.getElementById('emailLoginForm');
        const alertBox = document.getElementById('alertBox');
        const btnSubmit = document.getElementById('btnSubmit');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Verifying...';

            try {
                const actionUrl = '<?= $appBase ?>/auth/login/';
                const res = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': '<?= $csrfToken ?>'
                    },
                    body: JSON.stringify({
                        email: document.getElementById('email').value.trim(),
                        password: document.getElementById('password').value
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    alertBox.textContent = data.message || 'Authentication failed.';
                    alertBox.className = 'alert alert-error';
                    alertBox.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Verify Credentials →';
                    return;
                }

                if (data.data && data.data.redirect) {
                    window.location.href = data.data.redirect;
                } else {
                    window.location.href = '<?= $appBase ?>/admin/';
                }
            } catch (err) {
                // Defensive fallback: submit form natively if AJAX fails
                form.submit();
            }
        });
    </script>
</body>
</html>
