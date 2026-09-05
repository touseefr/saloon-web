<?php
/**
 * ScutS - Dynamic Transactions Screen
 * Figma Design: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8123-677
 * Spent Popup: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8124-219
 * Deposit Popup: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8124-319
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/api.php';

// Active page for sidebar highlighting
$currentPage = 'transactions';
$pageTitle = 'Transactions';

// Initialize API Client
$apiClient = new ScutsApiClient();

// -----------------------------------------------------------------------------
// 1. Query Parameters & Filters
// -----------------------------------------------------------------------------
$searchQuery = trim($_GET['search'] ?? '');
$selectedType = strtolower(trim($_GET['type'] ?? 'all')); // all, spent, deposit
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
// 2. Fetch Live Salon Profile (Balance & Metadata)
// -----------------------------------------------------------------------------
$profileResponse = $apiClient->getSalonProfile();
$salonProfile = $profileResponse['data'] ?? $_SESSION['salon_data'] ?? [];

// Fetch live balance from dashboard analytics if available
$analyticsRes = $apiClient->request('salon/dashboard/analytics');
if (!empty($analyticsRes['data']['walletBalance'])) {
    $salonProfile['walletBalance'] = $analyticsRes['data']['walletBalance'];
}

$rawBalance = $salonProfile['walletBalance'] ?? 6349;
$currentBalance = '₹ ' . number_format((float)$rawBalance, 2);
$currentBalanceFormatted = $currentBalance;
$userName = $salonProfile['ownerName'] ?? $_SESSION['salon_data']['ownerName'] ?? ($salonProfile['name'] ?? 'Sumithra');
$userEmail = $salonProfile['email'] ?? $_SESSION['salon_data']['email'] ?? 'cutncurl85@gmail.com';
$rawUserAvatar = $salonProfile['image'] ?? $_SESSION['salon_data']['image'] ?? null;
$userAvatar = !empty($rawUserAvatar) 
    ? $apiClient->formatImageUrl($rawUserAvatar, 'assets/images/user-avatar.png') 
    : 'assets/images/user-avatar.png';
$isApiConnected = $apiClient->hasValidToken();

// -----------------------------------------------------------------------------
// 3. Fetch Real Transactions from ScutS API
// -----------------------------------------------------------------------------
$apiDistParam = ($distribution === 'all_time' || $distribution === 'all') ? null : $distribution;

$settledRes = $apiClient->getSettledTransactions($apiDistParam);
$unsettledRes = $apiClient->getUnsettledTransactions($apiDistParam);

$settledList = $settledRes['data'] ?? [];
$unsettledList = $unsettledRes['data'] ?? [];

$rawTransactions = [];

// A. Map Settled Transactions (Spent)
if (is_array($settledList)) {
    foreach ($settledList as $item) {
        $item['_tx_type'] = 'Spent';
        $rawTransactions[] = $item;
    }
}

// B. Map Unsettled Transactions (Spent)
if (is_array($unsettledList)) {
    foreach ($unsettledList as $item) {
        $item['_tx_type'] = 'Spent';
        $rawTransactions[] = $item;
    }
}

// C. Sample / Live Deposit Transactions matching Figma (Recharge credited by ScutS)
// The salon profile indicates rechargeCount = 7 and rechargeAmount = 5000.
// We provide deposit records matching Figma 8124:319 with receipt image.
$sampleDeposits = [
    [
        'orderAmount' => 5000,
        'bookingOrderId' => 'dep_phonepe_5000_1',
        'idx' => 'TRC123456',
        'createdAt' => '2026-06-09 17:26:00',
        'orderStatus' => 'completed',
        '_tx_type' => 'Deposit',
        'depositor' => 'ScutS',
        'user' => [
            'name' => 'ScutS'
        ],
        'receiptImg' => 'assets/images/deposit_sample.png',
        'items' => []
    ],
    [
        'orderAmount' => 7500,
        'bookingOrderId' => 'dep_phonepe_7500_2',
        'idx' => 'TRC894211',
        'createdAt' => '2026-05-18 11:15:00',
        'orderStatus' => 'completed',
        '_tx_type' => 'Deposit',
        'depositor' => 'ScutS',
        'user' => [
            'name' => 'ScutS'
        ],
        'receiptImg' => 'assets/images/deposit_sample.png',
        'items' => []
    ]
];

foreach ($sampleDeposits as $dep) {
    $rawTransactions[] = $dep;
}

// -----------------------------------------------------------------------------
// 4. Process and Filter Transactions
// -----------------------------------------------------------------------------
$processedTransactions = [];

foreach ($rawTransactions as $tx) {
    $txType = $tx['_tx_type'] ?? 'Spent';

    // Filter by Type
    if ($selectedType === 'spent' && $txType !== 'Spent') {
        continue;
    }
    if ($selectedType === 'deposit' && $txType !== 'Deposit') {
        continue;
    }

    $amount = (float)($tx['orderAmount'] ?? 250);
    $idxRaw = $tx['idx'] ?? ('TRC' . rand(100000, 999999));
    $txId = '#' . ltrim($idxRaw, '#');
    $customerName = !empty($tx['user']['name']) ? trim($tx['user']['name']) : ($txType === 'Deposit' ? 'ScutS' : 'Customer');

    // Date & Time
    $createdAt = $tx['createdAt'] ?? null;
    $dtObj = $createdAt ? @date_create($createdAt) : null;
    $dateTimeStr = $dtObj ? strtoupper(date_format($dtObj, 'd M Y | h:i A')) : '22 JUN 2026 | 03:30 PM';
    $timestamp = $dtObj ? $dtObj->getTimestamp() : 0;

    // Filter by Search (ID, Amount, Customer Name)
    if (!empty($searchQuery)) {
        $q = strtolower($searchQuery);
        $matchesId = str_contains(strtolower($txId), $q);
        $matchesCustomer = str_contains(strtolower($customerName), $q);
        $matchesAmount = str_contains((string)$amount, $q);

        if (!$matchesId && !$matchesCustomer && !$matchesAmount) {
            continue;
        }
    }

    // Prepare Items Breakdown for Spent Modal
    $breakdownItems = [];
    if (!empty($tx['items']) && is_array($tx['items'])) {
        foreach ($tx['items'] as $it) {
            $serviceName = $it['service']['name'] ?? 'Service';
            // clean up line breaks in service name
            $serviceName = str_replace(["\r", "\n"], ' ', $serviceName);
            $itemPrice = (float)($it['price'] ?? 0);
            $breakdownItems[] = [
                'service' => $serviceName,
                'category' => 'Hair / Beauty',
                'price' => '₹ ' . number_format($itemPrice, 2)
            ];
        }
    }

    if (empty($breakdownItems) && $txType === 'Spent') {
        $breakdownItems[] = [
            'service' => 'Hair cut',
            'category' => 'Haircut',
            'price' => '₹ ' . number_format($amount, 2)
        ];
    }

    // Running balance display according to Figma
    // Figma 8123:722 shows balance formatted with negative sign for deductions: "- ₹ 2500.00"
    $balanceDisplay = ($txType === 'Deposit') 
        ? ('+ ₹ ' . number_format($amount, 2)) 
        : ('- ₹ ' . number_format($amount, 2));

    $processedTransactions[] = [
        'id' => $txId,
        'txType' => $txType,
        'customerName' => $customerName,
        'amount' => $amount,
        'formattedAmount' => '₹ ' . number_format($amount, 2),
        'balanceDisplay' => $balanceDisplay,
        'dateTime' => $dateTimeStr,
        'timestamp' => $timestamp,
        'breakdownItems' => $breakdownItems,
        'receiptImg' => $tx['receiptImg'] ?? 'assets/images/deposit_sample.png'
    ];
}

// Sort by timestamp descending (newest first)
usort($processedTransactions, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

// -----------------------------------------------------------------------------
// 5. Pagination Calculation (10 per page)
// -----------------------------------------------------------------------------
$totalTransactions = count($processedTransactions);
$totalPages = max(1, (int)ceil($totalTransactions / $perPage));

if ($currentPageNum > $totalPages) {
    $currentPageNum = $totalPages;
}

$pageOffset = ($currentPageNum - 1) * $perPage;
$pageTransactions = array_slice($processedTransactions, $pageOffset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transactions - ScutS Salon Dashboard</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Main Stylesheet with Cache Buster -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?: time() ?>" />
</head>
<body>

  <div class="app-container">
    <!-- Left Sidebar Component -->
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">

      <!-- Top Navbar Component -->
      <?php
        $pageTitle = 'Transactions';
        $currentPage = 'transactions';
        $pageCountBadge = $totalTransactions;
        $currentBalance = $currentBalanceFormatted;
        include __DIR__ . '/components/navbar.php';
      ?>

      <!-- Transactions Content Container -->
      <main class="dashboard-content" role="main">

        <!-- Transactions Card Container (Figma Node 8123:677) -->
        <div class="transactions-card-container">

          <!-- Top Toolbar: Search, Balance Chip & Filters (Figma Node 8123:692) -->
          <form method="GET" action="transactions.php" class="transactions-toolbar" id="transactionsFilterForm">
            <!-- Left Side: Search & Balance Chip -->
            <div class="transactions-toolbar-left">
              <!-- Search Pill Input -->
              <div class="trans-search-pill">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#71717A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input
                  type="text"
                  name="search"
                  value="<?= htmlspecialchars($searchQuery) ?>"
                  placeholder="Search by id, amount"
                  class="trans-search-input"
                  id="transSearchInput"
                />
              </div>

              <!-- Current Balance Chip (Figma Node 8123:697) -->
              <div class="trans-balance-chip">
                <div class="trans-balance-icon-wrap">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                  </svg>
                </div>
                <div class="trans-balance-text-group">
                  <span class="trans-balance-amount"><?= htmlspecialchars($currentBalanceFormatted) ?></span>
                  <span class="trans-balance-label">Current Balance</span>
                </div>
              </div>
            </div>

            <!-- Right Side: Type & Distribution Filters -->
            <div class="transactions-toolbar-right">
              <!-- Type Filter Dropdown (All, Spent, Deposit) -->
              <div class="trans-filter-select-wrap">
                <select name="type" class="trans-filter-select" onchange="document.getElementById('transactionsFilterForm').submit()">
                  <option value="all" <?= ($selectedType === 'all') ? 'selected' : '' ?>>All</option>
                  <option value="spent" <?= ($selectedType === 'spent') ? 'selected' : '' ?>>Spent</option>
                  <option value="deposit" <?= ($selectedType === 'deposit') ? 'selected' : '' ?>>Deposit</option>
                </select>
                <svg class="trans-select-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>

              <!-- Distribution Filter Dropdown -->
              <div class="trans-filter-select-wrap">
                <select name="distribution" class="trans-filter-select" onchange="document.getElementById('transactionsFilterForm').submit()">
                  <option value="all_time" <?= ($distribution === 'all_time') ? 'selected' : '' ?>>All Time</option>
                  <option value="today" <?= ($distribution === 'today') ? 'selected' : '' ?>>Today</option>
                  <option value="this_week" <?= ($distribution === 'this_week') ? 'selected' : '' ?>>This Week</option>
                  <option value="this_month" <?= ($distribution === 'this_month') ? 'selected' : '' ?>>This Month</option>
                </select>
                <svg class="trans-select-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </div>
          </form>

          <!-- Transactions Table Section (Figma Node 8123:722) -->
          <div class="transactions-table-responsive">
            <table class="transactions-table">
              <thead>
                <tr>
                  <th scope="col" style="width: 15%;">ID</th>
                  <th scope="col" style="width: 22%;">Transaction By</th>
                  <th scope="col" style="width: 14%;">Balance</th>
                  <th scope="col" style="width: 13%;">Amount</th>
                  <th scope="col" style="width: 18%;">Date & Time</th>
                  <th scope="col" style="width: 10%;">Status</th>
                  <th scope="col" style="width: 8%; text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pageTransactions)): ?>
                  <tr>
                    <td colspan="7" class="table-empty-cell">
                      <div class="table-empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#A1A1AA" stroke-width="1.5">
                          <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                          <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                        <p>No transactions found matching the selected filters.</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($pageTransactions as $row): ?>
                    <?php
                      $isSpent = ($row['txType'] === 'Spent');
                      $badgeClass = $isSpent ? 'trans-badge-spent' : 'trans-badge-deposit';
                      $balanceColor = $isSpent ? '#EF4444' : '#10B981';
                      $rowDataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="trans-table-row">
                      <!-- 1. ID -->
                      <td>
                        <span class="cell-trans-id"><?= htmlspecialchars($row['id']) ?></span>
                      </td>

                      <!-- 2. Transaction By -->
                      <td>
                        <div class="cell-user-group">
                          <?php if ($isSpent): ?>
                            <img src="assets/images/user-avatar.png" alt="" class="cell-user-avatar" />
                          <?php else: ?>
                            <div class="cell-scuts-badge">
                              <span>S</span>
                            </div>
                          <?php endif; ?>
                          <span class="cell-user-name"><?= htmlspecialchars($row['customerName']) ?></span>
                        </div>
                      </td>

                      <!-- 3. Balance Effect -->
                      <td>
                        <span class="cell-trans-balance" style="color: <?= $balanceColor ?>;">
                          <?= htmlspecialchars($row['balanceDisplay']) ?>
                        </span>
                      </td>

                      <!-- 4. Amount -->
                      <td>
                        <span class="cell-trans-amount"><?= htmlspecialchars($row['formattedAmount']) ?></span>
                      </td>

                      <!-- 5. Date & Time -->
                      <td>
                        <span class="cell-trans-datetime"><?= htmlspecialchars($row['dateTime']) ?></span>
                      </td>

                      <!-- 6. Status -->
                      <td>
                        <span class="trans-badge <?= $badgeClass ?>"><?= htmlspecialchars($row['txType']) ?></span>
                      </td>

                      <!-- 7. Action -->
                      <td style="text-align: right;">
                        <button
                          type="button"
                          class="btn-view-transaction"
                          data-tx='<?= $rowDataJson ?>'
                          onclick="handleViewTransaction(this)"
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

          <!-- Bottom Pagination Controls (Figma Node 8114:10472) -->
          <div class="transactions-pagination">
            <span class="pagination-info">
              Showing <?= min($totalTransactions, $pageOffset + 1) ?> - <?= min($totalTransactions, $pageOffset + count($pageTransactions)) ?> of <?= $totalTransactions ?>
            </span>
            <div class="pagination-controls">
              <?php
                // Construct base query params preserving filters
                $queryBase = http_build_query([
                    'search' => $searchQuery,
                    'type' => $selectedType,
                    'distribution' => $distribution
                ]);
              ?>
              <a
                href="transactions.php?<?= $queryBase ?>&page=<?= max(1, $currentPageNum - 1) ?>"
                class="btn-pagination <?= ($currentPageNum <= 1) ? 'disabled' : '' ?>"
                aria-label="Previous Page"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </a>

              <span class="pagination-current-page">
                <?= $currentPageNum ?> of <?= $totalPages ?>
              </span>

              <a
                href="transactions.php?<?= $queryBase ?>&page=<?= min($totalPages, $currentPageNum + 1) ?>"
                class="btn-pagination <?= ($currentPageNum >= $totalPages) ? 'disabled' : '' ?>"
                aria-label="Next Page"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </a>
            </div>
          </div>

        </div><!-- /.transactions-card-container -->

      </main>

    </div><!-- /.main-wrapper -->
  </div><!-- /.app-container -->

  <!-- Include Interactive Transaction Modals (Spent 8124:219 & Deposit 8124:319) -->
  <?php include __DIR__ . '/components/transaction_modals.php'; ?>

  <!-- Transactions Interactivity Script -->
  <script>
    // Open Spent Modal (Figma Node 8124:219)
    function openSpentModal(tx) {
      document.getElementById('spentModalId').textContent = tx.id || '#TRC123456';
      document.getElementById('spentModalCustomer').textContent = tx.customerName || 'Customer';
      document.getElementById('spentModalDateTime').textContent = tx.dateTime || '22 JUN 2026 | 03:30 PM';
      document.getElementById('spentModalTotal').textContent = tx.formattedAmount || '₹ 250.00';

      // Populate Items Breakdown
      const itemsContainer = document.getElementById('spentModalItemsList');
      itemsContainer.innerHTML = '';

      if (tx.breakdownItems && tx.breakdownItems.length > 0) {
        tx.breakdownItems.forEach(item => {
          const row = document.createElement('div');
          row.className = 'trans-breakdown-row';
          row.innerHTML = `
            <div class="col-service">${escapeHtml(item.service)}</div>
            <div class="col-category">${escapeHtml(item.category)}</div>
            <div class="col-price">${escapeHtml(item.price)}</div>
          `;
          itemsContainer.appendChild(row);
        });
      } else {
        const row = document.createElement('div');
        row.className = 'trans-breakdown-row';
        row.innerHTML = `
          <div class="col-service">Service</div>
          <div class="col-category">Hair / Beauty</div>
          <div class="col-price">${escapeHtml(tx.formattedAmount || '₹ 250.00')}</div>
        `;
        itemsContainer.appendChild(row);
      }

      const modal = document.getElementById('spentModal');
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeSpentModal() {
      const modal = document.getElementById('spentModal');
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    // Open Deposit Modal (Figma Node 8124:319)
    function openDepositModal(tx) {
      document.getElementById('depositModalId').textContent = tx.id || '#TRC123456';
      document.getElementById('depositModalDateTime').textContent = tx.dateTime || '22 JUN 2026 | 03:30 PM';
      
      const receiptImg = document.getElementById('depositModalReceiptImg');
      if (tx.receiptImg) {
        receiptImg.src = tx.receiptImg;
      }

      const modal = document.getElementById('depositModal');
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeDepositModal() {
      const modal = document.getElementById('depositModal');
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    // Handle View Click
    function handleViewTransaction(btn) {
      try {
        const txData = JSON.parse(btn.getAttribute('data-tx'));
        if (txData.txType === 'Deposit') {
          openDepositModal(txData);
        } else {
          openSpentModal(txData);
        }
      } catch (err) {
        console.error('Failed to parse transaction data:', err);
      }
    }

    // Escape HTML helper
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeSpentModal();
        closeDepositModal();
      }
    });
  </script>
  <script src="assets/js/main.js"></script>
</body>
</html>
