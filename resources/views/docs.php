<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // OpenAPI 3.0 Contract Specification</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/brand/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/brand/site.webmanifest">
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
                <span class="small text-cyan">Raw Contract Preview</span>
                <span class="small text-muted">openapi.yaml (14.4 KB)</span>
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
        Dreambox Nexus Core v0.1 Alpha // Zero-Guessing Security Invariant // OpenAPI Contract Registry
    </footer>
</body>
</html>

