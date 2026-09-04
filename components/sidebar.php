<?php
/**
 * Sidebar Component
 * Active page can be passed via $currentPage variable
 */
$currentPage = $currentPage ?? 'dashboard';
?>
<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Container -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-inner">
    <!-- Brand / Logo -->
    <div class="sidebar-header">
      <a href="index.php" class="brand-logo" aria-label="ScutS Home">
        <img src="assets/images/scuts-logo.svg" alt="ScutS" width="106" height="27" />
      </a>
      <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close Sidebar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav" aria-label="Main Navigation">
      <ul class="nav-list">
        <li class="nav-item">
          <a href="index.php" class="nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-dashboard.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="bookings.php" class="nav-link <?= ($currentPage === 'bookings') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-bookings.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Bookings</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#transactions" class="nav-link <?= ($currentPage === 'transactions') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-transactions.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Transactions</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#stylist" class="nav-link <?= ($currentPage === 'stylist') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-stylist.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Stylist</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#products" class="nav-link <?= ($currentPage === 'products') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-products.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Products</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#ratings" class="nav-link <?= ($currentPage === 'ratings') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-ratings.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Ratings & Reviews</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#settings" class="nav-link <?= ($currentPage === 'settings') ? 'active' : '' ?>">
            <span class="nav-icon">
              <img src="assets/images/icon-settings.svg" alt="" aria-hidden="true" width="20" height="20" />
            </span>
            <span class="nav-text">Settings</span>
          </a>
        </li>
      </ul>
    </nav>

    <!-- Sign Out Footer -->
    <div class="sidebar-footer">
      <a href="logout.php" class="sign-out-btn" role="button">
        <span class="sign-out-text">Sign Out</span>
        <span class="sign-out-icon">
          <img src="assets/images/icon-logout.svg" alt="" aria-hidden="true" width="24" height="24" />
        </span>
      </a>
    </div>
  </div>
</aside>
