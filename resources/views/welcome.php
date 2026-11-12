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
        <!-- Executive Hero / Profile Card -->
        <header class="resume-header">
            <div class="resume-header-info">
                <div class="resume-eyebrow">
                    <span class="pulse-dot"></span>
                    <span>Systems Architect &bull; DevSecOps &bull; Cloud Infrastructure</span>
                </div>
                <h1 class="resume-name">Timothy Dunn</h1>
                <div class="resume-title">
                    <span>Systems Architect</span>
                    <span>&bull;</span>
                    <span class="text-primary">DevSecOps Focused</span>
                </div>
                <div class="resume-contact-bar">
                    <span class="resume-contact-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span>Kansas City, MO</span>
                    </span>
                    <a href="tel:15550192834" class="resume-contact-item" title="Call Timothy Dunn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <span>(555) 019-2834</span>
                    </a>
                    <a href="mailto:admin@nexus.local" class="resume-contact-item" title="Email Timothy Dunn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span>admin@nexus.local</span>
                    </a>
                </div>
            </div>
            <div class="resume-header-actions">
                <img src="<?= htmlspecialchars($appBase) ?>/logos/logo.png" alt="Dreambox Nexus Master Logo" class="hero-brand-logo">
                <div class="resume-header-btn-group">
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

        <!-- Executive Stat & Architecture Highlights -->
        <section class="resume-stat-grid">
            <div class="resume-stat-card">
                <div class="resume-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    <span>Architecture</span>
                </div>
                <div class="resume-stat-val">Zero-Dependency Core</div>
                <div class="resume-stat-desc">Custom PHP 8.3/8.5 framework built from first principles without supply-chain vulnerabilities.</div>
            </div>
            <div class="resume-stat-card">
                <div class="resume-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span>DevSecOps</span>
                </div>
                <div class="resume-stat-val">Continuous Assurance</div>
                <div class="resume-stat-desc">Automated CI/CD pipelines, container scanning, and infrastructure orchestration.</div>
            </div>
            <div class="resume-stat-card">
                <div class="resume-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Governance</span>
                </div>
                <div class="resume-stat-val">5 Regulatory Standards</div>
                <div class="resume-stat-desc">Live runtime compliance for PCI-DSS v4.0, HIPAA §164.312, NIST CSF 2.0, GDPR, &amp; OWASP.</div>
            </div>
            <div class="resume-stat-card">
                <div class="resume-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                    <span>Academics</span>
                </div>
                <div class="resume-stat-val">M.S. Cybersecurity</div>
                <div class="resume-stat-desc">Western Governors University (In Progress) &bull; B.S. IT (Vatterott) &bull; Cisco CCST &amp; CCNA.</div>
            </div>
        </section>

        <!-- Executive Summary Card -->
        <section class="resume-summary-card">
            <div class="resume-eyebrow">01 / EXECUTIVE PROFILE</div>
            <h2 class="resume-section-heading">Professional Summary</h2>
            <p class="resume-summary-text">
                As an experienced Systems Architect, I specialize in building secure, high-performance platforms, automated CI/CD pipelines, and cloud infrastructures, integrating AI tools daily to enhance software delivery. Currently pursuing an M.S. in Cybersecurity and Information Assurance, I bring advanced expertise in DevSecOps practices, container security, cloud infrastructure orchestration, and robust API orchestration to deliver resilient, enterprise-scale solutions.
            </p>
        </section>

        <!-- Main 2-Column Responsive Layout -->
        <div class="resume-layout-grid">
            <!-- Left Column: Core Competencies, Certifications & Education -->
            <aside class="resume-column">
                <!-- Technical Skills & Competencies -->
                <section class="resume-block">
                    <div class="resume-eyebrow">02 / CORE COMPETENCIES</div>
                    <h3 class="resume-block-title">Technical Skills</h3>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Languages &amp; Runtimes</span>
                        </div>
                        <p class="resume-skill-desc">Modern language polyglot with focus on high-throughput server backends and clean frontend architectures.</p>
                        <div class="resume-tag-wrap">
                            <span class="badge-tech badge-tech-pri">PHP 8.3 / 8.5</span>
                            <span class="badge-tech">TypeScript</span>
                            <span class="badge-tech">JavaScript (ES6+)</span>
                            <span class="badge-tech">Vue.js</span>
                            <span class="badge-tech">Angular</span>
                            <span class="badge-tech">C# (.NET)</span>
                            <span class="badge-tech">SQL</span>
                            <span class="badge-tech">Bash</span>
                        </div>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Backend &amp; API Architecture</span>
                        </div>
                        <p class="resume-skill-desc">Enterprise service contracts, token federation, data masking, and automated verification suites.</p>
                        <div class="resume-tag-wrap">
                            <span class="badge-tech badge-tech-pri">RESTful APIs</span>
                            <span class="badge-tech">OAuth 2.0</span>
                            <span class="badge-tech">OpenAPI 3.0</span>
                            <span class="badge-tech">JWT</span>
                            <span class="badge-tech">EDI Transactions</span>
                            <span class="badge-tech">PHPUnit</span>
                            <span class="badge-tech">Service Workers</span>
                            <span class="badge-tech">Agile / Scrum</span>
                        </div>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>DevSecOps &amp; Cloud Infrastructure</span>
                        </div>
                        <p class="resume-skill-desc">Automated release engineering, vulnerability scanning, and infrastructure orchestration.</p>
                        <div class="resume-tag-wrap">
                            <span class="badge-tech badge-tech-pri">DevSecOps</span>
                            <span class="badge-tech">Docker</span>
                            <span class="badge-tech">CI/CD Pipelines</span>
                            <span class="badge-tech">Trivy / Clair</span>
                            <span class="badge-tech">GCP</span>
                            <span class="badge-tech">Linux Hardening</span>
                            <span class="badge-tech">IaC</span>
                            <span class="badge-tech">Vagrant / SSH</span>
                        </div>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>AI &amp; Agentic Systems</span>
                        </div>
                        <p class="resume-skill-desc">Applied AI orchestration and agentic tool integrations accelerating software delivery.</p>
                        <div class="resume-tag-wrap">
                            <span class="badge-tech badge-tech-pri">LLM Orchestration</span>
                            <span class="badge-tech">Agentic Systems</span>
                            <span class="badge-tech">Prompt Engineering</span>
                            <span class="badge-tech">Vector Pipelines</span>
                        </div>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Networking &amp; Support</span>
                        </div>
                        <p class="resume-skill-desc">Network topologies, enterprise packet routing, switching, and automated diagnostics.</p>
                        <div class="resume-tag-wrap">
                            <span class="badge-tech">Cisco CCST</span>
                            <span class="badge-tech">CCNA Automation</span>
                            <span class="badge-tech">Enterprise Routing</span>
                            <span class="badge-tech">IT Support</span>
                        </div>
                    </div>

                    <div class="resume-skill-cat">
                        <div class="resume-skill-name">
                            <span class="pulse-dot"></span>
                            <span>Hardware &amp; Systems</span>
                        </div>
                        <p class="resume-skill-desc">Systems administration, hardware lifecycle management, and physical safety standards.</p>
                        <div class="resume-tag-wrap">
                            <span class="badge-tech">System Hardening</span>
                            <span class="badge-tech">Server Lifecycle</span>
                            <span class="badge-tech">NFPA 70E Safety</span>
                            <span class="badge-tech">OSHA 10</span>
                        </div>
                    </div>
                </section>

                <!-- Certifications & Training -->
                <section class="resume-block">
                    <div class="resume-eyebrow">03 / CREDENTIALS</div>
                    <h3 class="resume-block-title">Certifications &amp; Training</h3>
                    <div class="resume-cert-list">
                        <div class="resume-cert-card">
                            <div class="resume-cert-title">Google AI Professional</div>
                            <div class="resume-cert-meta">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Completed May 2026</span>
                            </div>
                        </div>
                        <div class="resume-cert-card">
                            <div class="resume-cert-title">Google IT Support</div>
                            <div class="resume-cert-meta">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Completed September 2026</span>
                            </div>
                        </div>
                        <div class="resume-cert-card">
                            <div class="resume-cert-title">Cisco Certified Support Technician (CCST) - Networking (100-150)</div>
                            <div class="resume-cert-meta">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Completed July 2026</span>
                            </div>
                        </div>
                        <div class="resume-cert-card">
                            <div class="resume-cert-title">Cisco Certified Network Associate (CCNA) - Automation (200-301)</div>
                            <div class="resume-cert-meta">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Expected Completion: February 2027</span>
                            </div>
                        </div>
                        <div class="resume-cert-card">
                            <div class="resume-cert-title">OSHA 10 &bull; NFPA 70E Safety</div>
                            <div class="resume-cert-meta">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Completed August 2026</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Education -->
                <section class="resume-block">
                    <div class="resume-eyebrow">04 / ACADEMICS</div>
                    <h3 class="resume-block-title">Education</h3>
                    <div class="resume-edu-list">
                        <div class="resume-edu-card">
                            <div class="resume-edu-degree">M.S. in Cybersecurity &amp; Information Assurance</div>
                            <div class="resume-edu-school">Western Governors University (In Progress, Expected March 2027)</div>
                        </div>
                        <div class="resume-edu-card">
                            <div class="resume-edu-degree">Bachelor of Science in Information Technology</div>
                            <div class="resume-edu-school">Vatterott College, Fenton, MO</div>
                        </div>
                    </div>
                </section>
            </aside>

            <!-- Right Column: Flagship Architecture & Career Timeline -->
            <section class="resume-column">
                <!-- Projects Showcase -->
                <div class="resume-block">
                    <div class="resume-eyebrow">05 / ARCHITECTURAL SHOWCASE</div>
                    <h3 class="resume-block-title">Key Projects &amp; Frameworks</h3>

                    <!-- Dreambox Nexus Flagship Project -->
                    <article class="resume-project-card flagship-project">
                        <div class="resume-exp-top">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="resume-exp-company">Dreambox Nexus</span>
                                    <span class="badge-flagship"><span class="pulse-dot"></span> Flagship Architecture</span>
                                </div>
                                <div class="resume-exp-role">Systems Architect &amp; Principal Framework Engineer</div>
                            </div>
                            <span class="resume-exp-date">October 2026 &ndash; Present</span>
                        </div>

                        <div class="resume-tag-wrap mb-3">
                            <span class="badge-tech badge-tech-pri">PHP 8.3 / 8.5</span>
                            <span class="badge-tech">Zero Dependencies</span>
                            <span class="badge-tech">Argon2id</span>
                            <span class="badge-tech">AES-256-GCM AEAD</span>
                            <span class="badge-tech">SHA-256 Merkle Chain</span>
                            <span class="badge-tech">MongoDB NoSQL</span>
                            <span class="badge-tech">OAuth 2.0 / TOTP 2FA</span>
                            <span class="badge-tech">PCI-DSS v4.0</span>
                            <span class="badge-tech">HIPAA §164.312</span>
                            <span class="badge-tech">NIST CSF 2.0</span>
                            <span class="badge-tech">OpenAPI 3.0</span>
                        </div>

                        <p class="resume-project-intro">
                            A 100% custom, zero-external-dependency PHP 8.3/8.5 enterprise web framework and application gateway engineered from first principles for defense-in-depth security, strict regulatory compliance, and high-performance API orchestration.
                        </p>

                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item"><strong>Zero-Dependency Core Framework:</strong> Engineered a lightweight, high-throughput application gateway eliminating third-party supply chain risks, complete with native autoloader, secure PSR/PER-CS routing pipelines, and timing-safe request handling.</li>
                            <li class="resume-bullet-item"><strong>Automated Regulatory Compliance Matrix:</strong> Architected a multi-framework regulatory assessment engine covering PCI-DSS v4.0, HIPAA Security Rule (§164.312), NIST CSF 2.0, EU GDPR, and OWASP Top 10 with a 10-point live runtime diagnostic probe suite.</li>
                            <li class="resume-bullet-item"><strong>Cryptographic Merkle Audit Hash-Chain:</strong> Engineered continuous SHA-256 block-linked forensic logging across database and filesystem ledgers; any row deletion, payload tampering, or out-of-order insertion breaks the Merkle chain and flags immediate SOC alerts.</li>
                            <li class="resume-bullet-item"><strong>Defense-in-Depth Cryptography:</strong> Implemented AES-256-GCM authenticated encryption (AEAD), Argon2id memory-hard password hashing, timing-safe HMAC CSRF defense, and strict Content Security Policy headers.</li>
                            <li class="resume-bullet-item"><strong>Enterprise IAM &amp; Identity Federation:</strong> Built complete IAM suite featuring TOTP RFC 6238 two-factor authentication, OAuth 2.0 providers (Google &amp; GitHub), sliding-window rate limiting, and 5-strike account lockout protection.</li>
                            <li class="resume-bullet-item"><strong>Hybrid Datastore Persistence:</strong> Integrated MongoDB NoSQL document storage with operator-whitelisting injection immunity, resilient fallback ledger synchronization, and strict append-only audit enforcement.</li>
                            <li class="resume-bullet-item"><strong>Nexus UI Design System:</strong> Developed a responsive, high-contrast light-mode design framework with custom typography, glassmorphism cards, interactive offcanvas navigation, and real-time observability telemetry.</li>
                        </ul>

                        <div class="project-quick-actions">
                            <span class="small text-muted fw-bold">Live Platform Portals:</span>
                            <a href="<?= $appBase ?>/admin/" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                <span>IAM Console</span>
                            </a>
                            <a href="<?= $appBase ?>/compliance/" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                <span>Compliance Matrix</span>
                            </a>
                            <a href="<?= $appBase ?>/nexus/monitor/" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                <span>SecOps Cockpit</span>
                            </a>
                            <a href="<?= $appBase ?>/docs/openapi.yaml?format=html" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                <span>OpenAPI Contract</span>
                            </a>
                            <a href="<?= $appBase ?>/ui/" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                <span>Nexus UI</span>
                            </a>
                        </div>
                    </article>

                    <!-- Aether Project -->
                    <article class="resume-project-card">
                        <div class="resume-exp-top">
                            <div>
                                <div class="resume-exp-company mb-1">Aether Project</div>
                                <div class="resume-exp-role">Architect &amp; Lead Engineer</div>
                            </div>
                            <span class="resume-exp-date">Full-Stack Enterprise Architecture</span>
                        </div>

                        <div class="resume-tag-wrap mb-3">
                            <span class="badge-tech badge-tech-pri">Laravel 11</span>
                            <span class="badge-tech">Vue 3</span>
                            <span class="badge-tech">Inertia.js</span>
                            <span class="badge-tech">Tailwind CSS v4</span>
                            <span class="badge-tech">Postfix Pipelines</span>
                            <span class="badge-tech">MariaDB</span>
                            <span class="badge-tech">AI Agentic Workflows</span>
                        </div>

                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item"><strong>High-Volume Mail Engine:</strong> Architected a full-stack mail administration platform using Laravel 11, Vue 3, Inertia.js, and Tailwind CSS v4, implementing inbound Postfix transport pipelines, MIME stream parsing into MariaDB, and outbound SMTP relays with automatic thread tracking.</li>
                            <li class="resume-bullet-item"><strong>Collaborative Technical Wiki:</strong> Developed an integrated enterprise wiki module featuring real-time collaborative documentation, Markdown rendering, and granular access control for internal technical knowledge sharing.</li>
                            <li class="resume-bullet-item"><strong>Observability &amp; System Cockpit:</strong> Built a real-time system monitor dashboard tracking server health, daemon process states, memory usage, and service availability with instant alerting notifications.</li>
                            <li class="resume-bullet-item"><strong>Financial Allocation &amp; Budget Engine:</strong> Created a custom personal finance and budget tracking tool to monitor resource allocation, expenditures, and automated financial reporting.</li>
                            <li class="resume-bullet-item"><strong>AI-Accelerated Software Delivery:</strong> Integrated AI-assisted coding workflows and agentic tooling into daily development, accelerating system feature implementation, custom integration building, and codebase refactoring.</li>
                        </ul>
                    </article>
                </div>

                <!-- Professional Experience Timeline -->
                <div class="resume-block">
                    <div class="resume-eyebrow">06 / CAREER TRAJECTORY</div>
                    <h3 class="resume-block-title">Professional Experience</h3>

                    <!-- Independent 2024-2026 -->
                    <article class="resume-timeline-entry">
                        <div class="resume-exp-top">
                            <div>
                                <div class="resume-exp-company">Independent</div>
                                <div class="resume-exp-role">Software Developer &amp; Researcher | Remote</div>
                            </div>
                            <span class="resume-exp-date">March 2024 &ndash; March 2026</span>
                        </div>
                        <div class="resume-tag-wrap mb-2">
                            <span class="badge-tech">AI Systems</span>
                            <span class="badge-tech">DevSecOps</span>
                            <span class="badge-tech">Cybersecurity Research</span>
                        </div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Expanded AI expertise, mastering prompt engineering and integrating agentic AI tools into software delivery pipelines.</li>
                            <li class="resume-bullet-item">Pursued M.S. in Cybersecurity and Information Assurance to reinforce DevSecOps, zero-trust patterns, and platform security skills.</li>
                        </ul>
                    </article>

                    <!-- ApplicantPro -->
                    <article class="resume-timeline-entry">
                        <div class="resume-exp-top">
                            <div>
                                <div class="resume-exp-company">ApplicantPro</div>
                                <div class="resume-exp-role">Software Engineer | Remote</div>
                            </div>
                            <span class="resume-exp-date">Nov 2021 &ndash; March 2024</span>
                        </div>
                        <div class="resume-tag-wrap mb-2">
                            <span class="badge-tech badge-tech-pri">RESTful APIs</span>
                            <span class="badge-tech">Service Workers (MV3)</span>
                            <span class="badge-tech">JavaScript</span>
                            <span class="badge-tech">Cloud-Native</span>
                        </div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Developed high-availability ATS connectors with RESTful endpoints, achieving 99.9% uptime across third-party recruiting platform integrations.</li>
                            <li class="resume-bullet-item">Designed a cloud-native skill-testing platform leveraging modern JavaScript frameworks, accelerating system responsiveness by 25%.</li>
                            <li class="resume-bullet-item">Migrated core extension suites to Manifest V3 using Service Workers and background scripts, fortifying cross-browser reliability and performance.</li>
                        </ul>
                    </article>

                    <!-- Independent -->
                    <article class="resume-timeline-entry">
                        <div class="resume-exp-top">
                            <div>
                                <div class="resume-exp-company">Independent</div>
                                <div class="resume-exp-role">Software Engineer | Remote</div>
                            </div>
                            <span class="resume-exp-date">Consulting &amp; Systems</span>
                        </div>
                        <div class="resume-tag-wrap mb-2">
                            <span class="badge-tech">Laravel</span>
                            <span class="badge-tech">Vue.js</span>
                            <span class="badge-tech">MySQL</span>
                            <span class="badge-tech">PHP 8.x</span>
                            <span class="badge-tech">Hardware Integration</span>
                        </div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Delivered custom web applications using Laravel, Vue.js, and MySQL for SME clients through end-to-end technical leadership and consulting.</li>
                            <li class="resume-bullet-item">Modernized legacy PHP systems to version 8.x and hardened infrastructure against security vulnerabilities during key organizational technology shifts.</li>
                            <li class="resume-bullet-item">Integrated custom software interfaces with precision hardware and industrial laser systems to enable seamless real-time operational control.</li>
                        </ul>
                    </article>

                    <!-- Dreambox Services -->
                    <article class="resume-timeline-entry">
                        <div class="resume-exp-top">
                            <div>
                                <div class="resume-exp-company">Dreambox Services</div>
                                <div class="resume-exp-role">Software Engineer | Remote</div>
                            </div>
                            <span class="resume-exp-date">Enterprise Engineering</span>
                        </div>
                        <div class="resume-tag-wrap mb-2">
                            <span class="badge-tech">ASP.NET</span>
                            <span class="badge-tech">PHPUnit</span>
                            <span class="badge-tech">SQL Optimization</span>
                            <span class="badge-tech">CI/CD Standards</span>
                        </div>
                        <ul class="resume-bullet-list">
                            <li class="resume-bullet-item">Orchestrated the architectural migration of core systems to modular ASP.NET, establishing enterprise-grade scalability and maintainability.</li>
                            <li class="resume-bullet-item">Established rigorous code review workflows and automated unit testing standards using PHPUnit to elevate software quality across releases.</li>
                            <li class="resume-bullet-item">Optimized backend algorithms and SQL query execution paths, lowering infrastructure overhead and dramatically improving report generation speeds.</li>
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