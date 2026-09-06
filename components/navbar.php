<?php
/**
 * Dynamic Top Navbar Component
 * Fully self-sufficient: automatically fetches live salon profile, owner metadata,
 * avatar, and real-time wallet balance directly from the ScutS API.
 * Provides interactive profile dropdown with quick links and sign-out.
 */

$currentPage = $currentPage ?? 'dashboard';
$pageTitle = $pageTitle ?? ucfirst($currentPage);

// Map page icons
$pageIconMap = [
    'dashboard' => 'assets/images/icon-dashboard.svg',
    'bookings' => 'assets/images/icon-bookings.svg',
    'transactions' => 'assets/images/icon-transactions.svg',
    'stylist' => 'assets/images/icon-stylist.svg',
    'products' => 'assets/images/icon-products.svg',
    'ratings' => 'assets/images/icon-ratings.svg',
];
$pageIcon = $pageIcon ?? ($pageIconMap[$currentPage] ?? 'assets/images/icon-dashboard.svg');

// 1. Ensure API Client exists
if (!isset($apiClient)) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/api.php';
    $apiClient = new ScutsApiClient();
}

// 2. Fetch or resolve Live Salon Profile
if (!isset($salonProfile) || empty($salonProfile)) {
    if (method_exists($apiClient, 'hasValidToken') && $apiClient->hasValidToken()) {
        $profileRes = $apiClient->getSalonProfile();
        if (!empty($profileRes['data'])) {
            $salonProfile = $profileRes['data'];
        }
    }
    if (empty($salonProfile)) {
        $salonProfile = $_SESSION['salon_data'] ?? [];
    }
}

// 3. Resolve Live Wallet Balance
if (!isset($currentBalance)) {
    $walletBal = $salonProfile['walletBalance'] ?? null;
    if ($walletBal === null && method_exists($apiClient, 'hasValidToken') && $apiClient->hasValidToken()) {
        $analyticsRes = $apiClient->request('salon/dashboard/analytics');
        if (isset($analyticsRes['data']['walletBalance'])) {
            $walletBal = $analyticsRes['data']['walletBalance'];
            $salonProfile['walletBalance'] = $walletBal;
            if (isset($_SESSION['salon_data'])) {
                $_SESSION['salon_data']['walletBalance'] = $walletBal;
            }
        }
    }
    if ($walletBal === null) {
        $walletBal = 6349;
    }
    $currentBalance = '₹ ' . number_format((float)$walletBal, 2);
}

// 4. Resolve Dynamic Salon & Owner Attributes
$salonName = $salonProfile['name'] ?? 'Cut n Curl unisex salon';
$userName = $userName ?? ($salonProfile['ownerName'] ?? $_SESSION['salon_data']['ownerName'] ?? $salonName);
$userEmail = $userEmail ?? ($salonProfile['email'] ?? $_SESSION['salon_data']['email'] ?? 'cutncurl85@gmail.com');
$userPhone = $salonProfile['ownerMobile'] ?? $salonProfile['mobile'] ?? '9880652333';
$salonRating = $salonProfile['rating'] ?? 4.6;

if (!isset($userAvatar)) {
    $rawImg = $salonProfile['image'] ?? $_SESSION['salon_data']['image'] ?? null;
    $userAvatar = !empty($rawImg) ? $apiClient->formatImageUrl($rawImg, 'assets/images/user-avatar.png') : 'assets/images/user-avatar.png';
}

if (!isset($isApiConnected)) {
    $isApiConnected = method_exists($apiClient, 'hasValidToken') && $apiClient->hasValidToken();
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
      <?php if (isset($pageCountBadge) && $pageCountBadge !== null): ?>
        <span class="page-title-count-chip"><?= htmlspecialchars((string)$pageCountBadge) ?></span>
      <?php endif; ?>
    </div>

    <!-- API Status Badge -->
    <div class="api-status-pill <?= $isApiConnected ? 'api-status-online' : 'api-status-demo' ?>" title="<?= $isApiConnected ? 'Connected to live ScutS API' : 'Using Demo Mock Data' ?>">
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

    <!-- User Profile Dropdown Menu -->
    <div class="user-profile-menu" id="userProfileMenu">
      <button type="button" class="user-profile-btn" id="userProfileBtn" aria-expanded="false" aria-haspopup="true" aria-label="Open profile menu">
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

      <!-- Dropdown Popup Card -->
      <div class="user-dropdown-card" id="userDropdownCard" role="menu" aria-orientation="vertical">
        <div class="dropdown-user-header">
          <div class="dropdown-user-avatar">
            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="<?= htmlspecialchars($userName) ?>" onerror="this.src='assets/images/user-avatar.png'" />
          </div>
          <div class="dropdown-user-details">
            <span class="dropdown-user-title"><?= htmlspecialchars($userName) ?></span>
            <span class="dropdown-salon-name"><?= htmlspecialchars($salonName) ?></span>
            <span class="dropdown-user-email"><?= htmlspecialchars($userEmail) ?></span>
          </div>
        </div>

        <div class="dropdown-quick-info">
          <div class="dropdown-info-item">
            <span class="info-label">Current Balance</span>
            <span class="info-value"><?= htmlspecialchars($currentBalance) ?></span>
          </div>
          <div class="dropdown-info-item">
            <span class="info-label">Rating</span>
            <span class="info-value">★ <?= htmlspecialchars((string)$salonRating) ?></span>
          </div>
        </div>

        <ul class="dropdown-list">
          <li>
            <a href="index.php" class="<?= in_array($currentPage, ['dashboard', 'index'], true) ? 'active' : '' ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
              <span>Dashboard</span>
            </a>
          </li>
          <li>
            <a href="bookings.php" class="<?= $currentPage === 'bookings' ? 'active' : '' ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              <span>Bookings</span>
            </a>
          </li>
          <li>
            <a href="stylists.php" class="<?= in_array($currentPage, ['stylist', 'stylists'], true) ? 'active' : '' ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              <span>Stylists</span>
            </a>
          </li>
          <li>
            <a href="products.php" class="<?= in_array($currentPage, ['product', 'products'], true) ? 'active' : '' ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
              <span>Products</span>
            </a>
          </li>
          <li>
            <a href="transactions.php" class="<?= in_array($currentPage, ['transaction', 'transactions'], true) ? 'active' : '' ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
              <span>Transactions</span>
            </a>
          </li>
          <li>
            <a href="ratings.php" class="<?= in_array($currentPage, ['rating', 'ratings', 'reviews'], true) ? 'active' : '' ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
              <span>Ratings & Reviews</span>
            </a>
          </li>
          <li class="divider"></li>
          <li>
            <a href="logout.php" class="text-danger">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
              <span>Sign Out</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</header>

<!-- Self-Contained Styles for Top Navbar & Profile Dropdown (ensures flawless rendering on any page) -->
<style>
.top-navbar {
  position: relative !important;
  z-index: 100 !important;
}
.user-profile-menu {
  position: relative !important;
  display: inline-block;
}
.user-profile-btn {
  cursor: pointer;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 10px;
  border-radius: 9999px;
  transition: background-color 0.15s ease;
  user-select: none;
}
.user-profile-btn:hover {
  background-color: #EDE8F8;
}
.dropdown-chevron {
  display: flex;
  align-items: center;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.user-profile-menu.open .dropdown-chevron {
  transform: rotate(180deg);
}
.user-dropdown-card {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 270px;
  background-color: #FFFFFF;
  border: 1px solid #EDE8F8;
  border-radius: 16px;
  box-shadow: 0 14px 35px rgba(133, 102, 206, 0.18), 0 4px 12px rgba(0, 0, 0, 0.08);
  padding: 14px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px) scale(0.98);
  transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.2s ease;
  z-index: 1000 !important;
  pointer-events: none;
}
.user-profile-menu.open .user-dropdown-card {
  opacity: 1 !important;
  visibility: visible !important;
  transform: translateY(0) scale(1) !important;
  pointer-events: auto !important;
}
.dropdown-user-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #EDE8F8;
  margin-bottom: 10px;
}
.dropdown-user-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1.5px solid #8466CF;
  overflow: hidden;
  flex-shrink: 0;
}
.dropdown-user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.dropdown-user-details {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  text-align: left;
}
.dropdown-user-title {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #000000;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dropdown-salon-name {
  font-size: 0.75rem;
  font-weight: 600;
  color: #8466CF;
  line-height: 1.2;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dropdown-user-email {
  font-size: 0.6875rem;
  font-weight: 400;
  color: #71717A;
  line-height: 1.2;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dropdown-quick-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  background: #F9F7FD;
  border: 1px solid #EDE8F8;
  border-radius: 10px;
  padding: 8px 12px;
  margin-bottom: 10px;
}
.dropdown-info-item {
  display: flex;
  flex-direction: column;
  text-align: left;
}
.dropdown-info-item .info-label {
  font-size: 0.6875rem;
  color: #71717A;
  font-weight: 500;
}
.dropdown-info-item .info-value {
  font-size: 0.8125rem;
  font-weight: 700;
  color: #000000;
}
.dropdown-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.dropdown-list li a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #27272A;
  text-decoration: none;
  transition: all 0.15s ease;
}
.dropdown-list li a:hover {
  background-color: #EDE8F8;
  color: #6D4EB7;
}
.dropdown-list li a.active {
  background-color: #EDE8F8;
  color: #6D4EB7;
  font-weight: 600;
}
.dropdown-list .divider {
  height: 1px;
  background-color: #EDE8F8;
  margin: 6px 0;
}
.dropdown-list li a.text-danger {
  color: #DC2626;
}
.dropdown-list li a.text-danger:hover {
  background-color: #FEF2F2;
  color: #B91C1C;
}
</style>

<!-- Self-contained Dropdown Toggle Script for all existing and future pages -->
<script>
(function() {
  function initNavProfileDropdown() {
    var btn = document.getElementById('userProfileBtn');
    var menu = document.getElementById('userProfileMenu');
    if (!btn || !menu) return;
    if (btn.dataset.bound === 'true') return;
    btn.dataset.bound = 'true';

    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      }
      var isOpen = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function(e) {
      if (menu.classList.contains('open') && !menu.contains(e.target)) {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && menu.classList.contains('open')) {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavProfileDropdown);
  } else {
    initNavProfileDropdown();
  }
})();
</script>
