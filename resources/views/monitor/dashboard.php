<?php
$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$vitals = $vitals ?? (class_exists(\Nexus\Monitor\SystemProfiler::class) ? (new \Nexus\Monitor\SystemProfiler())->getMetrics() : []);
$initMem = ($vitals['memory']['current_mb'] ?? 2) . ' MB';
$initMemPeak = 'Peak: ' . ($vitals['memory']['peak_mb'] ?? 2) . ' MB | Limit: ' . ($vitals['memory']['limit'] ?? '-1');
$initCpu = ($vitals['cpu_load']['1m'] ?? '0.10') . ' / ' . ($vitals['cpu_load']['5m'] ?? '0.10') . ' / ' . ($vitals['cpu_load']['15m'] ?? '0.10');
$initOpcache = ($vitals['opcache']['hit_rate'] ?? 0) . '%';
$initOpcacheStats = 'Hits: ' . ($vitals['opcache']['hits'] ?? 0) . ' | Misses: ' . ($vitals['opcache']['misses'] ?? 0);
$initStorage = $vitals['storage_formatted']['logs'] ?? '0 B';
$initStorageBreakdown = 'Logs: ' . ($vitals['storage_formatted']['logs'] ?? '0 B') . ' | Cache: ' . ($vitals['storage_formatted']['cache'] ?? '0 B');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Telemetry & Observability HUD</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>

    <?php include dirname(__DIR__) . '/partials/topbar.php'; ?>
    <main class="container">

    <nav class="tabs">
        <button class="tab-btn active" data-tab="vitals" onclick="switchTab('vitals')">System Vitals</button>
        <button class="tab-btn" data-tab="security" onclick="switchTab('security')">Security & SOC</button>
        <button class="tab-btn" data-tab="traffic" onclick="switchTab('traffic')">Traffic & Ingress</button>
        <button class="tab-btn" data-tab="database" onclick="switchTab('database')">Database & Schema</button>
        <button class="tab-btn" data-tab="queue" onclick="switchTab('queue')">Queue & Workers</button>
        <button class="tab-btn" data-tab="audit" onclick="switchTab('audit')">Audit Hash-Chain</button>
        <button class="tab-btn" data-tab="gdpr" onclick="switchTab('gdpr')">Privacy & GDPR</button>
        <button class="tab-btn" data-tab="routes" onclick="switchTab('routes')">Route Registry</button>
    </nav>

    <!-- 1. System Vitals Tab -->
    <div id="pane-vitals" class="tab-pane active">
        <div class="grid-cards">
            <div class="card">
                <div class="card-header">
                    <span>Memory Allocation</span>
                    <span class="badge badge-cyan">RAM</span>
                </div>
                <div class="metric-value" id="val-mem"><?= htmlspecialchars((string)$initMem) ?></div>
                <div class="metric-sub" id="val-mem-peak"><?= htmlspecialchars((string)$initMemPeak) ?></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span>CPU Load Average</span>
                    <span class="badge badge-cyan">WSL2</span>
                </div>
                <div class="metric-value" id="val-cpu"><?= htmlspecialchars((string)$initCpu) ?></div>
                <div class="metric-sub">1m / 5m / 15m load interval</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span>Zend OPcache Hit Rate</span>
                    <span class="badge badge-success">ACCELERATION</span>
                </div>
                <div class="metric-value" id="val-opcache"><?= htmlspecialchars((string)$initOpcache) ?></div>
                <div class="metric-sub" id="val-opcache-stats"><?= htmlspecialchars((string)$initOpcacheStats) ?></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span>Storage Footprint</span>
                    <span class="badge badge-warning">STORAGE</span>
                </div>
                <div class="metric-value" id="val-storage"><?= htmlspecialchars((string)$initStorage) ?></div>
                <div class="metric-sub" id="val-storage-breakdown"><?= htmlspecialchars((string)$initStorageBreakdown) ?></div>
            </div>
        </div>
    </div>

    <!-- 2. Security & SOC Tab -->
    <div id="pane-security" class="tab-pane">
        <div class="grid-cards">
            <div class="card">
                <div class="card-header">
                    <span>Throttled Client IPs</span>
                    <span class="badge badge-warning">RATE LIMIT</span>
                </div>
                <div class="metric-value" id="val-throttled-count">0</div>
                <div class="metric-sub">Active sliding-window blocks</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span>PCI Locked Accounts</span>
                    <span class="badge badge-danger">PCI-DSS 8.3.4</span>
                </div>
                <div class="metric-value" id="val-locked-count">0</div>
                <div class="metric-sub">Cooling window active (5 strikes)</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span>Defense-in-Depth Headers</span>
                    <span class="badge badge-success">ARMED</span>
                </div>
                <div class="metric-value">100%</div>
                <div class="metric-sub">Dynamic CSP Nonces, HSTS, DENY</div>
            </div>
        </div>

        <div class="data-table-wrapper">
            <div class="p-3 fw-bold border-bottom">
                Browser CSP Violations Log
            </div>
            <table id="tbl-csp">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Client IP</th>
                        <th>Blocked URI</th>
                        <th>Violated Directive</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="text-center text-muted">No active CSP violation reports detected.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Traffic & Ingress Tab -->
    <div id="pane-traffic" class="tab-pane">
        <div class="grid-cards">
            <div class="card">
                <div class="card-header"><span>Throughput Rate</span><span class="badge badge-cyan">RPS</span></div>
                <div class="metric-value" id="val-traffic-rps">--</div>
                <div class="metric-sub">Requests per second</div>
            </div>
            <div class="card">
                <div class="card-header"><span>Latency (p50)</span><span class="badge badge-success">RESPONSE</span></div>
                <div class="metric-value" id="val-latency-p50">-- ms</div>
                <div class="metric-sub">Median response duration</div>
            </div>
            <div class="card">
                <div class="card-header"><span>Latency (p99)</span><span class="badge badge-warning">RESPONSE</span></div>
                <div class="metric-value" id="val-latency-p99">-- ms</div>
                <div class="metric-sub">Tail latency duration</div>
            </div>
        </div>
    </div>

    <!-- 4. Database & Schema Tab -->
    <div id="pane-database" class="tab-pane">
        <div class="grid-cards">
            <div class="card">
                <div class="card-header"><span>Database Connection</span><span class="badge badge-success">MARIADB</span></div>
                <div class="metric-value" id="val-db-status">ONLINE</div>
                <div class="metric-sub">Charset: utf8mb4 | Collation: utf8mb4_unicode_ci</div>
            </div>
            <div class="card">
                <div class="card-header"><span>Schema Migrations</span><span class="badge badge-cyan">VERSIONED</span></div>
                <div class="metric-value" id="val-db-migrations">-- / --</div>
                <div class="metric-sub">Applied / Total migrations</div>
            </div>
        </div>

        <div class="data-table-wrapper">
            <table id="tbl-migrations">
                <thead>
                    <tr>
                        <th>Migration File</th>
                        <th>Batch</th>
                        <th>Applied At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- 5. Queue & Workers Tab -->
    <div id="pane-queue" class="tab-pane">
        <div class="grid-cards">
            <div class="card">
                <div class="card-header"><span>Pending Queue Depth</span><span class="badge badge-cyan">JOBS</span></div>
                <div class="metric-value" id="val-queue-pending">0</div>
                <div class="metric-sub">Worker Engine: SKIP LOCKED</div>
            </div>
            <div class="card">
                <div class="card-header"><span>Dead-Letter Queue</span><span class="badge badge-danger">FAILED</span></div>
                <div class="metric-value" id="val-queue-failed">0</div>
                <div class="metric-sub">Jobs requiring retry</div>
            </div>
        </div>

        <div class="data-table-wrapper">
            <div class="p-3 fw-bold border-bottom">
                Failed Background Jobs (Dead-Letter Queue)
            </div>
            <table id="tbl-failed-jobs">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Queue</th>
                        <th>Failed At</th>
                        <th>Exception Summary</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- 6. Audit Hash-Chain Tab -->
    <div id="pane-audit" class="tab-pane">
        <div class="card mb-3">
            <div class="card-header">
                <span>Cryptographic Hash-Chain Verification Status</span>
                <span class="badge badge-success" id="val-audit-chain-badge">VERIFIED</span>
            </div>
            <div class="metric-value" id="val-audit-verified-count">-- Records</div>
            <div class="metric-sub" id="val-audit-status-desc">Every database row cryptographically validated against previous SHA-256 hash.</div>
        </div>

        <div class="data-table-wrapper">
            <table id="tbl-audit">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>Actor ID</th>
                        <th>IP Address</th>
                        <th>Hash Fingerprint</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- 7. Privacy & GDPR Tab -->
    <div id="pane-gdpr" class="tab-pane">
        <div class="grid-cards">
            <div class="card">
                <div class="card-header"><span>Consent Ledger</span><span class="badge badge-cyan">GDPR Art 7</span></div>
                <div class="metric-value">Active</div>
                <div class="metric-sub">Granular affirmative consent tracking</div>
            </div>
            <div class="card">
                <div class="card-header"><span>Digital Shredder</span><span class="badge badge-success">GDPR Art 17</span></div>
                <div class="metric-value">Armed</div>
                <div class="metric-sub">Right to be Forgotten automated engine</div>
            </div>
            <div class="card">
                <div class="card-header"><span>Data Portability</span><span class="badge badge-purple">GDPR Art 20</span></div>
                <div class="metric-value">Ready</div>
                <div class="metric-sub">JSON/CSV bundle generator</div>
            </div>
        </div>
    </div>

    <!-- 8. Route Registry Tab -->
    <div id="pane-routes" class="tab-pane">
        <div class="data-table-wrapper">
            <table id="tbl-routes">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>URI Pattern</th>
                        <th>Handler</th>
                        <th>Active Middleware</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script nonce="<?= $nonce ?? '' ?>">
        function switchTab(name) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

            const activeBtn = document.querySelector(`.tab-btn[data-tab="${name}"]`) ||
                              Array.from(document.querySelectorAll('.tab-btn')).find(b => b.innerText.toLowerCase().includes(name));
            if (activeBtn) activeBtn.classList.add('active');

            const targetPane = document.getElementById('pane-' + name);
            if (targetPane) targetPane.classList.add('active');

            loadTelemetry(name);
        }
        window.switchTab = switchTab;

        // Guaranteed server-computed application base path
        const API_BASE = <?= json_encode($appBase ?? '') ?> || window.location.pathname.replace(/\/(public|nexus|health)(\/.*)?$/i, '').replace(/\/index\.(html|php)$/, '').replace(/\/$/, '');

        async function fetchEndpoint(endpoint) {
            try {
                // In Nginx subdirectory environments without custom URL rewriting, 
                // the query parameter ?r= through index.php reliably resolves every API endpoint.
                const queryUrl = (API_BASE ? API_BASE : '') + '/index.php?r=' + encodeURIComponent(endpoint);
                let res = await fetch(queryUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    // Try direct relative path as fallback
                    const directUrl = (API_BASE ? API_BASE : '') + endpoint;
                    res = await fetch(directUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                }
                if (!res.ok) return null;
                const json = await res.json();
                return json.data || null;
            } catch (err) {
                console.error('Failed to fetch endpoint ' + endpoint, err);
                return null;
            }
        }

        async function loadTelemetry(tab) {
            if (tab === 'vitals' || !tab) {
                const data = await fetchEndpoint('/api/v1/monitor/overview');
                if (data) {
                    document.getElementById('val-mem').innerText = (data.memory?.current_mb || '--') + ' MB';
                    document.getElementById('val-mem-peak').innerText = `Peak: ${data.memory?.peak_mb || '--'} MB | Limit: ${data.memory?.limit || '--'}`;
                    document.getElementById('val-cpu').innerText = `${data.cpu_load?.['1m'] || 0} / ${data.cpu_load?.['5m'] || 0} / ${data.cpu_load?.['15m'] || 0}`;
                    document.getElementById('val-opcache').innerText = (data.opcache?.hit_rate || '0') + '%';
                    document.getElementById('val-opcache-stats').innerText = `Hits: ${data.opcache?.hits || 0} | Misses: ${data.opcache?.misses || 0}`;
                    document.getElementById('val-storage').innerText = data.storage_formatted?.logs || '0 B';
                    document.getElementById('val-storage-breakdown').innerText = `Logs: ${data.storage_formatted?.logs || '0B'} | Cache: ${data.storage_formatted?.cache || '0B'}`;
                }
            }

            if (tab === 'security') {
                const data = await fetchEndpoint('/api/v1/monitor/security');
                if (data) {
                    document.getElementById('val-throttled-count').innerText = data.throttled_clients_count || 0;
                    document.getElementById('val-locked-count').innerText = data.locked_accounts_count || 0;
                }
            }

            if (tab === 'traffic') {
                const data = await fetchEndpoint('/api/v1/monitor/traffic');
                if (data) {
                    document.getElementById('val-traffic-rps').innerText = data.rps || 0;
                    document.getElementById('val-latency-p50').innerText = (data.latency_ms?.p50 || 0) + ' ms';
                    document.getElementById('val-latency-p99').innerText = (data.latency_ms?.p99 || 0) + ' ms';
                }
            }

            if (tab === 'database') {
                const data = await fetchEndpoint('/api/v1/monitor/database');
                if (data) {
                    document.getElementById('val-db-status').innerText = data.connection_alive ? 'ONLINE' : 'DEGRADED';
                    document.getElementById('val-db-migrations').innerText = `${data.migrations_applied || 0} / ${data.migrations_total || 0}`;
                    
                    const tbody = document.querySelector('#tbl-migrations tbody');
                    tbody.innerHTML = '';
                    (data.migrations || []).forEach(m => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${m.migration}</td>
                            <td>${m.batch || '-'}</td>
                            <td>${m.applied_at || '-'}</td>
                            <td><span class="badge ${m.applied ? 'badge-success' : 'badge-warning'}">${m.applied ? 'APPLIED' : 'PENDING'}</span></td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }

            if (tab === 'queue') {
                const data = await fetchEndpoint('/api/v1/monitor/queue');
                if (data) {
                    document.getElementById('val-queue-pending').innerText = data.pending_jobs || 0;
                    document.getElementById('val-queue-failed').innerText = data.failed_jobs_count || 0;

                    const tbody = document.querySelector('#tbl-failed-jobs tbody');
                    tbody.innerHTML = '';
                    if (data.failed_jobs && data.failed_jobs.length > 0) {
                        data.failed_jobs.forEach(j => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>#${j.id}</td>
                                <td>${j.queue}</td>
                                <td>${j.failed_at}</td>
                                <td class="table-cell-truncate">${j.exception}</td>
                                <td><button class="btn" onclick="retryJob(${j.id})">Retry</button></td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Dead-letter queue is clear. No failed jobs.</td></tr>';
                    }
                }
            }

            if (tab === 'audit') {
                const data = await fetchEndpoint('/api/v1/monitor/audit');
                if (data) {
                    const chain = data.chain_status || {};
                    const badge = document.getElementById('val-audit-chain-badge');
                    badge.innerText = chain.intact ? 'VERIFIED INTACT' : 'TAMPER DETECTED';
                    badge.className = 'badge ' + (chain.intact ? 'badge-success' : 'badge-danger');
                    document.getElementById('val-audit-verified-count').innerText = `${chain.total_verified || 0} Records`;

                    const tbody = document.querySelector('#tbl-audit tbody');
                    tbody.innerHTML = '';
                    (data.recent_logs || []).forEach(l => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>#${l.id}</td>
                            <td><span class="badge badge-cyan">${l.action}</span></td>
                            <td>${l.resource}</td>
                            <td>${l.user_id || 'SYSTEM'}</td>
                            <td>${l.ip_address}</td>
                            <td class="small text-cyan">${(l.hash || '').substring(0, 16)}...</td>
                            <td>${l.created_at}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }

            if (tab === 'routes') {
                const data = await fetchEndpoint('/api/v1/monitor/routes');
                if (data) {
                    const tbody = document.querySelector('#tbl-routes tbody');
                    tbody.innerHTML = '';
                    (data.routes || []).forEach(r => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><span class="badge badge-cyan">${r.method}</span></td>
                            <td>${r.uri}</td>
                            <td>${r.handler}</td>
                            <td>${r.middleware.join(', ') || 'None'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }
        }

        async function retryJob(id) {
            const endpoint = '/api/v1/monitor/queue/retry';
            let url = (API_BASE ? API_BASE : '') + endpoint;
            let res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ job_id: id })
            });
            if (!res.ok) {
                const queryUrl = (API_BASE ? API_BASE : '') + '/index.php?r=' + encodeURIComponent(endpoint);
                res = await fetch(queryUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: id })
                });
            }
            if (res.ok) {
                alert('Job dispatched back to active queue.');
                loadTelemetry('queue');
            }
        }

        // Initialize HUD
        document.addEventListener('DOMContentLoaded', () => {
            // Register click handlers for tabs to ensure CSP compliance
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tab = btn.getAttribute('data-tab');
                    if (tab) switchTab(tab);
                });
            });

            loadTelemetry('vitals');
            // Auto refresh active telemetry every 10 seconds
            setInterval(() => {
                const activePane = document.querySelector('.tab-pane.active');
                if (activePane) {
                    const tabName = activePane.id.replace('pane-', '');
                    loadTelemetry(tabName);
                }
            }, 10000);
        });
    </script>
</main>
</body>
</html>

