<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // OpenAPI 3.0 Contract Specification</title>
    <style>
        :root {
            --bg-base: #06080e;
            --bg-card: rgba(14, 18, 28, 0.75);
            --bg-card-hover: rgba(20, 26, 40, 0.85);
            --border-glass: rgba(56, 189, 248, 0.15);
            --border-focus: rgba(56, 189, 248, 0.5);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-cyan: #38bdf8;
            --accent-green: #10b981;
            --accent-purple: #a855f7;
            --accent-amber: #f59e0b;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.08) 0px, transparent 50%);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 32px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-glass);
            max-width: 1000px;
            margin: 0 auto 32px auto;
            width: 100%;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-badge {
            background: rgba(245, 158, 11, 0.12);
            color: var(--accent-amber);
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.75rem;
            font-family: var(--font-mono);
            padding: 3px 8px;
            border-radius: 4px;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.25);
            padding: 6px 14px;
            border-radius: 9999px;
            font-family: var(--font-mono);
        }

        main {
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }

        .hero {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-top: 3px solid var(--accent-amber);
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .hero p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            max-width: 600px;
        }

        .endpoint-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 32px;
        }

        .endpoint-row {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .endpoint-row:hover {
            border-color: var(--border-focus);
            background: var(--bg-card-hover);
        }

        .endpoint-meta {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .method-badge {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            width: 70px;
            text-align: center;
        }

        .get { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .post { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .delete { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }

        .endpoint-path {
            font-family: var(--font-mono);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .endpoint-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .yaml-box {
            background: #030408;
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .yaml-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        pre {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            color: #94a3b8;
            max-height: 400px;
            overflow-y: auto;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.4);
        }

        .btn-primary:hover {
            background: var(--accent-cyan);
            color: var(--bg-base);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid var(--border-glass);
        }

        .btn-secondary:hover {
            color: var(--text-primary);
            border-color: var(--text-secondary);
        }

        footer {
            text-align: center;
            font-size: 0.75rem;
            color: #475569;
            font-family: var(--font-mono);
            border-top: 1px solid var(--border-glass);
            padding-top: 24px;
            max-width: 1000px;
            margin: 32px auto 0 auto;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <div class="brand-title">
            <span>DREAMBOX NEXUS</span>
            <span class="brand-badge">CORE v7.0</span>
        </div>
        <div class="status-pill">
            <span>OPENAPI 3.0.3 CONTRACT</span>
        </div>
    </header>

    <main>
        <div class="hero">
            <div>
                <h1>API Contract Specification</h1>
                <p>Zero-dependency OpenAPI 3.0.3 specification enforcing uniform <code>{data, meta, message}</code> response envelopes across all endpoints.</p>
            </div>
            <div>
                <a href="?format=yaml" class="btn btn-primary">⬇️ Download Raw YAML</a>
            </div>
        </div>

        <div class="endpoint-list">
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/health</span>
                </div>
                <span class="endpoint-desc">Liveness probe returning HTTP 200 health state</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/health/ready</span>
                </div>
                <span class="endpoint-desc">Readiness probe checking DB & storage writability</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/nexus/monitor</span>
                </div>
                <span class="endpoint-desc">Secured Cyber-Noir Observability HUD (Restricted)</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/api/v1/monitor/overview</span>
                </div>
                <span class="endpoint-desc">System vitals: CPU, Memory, OPcache & Storage</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/api/v1/monitor/security</span>
                </div>
                <span class="endpoint-desc">SOC telemetry: Rate limits, CSRF & account lockouts</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/api/v1/monitor/traffic</span>
                </div>
                <span class="endpoint-desc">Traffic telemetry: RPS, latencies & status codes</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/api/v1/monitor/audit</span>
                </div>
                <span class="endpoint-desc">Tamper-evident audit logs with cryptographic hash chain</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/api/v1/auth/login</span>
                </div>
                <span class="endpoint-desc">Dual-guard authentication (Argon2id + 5-strike lockout)</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/api/v1/gdpr/export</span>
                </div>
                <span class="endpoint-desc">Right of Access (JSON portability bundle download)</span>
            </div>
            <div class="endpoint-row">
                <div class="endpoint-meta">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/api/v1/gdpr/erase</span>
                </div>
                <span class="endpoint-desc">Right to be Forgotten (Irreversible digital shredder)</span>
            </div>
        </div>

        <div class="yaml-box">
            <div class="yaml-header">
                <span style="font-family: var(--font-mono); font-size: 0.85rem; color: var(--accent-cyan);">Raw Contract Preview</span>
                <span style="font-size: 0.75rem; color: var(--text-secondary); font-family: var(--font-mono);">openapi.yaml (14.4 KB)</span>
            </div>
            <pre><code><?= htmlspecialchars($rawYaml ?? '', ENT_QUOTES, 'UTF-8') ?></code></pre>
        </div>

        <div class="actions">
            <a href="<?= $appBase ? $appBase . '/' : '/' ?>" class="btn btn-secondary">&larr; Return to Application Gateway</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-primary">🔒 Open SecOps Cockpit</a>
            <a href="<?= $appBase ?>/health/" class="btn btn-secondary">❤️ Health Probe</a>
        </div>
    </main>

    <footer>
        Dreambox Nexus Core v7.0 // Zero-Guessing Security Invariant // OpenAPI Contract Registry
    </footer>
</body>
</html>

