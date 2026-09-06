<?php
/**
 * ScutS - Ratings & Reviews Module
 * Figma Design: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8330-7022
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/api.php';

$apiClient = new ScutsApiClient();

// -----------------------------------------------------------------------------
// Helper: Format Relative Time
// -----------------------------------------------------------------------------
function formatRelativeTime($dateString) {
    if (empty($dateString)) return 'Recent';
    try {
        $time = strtotime($dateString);
        if (!$time) return 'Recent';
        $diff = time() - $time;
        if ($diff < 3600) return 'Just now';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        if ($diff < 2592000) return floor($diff / 604800) . 'w ago';
        if ($diff < 31536000) return floor($diff / 2592000) . 'm ago';
        return floor($diff / 31536000) . 'y ago';
    } catch (Exception $e) {
        return 'Recent';
    }
}

// -----------------------------------------------------------------------------
// 1. Fetch Live Reviews from ScutS API
// -----------------------------------------------------------------------------
$liveReviews = [];
$uniqueStylists = [];

// A. Fetch Reviews by Artist
$byArtistRes = $apiClient->request('salon/review/by-artist');
if (!empty($byArtistRes['data']) && is_array($byArtistRes['data'])) {
    foreach ($byArtistRes['data'] as $artistGroup) {
        $stylistName = $artistGroup['name'] ?? 'Stylist';
        $stylistImg = !empty($artistGroup['image']) ? $apiClient->formatImageUrl($artistGroup['image']) : 'assets/images/stylist-1.png';
        $stylistRating = !empty($artistGroup['rating']) ? (float)$artistGroup['rating'] : 5.0;

        if (!in_array($stylistName, $uniqueStylists, true)) {
            $uniqueStylists[] = $stylistName;
        }

        if (!empty($artistGroup['reviews']) && is_array($artistGroup['reviews'])) {
            foreach ($artistGroup['reviews'] as $rev) {
                $revText = trim($rev['review'] ?? '');
                // Include reviews that have content
                if (!empty($revText)) {
                    $custName = $rev['user']['name'] ?? 'Customer';
                    $custAvatar = !empty($rev['user']['profileImage']) 
                        ? $apiClient->formatImageUrl($rev['user']['profileImage'], 'assets/images/booking-user-1.png') 
                        : 'assets/images/booking-user-1.png';

                    $liveReviews[] = [
                        'id' => $rev['id'],
                        'customerName' => $custName,
                        'customerAvatar' => $custAvatar,
                        'stylistName' => $stylistName,
                        'rating' => 5,
                        'review' => $revText,
                        'timeAgo' => formatRelativeTime($rev['createdAt'] ?? ''),
                        'isLive' => true
                    ];
                }
            }
        }
    }
}

// B. Fetch Overall Salon Reviews
$overallRes = $apiClient->request('salon/review/overall/list');
if (!empty($overallRes['data']) && is_array($overallRes['data'])) {
    foreach ($overallRes['data'] as $salonRev) {
        $text = trim($salonRev['review'] ?? '');
        if (!empty($text)) {
            $liveReviews[] = [
                'id' => $salonRev['id'],
                'customerName' => $salonRev['user']['name'] ?? 'Verified Customer',
                'customerAvatar' => 'assets/images/booking-user-2.png',
                'stylistName' => 'Cut n Curl Team',
                'rating' => (int)($salonRev['rating'] ?? 5),
                'review' => $text,
                'timeAgo' => formatRelativeTime($salonRev['createdAt'] ?? ''),
                'isLive' => true
            ];
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Figma High-Fidelity Reviews (Node 8330:7022)
// -----------------------------------------------------------------------------
$figmaReviews = [
    [
        'id' => 'figma_rev_1',
        'customerName' => 'Dominick Bogisich',
        'customerAvatar' => 'assets/images/booking-user-1.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar is a talented stylist who excels in her craft. Her keen eye for detail and personalized service ensure every visit is enjoyable. I always leave feeling refreshed and confident!',
        'timeAgo' => '3d ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_2',
        'customerName' => "Felicia O'Conner",
        'customerAvatar' => 'assets/images/booking-user-2.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar always delivers outstanding results with incredible precision. Her friendly approach and professional skills make every appointment relaxing. I always leave loving my new look!',
        'timeAgo' => '3d ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_3',
        'customerName' => 'Emmett Connelly',
        'customerAvatar' => 'assets/images/booking-user-3.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'The precision and styling care here is unmatched. From consultation to final touch, everything was handled with sheer professionalism. Definitely my go-to salon!',
        'timeAgo' => '4d ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_4',
        'customerName' => 'Lillian Fahey',
        'customerAvatar' => 'assets/images/booking-user-4.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar pays close attention to every detail and always delivers excellent results. Her warm personality and styling expertise make every visit memorable.',
        'timeAgo' => '5d ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_5',
        'customerName' => 'Sheila Hyatt',
        'customerAvatar' => 'assets/images/booking-user-1.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar makes every salon visit comfortable and enjoyable. Her attention to detail and friendly personality ensure I always leave feeling my absolute best!',
        'timeAgo' => '6d ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_6',
        'customerName' => 'Marvin Kirlin',
        'customerAvatar' => 'assets/images/booking-user-2.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar is a fantastic stylist with outstanding skills. Her personalized recommendations and flawless service make every appointment worth looking forward to.',
        'timeAgo' => '1w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_7',
        'customerName' => 'Dana Little-Beatty',
        'customerAvatar' => 'assets/images/booking-user-3.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar has an amazing eye for style and detail. She listens carefully to my preferences and consistently creates the perfect look. Highly recommended for anyone!',
        'timeAgo' => '1w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_8',
        'customerName' => "Eula D'Amore",
        'customerAvatar' => 'assets/images/booking-user-4.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar is incredibly talented and genuinely cares about her clients. She listens carefully and consistently creates a style that looks and feels amazing.',
        'timeAgo' => '1w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_9',
        'customerName' => 'Terry Schmidt',
        'customerAvatar' => 'assets/images/booking-user-1.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar is professional, welcoming, and incredibly skilled. She takes time to understand exactly what I want and delivers beautiful results every time.',
        'timeAgo' => '2w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_10',
        'customerName' => 'Sadie Yundt',
        'customerAvatar' => 'assets/images/booking-user-2.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar always provides top-quality service with exceptional care. Her creativity, professionalism, and precision make every appointment a wonderful experience.',
        'timeAgo' => '2w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_11',
        'customerName' => 'Brandon Kirlin',
        'customerAvatar' => 'assets/images/booking-user-3.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar creates an amazing salon experience from start to finish. She is attentive, professional, and always ensures I leave feeling confident and refreshed.',
        'timeAgo' => '2w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_12',
        'customerName' => 'Lori Casper',
        'customerAvatar' => 'assets/images/booking-user-4.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar consistently exceeds my expectations with her styling expertise. She is thoughtful, talented, and always delivers results that perfectly suit my style.',
        'timeAgo' => '3w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_13',
        'customerName' => 'Glenda Hamill',
        'customerAvatar' => 'assets/images/booking-user-1.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar makes every salon visit comfortable and enjoyable. Her attention to detail and friendly personality ensure I always leave feeling my absolute best!',
        'timeAgo' => '3w ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_14',
        'customerName' => 'Willis Kovacek',
        'customerAvatar' => 'assets/images/booking-user-2.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar is a fantastic stylist with outstanding skills. Her personalized recommendations and flawless service make every appointment worth looking forward to.',
        'timeAgo' => '1m ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_15',
        'customerName' => 'Judy Lemke',
        'customerAvatar' => 'assets/images/booking-user-3.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar pays close attention to every detail and always delivers excellent results. Her warm personality and styling expertise make every visit memorable.',
        'timeAgo' => '1m ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_16',
        'customerName' => 'Ed Borer',
        'customerAvatar' => 'assets/images/booking-user-4.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar is a talented stylist who excels in her craft. Her keen eye for detail and personalized service ensure every visit is enjoyable. I always leave feeling refreshed and confident!',
        'timeAgo' => '1m ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_17',
        'customerName' => 'Patti Franey',
        'customerAvatar' => 'assets/images/booking-user-1.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Shannon Morar always delivers outstanding results with incredible precision. Her friendly approach and professional skills make every appointment relaxing. I always leave loving my new look!',
        'timeAgo' => '2m ago',
        'isLive' => false
    ],
    [
        'id' => 'figma_rev_18',
        'customerName' => 'Carroll Torp',
        'customerAvatar' => 'assets/images/booking-user-2.png',
        'stylistName' => 'Shannon Morar',
        'rating' => 5,
        'review' => 'Top tier expertise! The team understands exactly what fits each face shape and hair texture. Super clean salon and relaxing vibe throughout the appointment.',
        'timeAgo' => '2m ago',
        'isLive' => false
    ]
];

// Ensure unique stylists contains Shannon Morar
if (!in_array('Shannon Morar', $uniqueStylists, true)) {
    $uniqueStylists[] = 'Shannon Morar';
}

// Merge live reviews first, then append Figma samples up to 26 items matching Figma
$allReviews = array_merge($liveReviews, $figmaReviews);
$allReviews = array_slice($allReviews, 0, 26);
$totalReviewCount = count($allReviews);

// Top Navbar Component Variables
$currentPage = 'ratings';
$pageTitle = 'Ratings & Reviews';
$pageCountBadge = $totalReviewCount;
$cssVersion = @filemtime(__DIR__ . '/assets/css/style.css') ?: time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ratings & Reviews - ScutS Salon Dashboard</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Main Stylesheet with Cache Buster -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $cssVersion ?>" />

  <!-- Scoped Styles for Ratings & Reviews (Figma Node 8330:7022) -->
  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #FCFCFC;
      color: #000000;
    }

    .app-container {
      display: flex;
      min-height: 100vh;
      padding: 24px;
      gap: 16px;
      max-width: 1440px;
      margin: 0 auto;
      box-sizing: border-box;
    }

    .main-wrapper {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    /* Main Container (Figma Node 8330:7036) */
    .reviews-card-container {
      background-color: #FCFCFC;
      border: 1px solid #EDE8F8;
      border-radius: 16px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-height: 800px;
    }

    /* Top Toolbar (Figma Node 8330:7037) */
    .reviews-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      border-bottom: 1px solid #EDE8F8;
      gap: 16px;
      flex-wrap: wrap;
    }

    /* Search Pill (Figma Node 8330:7038) */
    .reviews-search-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 320px;
      max-width: 100%;
      height: 44px;
      padding: 0 16px;
      background-color: #FCFCFC;
      border: 1px solid #EDE8F8;
      border-radius: 22px;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .reviews-search-pill:focus-within {
      border-color: #8466CF;
      box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
    }

    .reviews-search-pill input {
      border: none;
      outline: none;
      background: transparent;
      width: 100%;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem; /* 14px */
      color: #000000;
    }

    .reviews-search-pill input::placeholder {
      color: #8C8C8C;
      font-weight: 400;
    }

    /* Filter Dropdown Pill (Figma Node 8330:7692) */
    .reviews-filter-wrap {
      position: relative;
    }

    .reviews-filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      height: 40px;
      background-color: #FCFCFC;
      border: 1px solid #E0E0E0;
      border-radius: 50px;
      font-family: 'Manrope', sans-serif;
      font-size: 0.9375rem; /* 15px */
      font-weight: 500;
      color: #000000;
      cursor: pointer;
      user-select: none;
      transition: all 0.15s ease;
    }

    .reviews-filter-btn:hover {
      border-color: #8466CF;
      background-color: #F9F7FD;
    }

    .filter-btn-icon {
      display: flex;
      align-items: center;
      color: #8C8C8C;
    }

    .filter-btn-arrow {
      display: flex;
      align-items: center;
      transition: transform 0.2s ease;
      color: #8C8C8C;
    }

    .reviews-filter-wrap.open .filter-btn-arrow {
      transform: rotate(180deg);
    }

    /* Filter Menu Dropdown */
    .reviews-filter-dropdown {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      width: 220px;
      background-color: #FFFFFF;
      border: 1px solid #EDE8F8;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(132, 102, 207, 0.15), 0 4px 12px rgba(0, 0, 0, 0.06);
      padding: 8px;
      z-index: 100;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-8px);
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .reviews-filter-wrap.open .reviews-filter-dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .filter-dropdown-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 500;
      color: #27272A;
      cursor: pointer;
      background: transparent;
      border: none;
      width: 100%;
      text-align: left;
      font-family: 'Manrope', sans-serif;
      transition: background-color 0.12s ease;
    }

    .filter-dropdown-item:hover {
      background-color: #EDE8F8;
      color: #6D4EB7;
    }

    .filter-dropdown-item.active {
      background-color: #EDE8F8;
      color: #6D4EB7;
      font-weight: 600;
    }

    .filter-divider {
      height: 1px;
      background-color: #EDE8F8;
      margin: 4px 0;
    }

    .filter-section-title {
      font-size: 0.6875rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #8C8C8C;
      padding: 6px 12px 2px;
    }

    /* Reviews Masonry / 3-Column Grid (Figma Node 8330:7069) */
    .reviews-masonry-container {
      display: flex;
      gap: 16px;
      padding: 20px;
      width: 100%;
      box-sizing: border-box;
      align-items: flex-start;
    }

    .reviews-column {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 16px;
      min-width: 0;
    }

    /* Review Card (Figma Node 8330:7354 & 8330:7468) */
    .review-card {
      background-color: #FCFCFC;
      border: 1px solid #EDE8F8;
      border-radius: 12px;
      padding: 16px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      gap: 12px;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      position: relative;
    }

    .review-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(133, 102, 206, 0.1);
      border-color: #8466CF;
    }

    /* Live Badge for real API reviews */
    .review-live-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.6875rem;
      font-weight: 600;
      color: #059669;
      background-color: #ECFDF5;
      padding: 2px 6px;
      border-radius: 4px;
      margin-left: auto;
    }

    /* Stars Row (Figma Node 8330:7356) */
    .review-header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .review-stars-wrap {
      display: flex;
      align-items: center;
      gap: 2px;
    }

    .review-star-icon {
      color: #8466CF; /* Figma uses Theme Color/500 for stars */
      fill: #8466CF;
      width: 14px;
      height: 14px;
    }

    .review-star-icon.inactive {
      color: #E0E0E0;
      fill: #E0E0E0;
    }

    .review-time-text {
      font-family: 'Manrope', sans-serif;
      font-size: 0.75rem; /* 12px */
      font-weight: 500;
      color: #8C8C8C;
      white-space: nowrap;
    }

    /* Review Comment (Figma Node 8330:7369) */
    .review-comment-text {
      margin: 0;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem; /* 14px */
      font-weight: 400;
      color: #18181B;
      line-height: 1.5;
      word-break: break-word;
    }

    /* Customer Info Row (Figma Node 8330:7370) */
    .review-customer-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 4px;
    }

    .review-cust-avatar {
      width: 26px;
      height: 26px;
      min-width: 26px;
      border-radius: 50%;
      object-fit: cover;
      background-color: #EDE8F8;
      border: 1px solid #EDE8F8;
    }

    .review-cust-name {
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem; /* 14px */
      font-weight: 600;
      color: #000000;
      line-height: 18px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .review-stylist-tag {
      margin-left: auto;
      font-size: 0.6875rem;
      font-weight: 500;
      color: #8466CF;
      background-color: #F9F7FD;
      border: 1px solid #EDE8F8;
      border-radius: 6px;
      padding: 2px 6px;
      white-space: nowrap;
    }

    /* Empty state */
    .reviews-empty-state {
      padding: 60px 20px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      width: 100%;
    }

    .reviews-empty-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background-color: #EDE8F8;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #8466CF;
    }

    .reviews-empty-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #000000;
      margin: 0;
    }

    .reviews-empty-desc {
      font-size: 0.875rem;
      color: #8C8C8C;
      margin: 0;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .reviews-masonry-container {
        flex-direction: column;
      }
      .reviews-column {
        width: 100%;
      }
    }
  </style>
</head>
<body>

  <div class="app-container">
    <!-- Left Sidebar Component -->
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">

      <!-- Dynamic Top Navbar Component (Unified ScutS Live Header) -->
      <?php include __DIR__ . '/components/navbar.php'; ?>

      <!-- Ratings & Reviews Main Card (Figma Node 8330:7036) -->
      <main class="reviews-card-container" role="main">

        <!-- Top Toolbar: Search & Filter (Figma Node 8330:7037) -->
        <div class="reviews-toolbar">
          <!-- Search Pill Input -->
          <div class="reviews-search-pill">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="text"
              id="reviewSearchInput"
              placeholder="Search by customer name, stylist"
              aria-label="Search by customer name, stylist"
              oninput="handleReviewSearch()"
            />
          </div>

          <!-- Filter Pill Dropdown (Figma Node 8330:7692) -->
          <div class="reviews-filter-wrap" id="reviewsFilterWrap">
            <button type="button" class="reviews-filter-btn" id="reviewsFilterBtn" onclick="toggleReviewFilter(event)">
              <span class="filter-btn-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
              </span>
              <span id="currentFilterLabel">All</span>
              <span class="filter-btn-arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </span>
            </button>

            <!-- Dropdown Menu -->
            <div class="reviews-filter-dropdown" id="reviewsFilterDropdown" role="menu">
              <button type="button" class="filter-dropdown-item active" onclick="selectReviewFilter(event, 'all', 'All')">
                <span>All Reviews</span>
                <span class="filter-check">✓</span>
              </button>
              
              <div class="filter-divider"></div>
              <div class="filter-section-title">Rating</div>
              <button type="button" class="filter-dropdown-item" onclick="selectReviewFilter(event, 'rating_5', '5 Stars')">
                <span>★★★★★ 5 Stars</span>
              </button>
              <button type="button" class="filter-dropdown-item" onclick="selectReviewFilter(event, 'rating_4', '4 Stars')">
                <span>★★★★☆ 4 Stars</span>
              </button>
              <button type="button" class="filter-dropdown-item" onclick="selectReviewFilter(event, 'rating_3', '3 Stars')">
                <span>★★★☆☆ 3 Stars</span>
              </button>

              <?php if (!empty($uniqueStylists)): ?>
                <div class="filter-divider"></div>
                <div class="filter-section-title">By Stylist</div>
                <?php foreach ($uniqueStylists as $stName): ?>
                  <button type="button" class="filter-dropdown-item" onclick="selectReviewFilter(event, 'stylist_<?= htmlspecialchars(addslashes($stName)) ?>', '<?= htmlspecialchars(addslashes($stName)) ?>')">
                    <span><?= htmlspecialchars($stName) ?></span>
                  </button>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Reviews Masonry / 3-Column Grid (Figma Node 8330:7069) -->
        <div class="reviews-masonry-container" id="reviewsContainer">
          <!-- Column 1 -->
          <div class="reviews-column" id="reviewsCol1"></div>
          <!-- Column 2 -->
          <div class="reviews-column" id="reviewsCol2"></div>
          <!-- Column 3 -->
          <div class="reviews-column" id="reviewsCol3"></div>
        </div>

        <!-- Empty State -->
        <div class="reviews-empty-state" id="reviewsEmptyState" style="display: none;">
          <div class="reviews-empty-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </div>
          <h3 class="reviews-empty-title">No Reviews Found</h3>
          <p class="reviews-empty-desc">Try clearing your search query or selecting a different filter.</p>
        </div>

      </main>
    </div>
  </div>

  <!-- Global Application Scripts -->
  <script src="assets/js/main.js"></script>

  <!-- Client-Side Controller for Ratings & Reviews -->
  <script>
    const ALL_REVIEWS = <?= json_encode($allReviews, JSON_UNESCAPED_SLASHES) ?>;
    let currentFilter = 'all';
    let currentSearch = '';

    document.addEventListener('DOMContentLoaded', () => {
      renderReviewsGrid();

      // Close filter on outside click
      document.addEventListener('click', (e) => {
        const wrap = document.getElementById('reviewsFilterWrap');
        if (wrap && !wrap.contains(e.target)) {
          wrap.classList.remove('open');
        }
      });
    });

    function toggleReviewFilter(e) {
      e.stopPropagation();
      const wrap = document.getElementById('reviewsFilterWrap');
      if (wrap) wrap.classList.toggle('open');
    }

    function selectReviewFilter(event, filterKey, label) {
      if (event && event.stopPropagation) event.stopPropagation();
      currentFilter = filterKey;
      document.getElementById('currentFilterLabel').textContent = label;

      // Update active class on dropdown items
      document.querySelectorAll('.filter-dropdown-item').forEach(item => {
        item.classList.remove('active');
        const check = item.querySelector('.filter-check');
        if (check) check.remove();
      });

      const clickedBtn = (event && event.currentTarget) ? event.currentTarget : null;
      if (clickedBtn) {
        clickedBtn.classList.add('active');
        const checkSpan = document.createElement('span');
        checkSpan.className = 'filter-check';
        checkSpan.textContent = '✓';
        clickedBtn.appendChild(checkSpan);
      }

      const wrap = document.getElementById('reviewsFilterWrap');
      if (wrap) wrap.classList.remove('open');

      renderReviewsGrid();
    }

    function handleReviewSearch() {
      currentSearch = (document.getElementById('reviewSearchInput').value || '').trim().toLowerCase();
      renderReviewsGrid();
    }

    function renderReviewsGrid() {
      const col1 = document.getElementById('reviewsCol1');
      const col2 = document.getElementById('reviewsCol2');
      const col3 = document.getElementById('reviewsCol3');
      const emptyState = document.getElementById('reviewsEmptyState');

      col1.innerHTML = '';
      col2.innerHTML = '';
      col3.innerHTML = '';

      // Filter reviews
      const filtered = ALL_REVIEWS.filter(rev => {
        // Search check
        if (currentSearch) {
          const matchCust = (rev.customerName || '').toLowerCase().includes(currentSearch);
          const matchStylist = (rev.stylistName || '').toLowerCase().includes(currentSearch);
          const matchText = (rev.review || '').toLowerCase().includes(currentSearch);
          if (!matchCust && !matchStylist && !matchText) return false;
        }

        // Filter check
        if (currentFilter === 'all') return true;
        if (currentFilter.startsWith('rating_')) {
          const targetRating = parseInt(currentFilter.replace('rating_', ''), 10);
          return rev.rating === targetRating;
        }
        if (currentFilter.startsWith('stylist_')) {
          const targetStylist = currentFilter.replace('stylist_', '');
          return rev.stylistName === targetStylist;
        }
        return true;
      });

      // Update Count Badge in Header
      const countBadge = document.querySelector('.page-title-count-chip');
      if (countBadge) {
        countBadge.textContent = filtered.length;
      }

      if (filtered.length === 0) {
        emptyState.style.display = 'flex';
        return;
      } else {
        emptyState.style.display = 'none';
      }

      // Distribute into 3 columns (Round-robin balancing)
      filtered.forEach((rev, idx) => {
        const cardHtml = createReviewCardElement(rev);
        const colIdx = idx % 3;
        if (colIdx === 0) col1.appendChild(cardHtml);
        else if (colIdx === 1) col2.appendChild(cardHtml);
        else col3.appendChild(cardHtml);
      });
    }

    function createReviewCardElement(rev) {
      const card = document.createElement('div');
      card.className = 'review-card';
      card.dataset.id = rev.id;

      // Generate Stars HTML
      let starsHtml = '';
      const r = Math.round(rev.rating || 5);
      for (let i = 1; i <= 5; i++) {
        const isFilled = (i <= r);
        starsHtml += `
          <svg class="review-star-icon ${isFilled ? '' : 'inactive'}" viewBox="0 0 24 24">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
        `;
      }

      card.innerHTML = `
        <div class="review-header-row">
          <div class="review-stars-wrap">${starsHtml}</div>
          <span class="review-time-text">${escapeHtml(rev.timeAgo)}</span>
          ${rev.isLive ? '<span class="review-live-pill">Verified</span>' : ''}
        </div>
        <p class="review-comment-text">${escapeHtml(rev.review)}</p>
        <div class="review-customer-row">
          <img
            src="${escapeHtml(rev.customerAvatar)}"
            alt="${escapeHtml(rev.customerName)}"
            class="review-cust-avatar"
            onerror="this.src='assets/images/booking-user-1.png'"
          />
          <span class="review-cust-name" title="${escapeHtml(rev.customerName)}">${escapeHtml(rev.customerName)}</span>
          ${rev.stylistName ? `<span class="review-stylist-tag">${escapeHtml(rev.stylistName)}</span>` : ''}
        </div>
      `;

      return card;
    }

    function escapeHtml(text) {
      if (!text) return '';
      return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }
  </script>
</body>
</html>
