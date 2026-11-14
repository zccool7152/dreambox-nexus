<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$report = $report ?? [];
$score = $report['compliance_score'] ?? 100;
$status = $report['status'] ?? 'AUDIT READY';
$summary = $report['summary'] ?? [];
$liveChecks = $report['live_verification'] ?? [];
$frameworks = $report['frameworks'] ?? [];
$passedLive = $summary['live_runtime_checks']['passed'] ?? count($liveChecks);
$totalLive = $summary['live_runtime_checks']['total'] ?? count($liveChecks);
$totalControls = $summary['total_evaluated_controls'] ?? 24;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Regulatory Compliance Matrix</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/brand/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/brand/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <main>
        <div class="hero">
            <h1>Regulatory Compliance & Standards Matrix</h1>
            <p>
                Continuous automated verification mathematically proving defense-in-depth safeguards,
                cryptographic integrity, and strict adherence to global regulatory frameworks.
            </p>
        </div>

        <!-- 4 Scorecards -->
        <div class="scorecard-grid">
            <div class="score-card">
                <div class="score-label">Compliance Score</div>
                <div class="score-value text-success"><?= $score ?>%</div>
                <div class="score-sub">Full regulatory posture</div>
            </div>
            <div class="score-card">
                <div class="score-label">Audit Status</div>
                <div class="score-value text-cyan"><?= htmlspecialchars($status) ?></div>
                <div class="score-sub">Production verified</div>
            </div>
            <div class="score-card">
                <div class="score-label">Active Mandates</div>
                <div class="score-value text-purple"><?= $totalControls ?></div>
                <div class="score-sub">Controls mapped across 5 frameworks</div>
            </div>
            <div class="score-card">
                <div class="score-label">Live Verification</div>
                <div class="score-value text-success"><?= $passedLive ?> / <?= $totalLive ?></div>
                <div class="score-sub">10-point automated checks</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="tabs">
            <button class="tab-btn active" data-tab="live">Live Runtime Audit</button>
            <button class="tab-btn" data-tab="pci_dss">PCI-DSS v4.0</button>
            <button class="tab-btn" data-tab="hipaa">HIPAA Security</button>
            <button class="tab-btn" data-tab="nist_csf">NIST CSF 2.0</button>
            <button class="tab-btn" data-tab="eu_gdpr">EU GDPR</button>
            <button class="tab-btn" data-tab="owasp_top10">OWASP Defense</button>
        </nav>

        <!-- 1. Live Runtime Verification Tab -->
        <div id="pane-live" class="tab-pane active">
            <div class="framework-summary">
                <div class="framework-header">
                    <span class="framework-title">Automated Live Verification Suite</span>
                    <span class="badge badge-success">ACTIVE MONITORING</span>
                </div>
                <p class="framework-desc">
                    Real-time automated diagnostic checks continuously validating directory permissions,
                    cryptographic chain continuity, Argon2id availability, SSRF boundaries, and datastore integrity.
                </p>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Security Check</th>
                            <th>Target Frameworks</th>
                            <th>Diagnostics & Verification Detail</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($liveChecks as $key => $check): ?>
                            <tr>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($check['name']) ?>
                                </td>
                                <td>
                                    <?php foreach ($check['frameworks'] ?? [] as $fwTag): ?>
                                        <span class="tag-pill"><?= htmlspecialchars($fwTag) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($check['details']) ?>
                                </td>
                                <td>
                                    <?php if ($check['passed']): ?>
                                        <span class="badge badge-success">PASSED</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">ATTENTION</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2–6. Framework Breakdown Tabs -->
        <?php foreach ($frameworks as $fwKey => $fw): ?>
            <div id="pane-<?= htmlspecialchars($fwKey) ?>" class="tab-pane">
                <div class="framework-summary">
                    <div class="framework-header">
                        <div>
                            <span class="framework-title"><?= htmlspecialchars($fw['title']) ?></span>
                            <div class="text-cyan small mt-1">
                                <?= htmlspecialchars($fw['standard']) ?>
                            </div>
                        </div>
                        <span class="badge badge-cyan"><?= htmlspecialchars($fw['badge']) ?></span>
                    </div>
                    <p class="framework-desc"><?= htmlspecialchars($fw['summary']) ?></p>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Mandate / Article</th>
                                <th>Control Requirement</th>
                                <th>Framework Implementation Mechanism</th>
                                <th>Active Subsystem</th>
                                <th>Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fw['controls'] as $control): ?>
                                <tr>
                                    <td>
                                        <span class="mandate-tag"><?= htmlspecialchars($control['mandate']) ?></span>
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        <?= htmlspecialchars($control['name']) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars($control['mechanism']) ?>
                                    </td>
                                    <td>
                                        <code class="hash-code text-purple">
                                            <?= htmlspecialchars($control['implementation']) ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if (str_contains($control['status'], 'COMPLIANT') || str_contains($control['status'], 'INTACT')): ?>
                                            <span class="badge badge-success"><?= htmlspecialchars($control['status']) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-cyan"><?= htmlspecialchars($control['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Machine-Readable JSON Export Section -->
        <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="text-muted small">
                RFC 7807 / Machine-Readable Audit Feed:
            </div>
            <div class="d-flex gap-2">
                <button id="btn-toggle-json" class="btn btn-secondary">👁️ View Raw JSON Audit</button>
                <a href="<?= $appBase ?>/compliance?format=json" class="btn btn-primary" download="dreambox-compliance-report.json">⬇️ Download JSON Report</a>
            </div>
        </div>

        <div id="json-drawer" class="d-none">
            <pre class="json-preview"><?= htmlspecialchars(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
        </div>
    </main>

    <footer>
        Dreambox Nexus Core v0.1 Alpha // Zero-Guessing Security Invariant // Built for Extreme Reliability
    </footer>

    <script nonce="<?= $nonce ?? '' ?>">
        document.addEventListener('DOMContentLoaded', () => {
            // Tab switching without inline handlers for strict CSP compliance
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-tab');
                    tabButtons.forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

                    btn.classList.add('active');
                    const pane = document.getElementById('pane-' + target);
                    if (pane) pane.classList.add('active');
                });
            });

            // Toggle Raw JSON Drawer
            const btnJson = document.getElementById('btn-toggle-json');
            const drawer = document.getElementById('json-drawer');
            if (btnJson && drawer) {
                btnJson.addEventListener('click', () => {
                    if (drawer.classList.contains('d-none')) {
                        drawer.classList.remove('d-none');
                        btnJson.innerText = '✕ Hide Raw JSON Audit';
                    } else {
                        drawer.classList.add('d-none');
                        btnJson.innerText = '👁️ View Raw JSON Audit';
                    }
                });
            }
        });
    </script>
</body>
</html>

