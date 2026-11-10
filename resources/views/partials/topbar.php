<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Universal Light-Theme Navigation Top Bar & Dynamic User Dropdown
 */

if (session_status() !== PHP_SESSION_ACTIVE && isset($_COOKIE['nexus_session'])) {
    @session_start();
}

$appBase = $appBase ?? \Nexus\Core\Request::getAppBase();
$isLoggedIn = !empty($_SESSION['user_id']);
$userEmail = $_SESSION['user_email'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';
$userUsername = $_SESSION['user_username'] ?? '';
$userDisplayPref = $_SESSION['user_display_preference'] ?? 'name';
$userRoles = (array)($_SESSION['user_roles'] ?? ['Member']);
$userGroups = (array)($_SESSION['user_groups'] ?? []);
$userAvatar = $_SESSION['user_avatar'] ?? '';

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
?>

<style>
/* ── Universal Top Bar Design System (Smooth Light Aesthetics) ── */
.nexus-topbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: #0f172a;
    transition: all 0.2s ease;
}

.nexus-topbar-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0.75rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
}

/* Left Brand & Logo */
.nexus-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: #0f172a;
}

.nexus-logo-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0284c7 0%, #2563eb 50%, #4f46e5 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    flex-shrink: 0;
}

.nexus-brand-text {
    display: flex;
    flex-direction: column;
}

.nexus-brand-title {
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
    line-height: 1.2;
}

.nexus-brand-badge {
    font-size: 0.68rem;
    font-weight: 600;
    color: #0284c7;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

/* Right Side Controls */
.nexus-topbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Logged Out State: Login Button */
.nexus-btn-login {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #0284c7;
    color: #ffffff !0important;
    text-decoration: none !important;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.5rem 1.25rem;
    border-radius: 9999px;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
    transition: all 0.2s ease;
    border: none;
}

.nexus-btn-login:hover {
    background: #0369a1;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
    transform: translateY(-1px);
}

/* Logged In State: User Menu Dropdown Container */
.nexus-user-menu {
    position: relative;
}

.nexus-user-trigger {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 0.35rem 0.85rem 0.35rem 0.45rem;
    border-radius: 9999px;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    user-select: none;
}

.nexus-user-trigger:hover,
.nexus-user-trigger.active {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.nexus-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0284c7, #3b82f6);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.nexus-trigger-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.nexus-trigger-name {
    font-size: 0.825rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
    max-width: 140px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nexus-trigger-chevron {
    color: #94a3b8;
    transition: transform 0.2s ease;
}

.nexus-user-trigger.active .nexus-trigger-chevron {
    transform: rotate(180deg);
}

/* Dropdown Card */
.nexus-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    width: 300px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
    padding: 0.5rem;
    z-index: 1000;
    animation: nexusFadeIn 0.15s ease-out;
}

.nexus-dropdown.show {
    display: block;
}

@keyframes nexusFadeIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Dropdown Header */
.nexus-dropdown-header {
    padding: 0.75rem 0.85rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 0.4rem;
}

.nexus-dropdown-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.nexus-dropdown-sub {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    margin-top: 3px;
    line-height: 1.3;
}

.nexus-role-pill {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.03em;
}

/* Menu Sections */
.nexus-dropdown-section {
    padding: 0.35rem 0;
}

.nexus-section-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.35rem 0.85rem 0.2rem;
}

.nexus-menu-item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.5rem 0.85rem;
    border-radius: 8px;
    color: #334155;
    text-decoration: none !important;
    font-size: 0.825rem;
    font-weight: 500;
    transition: all 0.15s ease;
}

.nexus-menu-item:hover {
    background: #f1f5f9;
    color: #0284c7;
}

.nexus-menu-icon {
    width: 16px;
    height: 16px;
    color: #64748b;
    flex-shrink: 0;
}

.nexus-menu-item:hover .nexus-menu-icon {
    color: #0284c7;
}

.nexus-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 0.35rem 0;
}

/* Logout Button */
.nexus-menu-logout {
    color: #e11d48 !important;
}

.nexus-menu-logout:hover {
    background: #ffe4e6 !important;
    color: #be123c !important;
}

.nexus-menu-logout:hover .nexus-menu-icon {
    color: #be123c !important;
}
</style>

<header class="nexus-topbar">
    <div class="nexus-topbar-inner">
        <!-- Brand / Logo on Left -->
        <a href="<?= $appBase ?>/" class="nexus-brand" title="Dreambox Nexus Application Gateway">
            <div class="nexus-logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
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
                        <div class="nexus-avatar"><?= htmlspecialchars($initials) ?></div>
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
                                <div class="nexus-section-label">IAM Administration</div>
                                <a href="<?= $appBase ?>/admin/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    <span>IAM Console</span>
                                </a>
                                <a href="<?= $appBase ?>/admin/users/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <span>User Management</span>
                                </a>
                                <a href="<?= $appBase ?>/admin/roles/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <span>Roles & Permissions</span>
                                </a>
                                <a href="<?= $appBase ?>/admin/groups/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                    <span>Groups Management</span>
                                </a>
                                <a href="<?= $appBase ?>/admin/audit/" class="nexus-menu-item" role="menuitem">
                                    <svg class="nexus-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <span>Audit Hash Chain</span>
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

                        <!-- Link to Accounts Page ("where they can edit there password and update there info") -->
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
