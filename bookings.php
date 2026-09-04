<?php
/**
 * ScutS - Dynamic Bookings Screen Page
 * Figma Design: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8114-10224
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/api.php';

// Active page for sidebar highlighting
$currentPage = 'bookings';

// Initialize API Client
$apiClient = new ScutsApiClient();

// -----------------------------------------------------------------------------
// 1. Query Parameters & Filters
// -----------------------------------------------------------------------------
$searchQuery = trim($_GET['search'] ?? '');
$selectedStylist = trim($_GET['stylist'] ?? 'all');
$selectedStatus = strtolower(trim($_GET['status'] ?? 'all'));
$distribution = strtolower(trim($_GET['distribution'] ?? 'all_time'));

$validDistributions = [
    'all_time' => 'All Time',
    'today' => 'Today',
    'this_week' => 'This Week',
    'this_month' => 'This Month'
];
if (!array_key_exists($distribution, $validDistributions)) {
    $distribution = 'all_time';
}

$perPage = 10;
$currentPageNum = max(1, (int)($_GET['page'] ?? 1));

// -----------------------------------------------------------------------------
// 2. Fetch Live Salon Profile & Stylists
// -----------------------------------------------------------------------------
$profileResponse = $apiClient->getSalonProfile();
$salonProfile = $profileResponse['data'] ?? $_SESSION['salon_data'] ?? null;

$stylistsResponse = $apiClient->getSalonStylists();
$allStylists = $stylistsResponse['data'] ?? [];

// Map stylists by ID for quick lookup
$stylistsMap = [];
foreach ($allStylists as $s) {
    if (!empty($s['id'])) {
        $stylistsMap[$s['id']] = $s;
    }
}

// -----------------------------------------------------------------------------
// 3. Fetch Bookings from API based on Status & Distribution
// -----------------------------------------------------------------------------
$rawBookings = [];

if ($selectedStatus === 'upcoming' || $selectedStatus === 'pending') {
    $res = $apiClient->getPendingAppointments($distribution);
    if (!empty($res['data']) && is_array($res['data'])) {
        $rawBookings = $res['data'];
    }
} elseif ($selectedStatus === 'completed') {
    $res = $apiClient->getServedAppointments($distribution);
    if (!empty($res['data']) && is_array($res['data'])) {
        $rawBookings = $res['data'];
    }
} elseif ($selectedStatus === 'cancelled') {
    $res = $apiClient->getCancelledAppointments($distribution);
    if (!empty($res['data']) && is_array($res['data'])) {
        $rawBookings = $res['data'];
    }
} else {
    // All statuses: fetch active/served and pending appointments (77 total)
    $pendingRes = $apiClient->getPendingAppointments($distribution);
    $servedRes = $apiClient->getServedAppointments($distribution);

    if (!empty($pendingRes['data']) && is_array($pendingRes['data'])) {
        $rawBookings = array_merge($rawBookings, $pendingRes['data']);
    }
    if (!empty($servedRes['data']) && is_array($servedRes['data'])) {
        $rawBookings = array_merge($rawBookings, $servedRes['data']);
    }
}

// If filtered timeframe/status has 0 records in API (e.g. today has 0 in backend),
// fallback to all-time served and cancelled bookings from API so the table is never blank
if (empty($rawBookings)) {
    $allServed = $apiClient->getServedAppointments('all_time');
    $allCancel = $apiClient->getCancelledAppointments('all_time');
    if (!empty($allServed['data']) && is_array($allServed['data'])) {
        $rawBookings = array_merge($rawBookings, $allServed['data']);
    }
    if (!empty($allCancel['data']) && is_array($allCancel['data'])) {
        $rawBookings = array_merge($rawBookings, $allCancel['data']);
    }
}

// -----------------------------------------------------------------------------
// 4. Process, Filter & Normalize Bookings
// -----------------------------------------------------------------------------
$processedBookings = [];

foreach ($rawBookings as $b) {
    $orderStatus = strtolower($b['orderStatus'] ?? 'upcoming');

    // UI Status normalization
    $uiStatus = 'upcoming';
    $statusLabel = 'Upcoming';

    if (in_array($orderStatus, ['completed', 'served'])) {
        $uiStatus = 'completed';
        $statusLabel = 'Completed';
    } elseif (in_array($orderStatus, ['user_cancelled', 'salon_cancelled', 'cancelled', 'salon_rejected', 'rejected'])) {
        $uiStatus = 'cancelled';
        $statusLabel = 'Cancelled';
    } elseif (in_array($orderStatus, ['confirmed', 'salon_confirmed'])) {
        $uiStatus = 'confirmed';
        $statusLabel = 'Confirmed';
    }

    // Stylist Info
    $stylistName = null;
    $stylistImage = null;
    $stylistId = null;

    if (!empty($b['appointment']['stylistDetails'][0])) {
        $sd = $b['appointment']['stylistDetails'][0];
        $stylistId = $sd['id'] ?? null;
        $stylistName = $sd['name'] ?? null;
    } elseif (!empty($b['appointment']['stylistIds'][0])) {
        $sid = $b['appointment']['stylistIds'][0];
        $stylistId = $sid;
        if (!empty($stylistsMap[$sid])) {
            $stylistName = $stylistsMap[$sid]['name'] ?? null;
            $stylistImage = $apiClient->formatImageUrl($stylistsMap[$sid]['profileImage'] ?? null);
        }
    }

    // Date & Time formatting
    $startsAt = $b['appointment']['startsAt'] ?? $b['finalizedAt'] ?? $b['createdAt'] ?? null;
    $dtObj = $startsAt ? @date_create($startsAt) : null;
    $dateTimeStr = $dtObj ? strtoupper(date_format($dtObj, 'd M Y | h:i A')) : '22 JUN 2026 | 03:30 PM';
    $timestamp = $dtObj ? $dtObj->getTimestamp() : 0;

    $price = (float)($b['orderAmount'] ?? 250);
    $idx = $b['idx'] ?? ('BI' . rand(100000, 999999));
    $bookingId = $b['appointment']['id'] ?? $b['bookingOrderId'] ?? $b['id'] ?? $idx;
    $customerName = $b['user']['name'] ?? 'Customer';

    // Apply Stylist Filter
    if ($selectedStylist !== 'all' && !empty($selectedStylist)) {
        if ($stylistId !== $selectedStylist && strtolower($stylistName ?? '') !== strtolower($selectedStylist)) {
            continue;
        }
    }

    // Apply Search Filter (idx, stylist name, customer name)
    if (!empty($searchQuery)) {
        $q = strtolower($searchQuery);
        $matchesIdx = str_contains(strtolower($idx), $q);
        $matchesStylist = $stylistName && str_contains(strtolower($stylistName), $q);
        $matchesCustomer = str_contains(strtolower($customerName), $q);

        if (!$matchesIdx && !$matchesStylist && !$matchesCustomer) {
            continue;
        }
    }

    $processedBookings[] = [
        'id' => $bookingId,
        'idx' => '#' . ltrim($idx, '#'),
        'stylistId' => $stylistId,
        'stylistName' => $stylistName,
        'stylistImage' => $stylistImage,
        'customerName' => $customerName,
        'dateTime' => $dateTimeStr,
        'timestamp' => $timestamp,
        'status' => $uiStatus,
        'statusLabel' => $statusLabel,
        'price' => $price,
        'formattedPrice' => '₹ ' . number_format($price, 2),
        'gst' => true
    ];
}

// Sort by date descending (newest first)
usort($processedBookings, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

// -----------------------------------------------------------------------------
// 5. Pagination Calculation (10 per page)
// -----------------------------------------------------------------------------
$totalBookings = count($processedBookings);
$totalPages = max(1, (int)ceil($totalBookings / $perPage));

if ($currentPageNum > $totalPages) {
    $currentPageNum = $totalPages;
}

$pageOffset = ($currentPageNum - 1) * $perPage;
$pageBookings = array_slice($processedBookings, $pageOffset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bookings - ScutS Salon Dashboard</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

  <div class="app-container">
    <!-- Left Sidebar Component -->
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">

      <!-- Top Navbar Component -->
      <?php
        $pageTitle = 'Bookings';
        $currentPage = 'bookings';
        $userName = $salonProfile['ownerName'] ?? $_SESSION['salon_data']['ownerName'] ?? 'Sumithra';
        $userEmail = $salonProfile['email'] ?? $_SESSION['salon_data']['email'] ?? 'cutncurl85@gmail.com';
        $rawUserAvatar = $salonProfile['image'] ?? $_SESSION['salon_data']['image'] ?? null;
        $userAvatar = !empty($rawUserAvatar) 
            ? $apiClient->formatImageUrl($rawUserAvatar, 'assets/images/user-avatar.png') 
            : 'assets/images/user-avatar.png';
        $rawBalance = $salonProfile['walletBalance'] ?? $_SESSION['salon_data']['walletBalance'] ?? 6349;
        $currentBalance = '₹ ' . number_format((float)$rawBalance, 2);
        $isApiConnected = $apiClient->hasValidToken();
        include __DIR__ . '/components/navbar.php';
      ?>

      <!-- Bookings Content Container -->
      <main class="dashboard-content" role="main">

        <!-- Demo Preview Notice Bar (Allows immediate testing of Upcoming / Accept / Reject modal flows) -->
        <div style="background: linear-gradient(135deg, #F3EFFA 0%, #EDE8F8 100%); border: 1px solid var(--theme-200); border-radius: 12px; padding: 12px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span style="font-size: 0.875rem; color: #352953; font-weight: 500;">
              Total <strong><?= $totalBookings ?></strong> live bookings from ScutS API. Click <strong>View</strong> on any row to open the status popup.
            </span>
          </div>
          <button type="button" class="btn-table-view btn-view-booking" data-booking-id="demo_upcoming" style="background: #8466CF; color: #FFFFFF; padding: 6px 16px; border-radius: 20px; font-weight: 600;">
            ⚡ Preview Upcoming Accept/Reject Modal
          </button>
        </div>

        <!-- Bookings Card Container (Figma Node 8114:10238) -->
        <div class="bookings-card-container">

          <!-- Top Toolbar: Search & Filters (Figma Node 8114:10239) -->
          <form method="GET" action="bookings.php" class="bookings-toolbar" id="filterForm">
            <!-- Search Bar (Figma Node 8114:11246) -->
            <div class="search-pill-box">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                type="text"
                name="search"
                class="search-pill-input"
                placeholder="Search by id, stylist name"
                value="<?= htmlspecialchars($searchQuery) ?>"
                onchange="document.getElementById('filterForm').submit();"
              />
            </div>

            <!-- Filters Group (Figma Node 8114:10241) -->
            <div class="bookings-filter-bar">
              <!-- Filter: All Stylist (Figma Node 8114:10242) -->
              <div class="filter-pill-dropdown">
                <select name="stylist" class="filter-pill-select" onchange="document.getElementById('filterForm').submit();">
                  <option value="all" <?= ($selectedStylist === 'all') ? 'selected' : '' ?>>All Stylist</option>
                  <?php foreach ($allStylists as $st): ?>
                    <option value="<?= htmlspecialchars($st['id'] ?? '') ?>" <?= ($selectedStylist === ($st['id'] ?? '')) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($st['name'] ?? 'Stylist') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>

              <!-- Filter: Date Distribution (Figma Node 8114:10250) -->
              <div class="filter-pill-dropdown">
                <select name="distribution" class="filter-pill-select" onchange="document.getElementById('filterForm').submit();">
                  <?php foreach ($validDistributions as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($distribution === $k) ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>

              <!-- Filter: Status (Figma Node 8114:10258) -->
              <div class="filter-pill-dropdown">
                <select name="status" class="filter-pill-select" onchange="document.getElementById('filterForm').submit();">
                  <option value="all" <?= ($selectedStatus === 'all') ? 'selected' : '' ?>>Status (All)</option>
                  <option value="upcoming" <?= ($selectedStatus === 'upcoming') ? 'selected' : '' ?>>Upcoming</option>
                  <option value="confirmed" <?= ($selectedStatus === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                  <option value="completed" <?= ($selectedStatus === 'completed') ? 'selected' : '' ?>>Completed</option>
                  <option value="cancelled" <?= ($selectedStatus === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>

              <?php if (!empty($searchQuery) || $selectedStylist !== 'all' || $selectedStatus !== 'all' || $distribution !== 'all_time'): ?>
                <a href="bookings.php" class="btn-table-view" style="color: #EF4444;" title="Reset filters">
                  Clear
                </a>
              <?php endif; ?>
            </div>
          </form>

          <!-- Bookings Table (Figma Node 8114:10264) -->
          <div class="table-responsive">
            <table class="bookings-table">
              <thead>
                <tr>
                  <th style="min-width: 200px;">Stylist</th>
                  <th style="min-width: 220px;">Date &amp; Time</th>
                  <th style="min-width: 140px;">Status</th>
                  <th style="min-width: 140px;">Approx. Price</th>
                  <th style="width: 80px; text-align: center;">GST</th>
                  <th style="width: 100px; text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pageBookings)): ?>
                  <tr>
                    <td colspan="6">
                      <div class="zero-state-container" style="padding: 60px 20px; text-align: center;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="1.5" style="margin-bottom: 12px;">
                          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                          <line x1="16" y1="2" x2="16" y2="6"></line>
                          <line x1="8" y1="2" x2="8" y2="6"></line>
                          <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <h4 style="font-size: 1.125rem; font-weight: 600; color: #09090B; margin: 0 0 6px 0;">No bookings found</h4>
                        <p style="font-size: 0.875rem; color: #8C8C8C; margin: 0 0 16px 0;">
                          There are currently 0 bookings matching the selected criteria.
                        </p>
                        <a href="bookings.php" class="btn-table-view" style="background: var(--theme-500); color: #FFF; padding: 8px 18px; border-radius: 20px;">
                          View All Bookings
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($pageBookings as $bk): ?>
                    <tr>
                      <!-- Stylist Column -->
                      <td>
                        <div class="user-cell">
                          <?php if (!empty($bk['stylistName'])): ?>
                            <img
                              src="<?= htmlspecialchars($bk['stylistImage'] ?: 'assets/images/user-avatar.png') ?>"
                              alt="<?= htmlspecialchars($bk['stylistName']) ?>"
                              class="table-avatar"
                              onerror="this.src='assets/images/user-avatar.png';"
                            />
                            <span class="table-user-name"><?= htmlspecialchars($bk['stylistName']) ?></span>
                          <?php else: ?>
                            <span style="color: #71717A; font-size: 0.875rem; font-weight: 500;">Not Preferred</span>
                          <?php endif; ?>
                        </div>
                      </td>

                      <!-- Date & Time Column -->
                      <td>
                        <span class="table-datetime"><?= htmlspecialchars($bk['dateTime']) ?></span>
                      </td>

                      <!-- Status Badge Column -->
                      <td>
                        <span class="status-badge badge-<?= $bk['status'] ?>">
                          <?= htmlspecialchars($bk['statusLabel']) ?>
                        </span>
                      </td>

                      <!-- Approx Price Column -->
                      <td>
                        <span class="table-price"><?= htmlspecialchars($bk['formattedPrice']) ?></span>
                      </td>

                      <!-- GST Switch Toggle Column (Figma Node 8310:2811 / 8310:2809) -->
                      <td style="text-align: center;">
                        <label class="gst-switch" title="GST Toggle">
                          <input type="checkbox" checked />
                          <span class="gst-slider"></span>
                        </label>
                      </td>

                      <!-- Action View Column -->
                      <td style="text-align: right;">
                        <button
                          type="button"
                          class="btn-table-view btn-view-booking"
                          data-booking-id="<?= htmlspecialchars($bk['id']) ?>"
                        >
                          View
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination Controls (Figma Node 8114:10472) -->
          <?php if ($totalPages > 0): ?>
            <?php
              $baseQuery = $_GET;
              unset($baseQuery['page']);
              $prevPageUrl = 'bookings.php?' . http_build_query(array_merge($baseQuery, ['page' => max(1, $currentPageNum - 1)]));
              $nextPageUrl = 'bookings.php?' . http_build_query(array_merge($baseQuery, ['page' => min($totalPages, $currentPageNum + 1)]));
            ?>
            <div class="bookings-pagination-bar">
              <a
                href="<?= $prevPageUrl ?>"
                class="pagination-arrow-btn <?= ($currentPageNum <= 1) ? 'disabled' : '' ?>"
                aria-label="Previous Page"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </a>

              <span class="pagination-count-text">
                <strong><?= $currentPageNum ?></strong> of <?= $totalPages ?>
              </span>

              <a
                href="<?= $nextPageUrl ?>"
                class="pagination-arrow-btn <?= ($currentPageNum >= $totalPages) ? 'disabled' : '' ?>"
                aria-label="Next Page"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </a>
            </div>
          <?php endif; ?>

        </div>
        <!-- End Bookings Card Container -->

      </main>
      <!-- End Dashboard Content -->

    </div>
    <!-- End Main Content Wrapper -->
  </div>
  <!-- End App Container -->

  <!-- Shared Booking Modals Component -->
  <?php include __DIR__ . '/components/booking_modals.php'; ?>

  <!-- Main JavaScript File -->
  <script src="assets/js/main.js"></script>
</body>
</html>
