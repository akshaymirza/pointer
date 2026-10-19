<?php
// includes/sidebar.php
require_once __DIR__ . '/auth.php';

if (!isset($currentPage)) {
    $currentPage = 'workspace';
}

$user = getCurrentUser();
$isLogged = isLoggedIn();
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-left">
      <div class="brand-mark">D</div>
      <div class="brand-info">
        <strong>Dynamic Parser</strong>
        <span><?php echo $isLogged ? 'Admin Console' : 'Public Workspace'; ?></span>
      </div>
    </div>

    <!-- Tombol tutup khusus saat sidebar terbuka -->
    <button
      type="button"
      class="close-sidebar-btn"
      id="closeSidebar"
      aria-label="Close sidebar"
      title="Tutup sidebar"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>

  <!-- Tombol buka kembali saat sidebar diciutkan (desktop) -->
  <button
    type="button"
    class="expand-sidebar-btn"
    id="expandSidebar"
    aria-label="Expand sidebar"
    title="Buka sidebar"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="9 6 15 12 9 18"></polyline>
    </svg>
  </button>

  <nav class="nav-list" aria-label="Main navigation">
    <?php if ($isLogged): ?>
      <a href="dashboard.php" class="nav-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" data-page="admin">
        <span class="icon" aria-hidden="true">H</span>
        <span>Templates</span>
      </a>

      <a href="editor.php" class="nav-item <?php echo $currentPage === 'editor' ? 'active' : ''; ?>" data-page="editor">
        <span class="icon" aria-hidden="true">+</span>
        <span>Create Template</span>
      </a>

      <a href="voucher_settings.php" class="nav-item <?php echo ($currentPage === 'voucher_settings') ? 'active' : ''; ?>" data-page="voucher_settings">
        <span class="icon" aria-hidden="true">⚙️</span>
        <span>Voucher Settings</span>
      </a>
    <?php endif; ?>

    <a href="index.php" class="nav-item <?php echo ($currentPage === 'index' || $currentPage === 'workspace') ? 'active' : ''; ?>" data-page="workspace">
      <span class="icon" aria-hidden="true">🔍</span>
      <span>Checking mode</span>
    </a>

    <a href="redeem.php" class="nav-item <?php echo ($currentPage === 'redeem') ? 'active' : ''; ?>" data-page="redeem">
      <span class="icon" aria-hidden="true">🎟️</span>
      <span>Redeeming mode</span>
    </a>

    <?php if ($isLogged): ?>
      <a href="logout.php" class="nav-item nav-logout" style="margin-top: 24px; color: var(--danger);">
        <span class="icon" aria-hidden="true">X</span>
        <span>Logout</span>
      </a>
    <?php else: ?>
      <a href="login.php" class="nav-item" style="margin-top: 24px; color: var(--primary);">
        <span class="icon" aria-hidden="true">L</span>
        <span>Login Admin</span>
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <span class="status-dot"></span>
    <span>Backend Proxy Ready</span>
  </div>
</aside>

<main class="main">
  <header class="topbar">
    <button class="icon-button" id="menuToggle" aria-label="Toggle sidebar">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>
    <div>
      <p class="eyebrow">Internal Operations</p>
      <h1 id="pageTitle"><?php echo htmlspecialchars($pageHeading ?? 'Workspace'); ?></h1>
    </div>
    <div class="profile-chip">
      <?php if ($isLogged): ?>
        <span style="font-weight: 600;"><?php echo htmlspecialchars($user['full_name'] ?? 'Administrator'); ?></span>
        <span class="role-badge" style="font-size: 11px; padding: 2px 8px; border-radius: 99px; background: #eff6ff; color: var(--primary); font-weight: bold; text-transform: uppercase;">
          ADMIN
        </span>
        <div class="avatar">AD</div>
      <?php else: ?>
        <span style="font-weight: 600;">Guest User</span>
        <span class="role-badge" style="font-size: 11px; padding: 2px 8px; border-radius: 99px; background: #f1f5f9; color: #64748b; font-weight: bold; text-transform: uppercase;">
          PUBLIC
        </span>
        <div class="avatar" style="background:#64748b;">GU</div>
      <?php endif; ?>
    </div>
  </header>
