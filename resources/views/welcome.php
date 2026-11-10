<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Security-First Application Gateway</title>
    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #ffffff;
            --border-light: #e2e8f0;
            --border-hover: #0284c7;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-subtle: #94a3b8;
            --primary: #0284c7;
            --primary-hover: #0369a1;
            --success: #10b981;
            --radius-lg: 16px;
            --radius-md: 10px;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 12px 20px -3px rgba(2, 132, 199, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-page);
            background-image: 
                radial-gradient(at 0% 0%, rgba(2, 132, 199, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(59, 130, 246, 0.04) 0px, transparent 50%);
            color: var(--text-main);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        main {
            max-width: 1100px;
            margin: 3.5rem auto;
            width: 100%;
            padding: 0 1.5rem;
            flex: 1;
        }

        .hero {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            padding: 5px 14px;
            border-radius: 9999px;
            margin-bottom: 1.25rem;
            letter-spacing: 0.02em;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
            color: #0f172a;
            line-height: 1.15;
        }

        .hero h1 span {
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-muted);
            font-size: 1.15rem;
            max-width: 680px;
            margin: 0 auto 1.75rem auto;
            line-height: 1.6;
        }

        .compliance-tags {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag {
            font-family: var(--font-mono);
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            color: #475569;
            box-shadow: var(--shadow-sm);
        }

        .tag-highlight {
            background: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: var(--shadow-card);
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .card:hover {
            border-color: var(--border-hover);
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .card-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            background: #f0f9ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.25rem;
        }

        .card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.01em;
        }

        .card p {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.6;
            flex: 1;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            font-size: 0.875rem;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155 !important;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a !important;
            transform: translateY(-1px);
        }

        footer {
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-subtle);
            border-top: 1px solid var(--border-light);
            padding: 1.75rem 0;
            background: #ffffff;
            margin-top: 3rem;
        }
    </style>
</head>
<body>

    <!-- Universal Light-Theme Navigation Top Bar with Dynamic User Dropdown -->
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <main>
        <div class="hero">
            <div class="hero-pill">
                <div class="pulse-dot"></div>
                <span>CIA IAM GATEWAY & OBSERVABILITY HUD</span>
            </div>
            <h1>Next-Gen <span>Security Gateway</span></h1>
            <p>
                A 100% custom, zero-external-dependency PHP framework engineered from the ground up 
                for regulatory compliance, defense-in-depth security, and high-integrity workloads.
            </p>
            <div class="compliance-tags">
                <span class="tag tag-highlight">ZERO THIRD-PARTY COMPOSER PACKAGES</span>
                <span class="tag">PCI-DSS v4.0</span>
                <span class="tag">HIPAA SECURITY RULE</span>
                <span class="tag">NIST CSF 2.0</span>
                <span class="tag">EU GDPR</span>
                <span class="tag">OWASP TOP 10</span>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h3>Defense-in-Depth Engine</h3>
                <p>
                    Strict PER-CS architecture with dynamic CSP nonces, Argon2id hashing, AES-256-GCM AEAD encryption, 
                    SSRF-blocked HTTP client, sliding-window rate limiting, and 5-strike account lockouts.
                </p>
            </div>
            <div class="card">
                <div class="card-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                </div>
                <h3>Cryptographic Audit Trail</h3>
                <p>
                    Immutable, SHA-256 hash-chained compliance audit logs mathematically guaranteeing 
                    tamper detection across all administrative and data subject access events.
                </p>
            </div>
            <div class="card">
                <div class="card-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                </div>
                <h3>Live Telemetry & NoSQL Engine</h3>
                <p>
                    Real-time observability cockpit, MongoDB v7.0 document store, asynchronous queue 
                    with dead-letter fault isolation, and zero-dependency CLI tooling via <code>php bin/nexus</code>.
                </p>
            </div>
        </div>

        <div class="actions">
            <a href="<?= $appBase ?>/admin/" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>IAM Console</span>
            </a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                <span>SecOps Cockpit</span>
            </a>
            <a href="<?= $appBase ?>/compliance/" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>Compliance Matrix</span>
            </a>
            <a href="<?= $appBase ?>/docs/openapi.yaml?format=html" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span>OpenAPI Contract</span>
            </a>
        </div>
    </main>

    <footer>
        Dreambox Nexus Core v0.1 Alpha • Zero-Guessing Security Invariant • Built for Extreme Reliability
    </footer>
</body>
</html>
