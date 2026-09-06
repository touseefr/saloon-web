<?php
/**
 * ScutS - Settings Module
 * Figma Designs:
 * - Settings -> Profile Tab: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8130-2094
 * - Discard Changes Popup: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8130-2603
 * - Settings -> Content Tab: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8131-2872
 * - Image/Video View Popup: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8132-617
 * - Add Content Popup: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8132-698
 * - Add Content Popup Preview: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8133-529
 * - Remove Content Popup: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8133-681
 * - Settings -> FAQs: https://www.figma.com/design/xGSUFmwbnqnbwWeSVzhvt6/Scuts---UI-Design?node-id=8134-936
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/api.php';

$apiClient = new ScutsApiClient();

// ============================================================================
// AJAX ACTION HANDLERS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // ------------------------------------------------------------------------
    // 1. UPDATE SALON PROFILE (PATCH salon/profile)
    // ------------------------------------------------------------------------
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $ownerMobile = trim($_POST['ownerMobile'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Salon name is required']);
            exit;
        }

        // Fetch current profile to retain geo coordinates and home service
        $currentProfRes = $apiClient->getSalonProfile();
        $currentData = $currentProfRes['data'] ?? ($_SESSION['salon_data'] ?? []);

        $geo = $currentData['geoLocationPoint'] ?? null;
        $coords = $geo['coordinates'] ?? [77.7013947, 12.9729706];

        $fields = [
            'name' => $name,
            'email' => $email,
            'address' => $address,
            'description' => $description,
            'ownerCountryCode' => '91',
            'ownerMobile' => $ownerMobile,
            'countryCode' => '91',
            'geolocationLat' => (string)($coords[1] ?? 12.9729706),
            'geolocationLng' => (string)($coords[0] ?? 77.7013947),
            'homeService' => (string)($currentData['homeService'] ?? 'salon')
        ];

        // Handle uploaded avatar image if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['image']['tmp_name'];
            $mime = mime_content_type($tmpPath) ?: 'image/jpeg';
            $cfile = new CURLFile($tmpPath, $mime, $_FILES['image']['name']);
            $fields['image'] = $cfile;
        }

        $patchRes = $apiClient->requestMultipart('salon/profile', 'PATCH', $fields);

        if ($patchRes && (!empty($patchRes['success']) || (isset($patchRes['statusCode']) && $patchRes['statusCode'] === 200))) {
            // Update session data
            if (!empty($patchRes['data']['salonData'])) {
                $_SESSION['salon_data'] = $patchRes['data']['salonData'];
                $_SESSION['salon_user'] = $patchRes['data']['salonData'];
            }
            if (!empty($patchRes['data']['accessToken'])) {
                $_SESSION['access_token'] = $patchRes['data']['accessToken'];
                $apiClient->setToken($patchRes['data']['accessToken']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Salon profile updated successfully',
                'data' => $patchRes['data']['salonData'] ?? []
            ]);
            exit;
        } else {
            $err = $apiClient->getLastError();
            $msg = $patchRes['message'] ?? ($err['response']['message'] ?? 'Failed to update salon profile');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
    }

    // ------------------------------------------------------------------------
    // 2. ADD SALON CONTENT / BLOG (POST salon/blog/add)
    // ------------------------------------------------------------------------
    if ($action === 'add_content') {
        $description = trim($_POST['caption'] ?? $_POST['description'] ?? '');

        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Caption description is required']);
            exit;
        }

        $fields = [
            'description' => $description
        ];

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['file']['tmp_name'];
            $mime = mime_content_type($tmpPath) ?: 'image/jpeg';
            $fields['file'] = new CURLFile($tmpPath, $mime, $_FILES['file']['name']);
        }

        $addRes = $apiClient->requestMultipart('salon/blog/add', 'POST', $fields);

        if ($addRes && (!empty($addRes['success']) || (isset($addRes['statusCode']) && $addRes['statusCode'] < 300))) {
            echo json_encode([
                'success' => true,
                'message' => 'Content published successfully'
            ]);
            exit;
        } else {
            $err = $apiClient->getLastError();
            $msg = $addRes['message'] ?? ($err['response']['message'] ?? 'Failed to publish content');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
    }

    // ------------------------------------------------------------------------
    // 3. REMOVE SALON CONTENT (DELETE salon/blog/{id})
    // ------------------------------------------------------------------------
    if ($action === 'delete_content') {
        $id = trim($_POST['id'] ?? '');

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Content ID is required']);
            exit;
        }

        $delRes = $apiClient->request('salon/blog/' . urlencode($id), 'DELETE');

        if ($delRes && (!empty($delRes['success']) || (isset($delRes['statusCode']) && $delRes['statusCode'] < 300))) {
            echo json_encode([
                'success' => true,
                'message' => 'Content removed permanently'
            ]);
            exit;
        } else {
            // If sample or mocked item, return success for local removal
            if (str_starts_with($id, 'figma_content_')) {
                echo json_encode(['success' => true, 'message' => 'Content removed permanently']);
                exit;
            }
            $err = $apiClient->getLastError();
            $msg = $delRes['message'] ?? ($err['response']['message'] ?? 'Failed to remove content');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ============================================================================
// INITIAL PAGE DATA RESOLUTION
// ============================================================================

// 1. Fetch Salon Profile
$profileRes = $apiClient->getSalonProfile();
$salon = $profileRes['data'] ?? ($_SESSION['salon_data'] ?? []);

$salonName = $salon['name'] ?? 'Cut n Curl unisex salon';
$salonEmail = $salon['email'] ?? 'cutncurl85@gmail.com';
$salonMobile = $salon['mobile'] ?? '9663777636';
$ownerMobile = $salon['ownerMobile'] ?? '9880652333';
$salonAddress = $salon['address'] ?? "Cut 'n' curl unisex salon, Vinayaka Layout, chinnappanahali, Marathahalli, Bengaluru, Karnataka, India";
$salonAbout = $salon['description'] ?? 'we offers all hair, beauty services.';
$salonImage = !empty($salon['image']) ? $apiClient->formatImageUrl($salon['image']) : 'assets/images/user-avatar.png';

// 2. Fetch Live Salon Blogs / Content
$liveBlogs = [];
$blogRes = $apiClient->request('salon/blog/salon-blogs');
if (!empty($blogRes['data']) && is_array($blogRes['data'])) {
    foreach ($blogRes['data'] as $b) {
        $mediaUrl = '';
        $isVideo = false;
        if (!empty($b['video'])) {
            $mediaUrl = $apiClient->formatImageUrl($b['video']);
            $isVideo = true;
        } elseif (!empty($b['image'])) {
            $mediaUrl = $apiClient->formatImageUrl($b['image']);
        }

        $thumbnail = '';
        if (!empty($b['thumbnail'])) {
            $thumbnail = $apiClient->formatImageUrl($b['thumbnail']);
        } elseif (!empty($b['image'])) {
            $thumbnail = $apiClient->formatImageUrl($b['image']);
        } elseif ($isVideo) {
            $thumbnail = 'assets/images/portfolio_sample1.png';
        }

        $liveBlogs[] = [
            'id' => $b['id'],
            'mediaUrl' => $mediaUrl,
            'thumbnail' => $thumbnail,
            'isVideo' => $isVideo,
            'caption' => $b['description'] ?? 'Salon work and styling',
            'isLive' => true
        ];
    }
}

// 100% Dynamic with live ScutS backend API
$allContent = $liveBlogs;

// 4. Exact FAQs from Figma Node 8134:936
$faqs = [
    [
        'question' => 'What features does Scuts offer to customers?',
        'answer' => "Scuts provides:\n  - Nearby salon discovery with ratings and distance.\n  - Detailed stylist profiles to choose your preferred professional.\n  - A service catalog for each salon.\n  - Appointment booking with specific stylists.\n  - Personalized blogs and insights based on your hair and skin type.",
        'isOpen' => true
    ],
    [
        'question' => 'What is Stylist Profile and How it helps?',
        'answer' => "A Stylist Profile displays a professional's expertise, years of experience, customer reviews, rating, and a portfolio of past work. It empowers customers to choose the exact stylist who best matches their preferences.",
        'isOpen' => false
    ],
    [
        'question' => 'What are personalized insights?',
        'answer' => 'Personalized insights provide customized haircare and skincare recommendations, service suggestions, and blog articles based on your individual profile, hair type, and styling goals.',
        'isOpen' => false
    ],
    [
        'question' => 'How do I check salon ratings?',
        'answer' => 'Salon ratings are prominently displayed on each salon card and profile page, calculated transparently from verified customer reviews and stylist feedback.',
        'isOpen' => false
    ],
    [
        'question' => 'Is Scuts free for customers?',
        'answer' => 'Yes, searching for salons, browsing stylist profiles, viewing reviews, and reading style insights on Scuts is completely free for all customers.',
        'isOpen' => false
    ],
    [
        'question' => 'Can I choose my stylist?',
        'answer' => 'Absolutely! During the booking process, you can view all available stylists at the salon and select your preferred expert based on ratings, portfolio, and schedule.',
        'isOpen' => false
    ],
    [
        'question' => 'Can I customise service products?',
        'answer' => 'Yes, many partner salons allow you to choose specific branded products (e.g., organic, ammonia-free, luxury lines) when customizing your treatment package.',
        'isOpen' => false
    ],
    [
        'question' => 'How average stylist rating of salon is calculated?',
        'answer' => 'The average rating is calculated as a weighted mean of all verified customer ratings submitted for appointments served by stylists at that salon location.',
        'isOpen' => false
    ],
    [
        'question' => 'Do We Save the pictures of the service without the consent?',
        'answer' => 'No. All photos and portfolio images are collected and shared strictly with explicit customer and stylist consent in accordance with our privacy guidelines.',
        'isOpen' => false
    ],
    [
        'question' => 'why do we collect service images?',
        'answer' => 'Service images help stylists showcase their craftsmanship, inspire other customers with real transformations, and maintain a visual record of quality service delivery.',
        'isOpen' => false
    ]
];

// Navbar Variables
$currentPage = 'settings';
$pageTitle = 'Settings';
$activeTab = in_array($_GET['tab'] ?? '', ['profile', 'content', 'faqs'], true) ? $_GET['tab'] : 'profile';
$cssVersion = @filemtime(__DIR__ . '/assets/css/style.css') ?: time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings - ScutS Salon Dashboard</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Main Stylesheet with Cache Buster -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $cssVersion ?>" />

  <!-- Scoped Styles for Settings Module (Figma 8130:2094, 8131:2872, 8134:936) -->
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

    /* Main Card Container (Figma 8130:2506) */
    .settings-card-container {
      background-color: #FCFCFC;
      border: 1px solid #EDE8F8;
      border-radius: 16px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
      display: flex;
      min-height: 850px;
      overflow: hidden;
    }

    /* Sub-Navigation Sidebar (Figma 8130:2369) */
    .settings-nav-sidebar {
      width: 260px;
      min-width: 260px;
      border-right: 1px solid #EDE8F8;
      padding: 16px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      gap: 16px;
      background-color: #FCFCFC;
    }

    .settings-menu-list {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .settings-menu-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 1.125rem; /* 18px */
      font-weight: 500;
      color: #707070;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      user-select: none;
      border: none;
      background: transparent;
      width: 100%;
      text-align: left;
    }

    .settings-menu-item:hover {
      background-color: #F9F7FD;
      color: #8466CF;
    }

    .settings-menu-item.active {
      background-color: #EDE8F8;
      color: #8466CF;
      font-weight: 600;
    }

    /* Settings Tab Content Area */
    .settings-content-area {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
    }

    /* Tab Header (Figma 8130:2566 & 8131:2897) */
    .settings-tab-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      border-bottom: 1px solid #EDE8F8;
      gap: 16px;
    }

    .settings-tab-title {
      font-size: 1.25rem; /* 20px */
      font-weight: 500;
      color: #000000;
      margin: 0;
    }

    .settings-header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Standard Button Styles */
    .btn-pill-discard {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 22px;
      border: 1px solid #707070;
      background-color: #FCFCFC;
      color: #707070;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem; /* 14px */
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .btn-pill-discard:hover {
      background-color: #F4F4F5;
      color: #18181B;
      border-color: #18181B;
    }

    .btn-pill-save {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 24px;
      border-radius: 22px;
      border: none;
      background-color: #8466CF;
      color: #FCFCFC;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem; /* 14px */
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .btn-pill-save:hover {
      background-color: #7252bd;
      box-shadow: 0 4px 12px rgba(132, 102, 207, 0.25);
    }

    .btn-pill-save:disabled,
    .btn-pill-discard:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* Tab Panes */
    .tab-pane {
      display: none;
      flex-direction: column;
      flex: 1;
    }

    .tab-pane.active {
      display: flex;
    }

    /* ========================================================================
       TAB 1: PROFILE TAB (Figma 8130:2094)
       ======================================================================== */
    .profile-form-body {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 24px;
      max-width: 900px;
    }

    /* Profile Avatar (86x86 + 32x32 button) */
    .profile-avatar-wrap {
      position: relative;
      width: 86px;
      height: 86px;
      cursor: pointer;
    }

    .profile-avatar-img {
      width: 86px;
      height: 86px;
      border-radius: 50%;
      object-fit: cover;
      border: 1px solid #8466CF;
      background-color: #EDE8F8;
      display: block;
    }

    .profile-avatar-badge {
      position: absolute;
      right: -2px;
      bottom: -2px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: #8466CF;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
      transition: transform 0.15s ease;
    }

    .profile-avatar-wrap:hover .profile-avatar-badge {
      transform: scale(1.08);
    }

    /* Form Fields */
    .form-row-2col {
      display: flex;
      gap: 24px;
      width: 100%;
    }

    .form-group {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }

    .form-label {
      font-size: 0.875rem; /* 14px */
      font-weight: 500;
      color: #8C8C8C;
    }

    .form-input-pill {
      height: 46px;
      border: 1px solid #EDE8F8;
      border-radius: 12px;
      padding: 0 16px;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem;
      font-weight: 500;
      color: #000000;
      background-color: #FCFCFC;
      box-sizing: border-box;
      outline: none;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .form-input-pill:focus {
      border-color: #8466CF;
      box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
    }

    /* Phone Input with +91 Prefix */
    .phone-input-wrap {
      display: flex;
      align-items: center;
      height: 46px;
      border: 1px solid #EDE8F8;
      border-radius: 12px;
      padding: 0 16px;
      gap: 8px;
      background-color: #FCFCFC;
      box-sizing: border-box;
      transition: border-color 0.15s ease;
    }

    .phone-input-wrap:focus-within {
      border-color: #8466CF;
      box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
    }

    .phone-prefix {
      font-size: 0.875rem;
      font-weight: 500;
      color: #8466CF;
      user-select: none;
    }

    .phone-input {
      border: none;
      outline: none;
      background: transparent;
      width: 100%;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem;
      font-weight: 500;
      color: #000000;
    }

    .phone-input:disabled {
      color: #707070;
      cursor: not-allowed;
    }

    /* Textareas */
    .form-textarea-box {
      border: 1px solid #EDE8F8;
      border-radius: 12px;
      padding: 16px;
      background-color: #FCFCFC;
      display: flex;
      flex-direction: column;
      gap: 8px;
      box-sizing: border-box;
      transition: border-color 0.15s ease;
    }

    .form-textarea-box:focus-within {
      border-color: #8466CF;
      box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
    }

    .form-textarea-field {
      border: none;
      outline: none;
      background: transparent;
      resize: vertical;
      min-height: 72px;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem;
      font-weight: 500;
      color: #000000;
      line-height: 1.5;
    }

    .char-count-text {
      font-size: 0.75rem; /* 12px */
      font-weight: 500;
      color: #8466CF;
      text-align: right;
      user-select: none;
    }

    /* ========================================================================
       TAB 2: CONTENT TAB (Figma 8131:2872)
       ======================================================================== */
    .content-gallery-body {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .content-grid-4col {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 24px;
      width: 100%;
    }

    .content-media-card {
      position: relative;
      height: 320px;
      border-radius: 16px;
      overflow: hidden;
      cursor: pointer;
      background-color: #18181B;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .content-media-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(132, 102, 207, 0.15);
    }

    .content-media-bg {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .content-card-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.15) 0%, rgba(0, 0, 0, 0.65) 100%);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 16px;
      box-sizing: border-box;
      opacity: 1;
      transition: opacity 0.2s ease;
    }

    .content-card-top-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    .card-icon-btn {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: rgba(255, 255, 255, 0.85);
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #18181B;
      cursor: pointer;
      transition: background-color 0.15s ease, transform 0.15s ease;
    }

    .card-icon-btn:hover {
      background-color: #FFFFFF;
      transform: scale(1.08);
    }

    .card-icon-btn.btn-delete:hover {
      background-color: #FEE2E2;
      color: #EF4444;
    }

    .content-play-btn-circle {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background-color: #FCFCFC;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #8466CF;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
      pointer-events: none;
      transition: transform 0.2s ease;
    }

    .content-media-card:hover .content-play-btn-circle {
      transform: translate(-50%, -50%) scale(1.1);
    }

    .content-caption-preview {
      font-size: 0.8125rem; /* 13px */
      font-weight: 500;
      color: #FFFFFF;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin: 0;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    }

    /* Empty state */
    .content-empty-box {
      padding: 60px 20px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
      width: 100%;
    }

    /* ========================================================================
       TAB 3: FAQS TAB (Figma 8134:936)
       ======================================================================== */
    .faqs-body {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .faqs-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .faq-card {
      border: 1.5px solid #E0E0E0;
      border-radius: 20px;
      background-color: #FCFCFC;
      backdrop-filter: blur(5px);
      overflow: hidden;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .faq-card.open {
      border-color: #8466CF;
      box-shadow: 0 4px 20px rgba(132, 102, 207, 0.08);
    }

    .faq-header {
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      cursor: pointer;
      user-select: none;
    }

    .faq-question-text {
      font-size: 1.125rem; /* 18px */
      font-weight: 500;
      color: #000000;
      margin: 0;
      flex: 1;
    }

    .faq-toggle-icon {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #707070;
      transition: transform 0.25s ease;
      min-width: 24px;
    }

    .faq-card.open .faq-toggle-icon {
      transform: rotate(180deg);
      color: #8466CF;
    }

    .faq-answer-content {
      display: none;
      padding: 0 20px 20px 20px;
      font-size: 1rem; /* 16px */
      font-weight: 400;
      color: #707070;
      line-height: 1.6;
      white-space: pre-line;
    }

    .faq-card.open .faq-answer-content {
      display: block;
    }

    /* FAQ Help & Support Card (Figma 8134:1174) */
    .faq-support-card {
      background-color: #F9F7FD;
      border: 1px solid #EDE8F8;
      border-radius: 16px;
      padding: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 32px;
      flex-wrap: wrap;
    }

    .faq-support-text {
      font-size: 1.125rem;
      font-weight: 500;
      color: #000000;
      margin: 0;
      flex: 1;
      min-width: 280px;
    }

    .faq-support-contacts {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .faq-contact-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 16px;
      background-color: #FCFCFC;
      border: 1px solid #EDE8F8;
      border-radius: 12px;
      color: #8466CF;
      font-size: 0.875rem;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.15s ease;
    }

    .faq-contact-pill:hover {
      background-color: #EDE8F8;
      border-color: #8466CF;
    }

    /* ========================================================================
       MODAL POPUPS (Figma 8130:2603, 8132:617, 8132:698, 8133:529, 8133:681)
       ======================================================================== */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(4px);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s ease, visibility 0.2s ease;
    }

    .modal-backdrop.show {
      opacity: 1;
      visibility: visible;
    }

    .modal-window {
      background-color: #FCFCFC;
      border: 1px solid #EDE8F8;
      border-radius: 16px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      max-width: 100%;
      transform: scale(0.95);
      transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .modal-backdrop.show .modal-window {
      transform: scale(1);
    }

    /* Confirmation Modal (400px - 460px) */
    .confirm-modal-box {
      width: 440px;
    }

    .confirm-modal-content {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .confirm-modal-title {
      font-size: 1.25rem; /* 20px */
      font-weight: 500;
      color: #000000;
      margin: 0;
    }

    .confirm-modal-desc {
      font-size: 1rem; /* 16px */
      color: #8C8C8C;
      margin: 0;
      line-height: 1.5;
    }

    .confirm-modal-footer {
      padding: 16px 24px;
      border-top: 1px solid #EDE8F8;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }

    .btn-modal-action {
      min-width: 86px;
      padding: 8px 18px;
      border-radius: 22px;
      font-family: 'Manrope', sans-serif;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
    }

    .btn-modal-secondary {
      background-color: #FCFCFC;
      border-color: #707070;
      color: #707070;
    }

    .btn-modal-secondary:hover {
      background-color: #F4F4F5;
      color: #18181B;
      border-color: #18181B;
    }

    .btn-modal-primary {
      background-color: #8466CF;
      color: #FCFCFC;
    }

    .btn-modal-primary:hover {
      background-color: #7252bd;
    }

    .btn-modal-danger {
      background-color: #EF4444;
      color: #FCFCFC;
      min-width: 120px;
    }

    .btn-modal-danger:hover {
      background-color: #dc2626;
    }

    /* Add Content Modal (560px) */
    .add-content-modal-box {
      width: 610px;
    }

    .modal-nav-header {
      padding: 16px 24px;
      border-bottom: 1px solid #EDE8F8;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-nav-title {
      font-size: 1.25rem;
      font-weight: 500;
      color: #000000;
      margin: 0;
    }

    .add-content-body {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* Dropzone (560x320) */
    .content-dropzone {
      width: 100%;
      height: 280px;
      border: 1px dashed #B5A3E2;
      border-radius: 12px;
      background-color: #F9F7FD;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      cursor: pointer;
      box-sizing: border-box;
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
    }

    .content-dropzone:hover,
    .content-dropzone.dragover {
      border-color: #8466CF;
      background-color: #EDE8F8;
    }

    .dropzone-icon-circle {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background-color: #EDE8F8;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #8466CF;
    }

    .dropzone-prompt-text {
      font-size: 0.875rem;
      font-weight: 400;
      color: #000000;
      margin: 0;
    }

    /* Preview Container inside Dropzone */
    .dropzone-preview-wrap {
      width: 100%;
      height: 100%;
      display: none;
      position: relative;
      background-color: #000000;
    }

    .dropzone-preview-media {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .dropzone-replace-btn {
      position: absolute;
      bottom: 16px;
      right: 16px;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background-color: #EDE8F8;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #8466CF;
      border: none;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      transition: transform 0.15s ease;
    }

    .dropzone-replace-btn:hover {
      transform: scale(1.08);
      background-color: #FFFFFF;
    }

    /* Image/Video View Modal (320x569 Mobile Reel Aspect) */
    .view-media-modal-box {
      width: 320px;
      height: 569px;
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      background-color: #000000;
    }

    .view-media-element {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .view-media-close-btn {
      position: absolute;
      top: 16px;
      right: 16px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(4px);
      border: none;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 10;
      transition: background-color 0.15s ease;
    }

    .view-media-close-btn:hover {
      background-color: rgba(0, 0, 0, 0.7);
    }

    .view-media-bottom-bar {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 16px;
      background: linear-gradient(0deg, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 10;
    }

    .view-media-caption-text {
      flex: 1;
      font-size: 0.75rem; /* 12px */
      font-weight: 400;
      color: #FCFCFC;
      line-height: 1.4;
      margin: 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .view-bar-btn {
      width: 32px;
      height: 32px;
      min-width: 32px;
      border-radius: 50%;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: transform 0.15s ease;
    }

    .view-bar-btn:hover {
      transform: scale(1.08);
    }

    .view-bar-btn.white {
      background-color: #FCFCFC;
      color: #EF4444;
    }

    .view-bar-btn.purple {
      background-color: #8466CF;
      color: #FCFCFC;
    }

    /* Toast Notification */
    .toast-container {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 2000;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .toast-msg {
      padding: 12px 20px;
      border-radius: 12px;
      font-size: 0.875rem;
      font-weight: 500;
      color: #FFFFFF;
      background-color: #18181B;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      gap: 10px;
      animation: toastIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .toast-msg.success {
      background-color: #059669;
    }

    .toast-msg.error {
      background-color: #DC2626;
    }

    @keyframes toastIn {
      from { transform: translateY(12px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
      .settings-card-container {
        flex-direction: column;
      }
      .settings-nav-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #EDE8F8;
      }
      .settings-menu-list {
        flex-direction: row;
        overflow-x: auto;
      }
      .content-grid-4col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
      .form-row-2col {
        flex-direction: column;
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

      <!-- Settings Main Card (Figma Node 8130:2506) -->
      <main class="settings-card-container" role="main">

        <!-- Inner Sub-Navigation Sidebar (Figma Node 8130:2369) -->
        <aside class="settings-nav-sidebar" aria-label="Settings navigation">
          <ul class="settings-menu-list" role="tablist">
            <li>
              <button
                type="button"
                class="settings-menu-item <?= ($activeTab === 'profile') ? 'active' : '' ?>"
                id="tabBtn-profile"
                role="tab"
                aria-selected="<?= ($activeTab === 'profile') ? 'true' : 'false' ?>"
                onclick="switchSettingsTab('profile')"
              >
                <span>Profile</span>
              </button>
            </li>
            <li>
              <button
                type="button"
                class="settings-menu-item <?= ($activeTab === 'content') ? 'active' : '' ?>"
                id="tabBtn-content"
                role="tab"
                aria-selected="<?= ($activeTab === 'content') ? 'true' : 'false' ?>"
                onclick="switchSettingsTab('content')"
              >
                <span>Content</span>
              </button>
            </li>
            <li>
              <button
                type="button"
                class="settings-menu-item <?= ($activeTab === 'faqs') ? 'active' : '' ?>"
                id="tabBtn-faqs"
                role="tab"
                aria-selected="<?= ($activeTab === 'faqs') ? 'true' : 'false' ?>"
                onclick="switchSettingsTab('faqs')"
              >
                <span>FAQs</span>
              </button>
            </li>
          </ul>
        </aside>

        <!-- Tab Content Area -->
        <section class="settings-content-area">

          <!-- ================================================================
               TAB 1: SALON PROFILE (Figma Node 8130:2094)
               ================================================================ -->
          <div class="tab-pane <?= ($activeTab === 'profile') ? 'active' : '' ?>" id="tabPane-profile" role="tabpanel">
            
            <!-- Sub-Header with Discard & Save Actions -->
            <div class="settings-tab-header">
              <h2 class="settings-tab-title">Salon’s profile</h2>
              <div class="settings-header-actions">
                <button type="button" class="btn-pill-discard" id="btnDiscardProfile" onclick="handleDiscardClick()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                  <span>DISCARD</span>
                </button>
                <button type="button" class="btn-pill-save" id="btnSaveProfile" onclick="handleSaveProfile()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span>SAVE</span>
                </button>
              </div>
            </div>

            <!-- Profile Form Content -->
            <form id="profileForm" class="profile-form-body" onsubmit="event.preventDefault(); handleSaveProfile();">
              
              <!-- Avatar with Camera Badge -->
              <div class="profile-avatar-wrap" onclick="document.getElementById('profileAvatarInput').click()" title="Change salon profile picture">
                <img
                  src="<?= htmlspecialchars($salonImage) ?>"
                  alt="<?= htmlspecialchars($salonName) ?>"
                  id="profileAvatarPreview"
                  class="profile-avatar-img"
                  onerror="this.onerror=null; this.src='assets/images/user-avatar.png'"
                />
                <div class="profile-avatar-badge" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                  </svg>
                </div>
                <input
                  type="file"
                  id="profileAvatarInput"
                  name="image"
                  accept="image/*"
                  style="display: none;"
                  onchange="handleAvatarFileSelect(this)"
                />
              </div>

              <!-- Row 1: Salon Name & Email Address -->
              <div class="form-row-2col">
                <div class="form-group">
                  <label class="form-label" for="profileName">Salon name</label>
                  <input
                    type="text"
                    id="profileName"
                    name="name"
                    class="form-input-pill"
                    value="<?= htmlspecialchars($salonName) ?>"
                    required
                    oninput="markFormDirty()"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label" for="profileEmail">Email address</label>
                  <input
                    type="email"
                    id="profileEmail"
                    name="email"
                    class="form-input-pill"
                    value="<?= htmlspecialchars($salonEmail) ?>"
                    required
                    oninput="markFormDirty()"
                  />
                </div>
              </div>

              <!-- Row 2: Salon Mobile & Owner Mobile -->
              <div class="form-row-2col">
                <div class="form-group">
                  <label class="form-label" for="profileMobile">Salon Mobile number</label>
                  <div class="phone-input-wrap">
                    <span class="phone-prefix">+91</span>
                    <input
                      type="text"
                      id="profileMobile"
                      name="mobile"
                      class="phone-input"
                      value="<?= htmlspecialchars($salonMobile) ?>"
                      disabled
                      title="Registered salon login mobile (read-only)"
                    />
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label" for="profileOwnerMobile">Owner mobile number</label>
                  <div class="phone-input-wrap">
                    <span class="phone-prefix">+91</span>
                    <input
                      type="text"
                      id="profileOwnerMobile"
                      name="ownerMobile"
                      class="phone-input"
                      value="<?= htmlspecialchars($ownerMobile) ?>"
                      oninput="markFormDirty()"
                    />
                  </div>
                </div>
              </div>

              <!-- Row 3: Salon Address -->
              <div class="form-group">
                <label class="form-label" for="profileAddress">Salon address</label>
                <div class="form-textarea-box">
                  <textarea
                    id="profileAddress"
                    name="address"
                    class="form-textarea-field"
                    rows="2"
                    oninput="markFormDirty()"
                  ><?= htmlspecialchars($salonAddress) ?></textarea>
                </div>
              </div>

              <!-- Row 4: About Salon with Character Limit -->
              <div class="form-group">
                <label class="form-label" for="profileAbout">About salon</label>
                <div class="form-textarea-box">
                  <textarea
                    id="profileAbout"
                    name="description"
                    class="form-textarea-field"
                    rows="4"
                    maxlength="250"
                    oninput="updateAboutCounter(this); markFormDirty();"
                  ><?= htmlspecialchars($salonAbout) ?></textarea>
                  <span class="char-count-text" id="aboutCharCount"><?= mb_strlen($salonAbout) ?>/250</span>
                </div>
              </div>

            </form>
          </div>

          <!-- ================================================================
               TAB 2: CONTENT TAB (Figma Node 8131:2872)
               ================================================================ -->
          <div class="tab-pane <?= ($activeTab === 'content') ? 'active' : '' ?>" id="tabPane-content" role="tabpanel">
            
            <!-- Sub-Header with Add Content Action -->
            <div class="settings-tab-header">
              <h2 class="settings-tab-title">Content</h2>
              <button type="button" class="btn-pill-save" onclick="openAddContentModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>ADD CONTENT</span>
              </button>
            </div>

            <!-- Media Gallery or Empty State -->
            <div class="content-gallery-body">
              <?php if (empty($allContent)): ?>
                <div class="content-empty-state" style="text-align: center; padding: 64px 20px; background: #FAF9FD; border-radius: 16px; border: 1px dashed #EDE8F8;">
                  <div style="width: 60px; height: 60px; border-radius: 50%; background: #EDE8F8; color: #8466CF; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                      <circle cx="8.5" cy="8.5" r="1.5"></circle>
                      <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                  </div>
                  <h3 style="font-size: 1.125rem; font-weight: 600; color: #18181B; margin-bottom: 8px;">No Salon Content Published</h3>
                  <p style="font-size: 0.875rem; color: #707070; margin-bottom: 24px; max-width: 440px; margin-left: auto; margin-right: auto;">Upload photos and videos of your hairstyles, treatments, and transformations to showcase your work to clients.</p>
                  <button type="button" class="btn-pill-save" onclick="openAddContentModal()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>ADD CONTENT</span>
                  </button>
                </div>
              <?php else: ?>
                <div class="content-grid-4col" id="contentGrid">
                  <?php foreach ($allContent as $item): ?>
                    <div
                      class="content-media-card"
                      data-id="<?= htmlspecialchars($item['id']) ?>"
                      data-url="<?= htmlspecialchars($item['mediaUrl']) ?>"
                      data-caption="<?= htmlspecialchars($item['caption']) ?>"
                      data-is-video="<?= $item['isVideo'] ? '1' : '0' ?>"
                      onclick="openViewMediaModal(this)"
                    >
                      <img
                        src="<?= htmlspecialchars($item['thumbnail']) ?>"
                        alt="<?= htmlspecialchars($item['caption']) ?>"
                        class="content-media-bg"
                        onerror="this.onerror=null; this.src='assets/images/portfolio_sample1.png'"
                      />

                      <!-- Dark Gradient Overlay -->
                      <div class="content-card-overlay">
                        <div class="content-card-top-actions">
                          <button
                            type="button"
                            class="card-icon-btn btn-delete"
                            title="Remove Content"
                            onclick="event.stopPropagation(); promptRemoveContent('<?= htmlspecialchars(addslashes($item['id'])) ?>')"
                          >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <polyline points="3 6 5 6 21 6"></polyline>
                              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                          </button>
                        </div>

                        <?php if ($item['isVideo']): ?>
                          <div class="content-play-btn-circle" aria-label="Play video">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                              <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                          </div>
                        <?php endif; ?>

                        <p class="content-caption-preview"><?= htmlspecialchars($item['caption']) ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

          </div>

          <!-- ================================================================
               TAB 3: FAQS TAB (Figma Node 8134:936)
               ================================================================ -->
          <div class="tab-pane <?= ($activeTab === 'faqs') ? 'active' : '' ?>" id="tabPane-faqs" role="tabpanel">
            
            <div class="settings-tab-header">
              <h2 class="settings-tab-title">FAQs</h2>
            </div>

            <div class="faqs-body">
              
              <!-- 10 Accordion Cards -->
              <div class="faqs-list">
                <?php foreach ($faqs as $index => $faq): ?>
                  <div class="faq-card <?= !empty($faq['isOpen']) ? 'open' : '' ?>" id="faqCard-<?= $index ?>">
                    <div class="faq-header" onclick="toggleFaqAccordion(<?= $index ?>)">
                      <h3 class="faq-question-text"><?= htmlspecialchars($faq['question']) ?></h3>
                      <span class="faq-toggle-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </span>
                    </div>
                    <div class="faq-answer-content"><?= htmlspecialchars($faq['answer']) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- Help / Support Contact Banner (Figma Node 8134:1174) -->
              <div class="faq-support-card">
                <p class="faq-support-text">Have questions? We're here to help! Reach out using the contact details below.</p>
                <div class="faq-support-contacts">
                  <a href="mailto:talktous@scuts.in" class="faq-contact-pill">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                      <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span>talktous@scuts.in</span>
                  </a>
                  <a href="tel:+918897090838" class="faq-contact-pill">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>+91 88970 90838</span>
                  </a>
                </div>
              </div>

            </div>

          </div>

        </section>

      </main>
    </div>
  </div>

  <!-- ==========================================================================
       MODAL 1: DISCARD UNSAVED CHANGES CONFIRMATION (Figma Node 8130:2603)
       ========================================================================== -->
  <div class="modal-backdrop" id="discardModal" onclick="closeModalOnBackdrop(event, 'discardModal')">
    <div class="modal-window confirm-modal-box" role="dialog" aria-modal="true">
      <div class="confirm-modal-content">
        <h3 class="confirm-modal-title">Discard unsaved changes?</h3>
        <p class="confirm-modal-desc">Any unsaved edits will be permanently lost and cannot be recovered.</p>
      </div>
      <div class="confirm-modal-footer">
        <button type="button" class="btn-modal-action btn-modal-secondary" onclick="closeDiscardModal()">No</button>
        <button type="button" class="btn-modal-action btn-modal-primary" onclick="confirmDiscardChanges()">Yes</button>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       MODAL 2: IMAGE / VIDEO VIEW POPUP (Figma Node 8132:617)
       ========================================================================== -->
  <div class="modal-backdrop" id="mediaViewModal" onclick="closeModalOnBackdrop(event, 'mediaViewModal')">
    <div class="modal-window view-media-modal-box" role="dialog" aria-modal="true">
      <button type="button" class="view-media-close-btn" onclick="closeViewMediaModal()" aria-label="Close media preview">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <!-- Media Element (Dynamic Image or Video) -->
      <div id="viewMediaContainer" style="width: 100%; height: 100%;">
        <img src="" alt="" id="viewMediaImg" class="view-media-element" style="display: none;" />
        <video src="" id="viewMediaVideo" class="view-media-element" controls playsinline style="display: none;"></video>
      </div>

      <!-- Bottom Floating Overlay Bar -->
      <div class="view-media-bottom-bar">
        <p class="view-media-caption-text" id="viewMediaCaptionText"></p>
        <button type="button" class="view-bar-btn white" id="viewMediaDeleteBtn" title="Remove content">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </button>
        <button type="button" class="view-bar-btn purple" title="Favorite">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       MODAL 3: ADD CONTENT POPUP (Figma Nodes 8132:698 & 8133:529)
       ========================================================================== -->
  <div class="modal-backdrop" id="addContentModal" onclick="closeModalOnBackdrop(event, 'addContentModal')">
    <div class="modal-window add-content-modal-box" role="dialog" aria-modal="true">
      <div class="modal-nav-header">
        <h3 class="modal-nav-title">Add Content</h3>
      </div>

      <div class="add-content-body">
        
        <!-- Dropzone / Media Preview Area -->
        <div class="content-dropzone" id="contentDropzone" onclick="document.getElementById('contentFileInput').click()">
          
          <!-- State A: Empty Prompt -->
          <div id="dropzoneEmptyState" style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
            <div class="dropzone-icon-circle">
              <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
              </svg>
            </div>
            <p class="dropzone-prompt-text">Drag and drop media here.</p>
          </div>

          <!-- State B: Selected Media Preview -->
          <div class="dropzone-preview-wrap" id="dropzonePreviewWrap">
            <img src="" alt="Preview" id="addContentImgPreview" class="dropzone-preview-media" style="display: none;" />
            <video src="" id="addContentVideoPreview" class="dropzone-preview-media" controls playsinline style="display: none;"></video>
            
            <button
              type="button"
              class="dropzone-replace-btn"
              title="Replace media"
              onclick="event.stopPropagation(); document.getElementById('contentFileInput').click()"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
              </svg>
            </button>
          </div>

          <input
            type="file"
            id="contentFileInput"
            accept="image/*,video/mp4,video/webm,video/quicktime"
            style="display: none;"
            onchange="handleContentFileSelect(this)"
          />
        </div>

        <!-- Caption Input -->
        <div class="form-group">
          <label class="form-label" for="contentCaptionInput">Caption</label>
          <div class="form-textarea-box">
            <textarea
              id="contentCaptionInput"
              class="form-textarea-field"
              rows="3"
              maxlength="120"
              placeholder="Write about the content"
              oninput="updateContentCaptionCounter(this)"
            ></textarea>
            <span class="char-count-text" id="contentCaptionCounter">0/120</span>
          </div>
        </div>

      </div>

      <div class="confirm-modal-footer">
        <button type="button" class="btn-modal-action btn-modal-secondary" onclick="closeAddContentModal()">CANCEL</button>
        <button type="button" class="btn-modal-action btn-modal-primary" id="btnPublishContent" onclick="handlePublishContent()">Publish</button>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       MODAL 4: REMOVE CONTENT PERMANENTLY POPUP (Figma Node 8133:681)
       ========================================================================== -->
  <div class="modal-backdrop" id="removeContentModal" onclick="closeModalOnBackdrop(event, 'removeContentModal')">
    <div class="modal-window confirm-modal-box" role="dialog" aria-modal="true">
      <div class="confirm-modal-content">
        <h3 class="confirm-modal-title">Remove content permanently?</h3>
        <p class="confirm-modal-desc">Once removed, the content and its related information may become permanently unavailable.</p>
      </div>
      <div class="confirm-modal-footer">
        <button type="button" class="btn-modal-action btn-modal-secondary" onclick="closeRemoveModal()">Cancel</button>
        <button type="button" class="btn-modal-action btn-modal-danger" id="btnConfirmRemove" onclick="executeRemoveContent()">Remove</button>
      </div>
    </div>
  </div>

  <!-- Global Toast Notifications Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Global Application Scripts -->
  <script src="assets/js/main.js"></script>

  <!-- Client-Side Controller for Settings Module -->
  <script>
    // Initial Form State for Dirty Tracking
    let originalFormState = {
      name: <?= json_encode($salonName) ?>,
      email: <?= json_encode($salonEmail) ?>,
      ownerMobile: <?= json_encode($ownerMobile) ?>,
      address: <?= json_encode($salonAddress) ?>,
      description: <?= json_encode($salonAbout) ?>,
      avatarSrc: <?= json_encode($salonImage) ?>
    };

    let isFormDirty = false;
    let selectedAvatarFile = null;
    let selectedContentFile = null;
    let pendingDeleteContentId = null;

    // ------------------------------------------------------------------------
    // TAB SWITCHING
    // ------------------------------------------------------------------------
    function switchSettingsTab(tabName) {
      document.querySelectorAll('.settings-menu-item').forEach(item => {
        item.classList.remove('active');
        item.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
      });

      const btn = document.getElementById('tabBtn-' + tabName);
      const pane = document.getElementById('tabPane-' + tabName);

      if (btn && pane) {
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        pane.classList.add('active');
        // Update URL query state without reload
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', newUrl);
      }
    }

    // ------------------------------------------------------------------------
    // PROFILE TAB DIRTY TRACKING & ACTIONS
    // ------------------------------------------------------------------------
    function markFormDirty() {
      isFormDirty = true;
    }

    function updateAboutCounter(textarea) {
      const count = (textarea.value || '').length;
      document.getElementById('aboutCharCount').textContent = `${count}/250`;
    }

    function handleAvatarFileSelect(input) {
      if (input.files && input.files[0]) {
        selectedAvatarFile = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('profileAvatarPreview').src = e.target.result;
          isFormDirty = true;
        };
        reader.readAsDataURL(selectedAvatarFile);
      }
    }

    function handleDiscardClick() {
      if (isFormDirty) {
        openModal('discardModal');
      } else {
        showToast('No unsaved changes to discard', 'info');
      }
    }

    function closeDiscardModal() {
      closeModal('discardModal');
    }

    function confirmDiscardChanges() {
      // Revert fields
      document.getElementById('profileName').value = originalFormState.name;
      document.getElementById('profileEmail').value = originalFormState.email;
      document.getElementById('profileOwnerMobile').value = originalFormState.ownerMobile;
      document.getElementById('profileAddress').value = originalFormState.address;
      document.getElementById('profileAbout').value = originalFormState.description;
      document.getElementById('profileAvatarPreview').src = originalFormState.avatarSrc;
      document.getElementById('aboutCharCount').textContent = `${originalFormState.description.length}/250`;

      selectedAvatarFile = null;
      isFormDirty = false;
      closeDiscardModal();
      showToast('Unsaved changes discarded', 'info');
    }

    async function handleSaveProfile() {
      const saveBtn = document.getElementById('btnSaveProfile');
      const name = document.getElementById('profileName').value.trim();
      const email = document.getElementById('profileEmail').value.trim();
      const ownerMobile = document.getElementById('profileOwnerMobile').value.trim();
      const address = document.getElementById('profileAddress').value.trim();
      const description = document.getElementById('profileAbout').value.trim();

      if (!name) {
        showToast('Salon name is required', 'error');
        return;
      }

      saveBtn.disabled = true;
      saveBtn.innerHTML = 'SAVING...';

      const formData = new FormData();
      formData.append('action', 'update_profile');
      formData.append('name', name);
      formData.append('email', email);
      formData.append('ownerMobile', ownerMobile);
      formData.append('address', address);
      formData.append('description', description);

      if (selectedAvatarFile) {
        formData.append('image', selectedAvatarFile);
      }

      try {
        const res = await fetch('settings.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          showToast(data.message || 'Profile saved successfully', 'success');
          isFormDirty = false;
          // Update original state
          originalFormState.name = name;
          originalFormState.email = email;
          originalFormState.ownerMobile = ownerMobile;
          originalFormState.address = address;
          originalFormState.description = description;

          // Update header avatar / name if changed
          const headerName = document.querySelector('.profile-user-name');
          if (headerName) headerName.textContent = name;
        } else {
          showToast(data.message || 'Failed to update profile', 'error');
        }
      } catch (err) {
        showToast('Network error while saving profile', 'error');
      } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = `
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>SAVE</span>
        `;
      }
    }

    // ------------------------------------------------------------------------
    // CONTENT TAB ACTIONS & MODALS
    // ------------------------------------------------------------------------
    function openViewMediaModal(cardEl) {
      const id = cardEl.dataset.id;
      const url = cardEl.dataset.url || '';
      const caption = cardEl.dataset.caption || '';
      // Check if item is video either via isVideo data attribute or file extension
      const isVideo = (cardEl.dataset.isVideo === '1') || (cardEl.getAttribute('data-is-video') === '1') || /\.(mp4|webm|mov|mkv|avi|m4v)(\?.*)?$/i.test(url);

      const imgEl = document.getElementById('viewMediaImg');
      const vidEl = document.getElementById('viewMediaVideo');
      const capEl = document.getElementById('viewMediaCaptionText');
      const delBtn = document.getElementById('viewMediaDeleteBtn');

      capEl.textContent = caption || 'Salon creation';

      delBtn.onclick = function() {
        closeViewMediaModal();
        promptRemoveContent(id);
      };

      if (isVideo) {
        // Stop and hide image
        imgEl.style.display = 'none';
        imgEl.removeAttribute('src');

        // Setup and show video
        vidEl.style.display = 'block';
        vidEl.muted = false; // allow audio on user click
        vidEl.currentTime = 0;
        if (vidEl.src !== url) {
          vidEl.src = url;
        }

        // Open modal
        openModal('mediaViewModal');

        // If video, it should automatically play
        const playPromise = vidEl.play();
        if (playPromise !== undefined) {
          playPromise.catch(err => {
            console.warn('Direct unmuted playback prevented by browser, playing muted:', err);
            vidEl.muted = true;
            vidEl.play().catch(e => console.error('Video playback failed:', e));
          });
        }
      } else {
        // If image, okay do nothing (stop any video, display image)
        vidEl.pause();
        vidEl.removeAttribute('src');
        vidEl.load();
        vidEl.style.display = 'none';

        imgEl.style.display = 'block';
        imgEl.src = url;

        openModal('mediaViewModal');
      }
    }

    function closeViewMediaModal() {
      const vidEl = document.getElementById('viewMediaVideo');
      if (vidEl) {
        vidEl.pause();
        vidEl.removeAttribute('src');
        vidEl.load();
        vidEl.style.display = 'none';
      }
      const imgEl = document.getElementById('viewMediaImg');
      if (imgEl) {
        imgEl.removeAttribute('src');
        imgEl.style.display = 'none';
      }
      closeModal('mediaViewModal');
    }

    function openAddContentModal() {
      // Reset dropzone & caption
      selectedContentFile = null;
      document.getElementById('contentFileInput').value = '';
      document.getElementById('contentCaptionInput').value = '';
      document.getElementById('contentCaptionCounter').textContent = '0/120';
      document.getElementById('dropzoneEmptyState').style.display = 'flex';
      document.getElementById('dropzonePreviewWrap').style.display = 'none';
      document.getElementById('addContentImgPreview').style.display = 'none';
      document.getElementById('addContentVideoPreview').style.display = 'none';

      openModal('addContentModal');
    }

    function closeAddContentModal() {
      const vidEl = document.getElementById('addContentVideoPreview');
      if (vidEl) vidEl.pause();
      closeModal('addContentModal');
    }

    function updateContentCaptionCounter(textarea) {
      const count = (textarea.value || '').length;
      document.getElementById('contentCaptionCounter').textContent = `${count}/120`;
    }

    function handleContentFileSelect(input) {
      if (input.files && input.files[0]) {
        selectedContentFile = input.files[0];
        const isVideo = selectedContentFile.type.startsWith('video/');

        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('dropzoneEmptyState').style.display = 'none';
          document.getElementById('dropzonePreviewWrap').style.display = 'block';

          if (isVideo) {
            const vid = document.getElementById('addContentVideoPreview');
            vid.src = e.target.result;
            vid.style.display = 'block';
            document.getElementById('addContentImgPreview').style.display = 'none';
          } else {
            const img = document.getElementById('addContentImgPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('addContentVideoPreview').style.display = 'none';
          }
        };
        reader.readAsDataURL(selectedContentFile);
      }
    }

    // Drag and Drop Handling for Content Dropzone
    const dropzone = document.getElementById('contentDropzone');
    if (dropzone) {
      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('dragover');
        });
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('dragover');
        });
      });

      dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (dt.files && dt.files.length) {
          document.getElementById('contentFileInput').files = dt.files;
          handleContentFileSelect(document.getElementById('contentFileInput'));
        }
      });
    }

    async function handlePublishContent() {
      const caption = document.getElementById('contentCaptionInput').value.trim();
      if (!caption) {
        showToast('Please enter a caption for the content', 'error');
        return;
      }
      if (!selectedContentFile) {
        showToast('Please select or drop an image or video', 'error');
        return;
      }

      const publishBtn = document.getElementById('btnPublishContent');
      publishBtn.disabled = true;
      publishBtn.textContent = 'Publishing...';

      const formData = new FormData();
      formData.append('action', 'add_content');
      formData.append('caption', caption);
      formData.append('file', selectedContentFile);

      try {
        const res = await fetch('settings.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          showToast(data.message || 'Content published successfully', 'success');
          closeAddContentModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToast(data.message || 'Failed to publish content', 'error');
        }
      } catch (err) {
        showToast('Network error while publishing content', 'error');
      } finally {
        publishBtn.disabled = false;
        publishBtn.textContent = 'Publish';
      }
    }

    function promptRemoveContent(contentId) {
      pendingDeleteContentId = contentId;
      openModal('removeContentModal');
    }

    function closeRemoveModal() {
      pendingDeleteContentId = null;
      closeModal('removeContentModal');
    }

    async function executeRemoveContent() {
      if (!pendingDeleteContentId) return;

      const removeBtn = document.getElementById('btnConfirmRemove');
      removeBtn.disabled = true;
      removeBtn.textContent = 'Removing...';

      const formData = new FormData();
      formData.append('action', 'delete_content');
      formData.append('id', pendingDeleteContentId);

      try {
        const res = await fetch('settings.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          showToast(data.message || 'Content removed permanently', 'success');
          // Remove card from UI
          const card = document.querySelector(`.content-media-card[data-id="${pendingDeleteContentId}"]`);
          if (card) card.remove();
          closeRemoveModal();
        } else {
          showToast(data.message || 'Failed to remove content', 'error');
        }
      } catch (err) {
        showToast('Network error while removing content', 'error');
      } finally {
        removeBtn.disabled = false;
        removeBtn.textContent = 'Remove';
      }
    }

    // ------------------------------------------------------------------------
    // FAQS ACCORDION
    // ------------------------------------------------------------------------
    function toggleFaqAccordion(index) {
      const card = document.getElementById('faqCard-' + index);
      if (card) {
        card.classList.toggle('open');
      }
    }

    // ------------------------------------------------------------------------
    // MODAL UTILITIES
    // ------------------------------------------------------------------------
    function openModal(modalId) {
      const m = document.getElementById(modalId);
      if (m) m.classList.add('show');
    }

    function closeModal(modalId) {
      const m = document.getElementById(modalId);
      if (m) m.classList.remove('show');
    }

    function closeModalOnBackdrop(event, modalId) {
      if (event.target && event.target.id === modalId) {
        if (modalId === 'mediaViewModal') {
          closeViewMediaModal();
        } else if (modalId === 'addContentModal') {
          closeAddContentModal();
        } else if (modalId === 'removeContentModal') {
          closeRemoveModal();
        } else if (modalId === 'discardModal') {
          closeDiscardModal();
        } else {
          closeModal(modalId);
        }
      }
    }

    // Escape Key to Close Modals
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const mediaModal = document.getElementById('mediaViewModal');
        if (mediaModal && mediaModal.classList.contains('show')) {
          closeViewMediaModal();
          return;
        }
        const addModal = document.getElementById('addContentModal');
        if (addModal && addModal.classList.contains('show')) {
          closeAddContentModal();
          return;
        }
        const removeModal = document.getElementById('removeContentModal');
        if (removeModal && removeModal.classList.contains('show')) {
          closeRemoveModal();
          return;
        }
        const discardModal = document.getElementById('discardModal');
        if (discardModal && discardModal.classList.contains('show')) {
          closeDiscardModal();
          return;
        }
      }
    });

    // ------------------------------------------------------------------------
    // TOAST SYSTEM
    // ------------------------------------------------------------------------
    function showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      if (!container) return;

      const toast = document.createElement('div');
      toast.className = `toast-msg ${type}`;
      toast.textContent = message;
      container.appendChild(toast);

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.25s ease';
        setTimeout(() => toast.remove(), 250);
      }, 3500);
    }
  </script>
</body>
</html>
