<?php
/**
 * Navbar Component
 * Accepts $pageTitle, user session details, and API connection status
 */
$currentPage = $currentPage ?? 'dashboard';
$pageTitle = $pageTitle ?? ucfirst($currentPage);

// Map page icons
$pageIconMap = [
    'dashboard' => 'assets/images/icon-dashboard.svg',
    'bookings' => 'assets/images/icon-bookings.svg',
    'transactions' => 'assets/images/icon-transactions.svg',
    'stylist' => 'assets/images/icon-stylist.svg',
];
$pageIcon = $pageIcon ?? ($pageIconMap[$currentPage] ?? 'assets/images/icon-dashboard.svg');

// Dynamic Salon Profile & User details fallback
$activeProfile = $salonProfile ?? $_SESSION['salon_data'] ?? [];

$userName = $userName ?? ($activeProfile['ownerName'] ?? $_SESSION['salon_data']['ownerName'] ?? 'Sumithra');
$userEmail = $userEmail ?? ($activeProfile['email'] ?? $_SESSION['salon_data']['email'] ?? 'cutncurl85@gmail.com');

if (!isset($userAvatar)) {
    $rawImg = $activeProfile['image'] ?? $_SESSION['salon_data']['image'] ?? null;
    if (!empty($rawImg)) {
        $userAvatar = str_starts_with($rawImg, 'http') ? $rawImg : 'https://api.Scuts.in/' . ltrim($rawImg, '/');
    } else {
        $userAvatar = 'assets/images/user-avatar.png';
    }
}

if (!isset($currentBalance)) {
    $rawBal = $activeProfile['walletBalance'] ?? $_SESSION['salon_data']['walletBalance'] ?? 6349;
    $currentBalance = '₹ ' . number_format((float)$rawBal, 2);
}

if (!isset($isApiConnected)) {
    $isApiConnected = !empty($_SESSION['access_token']) || (defined('AUTH_TOKEN') && !empty(AUTH_TOKEN));
}
?>
<header class="top-navbar" role="banner">
  <div class="navbar-left">
    <!-- Hamburger button for mobile/tablet -->
    <button class="menu-toggle-btn" id="menuToggleBtn" aria-label="Open Navigation Menu" aria-controls="sidebar">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>

    <!-- Page Title with Icon -->
    <div class="page-title-wrap">
      <span class="page-title-icon" aria-hidden="true">
        <img src="<?= htmlspecialchars($pageIcon) ?>" alt="" width="20" height="20" />
      </span>
      <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <!-- API Status Badge -->
    <div class="api-status-pill <?= $isApiConnected ? 'api-status-online' : 'api-status-demo' ?>" title="<?= $isApiConnected ? 'Connected to ScutS API' : 'Using Demo Mock Data (Configure AUTH_TOKEN in config.php for live API)' ?>">
      <span class="status-dot"></span>
      <span class="status-text"><?= $isApiConnected ? 'API Live' : 'Demo Mode' ?></span>
    </div>
  </div>

  <div class="navbar-right">
    <!-- Current Balance Pill -->
    <div class="balance-badge" title="Your Current Balance">
      <div class="balance-icon-circle" aria-hidden="true">
        <img src="assets/images/icon-balance.svg" alt="" width="20" height="20" />
      </div>
      <div class="balance-details">
        <span class="balance-amount"><?= htmlspecialchars($currentBalance) ?></span>
        <span class="balance-label">Current Balance</span>
      </div>
    </div>

    <!-- User Profile Dropdown -->
    <div class="user-profile-menu" id="userProfileMenu">
      <button class="user-profile-btn" id="userProfileBtn" aria-expanded="false" aria-haspopup="true">
        <div class="user-avatar-wrap">
          <img src="<?= htmlspecialchars($userAvatar) ?>" alt="<?= htmlspecialchars($userName) ?>" class="user-avatar-img" width="39" height="39" onerror="this.src='assets/images/user-avatar.png'" />
        </div>
        <div class="user-info-text">
          <span class="user-name"><?= htmlspecialchars($userName) ?></span>
          <span class="user-email"><?= htmlspecialchars($userEmail) ?></span>
        </div>
        <span class="dropdown-chevron" aria-hidden="true">
          <img src="assets/images/icon-chevron-down.svg" alt="" width="20" height="20" />
        </span>
      </button>

      <!-- Dropdown Popup -->
      <div class="user-dropdown-card" id="userDropdownCard">
        <div class="dropdown-header">
          <strong><?= htmlspecialchars($userName) ?></strong>
          <small><?= htmlspecialchars($userEmail) ?></small>
        </div>
        <ul class="dropdown-list">
          <li><a href="#profile"><span class="icon">👤</span> My Profile</a></li>
          <li><a href="#settings"><span class="icon">⚙️</span> Account Settings</a></li>
          <li class="divider"></li>
          <li><a href="logout.php" class="text-danger"><span class="icon">🚪</span> Sign Out</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
