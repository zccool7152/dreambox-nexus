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
$cspNonce = (string)($nonce ?? (\Nexus\Security\Headers::getNonce() ?: ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timothy Dunn // Systems Architect | DevSecOps Focused // Security-First Application Gateway</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/brand/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/brand/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css?v=<?= htmlspecialchars($cssVersion) ?>">
</head>
<body>

    <!-- Universal Light-Theme Navigation Top Bar with Dynamic User Dropdown -->
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <main class="resume-wrapper">
        <article class="rx-sheet">

            <!-- 1. Executive Header Banner: Brand Logo, Identity, Contact Strip & Actions -->
            <header class="rx-header">
                <div class="rx-header-top">
                    <div class="rx-identity-block">
                        <div class="rx-logo-frame">
                            <img src="<?= htmlspecialchars($appBase) ?>/brand/logo.png" alt="Dreambox Nexus Master Logo" class="hero-brand-logo">
                        </div>
                        <div class="rx-identity-text">
                            <div class="rx-eyebrow-pill">
                                <span class="rx-status-dot" aria-hidden="true"></span>
                                <span>Principal Architecture &amp; Security Profile</span>
                            </div>
                            <h1 class="rx-name">Timothy Dunn</h1>
                            <p class="rx-role-line">
                                <span class="rx-role-primary">Systems Architect</span>
                                <span class="rx-sep" aria-hidden="true">|</span>
                                <span class="rx-role-secondary">DevSecOps Focused</span>
                            </p>
                        </div>
                    </div>

                    <div class="rx-header-actions">
                        <button type="button" class="rx-btn rx-btn-primary" data-open="contact">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span>Contact Me</span>
                        </button>
                        <button type="button" class="rx-btn rx-btn-ghost" data-print>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            <span>Print / PDF</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- 2. Full-Width Professional Summary & Key Impact Strip -->
            <section id="summary" class="rx-summary-band" aria-labelledby="heading-summary">
                <div class="rx-summary-inner">
                    <h2 id="heading-summary" class="rx-section-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        <span>Professional Summary</span>
                    </h2>
                    <p class="rx-summary-copy">
                        As an experienced Systems Architect, I specialize in building secure, high-performance platforms, automated CI/CD pipelines, and cloud infrastructures, integrating AI tools daily to enhance software delivery. Currently pursuing an M.S. in Cybersecurity and Information Assurance, I bring advanced expertise in DevSecOps practices, container security, cloud infrastructure orchestration, and robust API orchestration to deliver resilient, enterprise-scale solutions.
                    </p>
                </div>
            </section>

            <!-- 3. Asymmetric Two-Column Resume Body (38% Sidebar / 62% Main Track) -->
            <div class="rx-body-grid">

                <!-- Left Sidebar: Technical Skills, Certifications & Training, Education -->
                <aside class="rx-sidebar" aria-label="Technical Skills, Certifications and Education">

                    <!-- Technical Skills -->
                    <section id="skills" class="rx-side-section" aria-labelledby="heading-skills">
                        <h2 id="heading-skills" class="rx-section-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            <span>Technical Skills</span>
                        </h2>

                        <div class="rx-skill-list">
                            <div class="rx-skill-group">
                                <div class="rx-skill-heading">
                                    <span class="rx-dot rx-dot-cyan" aria-hidden="true"></span>
                                    <span>Languages</span>
                                </div>
                                <div class="rx-skill-tags">
                                    <span class="rx-skill-tag">PHP 8.3 / 8.5</span>
                                    <span class="rx-skill-tag">JavaScript (ES6+)</span>
                                    <span class="rx-skill-tag">TypeScript</span>
                                    <span class="rx-skill-tag">Angular</span>
                                    <span class="rx-skill-tag">Vue.js</span>
                                    <span class="rx-skill-tag">C# (.NET)</span>
                                    <span class="rx-skill-tag">SQL (MySQL, SQL Server)</span>
                                    <span class="rx-skill-tag">Bash</span>
                                </div>
                            </div>

                            <div class="rx-skill-group">
                                <div class="rx-skill-heading">
                                    <span class="rx-dot rx-dot-blue" aria-hidden="true"></span>
                                    <span>Backend &amp; APIs</span>
                                </div>
                                <div class="rx-skill-tags">
                                    <span class="rx-skill-tag">RESTful / SOAP API Design</span>
                                    <span class="rx-skill-tag">OAuth 2.0 / JWT</span>
                                    <span class="rx-skill-tag">EDI Transactions</span>
                                    <span class="rx-skill-tag">JSON</span>
                                    <span class="rx-skill-tag">DOM Scraping</span>
                                    <span class="rx-skill-tag">Service Workers</span>
                                    <span class="rx-skill-tag">PHPUnit Testing</span>
                                    <span class="rx-skill-tag">Mocking / Stubbing</span>
                                    <span class="rx-skill-tag">Agile / Scrum</span>
                                </div>
                            </div>

                            <div class="rx-skill-group">
                                <div class="rx-skill-heading">
                                    <span class="rx-dot rx-dot-purple" aria-hidden="true"></span>
                                    <span>AI &amp; Agentic Systems</span>
                                </div>
                                <div class="rx-skill-tags">
                                    <span class="rx-skill-tag">LLM Orchestration</span>
                                    <span class="rx-skill-tag">Prompt Engineering</span>
                                    <span class="rx-skill-tag">Agentic Workflows (Gemini)</span>
                                    <span class="rx-skill-tag">Vector DB / Context Pipelines</span>
                                    <span class="rx-skill-tag">API Integrations</span>
                                </div>
                            </div>

                            <div class="rx-skill-group">
                                <div class="rx-skill-heading">
                                    <span class="rx-dot rx-dot-emerald" aria-hidden="true"></span>
                                    <span>DevSecOps &amp; Cloud Infrastructure</span>
                                </div>
                                <div class="rx-skill-tags">
                                    <span class="rx-skill-tag">Infrastructure as Code (IaC)</span>
                                    <span class="rx-skill-tag">Docker</span>
                                    <span class="rx-skill-tag">Container Security (Trivy/Clair)</span>
                                    <span class="rx-skill-tag">CI/CD (GitHub Actions, GitLab CI)</span>
                                    <span class="rx-skill-tag">GCP</span>
                                    <span class="rx-skill-tag">Linux (Ubuntu, Debian, Mint)</span>
                                    <span class="rx-skill-tag">Python Automation</span>
                                    <span class="rx-skill-tag">Network Security</span>
                                    <span class="rx-skill-tag">Vagrant</span>
                                    <span class="rx-skill-tag">SSH</span>
                                </div>
                            </div>

                            <div class="rx-skill-group">
                                <div class="rx-skill-heading">
                                    <span class="rx-dot rx-dot-amber" aria-hidden="true"></span>
                                    <span>Networking &amp; Support</span>
                                </div>
                                <div class="rx-skill-tags">
                                    <span class="rx-skill-tag">Cisco Networking &amp; Automation</span>
                                    <span class="rx-skill-tag">Enterprise Networking Solutions</span>
                                    <span class="rx-skill-tag">IT Support</span>
                                </div>
                            </div>

                            <div class="rx-skill-group">
                                <div class="rx-skill-heading">
                                    <span class="rx-dot rx-dot-slate" aria-hidden="true"></span>
                                    <span>Hardware &amp; Systems</span>
                                </div>
                                <div class="rx-skill-tags">
                                    <span class="rx-skill-tag">Systems Administration (User/Group Mgmt, System Hardening)</span>
                                    <span class="rx-skill-tag">Hardware Lifecycle Management</span>
                                    <span class="rx-skill-tag">Custom PC Building &amp; Server Configuration</span>
                                    <span class="rx-skill-tag">Complex Computer-Based Systems</span>
                                    <span class="rx-skill-tag">Electrical Safety (NFPA 70E)</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Certifications & Training -->
                    <section id="certs" class="rx-side-section" aria-labelledby="heading-certs">
                        <h2 id="heading-certs" class="rx-section-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                            <span>Certifications, Training</span>
                        </h2>

                        <ul class="rx-cert-list">
                            <li class="rx-cert-item">
                                <div class="rx-cert-name">Google AI Professional</div>
                                <div class="rx-cert-meta">
                                    <span class="rx-cert-issuer">Google</span>
                                    <span class="badge badge-success">Completed May 2026</span>
                                </div>
                            </li>
                            <li class="rx-cert-item">
                                <div class="rx-cert-name">Google IT Support</div>
                                <div class="rx-cert-meta">
                                    <span class="rx-cert-issuer">Google</span>
                                    <span class="badge badge-success">Completed Sept 2026</span>
                                </div>
                            </li>
                            <li class="rx-cert-item">
                                <div class="rx-cert-name">Cisco Certified Support Technician (CCST) &ndash; Networking (100-150)</div>
                                <div class="rx-cert-meta">
                                    <span class="rx-cert-issuer">Cisco</span>
                                    <span class="badge badge-success">Completed July 2026</span>
                                </div>
                            </li>
                            <li class="rx-cert-item">
                                <div class="rx-cert-name">Cisco Certified Network Associate (CCNA) &ndash; Automation (200-301)</div>
                                <div class="rx-cert-meta">
                                    <span class="rx-cert-issuer">Cisco</span>
                                    <span class="badge badge-warning">Expected Feb 2027</span>
                                </div>
                            </li>
                            <li class="rx-cert-item">
                                <div class="rx-cert-name">OSHA 10, NFPA 70E</div>
                                <div class="rx-cert-meta">
                                    <span class="rx-cert-issuer">Safety Training</span>
                                    <span class="badge badge-success">Completed Aug 2026</span>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <!-- Education -->
                    <section id="education" class="rx-side-section" aria-labelledby="heading-education">
                        <h2 id="heading-education" class="rx-section-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            <span>Education</span>
                        </h2>

                        <div class="rx-edu-list">
                            <div class="rx-edu-item">
                                <div class="rx-edu-head">
                                    <div class="rx-edu-degree">M.S. in Cybersecurity and Information Assurance</div>
                                    <span class="badge badge-warning">In Progress</span>
                                </div>
                                <div class="rx-edu-school">Western Governors University</div>
                                <div class="rx-edu-note">Expected Completion: March 2027</div>
                            </div>

                            <div class="rx-edu-item">
                                <div class="rx-edu-head">
                                    <div class="rx-edu-degree">Bachelor of Science in Information Technology</div>
                                    <span class="badge badge-success">Completed</span>
                                </div>
                                <div class="rx-edu-school">Vatterott College, Fenton, MO</div>
                            </div>
                        </div>
                    </section>

                </aside>

                <!-- Right Main Column: Professional Experience First, Projects Underneath -->
                <div class="rx-main">

                    <!-- Professional Experience (All 4 Roles Visible in Clean Timeline) -->
                    <section id="experience" class="rx-main-section" aria-labelledby="heading-experience">
                        <h2 id="heading-experience" class="rx-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <span>Professional Experience</span>
                        </h2>

                        <div class="rx-timeline">

                            <!-- Role 1 -->
                            <article class="rx-role">
                                <div class="rx-role-marker" aria-hidden="true"></div>
                                <div class="rx-role-header">
                                    <div>
                                        <h3 class="rx-company">Independent</h3>
                                        <div class="rx-role-title">Software Developer &amp; Researcher <span class="rx-role-loc">&bull; Remote</span></div>
                                    </div>
                                    <span class="rx-date-badge">March 2024 &ndash; March 2026</span>
                                </div>
                                <ul class="rx-bullets">
                                    <li>Expanded AI expertise, mastering prompt engineering and integrating AI tools into software delivery.</li>
                                    <li>Pursued M.S. in Cybersecurity and Information Assurance to reinforce DevSecOps and platform security skills.</li>
                                </ul>
                            </article>

                            <!-- Role 2 -->
                            <article class="rx-role">
                                <div class="rx-role-marker" aria-hidden="true"></div>
                                <div class="rx-role-header">
                                    <div>
                                        <h3 class="rx-company">ApplicantPro</h3>
                                        <div class="rx-role-title">Software Engineer <span class="rx-role-loc">&bull; Remote</span></div>
                                    </div>
                                    <span class="rx-date-badge">Nov 2021 &ndash; March 2024</span>
                                </div>
                                <ul class="rx-bullets">
                                    <li>Developed high-availability ATS connectors with RESTful endpoints, achieving <strong>99.9% uptime</strong> across third-party recruiting platform integrations.</li>
                                    <li>Designed a cloud-native skill-testing platform leveraging modern JavaScript frameworks, accelerating system responsiveness by <strong>25%</strong>.</li>
                                    <li>Migrated core extension suites to Manifest V3 using Service Workers and background scripts, fortifying cross-browser reliability and performance.</li>
                                </ul>
                            </article>

                            <!-- Role 3 -->
                            <article class="rx-role">
                                <div class="rx-role-marker" aria-hidden="true"></div>
                                <div class="rx-role-header">
                                    <div>
                                        <h3 class="rx-company">Independent</h3>
                                        <div class="rx-role-title">Software Engineer <span class="rx-role-loc">&bull; Remote</span></div>
                                    </div>
                                    <span class="rx-date-badge rx-date-muted">Consulting &amp; Delivery</span>
                                </div>
                                <ul class="rx-bullets">
                                    <li>Delivered custom web applications using Laravel, Vue.js, and MySQL for SME clients through end-to-end technical leadership and consulting.</li>
                                    <li>Modernized legacy PHP systems to version 8.x and hardened infrastructure against security vulnerabilities during key organizational technology shifts.</li>
                                    <li>Integrated custom software interfaces with precision hardware and industrial laser systems to enable seamless real-time operational control.</li>
                                </ul>
                            </article>

                            <!-- Role 4 -->
                            <article class="rx-role">
                                <div class="rx-role-marker" aria-hidden="true"></div>
                                <div class="rx-role-header">
                                    <div>
                                        <h3 class="rx-company">Dreambox Services</h3>
                                        <div class="rx-role-title">Software Engineer <span class="rx-role-loc">&bull; Remote</span></div>
                                    </div>
                                    <span class="rx-date-badge rx-date-muted">Enterprise Systems</span>
                                </div>
                                <ul class="rx-bullets">
                                    <li>Orchestrated the architectural migration of core systems to modular ASP.NET, establishing enterprise-grade scalability and maintainability.</li>
                                    <li>Established rigorous code review workflows and automated unit testing standards using PHPUnit to elevate software quality across releases.</li>
                                    <li>Optimized backend algorithms and SQL query execution paths, lowering infrastructure overhead and dramatically improving report generation speeds.</li>
                                </ul>
                            </article>

                        </div>
                    </section>

                    <!-- Projects -->
                    <section id="projects" class="rx-main-section" aria-labelledby="heading-projects">
                        <h2 id="heading-projects" class="rx-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 22V12M3 7l9 5 9-5"/></svg>
                            <span>Projects</span>
                        </h2>

                        <!-- Project 1: Aether Project (Matches Real Resume Order & Bullets) -->
                        <article class="rx-project-card">
                            <div class="rx-project-top">
                                <div>
                                    <h3 class="rx-project-name">Aether Project</h3>
                                    <div class="rx-project-sub">Full-Stack Mail Administration &amp; Enterprise Tooling Suite</div>
                                </div>
                                <div class="rx-project-stack">
                                    <span class="badge badge-primary">Laravel 11</span>
                                    <span class="badge badge-info">Vue 3</span>
                                    <span class="badge badge-purple">Inertia.js</span>
                                    <span class="badge badge-success">Tailwind CSS v4</span>
                                    <span class="badge badge-secondary">MariaDB &bull; Postfix</span>
                                </div>
                            </div>
                            <ul class="rx-bullets">
                                <li><strong>Mail Administration System:</strong> Architected a full-stack mail administration system using Laravel 11, Vue 3, Inertia.js, and Tailwind CSS v4, implementing inbound Postfix transport pipelines, MIME stream parsing into MariaDB, and outbound SMTP relays with automatic thread tracking.</li>
                                <li><strong>Enterprise Wiki Module:</strong> Developed an integrated enterprise wiki module featuring real-time collaborative documentation, Markdown rendering, and granular access control for internal technical knowledge sharing.</li>
                                <li><strong>System Monitor Dashboard:</strong> Built a real-time system monitor dashboard tracking server health, daemon process states, memory usage, and service availability with instant alerting notifications.</li>
                                <li><strong>Personal Finance &amp; Budget Tracker:</strong> Created a custom personal finance and budget tracking tool for personal use to monitor resource allocation, expenditures, and automated financial reporting.</li>
                                <li><strong>AI-Assisted Engineering Workflows:</strong> Integrated AI-assisted coding workflows and agentic tooling into daily development, accelerating system feature implementation, custom integration building, and codebase refactoring.</li>
                            </ul>
                        </article>

                        <!-- Project 2: Dreambox Nexus (Live Platform Highlight) -->
                        <article class="rx-project-card rx-project-flagship">
                            <div class="rx-project-top">
                                <div>
                                    <h3 class="rx-project-name">Dreambox Nexus</h3>
                                    <div class="rx-project-sub">Security-First Application Gateway</div>
                                </div>
                                <div class="rx-project-stack">
                                    <span class="badge badge-primary">Live Platform</span>
                                    <span class="badge badge-info">Zero-Dep PHP 8.5</span>
                                    <span class="badge badge-success">MongoDB</span>
                                </div>
                            </div>
                            <p class="rx-project-desc">
                                Engineered a zero-dependency enterprise application gateway prioritizing cryptographic auditability, multi-framework compliance validation, and hardened identity management.
                            </p>
                            <div class="rx-nexus-highlights">
                                <div class="rx-highlight-row">
                                    <span class="badge badge-success">Cryptographic Audit</span>
                                    <span>SHA-256 Merkle hash chain with immutable forward ledger and tamper detection.</span>
                                </div>
                                <div class="rx-highlight-row">
                                    <span class="badge badge-info">Compliance Matrix</span>
                                    <span>Live runtime verification across PCI-DSS v4.0, HIPAA &sect;164.312, NIST CSF 2.0, EU GDPR, and OWASP Top 10.</span>
                                </div>
                                <div class="rx-highlight-row">
                                    <span class="badge badge-purple">Enterprise IAM</span>
                                    <span>Argon2id password hashing, RFC 6238 TOTP 2FA, OAuth 2.0 (Google/GitHub), and dynamic RBAC.</span>
                                </div>
                            </div>
                            <div class="rx-project-actions">
                                <a href="<?= htmlspecialchars($appBase) ?>/compliance/" class="btn btn-primary btn-sm">Compliance Matrix</a>
                                <a href="<?= htmlspecialchars($appBase) ?>/nexus/monitor/" class="btn btn-secondary btn-sm">Architecture Monitor</a>
                                <a href="<?= htmlspecialchars($appBase) ?>/ui/" class="btn btn-secondary btn-sm">Nexus UI Framework</a>
                            </div>
                        </article>
                    </section>

                </div>
            </div>

        </article>

        <footer class="rx-footer">
            &copy; <?= date('Y') ?> Timothy Dunn &bull; Systems Architect &bull; Built on Dreambox Nexus &amp; Nexus UI Framework.
        </footer>
    </main>

    <!-- Contact Modal -->
    <div class="bd" id="contact" role="dialog" aria-modal="true" aria-label="Contact Timothy Dunn">
        <div class="modal-content">
            <div class="modal-header">
                <b>Contact Timothy Dunn</b>
                <button type="button" class="btn-close" data-close aria-label="Close"></button>
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

    <script nonce="<?= htmlspecialchars($cspNonce) ?>">
    (function () {
        function openModal(id) {
            var el = document.getElementById(id);
            if (el) { el.classList.add('open'); }
        }
        function closeAllModals() {
            document.querySelectorAll('.bd.open').forEach(function (x) {
                x.classList.remove('open');
            });
        }
        document.querySelectorAll('[data-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(btn.getAttribute('data-open'));
            });
        });
        document.querySelectorAll('[data-close]').forEach(function (btn) {
            btn.addEventListener('click', closeAllModals);
        });
        document.querySelectorAll('[data-print]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.print();
            });
        });
        var contactModal = document.getElementById('contact');
        if (contactModal) {
            contactModal.addEventListener('click', function (e) {
                if (e.target === contactModal) { closeAllModals(); }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeAllModals(); }
        });
    })();
    </script>
</body>
</html>
