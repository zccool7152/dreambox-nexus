<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // System Health Diagnostics</title>
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
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
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
            max-width: 900px;
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
            background: rgba(16, 185, 129, 0.12);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
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
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 6px 14px;
            border-radius: 9999px;
            font-family: var(--font-mono);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        main {
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .status-hero {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-top: 3px solid var(--accent-green);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
        }

        .status-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }

        .status-hero h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--accent-green);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .status-hero p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            padding: 20px;
        }

        .card-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: var(--font-mono);
            margin-bottom: 6px;
        }

        .card-value {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: var(--font-mono);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-live {
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-green);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .json-preview {
            background: #030408;
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 32px;
            font-family: var(--font-mono);
            font-size: 0.8rem;
            color: #93c5fd;
            overflow-x: auto;
            position: relative;
        }

        .json-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            max-width: 900px;
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
            <div class="pulse-dot"></div>
            <span>PROBE ACTIVE</span>
        </div>
    </header>

    <main>
        <div class="status-hero">
            <div class="status-icon">💚</div>
            <h1>All Systems Operational</h1>
            <p>Framework micro-kernel is running smoothly and ready to accept ingress traffic.</p>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-label">HTTP Liveness Status</div>
                <div class="card-value">
                    <span class="badge-live">STATUS: UP (200 OK)</span>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Engine & Platform</div>
                <div class="card-value" style="font-size: 0.95rem;">
                    PHP <?= PHP_VERSION ?> // <?= PHP_OS_FAMILY ?>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Active Request Trace</div>
                <div class="card-value" style="font-size: 0.8rem; color: var(--accent-cyan);">
                    <?= htmlspecialchars($traceId ?? 'trace-live', ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Security & Defense Subsystem</div>
                <div class="card-value" style="font-size: 0.85rem; color: #a78bfa;">
                    Strict PER-CS // Nonced CSP
                </div>
            </div>
        </div>

        <div class="json-preview">
            <div class="json-header">
                <span>Programmatic Output (RFC 7807 Envelope)</span>
                <a href="?format=json" style="color: var(--accent-cyan); text-decoration: none;">View Raw JSON &rarr;</a>
            </div>
            <pre><code>{
  "data": {
    "status": "UP",
    "timestamp": <?= time() ?>,
    "php_version": "<?= PHP_VERSION ?>",
    "server_os": "<?= PHP_OS_FAMILY ?>"
  },
  "meta": {
    "trace_id": "<?= htmlspecialchars($traceId ?? 'live-trace', ENT_QUOTES, 'UTF-8') ?>",
    "timestamp": "<?= gmdate('Y-m-d\TH:i:s\Z') ?>"
  },
  "message": "Service is alive and accepting traffic."
}</code></pre>
        </div>

        <div class="actions">
            <a href="<?= $appBase ? $appBase . '/' : '/' ?>" class="btn btn-secondary">&larr; Return to Application Gateway</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-primary">🔒 Open SecOps Cockpit</a>
            <a href="<?= $appBase ?>/docs/" class="btn btn-secondary">📜 OpenAPI 3.0 Contract</a>
        </div>
    </main>

    <footer>
        Dreambox Nexus Core v7.0 // Zero-Guessing Security Invariant // High-Integrity Health Probe
    </footer>
</body>
</html>

