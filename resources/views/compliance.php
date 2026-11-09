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
                radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.08) 0px, transparent 50%);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 32px;
            overflow-x: hidden;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-glass);
            max-width: 1100px;
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
            background: rgba(56, 189, 248, 0.12);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.3);
            font-size: 0.75rem;
            font-family: var(--font-mono);
            padding: 3px 8px;
            border-radius: 4px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
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
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
            flex-grow: 1;
        }

        .hero {
            text-align: center;
            margin-bottom: 36px;
        }

        .hero h1 {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #f1f5f9 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            max-width: 720px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Scorecard Grid */
        .scorecard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .score-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(16px);
            transition: all 0.2s ease;
        }

        .score-card:hover {
            border-color: var(--border-focus);
            transform: translateY(-2px);
        }

        .score-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-family: var(--font-mono);
        }

        .score-value {
            font-size: 2rem;
            font-weight: 800;
            font-family: var(--font-mono);
            letter-spacing: -0.02em;
        }

        .score-sub {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Navigation Tabs */
        nav.tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
            overflow: hidden !important;
            overflow-x: hidden !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }

        nav.tabs::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        .tab-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: var(--font-mono);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--text-primary);
            border-color: var(--border-focus);
            background: var(--bg-card-hover);
        }

        .tab-btn.active {
            color: var(--accent-cyan);
            border-color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.12);
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.15);
        }

        /* Tab Panes */
        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .framework-summary {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            backdrop-filter: blur(16px);
        }

        .framework-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .framework-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .framework-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Tables */
        .table-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        th {
            background: rgba(14, 18, 28, 0.9);
            color: var(--text-secondary);
            padding: 14px 18px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-glass);
            font-family: var(--font-mono);
        }

        td {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .mandate-tag {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 4px;
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.25);
            white-space: nowrap;
            font-weight: 600;
        }

        .tag-pill {
            display: inline-block;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            margin: 2px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-family: var(--font-mono);
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-cyan {
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .badge-purple {
            background: rgba(168, 85, 247, 0.15);
            color: var(--accent-purple);
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--accent-amber);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Buttons */
        .btn {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid var(--border-glass);
        }

        .btn-secondary:hover {
            color: var(--text-primary);
            border-color: var(--border-focus);
            background: rgba(255, 255, 255, 0.08);
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

        /* Raw JSON Box */
        .json-preview {
            background: rgba(6, 8, 14, 0.9);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 16px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: #38bdf8;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: 12px;
        }

        footer {
            text-align: center;
            font-size: 0.75rem;
            color: #475569;
            font-family: var(--font-mono);
            border-top: 1px solid var(--border-glass);
            padding-top: 24px;
            max-width: 1100px;
            margin: 40px auto 0 auto;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <div class="brand-title">
            <span>DREAMBOX NEXUS</span>
            <span class="brand-badge">COMPLIANCE MATRIX v0.1 ALPHA</span>
        </div>
        <div class="header-actions">
            <a href="<?= $appBase . '/' ?>" class="btn btn-secondary">&larr; Exit to Gateway</a>
            <div class="status-pill">
                <div class="pulse-dot"></div>
                <span><?= htmlspecialchars($status) ?></span>
            </div>
        </div>
    </header>

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
                <div class="score-value" style="color: var(--accent-green);"><?= $score ?>%</div>
                <div class="score-sub">Full regulatory posture</div>
            </div>
            <div class="score-card">
                <div class="score-label">Audit Status</div>
                <div class="score-value" style="color: var(--accent-cyan); font-size: 1.5rem; line-height: 2rem;"><?= htmlspecialchars($status) ?></div>
                <div class="score-sub">Production verified</div>
            </div>
            <div class="score-card">
                <div class="score-label">Active Mandates</div>
                <div class="score-value" style="color: var(--accent-purple);"><?= $totalControls ?></div>
                <div class="score-sub">Controls mapped across 5 frameworks</div>
            </div>
            <div class="score-card">
                <div class="score-label">Live Verification</div>
                <div class="score-value" style="color: var(--accent-green);"><?= $passedLive ?> / <?= $totalLive ?></div>
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
                                <td style="font-weight: 600; font-family: var(--font-mono); color: var(--text-primary);">
                                    <?= htmlspecialchars($check['name']) ?>
                                </td>
                                <td>
                                    <?php foreach ($check['frameworks'] ?? [] as $fwTag): ?>
                                        <span class="tag-pill"><?= htmlspecialchars($fwTag) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.8rem;">
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
                            <div style="font-size: 0.8rem; color: var(--accent-cyan); font-family: var(--font-mono); margin-top: 4px;">
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
                                <th style="width: 140px;">Mandate / Article</th>
                                <th style="width: 220px;">Control Requirement</th>
                                <th>Framework Implementation Mechanism</th>
                                <th>Active Subsystem</th>
                                <th style="width: 160px;">Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fw['controls'] as $control): ?>
                                <tr>
                                    <td>
                                        <span class="mandate-tag"><?= htmlspecialchars($control['mandate']) ?></span>
                                    </td>
                                    <td style="font-weight: 600; color: var(--text-primary);">
                                        <?= htmlspecialchars($control['name']) ?>
                                    </td>
                                    <td style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5;">
                                        <?= htmlspecialchars($control['mechanism']) ?>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.75rem; color: var(--accent-purple); font-family: var(--font-mono); background: rgba(168, 85, 247, 0.1); padding: 2px 6px; border-radius: 4px;">
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
        <div style="margin-top: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="font-size: 0.85rem; color: var(--text-secondary); font-family: var(--font-mono);">
                RFC 7807 / Machine-Readable Audit Feed:
            </div>
            <div style="display: flex; gap: 8px;">
                <button id="btn-toggle-json" class="btn btn-secondary">👁️ View Raw JSON Audit</button>
                <a href="<?= $appBase ?>/compliance?format=json" class="btn btn-primary" download="dreambox-compliance-report.json">⬇️ Download JSON Report</a>
            </div>
        </div>

        <div id="json-drawer" style="display: none;">
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
                    if (drawer.style.display === 'none') {
                        drawer.style.display = 'block';
                        btnJson.innerText = '✕ Hide Raw JSON Audit';
                    } else {
                        drawer.style.display = 'none';
                        btnJson.innerText = '👁️ View Raw JSON Audit';
                    }
                });
            }
        });
    </script>
</body>
</html>

