<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Core v7.0</title>
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
                radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.08) 0px, transparent 50%);
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
            background: rgba(56, 189, 248, 0.12);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.3);
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
            max-width: 1000px;
            margin: 40px auto;
            width: 100%;
        }

        .hero {
            text-align: center;
            margin-bottom: 48px;
        }

        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #f1f5f9 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-secondary);
            font-size: 1.15rem;
            max-width: 650px;
            margin: 0 auto 24px auto;
            line-height: 1.6;
        }

        .compliance-tags {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tag {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: var(--accent-cyan);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s ease;
        }

        .card:hover {
            border-color: var(--border-focus);
            background: var(--bg-card-hover);
            transform: translateY(-2px);
        }

        .card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card p {
            font-size: 0.85rem;
            color: var(--text-secondary);
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
            <span>GATEWAY ACTIVE</span>
        </div>
    </header>

    <main>
        <div class="hero">
            <h1>Security-First Application Gateway</h1>
            <p>
                A 100% custom, zero-external-dependency PHP framework engineered from scratch 
                for regulatory compliance, defense-in-depth security, and high-integrity workloads.
            </p>
            <div class="compliance-tags">
                <span class="tag">ZERO THIRD-PARTY COMPOSER PACKAGES</span>
                <span class="tag">PCI-DSS v4.0</span>
                <span class="tag">HIPAA SECURITY RULE</span>
                <span class="tag">NIST CSF 2.0</span>
                <span class="tag">EU GDPR</span>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>🛡️ Defense-in-Depth Engine</h3>
                <p>
                    Strict PER-CS architecture with CSP nonces, Argon2id hashing, AES-256-GCM AEAD encryption, 
                    SSRF-blocked HTTP client, sliding-window rate limiting, and 5-strike account lockouts.
                </p>
            </div>
            <div class="card">
                <h3>🔗 Cryptographic Audit Trail</h3>
                <p>
                    Immutable, SHA-256 hash-chained compliance audit logs mathematically guaranteeing 
                    tamper detection across all administrative and data subject access events.
                </p>
            </div>
            <div class="card">
                <h3>⚡ Native Async Subsystems</h3>
                <p>
                    Built-in queue engine with dead-letter recovery, transactional database migrations, 
                    cron scheduler, and zero-dependency CLI tooling via <code>php bin/nexus</code>.
                </p>
            </div>
        </div>

        <div class="actions">
            <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-primary">🔒 SecOps Cockpit (Restricted)</a>
            <a href="<?= $appBase ?>/compliance/" class="btn btn-secondary">⚖️ Compliance Matrix</a>
            <a href="<?= $appBase ?>/health/" class="btn btn-secondary">❤️ Health Probe</a>
            <a href="<?= $appBase ?>/docs/" class="btn btn-secondary">📜 OpenAPI 3.0 Contract</a>
        </div>
    </main>

    <footer>
        Dreambox Nexus Core v7.0 // Zero-Guessing Security Invariant // Built for Extreme Reliability
    </footer>
</body>
</html>

