<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Universal UI Component Framework & Interactive Showcase
 * Complete Bootstrap-grade kitchen sink and documentation system.
 */

$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$traceId = $traceId ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus UI // Component Framework & Kitchen Sink</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body class="bg-light">

    <!-- Universal Top Navigation Bar -->
    <?php include dirname(__DIR__) . '/partials/topbar.php'; ?>

    <div class="showcase-wrapper">
        <!-- Sticky Documentation Sidebar -->
        <aside class="showcase-sidebar">
            <div class="showcase-sidebar-title">Getting Started</div>
            <ul class="showcase-nav-list">
                <li class="showcase-nav-item"><a href="#overview" class="showcase-nav-link active">Overview & CDN</a></li>
                <li class="showcase-nav-item"><a href="#grid" class="showcase-nav-link">12-Column Grid</a></li>
                <li class="showcase-nav-item"><a href="#typography" class="showcase-nav-link">Typography</a></li>
                <li class="showcase-nav-item"><a href="#colors" class="showcase-nav-link">Color Tokens</a></li>
            </ul>

            <div class="showcase-sidebar-title">Components</div>
            <ul class="showcase-nav-list">
                <li class="showcase-nav-item"><a href="#buttons" class="showcase-nav-link">Buttons & Groups</a></li>
                <li class="showcase-nav-item"><a href="#forms" class="showcase-nav-link">Form Controls & Validation</a></li>
                <li class="showcase-nav-item"><a href="#cards" class="showcase-nav-link">Cards & Stat Metrics</a></li>
                <li class="showcase-nav-item"><a href="#navigation" class="showcase-nav-link">Navs, Tabs & Breadcrumbs</a></li>
                <li class="showcase-nav-item"><a href="#tables" class="showcase-nav-link">Tables & Data Grids</a></li>
                <li class="showcase-nav-item"><a href="#alerts" class="showcase-nav-link">Alerts, Badges & Callouts</a></li>
                <li class="showcase-nav-item"><a href="#modals" class="showcase-nav-link">Modals & Offcanvas</a></li>
                <li class="showcase-nav-item"><a href="#progress" class="showcase-nav-link">Progress & Spinners</a></li>
            </ul>

            <div class="showcase-sidebar-title">Utilities</div>
            <ul class="showcase-nav-list">
                <li class="showcase-nav-item"><a href="#utilities" class="showcase-nav-link">Spacing, Flex & Borders</a></li>
            </ul>
        </aside>

        <!-- Main Showcase Content -->
        <main class="showcase-content">

            <!-- Hero Section -->
            <section id="overview" class="showcase-hero">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge badge-primary">Nexus UI Core v1.0.0</span>
                    <span class="badge badge-success">Zero Dependencies</span>
                    <span class="badge badge-info">100% Custom CSS</span>
                </div>
                <h1 class="display-3 mb-2">Nexus UI Framework</h1>
                <p class="lead mb-4">
                    A lightweight, high-performance, security-first component library and design system designed for modern full-stack web applications, admin consoles, and telemetry HUDs.
                </p>

                <!-- Quick Start CDN Box -->
                <div class="card p-3 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small text-uppercase">Quick Start (Single Centralized Stylesheet)</strong>
                        <button type="button" class="btn-copy-code" data-target="#cdn-code">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            Copy Tag
                        </button>
                    </div>
                    <code id="cdn-code" class="hash-code p-2">&lt;link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css"&gt;</code>
                </div>
            </section>

            <!-- 1. Grid System -->
            <section id="grid" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line><line x1="15" y1="3" x2="15" y2="21"></line></svg>
                    12-Column Responsive Grid
                </h2>
                <p class="showcase-section-desc">
                    Built on modern flexbox with 12 column divisions, responsive breakpoints (<code class="small">col-md-*</code>, <code class="small">col-lg-*</code>), and flexible gutter spacing (<code class="small">g-0</code> to <code class="small">g-5</code>).
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <div class="row g-2 mb-2 text-center text-white small">
                            <div class="col-12"><div class="p-2 bg-primary rounded">col-12 (100%)</div></div>
                        </div>
                        <div class="row g-2 mb-2 text-center text-white small">
                            <div class="col-6"><div class="p-2 bg-primary rounded">col-6 (50%)</div></div>
                            <div class="col-6"><div class="p-2 bg-primary rounded">col-6 (50%)</div></div>
                        </div>
                        <div class="row g-2 mb-2 text-center text-white small">
                            <div class="col-4"><div class="p-2 bg-primary rounded">col-4 (33.3%)</div></div>
                            <div class="col-4"><div class="p-2 bg-primary rounded">col-4 (33.3%)</div></div>
                            <div class="col-4"><div class="p-2 bg-primary rounded">col-4 (33.3%)</div></div>
                        </div>
                        <div class="row g-2 text-center text-white small">
                            <div class="col-3"><div class="p-2 bg-primary rounded">col-3</div></div>
                            <div class="col-3"><div class="p-2 bg-primary rounded">col-3</div></div>
                            <div class="col-3"><div class="p-2 bg-primary rounded">col-3</div></div>
                            <div class="col-3"><div class="p-2 bg-primary rounded">col-3</div></div>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-grid">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-grid">&lt;div class="row g-3"&gt;
  &lt;div class="col-12 col-md-6 col-lg-4"&gt;Column 1&lt;/div&gt;
  &lt;div class="col-12 col-md-6 col-lg-4"&gt;Column 2&lt;/div&gt;
  &lt;div class="col-12 col-md-12 col-lg-4"&gt;Column 3&lt;/div&gt;
&lt;/div&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 2. Typography -->
            <section id="typography" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>
                    Typography & Display Headings
                </h2>
                <p class="showcase-section-desc">
                    Fluid scale typography with high contrast, crisp letter spacing, display classes, lead paragraphs, and text weight utilities.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <div class="h1">Heading 1 (.h1, 32px / 2rem)</div>
                        <div class="h2">Heading 2 (.h2, 26px / 1.6rem)</div>
                        <div class="h3">Heading 3 (.h3, 22px / 1.35rem)</div>
                        <div class="h4">Heading 4 (.h4, 18px / 1.15rem)</div>
                        <p class="lead mt-3 mb-1">
                            This is a lead paragraph (<code class="small">.lead</code>). It stands out from standard prose text with an enlarged font size and comfortable line height.
                        </p>
                        <p class="text-muted small">
                            Subtle muted secondary caption (<code class="small">.text-muted .small</code>) with crisp rendering.
                        </p>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-typo">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-typo">&lt;h1 class="h1"&gt;Heading 1&lt;/h1&gt;
&lt;h2 class="h2"&gt;Heading 2&lt;/h2&gt;
&lt;p class="lead"&gt;Lead paragraph text for introductory content.&lt;/p&gt;
&lt;p class="text-muted small"&gt;Secondary metadata note.&lt;/p&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 3. Color Tokens -->
            <section id="colors" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"></path></svg>
                    Design Tokens & Palette
                </h2>
                <p class="showcase-section-desc">
                    Semantic CSS variables providing predictable color consistency across UI states and light themes.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <div class="row g-3 text-center text-white small">
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-primary shadow-sm">
                                    <div class="fw-bold">Primary</div>
                                    <div class="small opacity-75">#2563eb</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-success shadow-sm">
                                    <div class="fw-bold">Success</div>
                                    <div class="small opacity-75">#16a34a</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-danger shadow-sm">
                                    <div class="fw-bold">Danger</div>
                                    <div class="small opacity-75">#dc2626</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-warning shadow-sm">
                                    <div class="fw-bold">Warning</div>
                                    <div class="small opacity-75">#ea580c</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-cyan shadow-sm">
                                    <div class="fw-bold">Cyan / Info</div>
                                    <div class="small opacity-75">#0284c7</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-purple shadow-sm">
                                    <div class="fw-bold">Purple</div>
                                    <div class="small opacity-75">#7c3aed</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded shadow-sm bg-dark">
                                    <div class="fw-bold">Slate / Dark</div>
                                    <div class="small opacity-75">#0f172a</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded shadow-sm text-dark border bg-light">
                                    <div class="fw-bold">Canvas / Light</div>
                                    <div class="small text-muted">#f8fafc</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. Buttons & Groups -->
            <section id="buttons" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"></rect><circle cx="8" cy="12" r="1.5"></circle></svg>
                    Buttons & Button Groups
                </h2>
                <p class="showcase-section-desc">
                    Solid colors, smooth hover lifts, clean outline variants, sizing modifiers (<code class="small">.btn-sm</code>, <code class="small">.btn-lg</code>), and button groups.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <!-- Solid Variants -->
                        <div class="mb-3">
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Solid Variants</span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-primary">Primary</button>
                                <button type="button" class="btn btn-secondary">Secondary</button>
                                <button type="button" class="btn btn-success">Success</button>
                                <button type="button" class="btn btn-danger">Danger</button>
                                <button type="button" class="btn btn-warning">Warning</button>
                                <button type="button" class="btn btn-info">Info</button>
                                <button type="button" class="btn btn-dark">Dark</button>
                                <button type="button" class="btn btn-light">Light</button>
                            </div>
                        </div>

                        <!-- Outline Variants -->
                        <div class="mb-3">
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Outline Variants</span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary">Outline Primary</button>
                                <button type="button" class="btn btn-outline-secondary">Outline Secondary</button>
                                <button type="button" class="btn btn-outline-success">Outline Success</button>
                                <button type="button" class="btn btn-outline-danger">Outline Danger</button>
                            </div>
                        </div>

                        <!-- Sizes & Groups -->
                        <div>
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Sizes & Groups</span>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <button type="button" class="btn btn-primary btn-sm">Small Button</button>
                                <button type="button" class="btn btn-primary">Default Size</button>
                                <button type="button" class="btn btn-primary btn-lg">Large Button</button>

                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-secondary">Left</button>
                                    <button type="button" class="btn btn-secondary">Middle</button>
                                    <button type="button" class="btn btn-secondary">Right</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-btn">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-btn">&lt;!-- Solid & Outline Buttons --&gt;
&lt;button class="btn btn-primary"&gt;Primary&lt;/button&gt;
&lt;button class="btn btn-outline-primary"&gt;Outline&lt;/button&gt;
&lt;button class="btn btn-primary btn-sm"&gt;Small&lt;/button&gt;
&lt;button class="btn btn-primary btn-lg"&gt;Large&lt;/button&gt;

&lt;!-- Button Group --&gt;
&lt;div class="btn-group"&gt;
  &lt;button class="btn btn-secondary"&gt;Left&lt;/button&gt;
  &lt;button class="btn btn-secondary"&gt;Middle&lt;/button&gt;
  &lt;button class="btn btn-secondary"&gt;Right&lt;/button&gt;
&lt;/div&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 5. Form Controls & Validation -->
            <section id="forms" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Form Controls & Validation States
                </h2>
                <p class="showcase-section-desc">
                    Comprehensive input controls, select dropdowns, input groups with addons, checkboxes, radio buttons, switch toggles, and live validation states.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <form id="demo-form" onsubmit="event.preventDefault();">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" placeholder="e.g. Timothy Dunn" value="Timothy Dunn">
                                    <div class="form-text">We'll never share your personal identity without authorization.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Email Address (Validated)</label>
                                    <input type="email" class="form-control is-valid" value="user@example.com">
                                    <div class="valid-feedback">Looks great! RFC 5322 syntax verified.</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Security Role (Select)</label>
                                    <select class="form-select">
                                        <option value="SuperAdmin">SuperAdmin (Full Governance)</option>
                                        <option value="SecurityOfficer">SecurityOfficer (SecOps)</option>
                                        <option value="ComplianceAuditor">ComplianceAuditor</option>
                                        <option value="Developer">Developer</option>
                                        <option value="Member">Member</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Invalid Sample Input</label>
                                    <input type="text" class="form-control is-invalid" value="invalid token!!">
                                    <div class="invalid-feedback">Must be a valid hexadecimal SHA-256 hash.</div>
                                </div>
                            </div>

                            <!-- Input Group -->
                            <div class="mb-3">
                                <label class="form-label">Input Group with Addons</label>
                                <div class="input-group">
                                    <span class="input-group-text">https://api.domain.com/v1/</span>
                                    <input type="text" class="form-control" placeholder="endpoint">
                                    <button class="btn btn-primary" type="button">Execute</button>
                                </div>
                            </div>

                            <!-- Switches & Checks -->
                            <div class="d-flex flex-wrap gap-4 mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="demoSwitch" checked>
                                    <label class="form-check-label" for="demoSwitch">Hardware MFA Enforced</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="demoCheck" checked>
                                    <label class="form-check-label" for="demoCheck">Remember identity device</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="demoRadio" id="radio1" checked>
                                    <label class="form-check-label" for="radio1">Option Alpha</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="demoRadio" id="radio2">
                                    <label class="form-check-label" for="radio2">Option Beta</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-forms">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-forms">&lt;!-- Text Input with Label & Form Text --&gt;
&lt;div class="mb-3"&gt;
  &lt;label class="form-label"&gt;Email Address&lt;/label&gt;
  &lt;input type="email" class="form-control is-valid" value="user@example.com"&gt;
  &lt;div class="valid-feedback"&gt;Valid email format.&lt;/div&gt;
&lt;/div&gt;

&lt;!-- Input Group --&gt;
&lt;div class="input-group"&gt;
  &lt;span class="input-group-text"&gt;@&lt;/span&gt;
  &lt;input type="text" class="form-control" placeholder="Username"&gt;
&lt;/div&gt;

&lt;!-- Switch Toggle --&gt;
&lt;div class="form-check form-switch"&gt;
  &lt;input class="form-check-input" type="checkbox" id="switch1" checked&gt;
  &lt;label class="form-check-label" for="switch1"&gt;Enable Feature&lt;/label&gt;
&lt;/div&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 6. Cards & Stat Metrics -->
            <section id="cards" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line></svg>
                    Cards & Stat Metric Widgets
                </h2>
                <p class="showcase-section-desc">
                    Flexible content containers with card headers, footers, and dedicated telemetry stat metric cards featuring real-time delta badges.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <!-- Stat Metric Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <span>Total Authenticated Requests</span>
                                        <span class="badge badge-primary">24h</span>
                                    </div>
                                    <div class="metric-value">128,490</div>
                                    <div class="metric-footer">
                                        <span class="metric-delta up">▲ 14.2%</span>
                                        <span>vs. previous period</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <span>SOC Threat Ingress Blocks</span>
                                        <span class="badge badge-danger">Real-time</span>
                                    </div>
                                    <div class="metric-value">0 Incidents</div>
                                    <div class="metric-footer">
                                        <span class="metric-delta down">▼ 100%</span>
                                        <span>Clean perimeter</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <span>Compliance Verification Score</span>
                                        <span class="badge badge-success">Audited</span>
                                    </div>
                                    <div class="metric-value">98.5%</div>
                                    <div class="metric-footer">
                                        <span class="metric-delta up">▲ 2.1%</span>
                                        <span>PCI-DSS / HIPAA compliant</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Standard Content Card -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <strong class="text-dark">Featured Project Architecture</strong>
                                        <span class="badge badge-cyan">v1.0</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">Clean Modular Structure</h5>
                                        <p class="card-text text-muted">
                                            Every view utilizes the centralized <code class="small">theme.css</code> framework with zero external dependencies and deterministic rendering.
                                        </p>
                                        <button class="btn btn-primary btn-sm">Explore Blueprint</button>
                                    </div>
                                    <div class="card-footer text-muted small">
                                        Last verified: Just now
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <strong class="text-dark">Quick Action Card</strong>
                                        <button class="btn-close" aria-label="Close"></button>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">Identity & Access Governance</h5>
                                        <p class="card-text text-muted">
                                            Manage RBAC permissions, identity groups, and audit trails directly through the unified IAM suite.
                                        </p>
                                        <div class="d-flex gap-2">
                                            <a href="<?= $appBase ?>/admin/users/" class="btn btn-outline-primary btn-sm">Manage Users</a>
                                            <a href="<?= $appBase ?>/admin/audit/" class="btn btn-secondary btn-sm">Audit Log</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-cards">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-cards">&lt;!-- Metric Stat Card --&gt;
&lt;div class="metric-card"&gt;
  &lt;div class="metric-header"&gt;
    &lt;span&gt;Active Users&lt;/span&gt;
    &lt;span class="badge badge-primary"&gt;Live&lt;/span&gt;
  &lt;/div&gt;
  &lt;div class="metric-value"&gt;1,248&lt;/div&gt;
  &lt;div class="metric-footer"&gt;
    &lt;span class="metric-delta up"&gt;▲ 8.4%&lt;/span&gt;
  &lt;/div&gt;
&lt;/div&gt;

&lt;!-- Standard Content Card --&gt;
&lt;div class="card"&gt;
  &lt;div class="card-header"&gt;Card Title&lt;/div&gt;
  &lt;div class="card-body"&gt;
    &lt;h5 class="card-title"&gt;Content Subheading&lt;/h5&gt;
    &lt;p class="card-text"&gt;Main descriptive text.&lt;/p&gt;
  &lt;/div&gt;
  &lt;div class="card-footer"&gt;Footer Action&lt;/div&gt;
&lt;/div&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 7. Navigation, Tabs & Breadcrumbs -->
            <section id="navigation" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    Navs, Tabs, Breadcrumbs & Pagination
                </h2>
                <p class="showcase-section-desc">
                    Interactive tabbed navigation with smooth panel switching, breadcrumb trails, and pagination controls.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <!-- Breadcrumbs -->
                        <div class="mb-4">
                            <span class="d-block small text-muted mb-1 text-uppercase fw-semibold">Breadcrumb Trail</span>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?= $appBase ?>/">Home</a></li>
                                    <li class="breadcrumb-item"><a href="<?= $appBase ?>/ui/">Framework</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Kitchen Sink</li>
                                </ol>
                            </nav>
                        </div>

                        <!-- Interactive Tabs Demo -->
                        <div class="mb-4">
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Interactive Nav Tabs (Click to Switch)</span>
                            <ul class="nav nav-tabs" id="demoTabs">
                                <li class="nav-item">
                                    <button class="nav-link active" data-tab-target="#tab-pane-1">System Architecture</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-tab-target="#tab-pane-2">Security Perimeter</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-tab-target="#tab-pane-3">API Contract</button>
                                </li>
                            </ul>
                            <div class="p-3 border border-top-0 rounded-bottom bg-white" id="demoTabContent">
                                <div id="tab-pane-1" class="tab-pane-content">
                                    <h5 class="h5 mb-2">High-Performance Architecture</h5>
                                    <p class="text-muted small mb-0">
                                        Dreambox Nexus runs on a zero-third-party PHP 8.3 kernel with native Argon2id password hashing, AES-256-GCM AEAD encryption, and MongoDB database driver.
                                    </p>
                                </div>
                                <div id="tab-pane-2" class="tab-pane-content d-none">
                                    <h5 class="h5 mb-2">SOC Threat Defense</h5>
                                    <p class="text-muted small mb-0">
                                        Multi-layer perimeter enforcement including strict CSP nonces, HMAC webhook signature verification, and private CIDR IP allowlisting.
                                    </p>
                                </div>
                                <div id="tab-pane-3" class="tab-pane-content d-none">
                                    <h5 class="h5 mb-2">Standardized OpenAPI 3.0 Contract</h5>
                                    <p class="text-muted small mb-0">
                                        Uniform JSON response envelopes ({data, meta, message}) guaranteed across all 35+ API endpoints with ISO 8601 timestamps and UUIDv4 trace IDs.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div>
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Pagination Bar</span>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-nav">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-nav">&lt;!-- Nav Tabs --&gt;
&lt;ul class="nav nav-tabs"&gt;
  &lt;li class="nav-item"&gt;&lt;a class="nav-link active" href="#"&gt;Active Tab&lt;/a&gt;&lt;/li&gt;
  &lt;li class="nav-item"&gt;&lt;a class="nav-link" href="#"&gt;Link&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;

&lt;!-- Breadcrumb --&gt;
&lt;ol class="breadcrumb"&gt;
  &lt;li class="breadcrumb-item"&gt;&lt;a href="#"&gt;Home&lt;/a&gt;&lt;/li&gt;
  &lt;li class="breadcrumb-item active"&gt;Current&lt;/li&gt;
&lt;/ol&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 8. Tables & Data Grids -->
            <section id="tables" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    Tables & High-Contrast Data Grids
                </h2>
                <p class="showcase-section-desc">
                    High-density data tables with striped rows, hover highlighting, badges, and responsive horizontal overflow wrapping.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Identity</th>
                                        <th>Role</th>
                                        <th>Assigned Groups</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">Timothy Dunn</div>
                                            <div class="small text-muted">admin@nexus.local</div>
                                        </td>
                                        <td><span class="badge badge-primary">SuperAdmin</span></td>
                                        <td><span class="badge badge-secondary">SecOps</span> <span class="badge badge-secondary">Engineering</span></td>
                                        <td><span class="badge badge-success">Active</span></td>
                                        <td><button class="btn btn-outline-primary btn-sm">Edit</button></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">Compliance Auditor</div>
                                            <div class="small text-muted">auditor@nexus.local</div>
                                        </td>
                                        <td><span class="badge badge-info">Auditor</span></td>
                                        <td><span class="badge badge-secondary">Compliance</span></td>
                                        <td><span class="badge badge-success">Active</span></td>
                                        <td><button class="btn btn-outline-primary btn-sm">Edit</button></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">Suspended Test Identity</div>
                                            <div class="small text-muted">test_suspended@example.com</div>
                                        </td>
                                        <td><span class="badge badge-secondary">Member</span></td>
                                        <td><span class="badge badge-secondary">General</span></td>
                                        <td><span class="badge badge-danger">Suspended</span></td>
                                        <td><button class="btn btn-outline-danger btn-sm">Reinstate</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-table">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-table">&lt;div class="table-responsive"&gt;
  &lt;table class="table table-striped table-hover"&gt;
    &lt;thead&gt;
      &lt;tr&gt;
        &lt;th&gt;Header 1&lt;/th&gt;
        &lt;th&gt;Header 2&lt;/th&gt;
      &lt;/tr&gt;
    &lt;/thead&gt;
    &lt;tbody&gt;
      &lt;tr&gt;
        &lt;td&gt;Data 1&lt;/td&gt;
        &lt;td&gt;&lt;span class="badge badge-success"&gt;Active&lt;/span&gt;&lt;/td&gt;
      &lt;/tr&gt;
    &lt;/tbody&gt;
  &lt;/table&gt;
&lt;/div&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 9. Alerts, Badges & Callouts -->
            <section id="alerts" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    Alerts, Badges & Callouts
                </h2>
                <p class="showcase-section-desc">
                    Contextual feedback alerts with dismiss triggers, pill badges, and prominent callout note boxes.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <!-- Dismissible Alerts -->
                        <div class="alert alert-primary alert-dismissible" role="alert">
                            <div><strong>Primary Notice:</strong> Application deployed in WSL2 local environment with native Dev Drive acceleration.</div>
                            <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
                        </div>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div><strong>Success:</strong> Automated test suite passed 126/126 checks with zero regressions.</div>
                            <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
                        </div>
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <div><strong>Warning:</strong> Argon2id memory cost exceeds default container limit on low-spec VMs.</div>
                            <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
                        </div>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div><strong>Critical:</strong> Untrusted IP blocked at perimeter boundary by SOC firewall.</div>
                            <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
                        </div>

                        <!-- Callout -->
                        <div class="callout callout-info">
                            <h5 class="h5 mb-1 text-dark">Zero-Guessing Architectural Guarantee</h5>
                            <p class="text-muted small mb-0">
                                Every component adheres to strict design tokens, standardized envelopes, and zero undocumented variables.
                            </p>
                        </div>

                        <!-- Badges & Pills -->
                        <div class="mt-3">
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Badges & Pill Variants</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-primary">Primary</span>
                                <span class="badge badge-secondary">Secondary</span>
                                <span class="badge badge-success">Success</span>
                                <span class="badge badge-danger">Danger</span>
                                <span class="badge badge-warning">Warning</span>
                                <span class="badge badge-info">Info</span>
                                <span class="badge badge-dark">Dark</span>

                                <span class="badge badge-pill badge-primary">Pill 10</span>
                                <span class="badge badge-pill badge-success">Pill Verified</span>
                            </div>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-alerts">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-alerts">&lt;!-- Alert with Dismiss Button --&gt;
&lt;div class="alert alert-success alert-dismissible" role="alert"&gt;
  &lt;div&gt;&lt;strong&gt;Success!&lt;/strong&gt; Operation completed.&lt;/div&gt;
  &lt;button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"&gt;&lt;/button&gt;
&lt;/div&gt;

&lt;!-- Callout Box --&gt;
&lt;div class="callout callout-info"&gt;
  &lt;h5&gt;Information Note&lt;/h5&gt;
  &lt;p&gt;Detailed note content.&lt;/p&gt;
&lt;/div&gt;

&lt;!-- Badges --&gt;
&lt;span class="badge badge-primary"&gt;Badge&lt;/span&gt;
&lt;span class="badge badge-pill badge-success"&gt;Pill Badge&lt;/span&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 10. Modals & Offcanvas Drawers -->
            <section id="modals" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    Modals & Offcanvas Side Drawers
                </h2>
                <p class="showcase-section-desc">
                    Fully functional interactive overlays with backdrop blur, smooth slide-ins, and keyboard accessibility (ESC key dismissal).
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <div class="d-flex flex-wrap gap-3">
                            <button type="button" class="btn btn-primary" id="btnLaunchModal">
                                Launch Interactive Modal
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnLaunchDrawer">
                                Slide Out Side Drawer
                            </button>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-modal">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-modal">&lt;!-- Modal Dialog --&gt;
&lt;div class="modal" id="myModal"&gt;
  &lt;div class="modal-dialog"&gt;
    &lt;div class="modal-content"&gt;
      &lt;div class="modal-header"&gt;
        &lt;h5 class="modal-title"&gt;Modal Title&lt;/h5&gt;
        &lt;button class="btn-close" onclick="closeModal()"&gt;&lt;/button&gt;
      &lt;/div&gt;
      &lt;div class="modal-body"&gt;
        Modal content here...
      &lt;/div&gt;
      &lt;div class="modal-footer"&gt;
        &lt;button class="btn btn-secondary" onclick="closeModal()"&gt;Cancel&lt;/button&gt;
        &lt;button class="btn btn-primary"&gt;Save Changes&lt;/button&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 11. Progress Bars & Spinners -->
            <section id="progress" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                    Progress Bars, Spinners & Loading States
                </h2>
                <p class="showcase-section-desc">
                    Animated striped progress bars, indeterminate spinners, and size modifiers.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <!-- Progress Bars -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Default Progress Bar</span>
                                <span id="progress-text">65%</span>
                            </div>
                            <div class="progress mb-3">
                                <div id="demo-bar" class="progress-bar progress-bar-striped progress-bar-animated w-75"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" id="btnStepProgress">Simulate Step (+15%)</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetProgress">Reset</button>
                            </div>
                        </div>

                        <!-- Spinners -->
                        <div class="mt-4">
                            <span class="d-block small text-muted mb-2 text-uppercase fw-semibold">Loading Spinners</span>
                            <div class="d-flex align-items-center gap-3">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="spinner-border spinner-sm text-secondary" role="status"></div>
                                <div class="spinner-grow text-success" role="status"></div>
                                <button class="btn btn-primary" disabled>
                                    <span class="spinner-border spinner-sm" role="status"></span>
                                    <span>Processing Action...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="demo-code-wrapper">
                        <div class="demo-code-header">
                            <span>HTML Markup</span>
                            <button type="button" class="btn-copy-code" data-target="#code-progress">Copy</button>
                        </div>
                        <pre class="code-snippet" id="code-progress">&lt;!-- Progress Bar --&gt;
&lt;div class="progress"&gt;
  &lt;div class="progress-bar progress-bar-striped progress-bar-animated w-75"&gt;&lt;/div&gt;
&lt;/div&gt;

&lt;!-- Spinners --&gt;
&lt;div class="spinner-border text-primary" role="status"&gt;&lt;/div&gt;
&lt;button class="btn btn-primary" disabled&gt;
  &lt;span class="spinner-border spinner-sm"&gt;&lt;/span&gt; Loading...
&lt;/button&gt;</pre>
                    </div>
                </div>
            </section>

            <!-- 12. Utilities Summary -->
            <section id="utilities" class="showcase-section">
                <h2 class="showcase-section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    Utilities: Spacing, Flex, Borders & Shadows
                </h2>
                <p class="showcase-section-desc">
                    Standardized utility helpers to quickly build custom layouts without touching custom CSS rules.
                </p>

                <div class="component-demo">
                    <div class="demo-preview">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="p-3 bg-white rounded shadow-sm border text-center">
                                    <strong class="d-block text-dark">.shadow-sm</strong>
                                    <span class="small text-muted">Subtle elevation</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 bg-white rounded shadow border text-center">
                                    <strong class="d-block text-dark">.shadow</strong>
                                    <span class="small text-muted">Medium elevation</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 bg-white rounded shadow-lg border text-center">
                                    <strong class="d-block text-dark">.shadow-lg</strong>
                                    <span class="small text-muted">Prominent elevation</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded border mt-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-primary">Flexbox</span>
                                <span class="small text-dark fw-medium">.d-flex .justify-content-between .align-items-center</span>
                            </div>
                            <button class="btn btn-secondary btn-sm">Sample Action</button>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Live Interactive Demo Modal -->
    <div class="modal" id="demoModal" role="dialog" aria-modal="true" aria-labelledby="demoModalTitle">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="demoModalTitle">Interactive Nexus Modal</h5>
                    <button type="button" class="btn-close" aria-label="Close" id="btnCloseModalX"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        This modal is rendered dynamically using the centralized <code class="small">theme.css</code> framework. It features backdrop blurring, smooth transitions, and keyboard escape dismissal.
                    </p>
                    <div class="callout callout-success m-0">
                        <strong class="d-block text-dark small mb-1">State Verification</strong>
                        <span class="small text-muted">Zero external JavaScript libraries used. 100% native DOM event bindings.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCloseModalBtn">Close</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmModal">Confirm Action</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Interactive Offcanvas Drawer -->
    <div class="offcanvas offcanvas-end" id="demoDrawer" role="dialog" aria-modal="true">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">System Settings Drawer</h5>
            <button type="button" class="btn-close" aria-label="Close" id="btnCloseDrawerX"></button>
        </div>
        <div class="offcanvas-body">
            <p class="text-muted small mb-3">
                Slide-out offcanvas drawer suitable for property inspectors, navigation panels, and quick settings.
            </p>
            <div class="mb-3">
                <label class="form-label">Cluster Environment</label>
                <select class="form-select">
                    <option>WSL2 Development (Local)</option>
                    <option>Production Cluster</option>
                    <option>Staging</option>
                </select>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="drawerSwitch1" checked>
                <label class="form-check-label" for="drawerSwitch1">Real-time Telemetry Polling</label>
            </div>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" id="drawerSwitch2" checked>
                <label class="form-check-label" for="drawerSwitch2">Audit Chain Verification</label>
            </div>
            <button type="button" class="btn btn-primary w-100" id="btnCloseDrawerBtn">Apply Changes</button>
        </div>
    </div>

    <!-- Feedback Toast for Clipboard Copying -->
    <div class="nexus-toast" id="nexusToast">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span>HTML snippet copied to clipboard!</span>
    </div>

    <!-- Interactive Vanilla JavaScript -->
    <script nonce="<?= $nonce ?? '' ?>">
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Modal Logic
        const modal = document.getElementById('demoModal');
        const btnLaunchModal = document.getElementById('btnLaunchModal');
        const btnCloseModalX = document.getElementById('btnCloseModalX');
        const btnCloseModalBtn = document.getElementById('btnCloseModalBtn');
        const btnConfirmModal = document.getElementById('btnConfirmModal');

        function openModal() {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (btnLaunchModal) btnLaunchModal.addEventListener('click', openModal);
        if (btnCloseModalX) btnCloseModalX.addEventListener('click', closeModal);
        if (btnCloseModalBtn) btnCloseModalBtn.addEventListener('click', closeModal);
        if (btnConfirmModal) btnConfirmModal.addEventListener('click', closeModal);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        }

        // 2. Drawer Logic
        const drawer = document.getElementById('demoDrawer');
        const btnLaunchDrawer = document.getElementById('btnLaunchDrawer');
        const btnCloseDrawerX = document.getElementById('btnCloseDrawerX');
        const btnCloseDrawerBtn = document.getElementById('btnCloseDrawerBtn');

        function openDrawer() {
            drawer.classList.add('show');
        }
        function closeDrawer() {
            drawer.classList.remove('show');
        }

        if (btnLaunchDrawer) btnLaunchDrawer.addEventListener('click', openDrawer);
        if (btnCloseDrawerX) btnCloseDrawerX.addEventListener('click', closeDrawer);
        if (btnCloseDrawerBtn) btnCloseDrawerBtn.addEventListener('click', closeDrawer);

        // Escape Key Handler for Overlays
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeDrawer();
            }
        });

        // 3. Tab Switching Logic
        const tabButtons = document.querySelectorAll('#demoTabs .nav-link');
        tabButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                tabButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const targetSelector = this.getAttribute('data-tab-target');
                document.querySelectorAll('#demoTabContent .tab-pane-content').forEach(pane => pane.classList.add('d-none'));
                const targetPane = document.querySelector(targetSelector);
                if (targetPane) targetPane.classList.remove('d-none');
            });
        });

        // 4. Progress Simulation
        const demoBar = document.getElementById('demo-bar');
        const progressText = document.getElementById('progress-text');
        const btnStepProgress = document.getElementById('btnStepProgress');
        const btnResetProgress = document.getElementById('btnResetProgress');
        let currentProgress = 65;

        if (btnStepProgress) {
            btnStepProgress.addEventListener('click', function() {
                currentProgress = (currentProgress + 15 > 100) ? 100 : currentProgress + 15;
                demoBar.style.width = currentProgress + '%';
                progressText.textContent = currentProgress + '%';
            });
        }
        if (btnResetProgress) {
            btnResetProgress.addEventListener('click', function() {
                currentProgress = 25;
                demoBar.style.width = '25%';
                progressText.textContent = '25%';
            });
        }

        // 5. Copy Code Snippets with Toast Feedback
        const toast = document.getElementById('nexusToast');
        let toastTimeout = null;

        function showToast(msg) {
            if (msg) toast.querySelector('span').textContent = msg;
            toast.classList.add('show');
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.remove('show'), 2200);
        }

        document.querySelectorAll('.btn-copy-code').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetSelector = this.getAttribute('data-target');
                const targetEl = document.querySelector(targetSelector);
                if (!targetEl) return;

                const textToCopy = targetEl.textContent;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    showToast('Snippet copied to clipboard!');
                    setTimeout(() => this.textContent = originalText, 1800);
                }).catch(() => {
                    showToast('Failed to copy code');
                });
            });
        });

        // 6. Highlight Active Sidebar Item on Scroll
        const sections = document.querySelectorAll('.showcase-section, .showcase-hero');
        const sidebarLinks = document.querySelectorAll('.showcase-nav-link');

        window.addEventListener('scroll', function() {
            let current = '';
            sections.forEach(function(section) {
                const sectionTop = section.offsetTop;
                if (window.scrollY >= sectionTop - 120) {
                    current = section.getAttribute('id');
                }
            });

            sidebarLinks.forEach(function(link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    });
    </script>
</body>
</html>

