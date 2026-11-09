<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Google Authenticator Enrollment</title>
    <style>
        :root {
            --bg-base: #06090e;
            --surface-glass: rgba(13, 19, 33, 0.85);
            --border-subtle: rgba(0, 242, 254, 0.2);
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
                radial-gradient(circle at 50% 15%, rgba(0, 242, 254, 0.1), transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(79, 172, 254, 0.05), transparent 40%);
            color: var(--text-main);
            font-family: var(--font-mono);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 480px;
            background: var(--surface-glass);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            backdrop-filter: blur(16px);
            padding: 2.2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 25px rgba(0, 242, 254, 0.12);
            text-align: center;
        }

        .brand-badge {
            display: inline-block;
            font-size: 0.65rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            background: rgba(0, 242, 254, 0.1);
            color: var(--neon-cyan);
            border: 1px solid rgba(0, 242, 254, 0.3);
            margin-bottom: 0.8rem;
        }

        h1 {
            font-size: 1.3rem;
            margin-bottom: 0.4rem;
            background: linear-gradient(135deg, #fff, var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .qr-card {
            background: #090e17;
            border: 1px solid rgba(0, 242, 254, 0.25);
            border-radius: 12px;
            padding: 1.2rem;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.2rem;
            box-shadow: 0 0 20px rgba(0, 242, 254, 0.08);
        }

        .qr-card svg {
            display: block;
            margin: 0 auto;
            border-radius: 6px;
        }

        .secret-box {
            margin-top: 0.8rem;
            font-size: 0.8rem;
            background: rgba(0, 0, 0, 0.4);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            border: 1px dashed rgba(0, 242, 254, 0.3);
            letter-spacing: 0.12em;
            color: var(--neon-cyan);
            word-break: break-all;
        }

        .instructions {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: left;
            margin-bottom: 1.4rem;
            background: rgba(255, 255, 255, 0.02);
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--neon-cyan);
            line-height: 1.6;
        }

        .instructions ol {
            padding-left: 1.2rem;
        }

        .instructions li {
            margin-bottom: 0.3rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .otp-input {
            width: 100%;
            padding: 0.85rem;
            font-size: 1.4rem;
            letter-spacing: 0.35em;
            text-align: center;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(0, 242, 254, 0.3);
            color: var(--neon-cyan);
            font-family: inherit;
            font-weight: 700;
            outline: none;
        }

        .otp-input:focus {
            border-color: var(--neon-cyan);
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.3);
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-blue));
            color: #06090e;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fda4af;
        }

        .recovery-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 1000;
        }

        .recovery-content {
            background: #0d1321;
            border: 1px solid var(--neon-emerald);
            border-radius: 12px;
            max-width: 440px;
            width: 100%;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.2);
        }

        .recovery-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin: 1.2rem 0;
        }

        .recovery-code {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.4rem;
            border-radius: 4px;
            font-size: 0.75rem;
            color: var(--neon-emerald);
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
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
                KEY: <?= htmlspecialchars($secret) ?>
            </div>
        </div>

        <form id="setupForm">
            <div class="form-group">
                <input type="text" id="code" class="otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">
                Confirm & Activate 2FA →
            </button>
        </form>
    </div>

    <!-- Recovery Codes Modal -->
    <div id="recoveryModal" class="recovery-modal">
        <div class="recovery-content">
            <h2 style="color:var(--neon-emerald);font-size:1.2rem;margin-bottom:0.5rem;">🎉 2FA Activated</h2>
            <p style="font-size:0.75rem;color:var(--text-muted);line-height:1.5;">
                Save these emergency recovery codes in a secure location. Each code can be used once if you lose access to Google Authenticator:
            </p>

            <div id="recoveryGrid" class="recovery-grid"></div>

            <button id="btnProceedAdmin" class="btn-submit" style="background:var(--neon-emerald);color:#06090e;">
                I Have Saved My Codes → Enter Admin
            </button>
        </div>
    </div>

    <script>
        const form = document.getElementById('setupForm');
        const alertBox = document.getElementById('alertBox');
        const btnSubmit = document.getElementById('btnSubmit');
        const recoveryModal = document.getElementById('recoveryModal');
        const recoveryGrid = document.getElementById('recoveryGrid');
        const btnProceedAdmin = document.getElementById('btnProceedAdmin');
        let redirectTarget = '<?= $appBase ?>/admin/';

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Verifying Code...';

            try {
                const res = await fetch('<?= $appBase ?>/api/v1/auth/2fa/setup', {
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
                alertBox.textContent = 'Network communication failure during 2FA activation.';
                alertBox.style.display = 'block';
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Confirm & Activate 2FA →';
            }
        });

        btnProceedAdmin.addEventListener('click', () => {
            window.location.href = redirectTarget;
        });
    </script>
</body>
</html>
