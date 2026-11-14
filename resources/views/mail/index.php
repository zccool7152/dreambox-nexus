<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambox Nexus // Secure Webmail</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/brand/apple-touch-icon.png">
    <link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/brand/site.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css">
</head>
<body>
    <?php include dirname(__DIR__) . "/partials/topbar.php"; ?>

    <div class="container nx-mail-wrap">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (empty($hasMailAccess)): ?>
            <!-- State 1: Mail Permission Not Granted -->
            <div class="nx-mail-onboarding-card">
                <div class="nx-mail-onboarding-badge nx-badge-danger">MAIL.ACCESS REQUIRED</div>
                <h1 class="h3 mt-2 mb-2">Webmail Access Not Yet Enabled</h1>
                <p class="text-muted mb-3">
                    Your account (<strong><?= htmlspecialchars($currentUser['email'] ?? '') ?></strong>) does not currently hold the
                    <code>mail.access</code> permission. Ask a SuperAdmin or SecurityOfficer to click <strong>Grant Mail</strong>
                    next to your user record inside the Admin Console.
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= htmlspecialchars($appBase) ?>/" class="btn btn-secondary">Back to Gateway</a>
                    <a href="<?= htmlspecialchars($appBase) ?>/account/" class="btn btn-primary">My Account</a>
                </div>
            </div>

        <?php elseif (!empty($needsClaim)): ?>
            <!-- State 2: First-Visit Mailbox Address Onboarding -->
            <div class="nx-mail-onboarding-card">
                <div class="nx-mail-onboarding-badge">FIRST-TIME MAILBOX SETUP</div>
                <h1 class="h3 mt-2 mb-2">Choose Your Nexus Email Address</h1>
                <p class="text-muted mb-4">
                    Webmail access has been enabled for your account. Pick your email handle on
                    <code>@<?= htmlspecialchars($mailDomain) ?></code> to initialize your mailbox, activate real-time antivirus
                    scanning, and enable the Ephemeral Reader Jail.
                </p>

                <form method="POST" action="<?= htmlspecialchars($appBase) ?>/mail/claim" class="nx-mail-claim-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label for="claimLocalPart" class="nx-mail-label">Desired Email Address</label>
                    <div class="nx-mail-claim-row">
                        <input
                            type="text"
                            id="claimLocalPart"
                            name="local_part"
                            class="form-input nx-mail-claim-input"
                            value="<?= htmlspecialchars($suggestedLocal) ?>"
                            placeholder="your.name"
                            pattern="[a-zA-Z0-9][a-zA-Z0-9._-]{0,30}[a-zA-Z0-9]"
                            required
                        >
                        <span class="nx-mail-domain-addon">@<?= htmlspecialchars($mailDomain) ?></span>
                    </div>
                    <div class="small text-muted mt-2 mb-3">
                        Allowed: 2–32 lowercase letters, numbers, dots (<code>.</code>), underscores (<code>_</code>), or hyphens (<code>-</code>).
                    </div>
                    <button type="submit" class="btn btn-primary">Claim Mailbox &amp; Open Inbox</button>
                </form>
            </div>

        <?php else: ?>
            <!-- State 3: Gmail-Style Webmail Workspace + Ephemeral Reader Jail -->
            <div class="nx-mail-topbar">
                <div class="nx-mail-topbar-left">
                    <span class="nx-mail-brand-pill">Nexus Mail</span>
                    <span class="nx-mail-address-pill" title="Your Claimed Nexus Address">
                        <?= htmlspecialchars($mailbox['address'] ?? '') ?>
                    </span>
                </div>

                <form method="GET" action="<?= htmlspecialchars($appBase) ?>/mail/" class="nx-mail-search-form" id="nxMailSearchForm">
                    <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">
                    <input
                        type="search"
                        name="q"
                        id="nxMailSearchInput"
                        class="nx-mail-search-input"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search mail (subject, sender, keywords)..."
                        aria-label="Search mail"
                    >
                    <button type="submit" class="btn btn-secondary nx-mail-search-btn">Search</button>
                </form>

                <div class="nx-mail-topbar-right">
                    <span class="nx-mail-shield-pill" title="Messages are AV-scanned and isolated in an ephemeral sandbox jail on read">
                        <span class="nx-shield-dot"></span> AV + Jail Protected
                    </span>
                </div>
            </div>

            <div class="nx-mail-shell"
                 data-app-base="<?= htmlspecialchars($appBase) ?>"
                 data-csrf="<?= htmlspecialchars($csrfToken) ?>"
                 data-folder="<?= htmlspecialchars($folder) ?>"
                 data-active-jail-id="<?= htmlspecialchars($activeJail['jail_id'] ?? '') ?>">

                <!-- Left Folder Sidebar -->
                <aside class="nx-mail-sidebar">
                    <button type="button" class="nx-mail-compose-btn" id="btnOpenCompose">
                        + Compose
                    </button>

                    <nav class="nx-mail-folders" aria-label="Mail Folders">
                        <?php
                            $folders = [
                                'inbox' => ['label' => 'Inbox', 'count' => (int)($counts['inbox_unread'] ?? 0)],
                                'starred' => ['label' => 'Starred', 'count' => (int)($counts['starred'] ?? 0)],
                                'sent' => ['label' => 'Sent', 'count' => (int)($counts['sent'] ?? 0)],
                                'drafts' => ['label' => 'Drafts', 'count' => (int)($counts['drafts'] ?? 0)],
                                'trash' => ['label' => 'Trash', 'count' => (int)($counts['trash'] ?? 0)],
                            ];
                        ?>
                        <?php foreach ($folders as $key => $meta): ?>
                            <a href="<?= htmlspecialchars($appBase) ?>/mail/?folder=<?= urlencode($key) ?>"
                               class="nx-mail-folder-item <?= $folder === $key ? 'active' : '' ?>">
                                <span><?= htmlspecialchars($meta['label']) ?></span>
                                <?php if ($meta['count'] > 0): ?>
                                    <span class="nx-mail-folder-badge"><?= $meta['count'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="nx-mail-security-card">
                        <div class="nx-mail-sec-title">Zero-Trust Containment</div>
                        <p class="nx-mail-sec-copy">
                            Every email is scanned by <strong>Nexus AV</strong> and opened inside a temporary sandbox jail under
                            <code>storage/cache/jails/</code> that destroys itself on close.
                        </p>
                    </div>
                </aside>

                <!-- Center Message List -->
                <section class="nx-mail-list-pane" aria-label="Message List">
                    <div class="nx-mail-list-header">
                        <span class="nx-mail-folder-title"><?= htmlspecialchars(ucfirst($folder)) ?></span>
                        <span class="small text-muted"><?= count($messages) ?> <?= count($messages) === 1 ? 'message' : 'messages' ?></span>
                    </div>

                    <div class="nx-mail-rows" id="nxMailRows">
                        <?php if (empty($messages)): ?>
                            <div class="nx-mail-empty">
                                No messages in <strong><?= htmlspecialchars(ucfirst($folder)) ?></strong><?= $search !== '' ? ' matching your search' : '' ?>.
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <?php
                                    $isSelected = $activeMessage !== null && ($activeMessage['id'] ?? '') === ($m['id'] ?? '');
                                    $scanStatus = strtolower((string)($m['scan_status'] ?? 'clean'));
                                    $displayParty = ($folder === 'sent' || $folder === 'drafts')
                                        ? ('To: ' . ($m['to_address'] ?: '(No recipient)'))
                                        : (($m['from_name'] ?: $m['from_address']));
                                    $snippet = mb_strimwidth(trim(preg_replace('/\s+/', ' ', (string)($m['body_text'] ?? ''))), 0, 90, '...');
                                ?>
                                <div class="nx-mail-row <?= empty($m['is_read']) ? 'is-unread' : '' ?> <?= $isSelected ? 'is-active' : '' ?>"
                                     data-msg-id="<?= htmlspecialchars($m['id']) ?>"
                                     data-folder="<?= htmlspecialchars($m['folder']) ?>"
                                     role="button"
                                     tabindex="0">
                                    <button type="button"
                                            class="nx-mail-star-btn <?= !empty($m['is_starred']) ? 'is-starred' : '' ?>"
                                            data-star-id="<?= htmlspecialchars($m['id']) ?>"
                                            title="Toggle Star"
                                            aria-label="Toggle Star">★</button>

                                    <div class="nx-mail-row-main">
                                        <div class="nx-mail-row-top">
                                            <span class="nx-mail-sender"><?= htmlspecialchars($displayParty) ?></span>
                                            <span class="nx-mail-scan-pill nx-scan-<?= htmlspecialchars($scanStatus) ?>">
                                                <?= htmlspecialchars(strtoupper($scanStatus)) ?>
                                            </span>
                                        </div>
                                        <div class="nx-mail-subject"><?= htmlspecialchars($m['subject']) ?></div>
                                        <div class="nx-mail-snippet"><?= htmlspecialchars($snippet) ?></div>
                                    </div>

                                    <div class="nx-mail-row-time">
                                        <?= htmlspecialchars(substr((string)($m['created_at'] ?? ''), 5, 11)) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Right Controlled Ephemeral Reader Jail Pane -->
                <section class="nx-mail-reader-pane" id="nxMailReaderPane" aria-label="Controlled Ephemeral Reader Jail">
                    <div class="nx-mail-reader-placeholder <?= $activeMessage !== null ? 'hidden' : '' ?>" id="nxMailPlaceholder">
                        <div class="nx-mail-placeholder-icon">🔒</div>
                        <h2 class="h5 mb-2">Controlled Ephemeral Reader Jail</h2>
                        <p class="text-muted small">
                            Select any email from the list to launch an isolated zero-privilege sandbox container.
                            Scripts, trackers, forms, and hazardous payloads remain locked out, and the container is shredded as soon as you close the message.
                        </p>
                    </div>

                    <div class="nx-mail-reader-active <?= $activeMessage === null ? 'hidden' : '' ?>" id="nxMailReaderActive">
                        <div class="nx-mail-jail-banner">
                            <div class="nx-mail-jail-meta">
                                <span class="nx-mail-jail-dot"></span>
                                <strong>Reader Jail Active:</strong>
                                <code id="nxJailIdBadge"><?= htmlspecialchars($activeJail['jail_id'] ?? '') ?></code>
                                <span class="nx-mail-scan-pill nx-scan-<?= htmlspecialchars($activeMessage['scan_status'] ?? 'clean') ?>" id="nxReaderScanPill">
                                    AV: <?= htmlspecialchars(strtoupper((string)($activeMessage['scan_status'] ?? 'clean'))) ?>
                                </span>
                            </div>

                            <div class="nx-mail-jail-actions">
                                <button type="button" class="btn btn-secondary btn-xs" id="btnReplyMail">Reply</button>
                                <button type="button" class="btn btn-secondary btn-xs" id="btnTrashMail">
                                    <?= $folder === 'trash' ? 'Delete Forever' : 'Trash' ?>
                                </button>
                                <button type="button" class="btn btn-danger btn-xs" id="btnCloseJail">End Jail &amp; Close</button>
                            </div>
                        </div>

                        <div class="nx-mail-message-head">
                            <h2 class="h5 mb-2" id="nxReaderSubject"><?= htmlspecialchars($activeMessage['subject'] ?? '') ?></h2>
                            <div class="nx-mail-parties small text-muted">
                                <div><strong>From:</strong> <span id="nxReaderFrom"><?= htmlspecialchars(($activeMessage['from_name'] ?? '') . ' <' . ($activeMessage['from_address'] ?? '') . '>') ?></span></div>
                                <div><strong>To:</strong> <span id="nxReaderTo"><?= htmlspecialchars($activeMessage['to_address'] ?? '') ?></span></div>
                                <div><strong>Time:</strong> <span id="nxReaderTime"><?= htmlspecialchars($activeMessage['created_at'] ?? '') ?></span></div>
                            </div>
                        </div>

                        <div class="nx-mail-iframe-wrap">
                            <iframe
                                id="nxMailJailFrame"
                                class="nx-mail-jail-frame"
                                sandbox=""
                                title="Sandboxed Email Reader Jail"
                                srcdoc="<?= htmlspecialchars($activeJail['sandbox_html'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            ></iframe>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Compose / Reply Modal -->
            <div class="nx-mail-modal" id="composeModal" aria-hidden="true">
                <div class="nx-mail-modal-card">
                    <div class="nx-mail-modal-head">
                        <h3 class="h5 mb-0">New Message <span class="small text-muted fw-normal">(AV Scanned on Send)</span></h3>
                        <button type="button" class="btn btn-secondary btn-xs" id="btnCloseCompose">✕</button>
                    </div>
                    <form method="POST" action="<?= htmlspecialchars($appBase) ?>/mail/send" id="composeForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="mb-3">
                            <label for="composeTo" class="nx-mail-label">To</label>
                            <input type="email" id="composeTo" name="to" class="form-input" placeholder="colleague@<?= htmlspecialchars($mailDomain) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="composeSubject" class="nx-mail-label">Subject</label>
                            <input type="text" id="composeSubject" name="subject" class="form-input" placeholder="Message subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="composeBody" class="nx-mail-label">Message</label>
                            <textarea id="composeBody" name="body" rows="7" class="form-input" placeholder="Write your message..." required></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <button type="submit" name="_action" value="draft" class="btn btn-secondary" formnovalidate>Save Draft</button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary" id="btnCancelCompose">Cancel</button>
                                <button type="submit" name="_action" value="send" class="btn btn-primary">Scan &amp; Send</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script nonce="<?= htmlspecialchars($nonce ?? '') ?>">
    (function() {
        const shell = document.querySelector('.nx-mail-shell');
        if (!shell) return;

        const appBase = shell.dataset.appBase || '';
        const csrf = shell.dataset.csrf || '';
        const folder = shell.dataset.folder || 'inbox';

        let activeJailId = shell.dataset.activeJailId || '';
        let activeMessage = null;

        const placeholderEl = document.getElementById('nxMailPlaceholder');
        const readerActiveEl = document.getElementById('nxMailReaderActive');
        const jailIdBadge = document.getElementById('nxJailIdBadge');
        const readerScanPill = document.getElementById('nxReaderScanPill');
        const readerSubject = document.getElementById('nxReaderSubject');
        const readerFrom = document.getElementById('nxReaderFrom');
        const readerTo = document.getElementById('nxReaderTo');
        const readerTime = document.getElementById('nxReaderTime');
        const jailFrame = document.getElementById('nxMailJailFrame');

        const composeModal = document.getElementById('composeModal');
        const composeTo = document.getElementById('composeTo');
        const composeSubject = document.getElementById('composeSubject');
        const composeBody = document.getElementById('composeBody');

        function setModalOpen(isOpen) {
            if (!composeModal) return;
            composeModal.classList.toggle('show', isOpen);
            composeModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }

        const btnOpenCompose = document.getElementById('btnOpenCompose');
        const btnCloseCompose = document.getElementById('btnCloseCompose');
        const btnCancelCompose = document.getElementById('btnCancelCompose');

        if (btnOpenCompose) {
            btnOpenCompose.addEventListener('click', function() {
                composeTo.value = '';
                composeSubject.value = '';
                composeBody.value = '';
                setModalOpen(true);
            });
        }
        if (btnCloseCompose) btnCloseCompose.addEventListener('click', () => setModalOpen(false));
        if (btnCancelCompose) btnCancelCompose.addEventListener('click', () => setModalOpen(false));

        async function closeActiveJail(useKeepalive) {
            if (!activeJailId) return;
            const targetId = activeJailId;
            activeJailId = '';
            shell.dataset.activeJailId = '';

            if (jailFrame) jailFrame.srcdoc = '';
            if (readerActiveEl) readerActiveEl.classList.add('hidden');
            if (placeholderEl) placeholderEl.classList.remove('hidden');

            document.querySelectorAll('.nx-mail-row.is-active').forEach(r => r.classList.remove('is-active'));

            try {
                await fetch(appBase + '/api/v1/mail/jail/' + encodeURIComponent(targetId) + '/close', {
                    method: 'POST',
                    keepalive: Boolean(useKeepalive),
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ csrf_token: csrf })
                });
            } catch (e) {}
        }

        async function openMessageInJail(msgId, rowEl) {
            if (activeJailId) {
                await closeActiveJail(false);
            }

            try {
                const res = await fetch(appBase + '/api/v1/mail/messages/' + encodeURIComponent(msgId) + '/open-jail', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ csrf_token: csrf })
                });

                const json = await res.json();
                if (!res.ok || !json.data || !json.data.jail) {
                    return;
                }

                const payload = json.data;
                activeMessage = payload.message;
                activeJailId = payload.jail.jail_id;
                shell.dataset.activeJailId = activeJailId;

                document.querySelectorAll('.nx-mail-row.is-active').forEach(r => r.classList.remove('is-active'));
                if (rowEl) {
                    rowEl.classList.add('is-active');
                    rowEl.classList.remove('is-unread');
                }

                const scanStatus = String(payload.scan?.status || payload.message?.scan_status || 'clean').toLowerCase();
                jailIdBadge.textContent = activeJailId;
                readerScanPill.textContent = 'AV: ' + scanStatus.toUpperCase();
                readerScanPill.className = 'nx-mail-scan-pill nx-scan-' + scanStatus;
                readerSubject.textContent = payload.message.subject || '(No Subject)';
                readerFrom.textContent = (payload.message.from_name || '') + ' <' + (payload.message.from_address || '') + '>';
                readerTo.textContent = payload.message.to_address || '';
                readerTime.textContent = payload.message.created_at || '';
                jailFrame.srcdoc = payload.jail.sandbox_html || '';

                placeholderEl.classList.add('hidden');
                readerActiveEl.classList.remove('hidden');
            } catch (e) {}
        }

        document.querySelectorAll('.nx-mail-row').forEach(function(row) {
            row.addEventListener('click', function() {
                const msgId = row.dataset.msgId;
                if (msgId) openMessageInJail(msgId, row);
            });
            row.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const msgId = row.dataset.msgId;
                    if (msgId) openMessageInJail(msgId, row);
                }
            });
        });

        document.querySelectorAll('.nx-mail-star-btn').forEach(function(btn) {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const id = btn.dataset.starId;
                if (!id) return;
                try {
                    const res = await fetch(appBase + '/api/v1/mail/messages/' + encodeURIComponent(id), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrf
                        },
                        body: JSON.stringify({ _action: 'toggle_star', csrf_token: csrf })
                    });
                    const json = await res.json();
                    if (res.ok && json.data && json.data.message) {
                        btn.classList.toggle('is-starred', Boolean(json.data.message.is_starred));
                    }
                } catch (err) {}
            });
        });

        const btnCloseJail = document.getElementById('btnCloseJail');
        if (btnCloseJail) {
            btnCloseJail.addEventListener('click', function() {
                closeActiveJail(false);
            });
        }

        const btnReplyMail = document.getElementById('btnReplyMail');
        if (btnReplyMail) {
            btnReplyMail.addEventListener('click', function() {
                if (!activeMessage) return;
                composeTo.value = activeMessage.from_address || '';
                const subj = activeMessage.subject || '';
                composeSubject.value = /^re:/i.test(subj) ? subj : ('Re: ' + subj);
                composeBody.value = "\n\n--- On " + (activeMessage.created_at || '') + ", " + (activeMessage.from_address || '') + " wrote ---\n" + (activeMessage.body_text || '');
                setModalOpen(true);
            });
        }

        const btnTrashMail = document.getElementById('btnTrashMail');
        if (btnTrashMail) {
            btnTrashMail.addEventListener('click', async function() {
                const msgId = activeMessage?.id || '';
                if (!msgId) return;
                const action = folder === 'trash' ? 'delete_forever' : 'trash';
                await closeActiveJail(false);
                try {
                    await fetch(appBase + '/api/v1/mail/messages/' + encodeURIComponent(msgId), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrf
                        },
                        body: JSON.stringify({ _action: action, csrf_token: csrf })
                    });
                    window.location.href = appBase + '/mail/?folder=' + encodeURIComponent(folder);
                } catch (err) {}
            });
        }

        // Shred active ephemeral reader jail when navigating away or closing tab
        window.addEventListener('pagehide', function() {
            if (activeJailId) {
                closeActiveJail(true);
            }
        });
    })();
    </script>
</body>
</html>
