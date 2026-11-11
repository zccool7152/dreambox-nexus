<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // System Health Diagnostics</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/logos/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/logos/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/logos/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/logos/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include __DIR__ . "/partials/topbar.php"; ?>
    <header>
        <div class="brand-title">
            <span>DREAMBOX NEXUS</span>
            <span class="brand-badge">CORE v0.1 ALPHA</span>
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
                <div class="card-value">
                    PHP <?= PHP_VERSION ?> // <?= PHP_OS_FAMILY ?>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Active Request Trace</div>
                <div class="card-value small text-cyan">
                    <?= htmlspecialchars($traceId ?? 'trace-live', ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Security & Defense Subsystem</div>
                <div class="card-value small text-purple">
                    Strict PER-CS // Nonced CSP
                </div>
            </div>
        </div>

        <div class="json-preview">
            <div class="json-header">
                <span>Programmatic Output (RFC 7807 Envelope)</span>
                <a href="?format=json" class="text-cyan">View Raw JSON &rarr;</a>
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
            <a href="<?= $appBase ?>/compliance/" class="btn btn-secondary">⚖️ Compliance Matrix</a>
            <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-primary">🔒 Open SecOps Cockpit</a>
            <a href="<?= $appBase ?>/docs/" class="btn btn-secondary">📜 OpenAPI 3.0 Contract</a>
        </div>
    </main>

    <footer>
        Dreambox Nexus Core v0.1 Alpha // Zero-Guessing Security Invariant // High-Integrity Health Probe
    </footer>
</body>
</html>

