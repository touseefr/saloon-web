<?php
/**
 * Authentication Gatekeeper
 * Ensures active session or auto-authenticates using configured salon credentials
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is already logged in
$isLoggedIn = !empty($_SESSION['access_token']) || !empty($_SESSION['is_demo_user']);

// 2. If not logged in but default salon credentials exist, auto-authenticate
if (!$isLoggedIn && defined('DEFAULT_SALON_MOBILE') && defined('DEFAULT_SALON_PASSWORD')) {
    $api = new ScutsApiClient();
    $loginRes = $api->loginWithPassword(DEFAULT_SALON_MOBILE, DEFAULT_SALON_COUNTRY_CODE, DEFAULT_SALON_PASSWORD);
    
    if (!empty($loginRes['data']['accessToken'])) {
        $_SESSION['access_token'] = $loginRes['data']['accessToken'];
        $_SESSION['salon_data'] = $loginRes['data']['salonData'] ?? null;
        $_SESSION['salon_user'] = $loginRes['data']['salonData'] ?? null;
        $_SESSION['is_demo_user'] = false;
        $isLoggedIn = true;
    }
}

// 3. If still not logged in, redirect to login page
if (!$isLoggedIn) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?redirect=' . urlencode($requestUri));
    exit;
}
