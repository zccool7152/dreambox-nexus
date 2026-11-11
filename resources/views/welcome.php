<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$user = $user ?? $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Security-First Application Gateway</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
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
            <a href="<?= $appBase ?>/ui/" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                <span>Nexus UI Framework</span>
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
