<?php
declare(strict_types=1);

$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$user = $user ?? $currentUser ?? null;
$cssVersion = file_exists(__DIR__ . '/../../css/theme.css') ? (string)filemtime(__DIR__ . '/../../css/theme.css') : (string)time();
if (class_exists(\Nexus\Core\Env::class) && !\Nexus\Core\Env::isLoaded() && file_exists(dirname(__DIR__, 2) . '/.env')) {
    \Nexus\Core\Env::load(dirname(__DIR__, 2) . '/.env');
}
$contactEmail = (string)(\Nexus\Core\Env::get('CONTACT_EMAIL') ?: getenv('CONTACT_EMAIL') ?: 'contact@dreamboxnexus.local');
$contactPhone = (string)(\Nexus\Core\Env::get('CONTACT_PHONE') ?: getenv('CONTACT_PHONE') ?: '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timothy Dunn // Systems Architect &amp; DevSecOps Specialist // Security-First Application Gateway</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/logos/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/logos/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/logos/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/logos/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css?v=<?= htmlspecialchars($cssVersion) ?>">
</head>
<body>

    <!-- Universal Light-Theme Navigation Top Bar with Dynamic User Dropdown -->
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <main class="resume-wrapper">
        <!-- 1. Single Top Row: Master Logo, Name, Badges, Contact Info & Action Buttons -->
        <header class="td-top-row">
            <div class="td-top-brand">
                <img src="<?= htmlspecialchars($appBase) ?>/logos/logo.png" alt="Dreambox Nexus Master Logo" class="hero-brand-logo">
                <div class="td-top-identity">
                    <h1 class="td-top-name">Timothy Dunn</h1>
                    <div class="td-top-tags">
                        <span class="badge badge-primary">Systems Architect</span>
                        <span class="badge badge-success">DevSecOps focused</span>
                        <span class="badge badge-info">Kansas City, MO</span>
                    </div>
                </div>
            </div>

            <div class="td-top-contact">
                <?php if (!empty($contactPhone)): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>" class="td-contact-item" title="Call">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span><?= htmlspecialchars($contactPhone) ?></span>
                </a>
                <?php endif; ?>
                <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="td-contact-item" title="Email">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <span><?= htmlspecialchars($contactEmail) ?></span>
                </a>
            </div>

            <div class="td-top-actions">
                <button type="button" class="btn btn-primary btn-sm" data-open="contact">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <span>Contact me</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    <span>Print PDF</span>
                </button>
            </div>
        </header>

        <!-- 2. Full-Width Professional Summary Directly Under Top Row -->
        <section id="summary" class="td-summary-section">
            <div class="callout">
                <h2><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Professional summary</h2>
                <p>An experienced Systems Architect specializing in secure, high-performance platforms, automated CI/CD pipelines, and cloud infrastructure. Pursuing an M.S. in Cybersecurity and Information Assurance, with advanced expertise in DevSecOps practices, container security, cloud orchestration, and API orchestration for resilient, enterprise-scale solutions.</p>
            </div>
        </section>

        <!-- 3. Two-Column Split Layout Underneath -->
        <div class="td-two-col">
            <!-- Left Column: Key Metrics, Technical Skills, Certifications & Education -->
            <div class="td-col">
                <!-- Key Metrics -->
                <section id="metrics">
                    <h2><svg viewBox="0 0 24 24"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>Key metrics</h2>
                    <div class="row">
                        <div class="metric-card c6">
                            <div class="metric-header"><span>ATS uptime</span><span class="badge badge-primary">ApplicantPro</span></div>
                            <div class="metric-value">99.9%</div>
                            <div class="metric-footer">Across recruiting integrations</div>
                        </div>
                        <div class="metric-card c6">
                            <div class="metric-header"><span>Responsiveness</span><span class="badge badge-success">Gain</span></div>
                            <div class="metric-value">+25%</div>
                            <div class="metric-footer"><span class="up">&blacktriangle;</span> Skill-testing platform</div>
                        </div>
                        <div class="metric-card c6">
                            <div class="metric-header"><span>Core Architecture</span><span class="badge badge-info">Zero-Dep</span></div>
                            <div class="metric-value">100%</div>
                            <div class="metric-footer">Pure PHP 8.5 &amp; MongoDB</div>
                        </div>
                        <div class="metric-card c6">
                            <div class="metric-header"><span>M.S. Cyber</span><span class="badge badge-warning">WGU</span></div>
                            <div class="metric-value">2027</div>
                            <div class="metric-footer">Expected March 2027</div>
                        </div>
                    </div>
                </section>

                <!-- Technical Skills -->
                <section id="skills">
                    <h2><svg viewBox="0 0 24 24"><path d="M8 6l-6 6 6 6M16 6l6 6-6 6"/></svg>Technical skills</h2>
                    <div class="row">
                        <div class="card c6">
                            <div class="card-header">Languages<span class="badge badge-primary">&#9679;</span></div>
                            <div class="card-body small">PHP 8.3 &bull; 8.5, JavaScript (ES6+, TypeScript, Angular, Vue.js), C# (.NET), SQL (MySQL, SQL Server), Bash</div>
                        </div>
                        <div class="card c6">
                            <div class="card-header">Backend &amp; APIs<span class="badge badge-info">&#9679;</span></div>
                            <div class="card-body small">RESTful/SOAP API design, OAuth 2.0/JWT, EDI transactions, JSON, DOM scraping, Service Workers, PHPUnit, Agile/Scrum</div>
                        </div>
                        <div class="card c6">
                            <div class="card-header">AI &amp; Agentic Systems<span class="badge badge-purple">&#9679;</span></div>
                            <div class="card-body small">LLM orchestration, prompt engineering, agentic workflows (Gemini), vector DB / context pipelines, API integrations</div>
                        </div>
                        <div class="card c6">
                            <div class="card-header">DevSecOps &amp; Cloud<span class="badge badge-success">&#9679;</span></div>
                            <div class="card-body small">Infrastructure as Code, Docker, container scanning (Trivy/Clair), CI/CD (GitHub Actions, GitLab CI), GCP, Linux, Python, SSH</div>
                        </div>
                        <div class="card c6">
                            <div class="card-header">Networking &amp; Support<span class="badge badge-warning">&#9679;</span></div>
                            <div class="card-body small">Cisco networking and automation, enterprise networking solutions, routing &amp; switching, IT support operations</div>
                        </div>
                        <div class="card c6">
                            <div class="card-header">Hardware &amp; Systems<span class="badge badge-secondary">&#9679;</span></div>
                            <div class="card-body small">Systems administration, OS hardening, hardware lifecycle management, server configuration, electrical safety (NFPA 70E)</div>
                        </div>
                    </div>
                </section>

                <!-- Certifications & Training -->
                <section id="certs">
                    <h2><svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="6"/><path d="M8 14l-2 8 6-3 6 3-2-8"/></svg>Certifications &amp; training</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Credential</th>
                                    <th>Issuer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><b>Google AI Professional</b></td>
                                    <td>Google</td>
                                    <td><span class="badge badge-success">Completed May 2026</span></td>
                                </tr>
                                <tr>
                                    <td><b>Google IT Support</b></td>
                                    <td>Google</td>
                                    <td><span class="badge badge-success">Completed Sept 2026</span></td>
                                </tr>
                                <tr>
                                    <td><b>CCST Networking (100-150)</b></td>
                                    <td>Cisco</td>
                                    <td><span class="badge badge-success">Completed July 2026</span></td>
                                </tr>
                                <tr>
                                    <td><b>OSHA 10, NFPA 70E</b></td>
                                    <td>Safety Training</td>
                                    <td><span class="badge badge-success">Completed Aug 2026</span></td>
                                </tr>
                                <tr>
                                    <td><b>CCNA Automation (200-301)</b></td>
                                    <td>Cisco</td>
                                    <td><span class="badge badge-warning">Expected Feb 2027</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Education -->
                <section id="education">
                    <h2><svg viewBox="0 0 24 24"><path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/></svg>Education</h2>
                    <div class="row">
                        <div class="card c6">
                            <div class="card-header">M.S. Cybersecurity &amp; Info Assurance <span class="badge badge-warning">In progress</span></div>
                            <div class="card-body">
                                <div><b>Western Governors University</b></div>
                                <div class="text-muted small mt-6">Expected completion: March 2027</div>
                            </div>
                        </div>
                        <div class="card c6">
                            <div class="card-header">B.S. Information Technology <span class="badge badge-success">Earned</span></div>
                            <div class="card-body">
                                <div><b>Vatterott College</b></div>
                                <div class="text-muted small mt-6">Fenton, MO</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right Column: Featured Projects & Professional Experience -->
            <div class="td-col">
                <!-- Featured Projects -->
                <section id="projects">
                    <h2><svg viewBox="0 0 24 24"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 22V12M3 7l9 5 9-5"/></svg>Featured projects</h2>

                    <!-- Flagship Project 1: Dreambox Nexus -->
                    <div class="card mb-16">
                        <div class="card-header">
                            <div><b>Dreambox Nexus</b> &bull; <span class="text-muted font-normal">Security-First Application Gateway</span></div>
                            <span class="badge badge-primary">Flagship Architecture</span>
                        </div>
                        <div class="card-body">
                            <p>A resilient, zero-dependency PHP 8.5 &amp; MongoDB application gateway engineered from the ground up for strict enterprise isolation, regulatory compliance, and rapid API telemetry.</p>
                            <div class="td-project-specs">
                                <div class="td-spec-item">
                                    <span class="badge badge-success">Cryptographic Audit</span>
                                    <span>SHA-256 Merkle hash chain with immutable forward ledger and row integrity tamper detection.</span>
                                </div>
                                <div class="td-spec-item">
                                    <span class="badge badge-info">Compliance Matrix</span>
                                    <span>Live runtime validation across PCI-DSS v4.0, HIPAA §164.312, NIST CSF 2.0, EU GDPR, and OWASP Top 10.</span>
                                </div>
                                <div class="td-spec-item">
                                    <span class="badge badge-purple">Enterprise IAM</span>
                                    <span>Argon2id password hashing, RFC 6238 TOTP 2FA, OAuth 2.0 (Google/GitHub), and dynamic RBAC.</span>
                                </div>
                            </div>
                            <div class="td-project-links mt-16">
                                <a href="<?= $appBase ?>/compliance/" class="btn btn-outline-primary btn-sm">Compliance Matrix</a>
                                <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-secondary btn-sm">Architecture Monitor</a>
                                <a href="<?= $appBase ?>/ui/" class="btn btn-secondary btn-sm">Nexus UI Framework</a>
                            </div>
                        </div>
                    </div>

                    <!-- Project 2: Aether Project Suite -->
                    <div class="card">
                        <div class="card-header">
                            <div><b>Aether Project</b> &bull; <span class="text-muted font-normal">Mail Admin &amp; Internal Tooling Suite</span></div>
                            <span class="badge badge-info">L11 &bull; V3</span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">A full-stack mail administration system and internal tooling suite built with Laravel 11, Vue 3, Inertia.js, and Tailwind CSS v4.</p>
                            <div class="row mt-16">
                                <div class="card c6">
                                    <div class="card-header">Mail administration <span class="badge badge-primary">Laravel 11</span></div>
                                    <div class="card-body small">Inbound Postfix transport pipelines, MIME stream parsing into MariaDB, and outbound SMTP relays with automatic thread tracking.</div>
                                </div>
                                <div class="card c6">
                                    <div class="card-header">Enterprise wiki <span class="badge badge-success">Collaborative</span></div>
                                    <div class="card-body small">Real-time collaborative documentation with Markdown rendering and granular access control for internal technical knowledge.</div>
                                </div>
                                <div class="card c6">
                                    <div class="card-header">System monitor <span class="badge badge-warning">Alerting</span></div>
                                    <div class="card-body small">A real-time dashboard tracking server health, daemon process states, memory usage, and service availability with instant alerts.</div>
                                </div>
                                <div class="card c6">
                                    <div class="card-header">Finance tracker <span class="badge badge-secondary">Personal</span></div>
                                    <div class="card-body small">A custom budget tool that monitors resource allocation and expenditures and automates financial reporting.</div>
                                </div>
                            </div>
                            <div class="code mt-16">
                                <header><span>AI-assisted workflow</span><button type="button" data-copy="aether">Copy</button></header>
                                <pre id="aether"><span class="k">workflow</span>: agentic tooling in daily development&#10;<span class="k">outcomes</span>: <span class="s">faster feature delivery</span>, <span class="s">custom integrations</span>, <span class="s">codebase refactoring</span></pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Professional Experience -->
                <section id="experience">
                    <h2><svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V4h6v3"/></svg>Professional experience</h2>
                    <div class="card">
                        <div class="card-body">
                            <ul class="nav-tabs" role="tablist">
                                <li><button class="nav-link active" role="tab" data-tab="t0">Independent R&amp;D</button></li>
                                <li><button class="nav-link" role="tab" data-tab="t1">ApplicantPro</button></li>
                                <li><button class="nav-link" role="tab" data-tab="t2">Independent Engineer</button></li>
                                <li><button class="nav-link" role="tab" data-tab="t3">Dreambox Services</button></li>
                            </ul>
                            <div class="pane active" id="t0">
                                <h3>Software Developer &amp; Researcher</h3>
                                <div class="text-muted small">Independent &bull; Remote &bull; March 2024 &ndash; March 2026</div>
                                <ul>
                                    <li>Expanded AI expertise by mastering prompt engineering and integrating AI tools into software delivery.</li>
                                    <li>Pursued an M.S. in Cybersecurity and Information Assurance to reinforce DevSecOps and platform security skills.</li>
                                </ul>
                            </div>
                            <div class="pane" id="t1">
                                <h3>Software Engineer</h3>
                                <div class="text-muted small">ApplicantPro &bull; Remote &bull; Nov 2021 &ndash; March 2024</div>
                                <ul>
                                    <li>Developed high-availability ATS connectors with RESTful endpoints, achieving 99.9% uptime across third-party recruiting platform integrations.</li>
                                    <li>Designed a cloud-native skill-testing platform on modern JavaScript frameworks, accelerating system responsiveness by 25%.</li>
                                    <li>Migrated core extension suites to Manifest V3 using Service Workers and background scripts, strengthening cross-browser reliability and performance.</li>
                                </ul>
                            </div>
                            <div class="pane" id="t2">
                                <h3>Software Engineer</h3>
                                <div class="text-muted small">Independent &bull; Remote</div>
                                <ul>
                                    <li>Delivered custom web applications using Laravel, Vue.js, and MySQL for SME clients through end-to-end technical leadership and consulting.</li>
                                    <li>Modernized legacy PHP systems to version 8.x and hardened infrastructure against security vulnerabilities during key organizational technology shifts.</li>
                                    <li>Integrated custom software interfaces with precision hardware and industrial laser systems for seamless real-time operational control.</li>
                                </ul>
                            </div>
                            <div class="pane" id="t3">
                                <h3>Software Engineer</h3>
                                <div class="text-muted small">Dreambox Services &bull; Remote</div>
                                <ul>
                                    <li>Orchestrated the architectural migration of core systems to modular ASP.NET, establishing enterprise-grade scalability and maintainability.</li>
                                    <li>Established code review workflows and automated unit testing standards with PHPUnit to raise software quality across releases.</li>
                                    <li>Optimized backend algorithms and SQL query execution paths, lowering infrastructure overhead and speeding up report generation.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <footer class="td-footer">
            &copy; <?= date('Y') ?> Timothy Dunn &bull; Systems Architect &bull; Built with Nexus UI components: zero dependencies, custom CSS.
        </footer>
    </main>

    <!-- Contact Modal -->
    <div class="bd" id="contact" role="dialog" aria-modal="true" aria-label="Contact Timothy">
        <div class="modal-content">
            <div class="modal-header">
                <b>Contact Timothy Dunn</b>
                <button type="button" class="btn-close" data-close aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
                <?php if (!empty($contactPhone)): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>"><?= htmlspecialchars($contactPhone) ?></a>
                <?php endif; ?>
                <span class="text-muted">Kansas City, MO</span>
            </div>
            <div class="modal-footer">
                <span class="small text-muted">Press Esc to close</span>
                <button type="button" class="btn btn-secondary btn-sm" data-close>Close</button>
            </div>
        </div>
    </div>

    <script>
    (function(){
        // Interactive Tab Switching
        document.querySelectorAll("[data-tab]").forEach(function(b){
            b.addEventListener("click", function(){
                document.querySelectorAll(".nav-link").forEach(function(x){ x.classList.remove("active"); });
                document.querySelectorAll(".pane").forEach(function(x){ x.classList.remove("active"); });
                b.classList.add("active");
                var target = document.getElementById(b.dataset.tab);
                if (target) { target.classList.add("active"); }
            });
        });

        // Modal Open / Close Handlers
        function openModal(id) {
            var el = document.getElementById(id);
            if (el) { el.classList.add("open"); }
        }
        function closeAllModals() {
            document.querySelectorAll(".bd.open").forEach(function(x){ x.classList.remove("open"); });
        }
        document.querySelectorAll("[data-open]").forEach(function(b){
            b.addEventListener("click", function(){ openModal(b.dataset.open); });
        });
        document.querySelectorAll("[data-close]").forEach(function(b){
            b.addEventListener("click", closeAllModals);
        });
        var contactModal = document.getElementById("contact");
        if (contactModal) {
            contactModal.addEventListener("click", function(e){
                if (e.target === this) { closeAllModals(); }
            });
        }
        document.addEventListener("keydown", function(e){
            if (e.key === "Escape") { closeAllModals(); }
        });

        // Copy Code Snippet
        document.querySelectorAll("[data-copy]").forEach(function(b){
            b.addEventListener("click", function(){
                var target = document.getElementById(b.dataset.copy);
                if (!target) return;
                var text = target.textContent;
                try {
                    navigator.clipboard.writeText(text).then(function(){
                        b.textContent = "Copied!";
                        setTimeout(function(){ b.textContent = "Copy"; }, 2000);
                    });
                } catch(e) {
                    b.textContent = "Select to copy";
                }
            });
        });
    })();
    </script>
</body>
</html>
