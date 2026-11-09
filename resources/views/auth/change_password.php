<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Mandatory Password Rotation</title>
    <style>
        :root {
            --bg-base: #06090e;
            --surface-glass: rgba(13, 19, 33, 0.85);
            --border-subtle: rgba(0, 242, 254, 0.2);
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
            max-width: 440px;
            background: var(--surface-glass);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            backdrop-filter: blur(16px);
            padding: 2.2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 25px rgba(0, 242, 254, 0.12);
        }

        .brand-badge {
            display: inline-block;
            font-size: 0.65rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            background: rgba(245, 158, 11, 0.15);
            color: var(--neon-amber);
            border: 1px solid rgba(245, 158, 11, 0.35);
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

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.4rem;
        }

        .form-input {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: var(--neon-cyan);
            box-shadow: 0 0 10px rgba(0, 242, 254, 0.2);
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
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            margin-bottom: 1.2rem;
            display: none;
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fda4af;
        }
    </style>
</head>
<body>
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

