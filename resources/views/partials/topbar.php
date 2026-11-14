<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Universal Light-Theme Navigation Top Bar & Dynamic User Dropdown
 */

$user = $user ?? $currentUser ?? null;
if ($user !== null && is_array($user)) {
    $isLoggedIn = !empty($user['id']);
    $userEmail = (string)($user['email'] ?? '');
    $userName = (string)($user['name'] ?? 'User');
    $userUsername = (string)($user['username'] ?? '');
    $userDisplayPref = (string)($user['display_preference'] ?? 'name');
    $userRoles = (array)($user['roles'] ?? ['Member']);
    $userGroups = (array)($user['groups'] ?? []);
    $userAvatar = (string)($user['avatar_url'] ?? '');
} else {
    if (session_status() !== PHP_SESSION_ACTIVE && isset($_COOKIE['nexus_session'])) {
        @session_start();
    }
    $isLoggedIn = !empty($_SESSION['user_id']);
    $userEmail = (string)($_SESSION['user_email'] ?? '');
    $userName = (string)($_SESSION['user_name'] ?? 'User');
    $userUsername = (string)($_SESSION['user_username'] ?? '');
    $userDisplayPref = (string)($_SESSION['user_display_preference'] ?? 'name');
    $userRoles = (array)($_SESSION['user_roles'] ?? ['Member']);
    $userGroups = (array)($_SESSION['user_groups'] ?? []);
    $userAvatar = (string)($_SESSION['user_avatar'] ?? '');
}

$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();

// Determine display name based on user's preference ("let the user decide")
$primaryDisplay = ($userDisplayPref === 'username' && $userUsername !== '') ? $userUsername : $userName;
$secondaryDisplay = ($userDisplayPref === 'username' && $userUsername !== '') ? $userName : ($userUsername !== '' ? '@' . $userUsername : $userEmail);

// Format user groups & roles for sub-header
$rolesStr = implode(', ', $userRoles);
$groupsStr = !empty($userGroups) ? implode(', ', $userGroups) : 'General';
$subHeaderText = $rolesStr . ($groupsStr !== '' ? ' • ' . $groupsStr : '');

// Compute initials for avatar fallback
$initials = '';
$nameParts = explode(' ', trim($userName));
if (!empty($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1 && !empty($nameParts[count($nameParts) - 1])) {
    $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
}
if ($initials === '') $initials = 'U';

// Check role permissions for menus
$isSuperAdmin = in_array('SuperAdmin', $userRoles, true);
$isAdmin = $isSuperAdmin || in_array('Admin', $userRoles, true);
$isSecOps = $isSuperAdmin || in_array('SecurityOfficer', $userRoles, true) || in_array('SecOps', $userGroups, true);
$isDeveloper = $isSuperAdmin || in_array('Developer', $userRoles, true) || in_array('Engineering', $userGroups, true);
$hasMailAccess = $isAdmin || $isSecOps || !empty($user['mail_access']) || !empty($_SESSION['mail_access']);
if (!$hasMailAccess && $isLoggedIn) {
    $topbarUid = (string)($user['id'] ?? $_SESSION['user_id'] ?? '');
    $overlayFile = dirname(__DIR__, 3) . '/storage/app/private/mail/user_mail_permissions.json';
    if ($topbarUid !== '' && is_file($overlayFile)) {
        $overlayMap = json_decode((string)file_get_contents($overlayFile), true);
        if (is_array($overlayMap) && !empty($overlayMap[$topbarUid]['mail_access'])) {
            $hasMailAccess = true;
        }
    }
}
?>

<link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appBase) ?>/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($appBase) ?>/brand/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($appBase) ?>/brand/apple-touch-icon.png">
<link rel="manifest" href="<?= htmlspecialchars($appBase) ?>/brand/site.webmanifest">
<link rel="stylesheet" href="<?= htmlspecialchars($appBase) ?>/css/theme.css?v=<?= file_exists(dirname(__DIR__, 2) . '/css/theme.css') ? filemtime(dirname(__DIR__, 2) . '/css/theme.css') : time() ?>">
<script nonce="<?= $nonce ?? '' ?>">
(function(){
    document.documentElement.dataset.theme = 'light';
    try { localStorage.setItem('nx-th', 'light'); } catch(e){}
})();
</script>

<header class="nexus-topbar">
    <div class="nexus-topbar-inner">
        <!-- Brand / Logo on Left -->
        <a href="<?= $appBase ?>/" class="nexus-brand" title="Dreambox Nexus Application Gateway">
            <div class="nexus-logo-icon">
                <img src="<?= htmlspecialchars($appBase) ?>/brand/logo-64.png" alt="Dreambox Nexus Logo" class="nexus-brand-img">
            </div>
            <div class="nexus-brand-text">
                <span class="nexus-brand-title">Dreambox Nexus</span>
                <span class="nexus-brand-badge">Core v0.1 Alpha</span>
            </div>
        </a>

        <!-- Right Side: Login or User Dropdown -->
        <div class="nexus-topbar-right">
            <?php if (!$isLoggedIn): ?>
                <!-- When Logged Out: Login Action -->
                <a href="<?= $appBase ?>/auth/login/" class="nexus-btn-login">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Log In</span>
                </a>
            <?php else: ?>
                <!-- When Logged In: Dynamic User Dropdown -->
                <div class="nexus-user-menu" id="nexusUserMenu">
                    <div class="nexus-user-trigger" id="nexusUserTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" title="User Menu">
                        <div class="nexus-avatar">
                            <?php if ($userAvatar !== ''): ?>
                                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="<?= htmlspecialchars($primaryDisplay) ?>" class="nexus-avatar-img">
                            <?php else: ?>
                                <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <div class="nexus-trigger-info">
                            <span class="nexus-trigger-name"><?= htmlspecialchars($primaryDisplay) ?></span>
                        </div>
                        <svg class="nexus-trigger-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>

                    <!-- Dropdown Content -->
                    <div class="nexus-dropdown" id="nexusDropdown" role="menu">
                        <!-- Dropdown Header: Name or Username + Smaller font Groups/Roles -->
                        <div class="nexus-dropdown-header">
                            <div class="nexus-dropdown-title">
                                <span><?= htmlspecialchars($primaryDisplay) ?></span>
                                <span class="nexus-role-pill"><?= htmlspecialchars($userRoles[0] ?? 'Member') ?></span>
                            </div>
                            <div class="nexus-dropdown-sub" title="Assigned Roles & Groups">
                                <?= htmlspecialchars($subHeaderText) ?>
                            </div>
                        </div>

                        <!-- Group's & Role's Dynamic Navigation Menus -->
                        <?php if ($isAdmin): ?>
                            <div class="nexus-dropdown-section">
                                <div class="nexus-section-label">Administration</div>
                                <a href="<?= $appBase ?>/admin/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    <span>Admin Area</span>
                                </a>
                            </div>
                            <div class="nexus-divider"></div>
                        <?php endif; ?>

                        <?php if ($isSecOps): ?>
                            <div class="nexus-dropdown-section">
                                <div class="nexus-section-label">SecOps Telemetry</div>
                                <a href="<?= $appBase ?>/nexus/monitor/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                    <span>Security Cockpit</span>
                                </a>
                                <a href="<?= $appBase ?>/compliance/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>Compliance Matrix</span>
                                </a>
                            </div>
                            <div class="nexus-divider"></div>
                        <?php endif; ?>

                        <?php if ($isDeveloper): ?>
                            <div class="nexus-dropdown-section">
                                <div class="nexus-section-label">Developer Suite</div>
                                <a href="<?= $appBase ?>/ui/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                    <span>Nexus UI Framework</span>
                                </a>
                                <a href="<?= $appBase ?>/docs/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                    <span>OpenAPI Contract</span>
                                </a>
                            </div>
                            <div class="nexus-divider"></div>
                        <?php endif; ?>

                        <!-- Link to Nexus Webmail & Accounts Page -->
                        <?php if ($hasMailAccess): ?>
                            <div class="nexus-dropdown-section">
                                <div class="nexus-section-label">Messaging &amp; Containment</div>
                                <a href="<?= $appBase ?>/mail/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                    <span>Nexus Webmail</span>
                                </a>
                            </div>
                            <div class="nexus-divider"></div>
                        <?php endif; ?>

                        <div class="nexus-dropdown-section">
                            <a href="<?= $appBase ?>/account/" class="nexus-menu-item" role="menuitem">
                                <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <span>Account & Password</span>
                            </a>
                        </div>
                        <div class="nexus-divider"></div>

                        <!-- Logout Button at the bottom -->
                        <div class="nexus-dropdown-section">
                            <a href="<?= $appBase ?>/auth/logout/" class="nexus-menu-item nexus-menu-logout" role="menuitem">
                                <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<script nonce="<?= $nonce ?? '' ?>">
(function() {
    const trigger = document.getElementById('nexusUserTrigger');
    const dropdown = document.getElementById('nexusDropdown');
    if (!trigger || !dropdown) return;

    function toggleDropdown(e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains('show');
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    function openDropdown() {
        dropdown.classList.add('show');
        trigger.classList.add('active');
        trigger.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        dropdown.classList.remove('show');
        trigger.classList.remove('active');
        trigger.setAttribute('aria-expanded', 'false');
    }

    trigger.addEventListener('click', toggleDropdown);
    trigger.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleDropdown(e);
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    document.addEventListener('click', function(e) {
        if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDropdown();
        }
    });
})();
</script>

