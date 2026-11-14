<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Mandatory Password Rotation</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/brand/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/brand/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>
    <div class="container">
        <span class="brand-badge">PCI-DSS REQ 8.3 // MANDATORY PASSWORD ROTATION</span>
        <h1>Establish New Password</h1>
        <p class="subtitle">
            Initial temporary credentials detected for <code><?= htmlspecialchars($user['email']) ?></code>. 
            You must choose a secure private password before proceeding to administrative systems.
        </p>

        <div id="alertBox" class="alert alert-error"></div>

        <form id="pwdForm" method="POST" action="<?= $appBase ?>/auth/password/change/">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="current_password">Temporary Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-input" placeholder="••••••••••••" required autocomplete="current-password" autofocus>
            </div>

            <div class="form-group">
                <label for="new_password">New Private Password (min 8 characters)</label>
                <input type="password" id="new_password" name="new_password" class="form-input" placeholder="••••••••••••" required minlength="8" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••••••" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">
                Rotate Password & Enter System →
            </button>
        </form>
    </div>

    <script nonce="<?= $nonce ?? '' ?>">
        const form = document.getElementById('pwdForm');
        const alertBox = document.getElementById('alertBox');
        const btnSubmit = document.getElementById('btnSubmit');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.style.display = 'none';

            const cur = document.getElementById('current_password').value;
            const np = document.getElementById('new_password').value;
            const cp = document.getElementById('confirm_password').value;

            if (np !== cp) {
                alertBox.textContent = 'New password and confirmation do not match.';
                alertBox.style.display = 'block';
                return;
            }

            if (np.length < 8) {
                alertBox.textContent = 'Password must be at least 8 characters long.';
                alertBox.style.display = 'block';
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Updating Credentials...';

            try {
                const res = await fetch('<?= $appBase ?>/auth/password/change/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': '<?= $csrfToken ?>'
                    },
                    body: JSON.stringify({
                        current_password: cur,
                        new_password: np,
                        confirm_password: cp
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    alertBox.textContent = data.message || 'Password update failed.';
                    alertBox.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Rotate Password & Enter System →';
                    return;
                }

                window.location.href = data.data.redirect || '<?= $appBase ?>/admin/';
            } catch (err) {
                // Defensive fallback to standard form submit
                form.submit();
            }
        });
    </script>
</body>
</html>

