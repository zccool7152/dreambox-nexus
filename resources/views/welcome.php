<?php
declare(strict_types=1);

$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$user = $user ?? $currentUser ?? null;
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
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>

    <!-- Universal Light-Theme Navigation Top Bar with Dynamic User Dropdown -->
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <main class="resume-wrapper">
        <!-- Resume Header / Profile Card -->
        <header class="resume-header">
            <div class="resume-header-info">
                <div class="brand-badge mb-2">SYSTEMS ARCHITECT &bull; DEVSECOPS SPECIALIST</div>
                <h1 class="resume-name">Timothy Dunn</h1>
                <div class="resume-title">
                    <span>Systems Architect</span>
                    <span>&bull;</span>
                    <span class="text-primary">DevSecOps Focused</span>
                </div>
                <div class="resume-contact-bar">
                    <span class="resume-contact-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Kansas City, MO
                    </span>
                    <a href="tel:15550192834" class="resume-contact-item" title="Call Timothy Dunn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        (555) 019-2834
                    </a>
                    <a href="mailto:admin@nexus.local" class="resume-contact-item" title="Email Timothy Dunn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        admin@nexus.local
                    </a>
                </div>
            </div>
            <div class="resume-header-actions">
                <img src="<?= htmlspecialchars($appBase) ?>/logos/logo-256.png" alt="Dreambox Nexus Master Logo" class="hero-brand-logo">
                <div class="d-flex flex-column gap-2">
                    <a href="mailto:admin@nexus.local" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span>Contact Timothy</span>
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Print / Save PDF</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Professional Summary -->
        <section class="resume-summary-card">
            <h2 class="resume-section-heading">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Professional Summary
            </h2>
            <p class="resume-summary-text">
                As an experienced Systems Architect, I specialize in building secure, high-performance platforms, automated CI/CD pipelines, and cloud infrastructures, integrating AI tools daily to enhance software delivery. Currently pursuing an M.S. in Cybersecurity and Information Assurance, I bring advanced expertise in DevSecOps practices, container security, cloud infrastructure orchestration, and robust API orchestration to deliver resilient, enterprise-scale solutions.
            </p>
        </section>

        <!-- Main 2-Column Responsive Layout -->
        <div class="resume-layout-grid">
            <!-- Left Column: Skills, Certifications & Education -->
            <aside class="resume-column">
                <!-- Technical Skills -->
                <section class="resume-block">
                    <h2 class="resume-section-heading">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        Technical Skills
                    </h2>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Languages</span>
                        </div>
                        <p class="resume-skill-desc">PHP 8.3, JavaScript (ES6+, TypeScript, Angular, Vue.js), C# (.NET), SQL (MySQL, SQL Server), Bash.</p>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Backend &amp; APIs</span>
                        </div>
                        <p class="resume-skill-desc">RESTful/SOAP API Design, OAuth 2.0/JWT, EDI Transactions, JSON, DOM Scraping, Service Workers, PHPUnit Testing, Mocking/Stubbing, Agile/Scrum.</p>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>AI &amp; Agentic Systems</span>
                        </div>
                        <p class="resume-skill-desc">LLM Orchestration, Prompt Engineering, Agentic Workflows (Gemini), Vector DB / Context Pipelines, API Integrations.</p>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>DevSecOps &amp; Cloud Infrastructure</span>
                        </div>
                        <p class="resume-skill-desc">Infrastructure as Code (IaC), Docker, Container Security Scanning (Trivy/Clair), CI/CD (GitHub Actions, GitLab CI), GCP, Linux (Ubuntu, Debian, Mint), Python-based Automation, Network Security, Vagrant, SSH.</p>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Networking &amp; Support</span>
                        </div>
                        <p class="resume-skill-desc">Cisco Networking &amp; Automation, Enterprise Networking Solutions, IT Support.</p>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Hardware &amp; Systems</span>
                        </div>
                        <p class="resume-skill-desc">Systems Administration (User/Group Mgmt, System Hardening), Hardware Lifecycle Management (Custom PC Building, Server Configuration, Upgrades), Maintenance of Complex Computer-Based Systems, Electrical Safety (NFPA 70E).</p>
                    </div>
                </section>

                <!-- Certifications & Training -->
                <section class="resume-block">
                    <h2 class="resume-section-heading">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                        Certifications &amp; Training
                    </h2>
                    <div class="resume-cert-list">
                        <div class="resume-cert-item">
                            <div class="resume-cert-title">Google AI Professional</div>
                            <div class="resume-cert-meta">Completed May 2026</div>
                        </div>
                        <div class="resume-cert-item">
                            <div class="resume-cert-title">Google IT Support</div>
                            <div class="resume-cert-meta">Completed September 2026</div>
                        </div>
                        <div class="resume-cert-item">
                            <div class="resume-cert-title">Cisco Certified Support Technician (CCST) - Networking (100-150)</div>
                            <div class="resume-cert-meta">Completed July 2026</div>
                        </div>
                        <div class="resume-cert-item">
                            <div class="resume-cert-title">Cisco Certified Network Associate (CCNA) - Automation (200-301)</div>
                            <div class="resume-cert-meta">Expected Completion: February 2027</div>
                        </div>
                        <div class="resume-cert-item">
                            <div class="resume-cert-title">OSHA 10, NFPA 70E</div>
                            <div class="resume-cert-meta">Completed August 2026</div>
                        </div>
                    </div>
                </section>

                <!-- Education -->
                <section class="resume-block">
                    <h2 class="resume-section-heading">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                        Education
                    </h2>
                    <div class="resume-edu-list">
                        <div class="resume-edu-item">
                            <div class="resume-edu-degree">Bachelor of Science in Information Technology</div>
                            <div class="resume-edu-school">Vatterott College, Fenton, MO</div>
                        </div>
                        <div class="resume-edu-item">
                            <div class="resume-edu-degree">M.S. in Cybersecurity and Information Assurance</div>
                            <div class="resume-edu-school">Western Governors University (In Progress, Expected Completion: March 2027)</div>
                        </div>
                    </div>
                </section>
            </aside>

            <!-- Right Column: Professional Experience & Projects -->
            <section class="resume-column">
                <!-- Professional Experience -->
                <div class="resume-block">
                    <h2 class="resume-section-heading">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        Professional Experience
                    </h2>

                    <!-- Independent 2024-2026 -->
                    <article class="resume-exp-entry">
                        <div class="resume-exp-top">
                            <div class="resume-exp-company">Independent</div>
                            <span class="resume-exp-date">March 2024 &ndash; March 2026</span>
                        </div>
                        <div class="resume-exp-role">Software Developer &amp; Researcher | Remote</div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Expanded AI expertise, mastering prompt engineering and integrating AI tools into software delivery.</li>
                            <li class="resume-bullet-item">Pursued M.S. in Cybersecurity and Information Assurance to reinforce DevSecOps and platform security skills.</li>
                        </ul>
                    </article>

                    <!-- ApplicantPro -->
                    <article class="resume-exp-entry">
                        <div class="resume-exp-top">
                            <div class="resume-exp-company">ApplicantPro</div>
                            <span class="resume-exp-date">Nov 2021 &ndash; March 2024</span>
                        </div>
                        <div class="resume-exp-role">Software Engineer | Remote</div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Developed high-availability ATS connectors with RESTful endpoints, achieving 99.9% uptime across third-party recruiting platform integrations.</li>
                            <li class="resume-bullet-item">Designed a cloud-native skill-testing platform leveraging modern JavaScript frameworks, accelerating system responsiveness by 25%.</li>
                            <li class="resume-bullet-item">Migrated core extension suites to Manifest V3 using Service Workers and background scripts, fortifying cross-browser reliability and performance.</li>
                        </ul>
                    </article>

                    <!-- Independent -->
                    <article class="resume-exp-entry">
                        <div class="resume-exp-top">
                            <div class="resume-exp-company">Independent</div>
                            <span class="resume-exp-date">Remote</span>
                        </div>
                        <div class="resume-exp-role">Software Engineer | Remote</div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Delivered custom web applications using Laravel, Vue.js, and MySQL for SME clients through end-to-end technical leadership and consulting.</li>
                            <li class="resume-bullet-item">Modernized legacy PHP systems to version 8.x and hardened infrastructure against security vulnerabilities during key organizational technology shifts.</li>
                            <li class="resume-bullet-item">Integrated custom software interfaces with precision hardware and industrial laser systems to enable seamless real-time operational control.</li>
                        </ul>
                    </article>

                    <!-- Dreambox Services -->
                    <article class="resume-exp-entry">
                        <div class="resume-exp-top">
                            <div class="resume-exp-company">Dreambox Services</div>
                            <span class="resume-exp-date">Remote</span>
                        </div>
                        <div class="resume-exp-role">Software Engineer | Remote</div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Orchestrated the architectural migration of core systems to modular ASP.NET, establishing enterprise-grade scalability and maintainability.</li>
                            <li class="resume-bullet-item">Established rigorous code review workflows and automated unit testing standards using PHPUnit to elevate software quality across releases.</li>
                            <li class="resume-bullet-item">Optimized backend algorithms and SQL query execution paths, lowering infrastructure overhead and dramatically improving report generation speeds.</li>
                        </ul>
                    </article>
                </div>

                <!-- Projects -->
                <div class="resume-block">
                    <h2 class="resume-section-heading">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        Key Projects
                    </h2>

                    <!-- Aether Project -->
                    <article class="resume-exp-entry">
                        <div class="resume-exp-top">
                            <div class="resume-exp-company">Aether Project</div>
                            <span class="resume-exp-date">Full-Stack Enterprise Architecture</span>
                        </div>
                        <div class="resume-exp-role">Architect &amp; Lead Engineer</div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Architected a full-stack mail administration system using Laravel 11, Vue 3, Inertia.js, and Tailwind CSS v4, implementing inbound Postfix transport pipelines, MIME stream parsing into MariaDB, and outbound SMTP relays with automatic thread tracking.</li>
                            <li class="resume-bullet-item">Developed an integrated enterprise wiki module featuring real-time collaborative documentation, Markdown rendering, and granular access control for internal technical knowledge sharing.</li>
                            <li class="resume-bullet-item">Built a real-time system monitor dashboard tracking server health, daemon process states, memory usage, and service availability with instant alerting notifications.</li>
                            <li class="resume-bullet-item">Created a custom personal finance and budget tracking tool for personal use to monitor resource allocation, expenditures, and automated financial reporting.</li>
                            <li class="resume-bullet-item">Integrated AI-assisted coding workflows and agentic tooling into daily development, accelerating system feature implementation, custom integration building, and codebase refactoring.</li>
                        </ul>
                    </article>
                </div>
            </section>
        </div>

        <!-- Platform Application Gateway Quick Dock -->
        <nav class="resume-nav-dock">
            <div>
                <span class="brand-badge">DREAMBOX NEXUS PLATFORM GATEWAY</span>
                <div class="small text-muted mt-1">Direct access to security, telemetry, and governance subsystems:</div>
            </div>
            <div class="resume-nav-links">
                <a href="<?= $appBase ?>/admin/" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>IAM Console</span>
                </a>
                <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    <span>SecOps Cockpit</span>
                </a>
                <a href="<?= $appBase ?>/compliance/" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Compliance</span>
                </a>
                <a href="<?= $appBase ?>/ui/" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    <span>Nexus UI</span>
                </a>
                <a href="<?= $appBase ?>/docs/openapi.yaml?format=html" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span>OpenAPI</span>
                </a>
            </div>
        </nav>
    </main>

    <footer>
        Timothy Dunn &bull; Systems Architect &amp; DevSecOps Focused &bull; Powered by Dreambox Nexus Core v0.1 Alpha
    </footer>
</body>
</html>
