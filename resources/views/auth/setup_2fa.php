<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Google Authenticator Enrollment</title>
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
        <span class="brand-badge">CIA MANDATE // 2FA ENFORCEMENT</span>
        <h1>Google Authenticator Setup</h1>
        <p class="subtitle">Enroll <code><?= htmlspecialchars($user['email']) ?></code> in RFC 6238 TOTP to fulfill zero-trust multi-factor compliance.</p>

        <div id="alertBox" class="alert alert-error"></div>

        <div class="instructions">
            <ol>
                <li>Open <strong>Google Authenticator</strong> on your mobile device.</li>
                <li>Tap <strong>+</strong> and choose <strong>Scan a QR code</strong>.</li>
                <li>Enter the resulting 6-digit verification code below.</li>
            </ol>
        </div>

        <div class="qr-card">
            <?= $qrSvg ?>
            <div class="secret-box" title="Manual Entry Key">
                <span>KEY: <strong id="rawSecret"><?= htmlspecialchars(chunk_split($secret, 4, ' ')) ?></strong></span>
                <button type="button" id="btnCopyKey" class="btn-copy-secret" data-secret="<?= htmlspecialchars($secret) ?>">Copy</button>
            </div>
        </div>

        <form id="setupForm" method="POST" action="<?= $appBase ?>/auth/2fa/setup/">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group">
                <input type="text" id="code" name="code" class="otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">
                Confirm & Activate 2FA →
            </button>
        </form>
    </div>

    <!-- Recovery Codes Modal -->
    <div id="recoveryModal" class="recovery-modal">
        <div class="recovery-content">
            <h2 class="h4 text-success mb-2">🎉 2FA Activated</h2>
            <p class="small text-muted mb-3">
                Save these emergency recovery codes in a secure location. Each code can be used once if you lose access to Google Authenticator:
            </p>

            <div id="recoveryGrid" class="recovery-grid"></div>

            <button id="btnProceedAdmin" class="btn btn-success w-100">
                I Have Saved My Codes → Enter Admin
            </button>
        </div>
    </div>

    <script nonce="<?= $nonce ?? '' ?>">
        const form = document.getElementById('setupForm');
        const alertBox = document.getElementById('alertBox');
        const btnSubmit = document.getElementById('btnSubmit');
        const recoveryModal = document.getElementById('recoveryModal');
        const recoveryGrid = document.getElementById('recoveryGrid');
        const btnProceedAdmin = document.getElementById('btnProceedAdmin');
        let redirectTarget = '<?= $appBase ?>/admin/';

        const btnCopyKey = document.getElementById('btnCopyKey');
        if (btnCopyKey) {
            btnCopyKey.addEventListener('click', () => {
                const secret = btnCopyKey.getAttribute('data-secret');
                navigator.clipboard.writeText(secret).then(() => {
                    const original = btnCopyKey.textContent;
                    btnCopyKey.textContent = 'Copied!';
                    setTimeout(() => { btnCopyKey.textContent = original; }, 2000);
                }).catch(() => {
                    prompt('Copy secret key:', secret);
                });
            });
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Verifying Code...';

            try {
                const res = await fetch('<?= $appBase ?>/auth/2fa/setup/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': '<?= $csrfToken ?>'
                    },
                    body: JSON.stringify({
                        code: document.getElementById('code').value.trim()
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    alertBox.textContent = data.message || 'Invalid 2FA code. Check clock sync and retry.';
                    alertBox.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Confirm & Activate 2FA →';
                    return;
                }

                if (data.data && data.data.recovery_codes) {
                    redirectTarget = data.data.redirect || '<?= $appBase ?>/admin/';
                    recoveryGrid.innerHTML = '';
                    data.data.recovery_codes.forEach(c => {
                        const div = document.createElement('div');
                        div.className = 'recovery-code';
                        div.textContent = c;
                        recoveryGrid.appendChild(div);
                    });
                    recoveryModal.style.display = 'flex';
                } else {
                    window.location.href = data.data.redirect || '<?= $appBase ?>/admin/';
                }
            } catch (err) {
                // Defensive fallback to standard form submit
                form.submit();
            }
        });

        btnProceedAdmin.addEventListener('click', () => {
            window.location.href = redirectTarget;
        });
    </script>
</body>
</html>

