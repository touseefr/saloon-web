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

// Helper to check if a JWT token has expired
if (!function_exists('isScutsTokenExpired')) {
    function isScutsTokenExpired(?string $token): bool {
        if (empty($token)) return true;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return true;
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!isset($payload['exp'])) return false;
        // Expired or expiring within 60 seconds
        return $payload['exp'] <= (time() + 60);
    }
}

// 1. Check if user is already logged in with a non-expired token
$hasActiveToken = !empty($_SESSION['access_token']) && !isScutsTokenExpired($_SESSION['access_token']);
$isLoggedIn = $hasActiveToken || !empty($_SESSION['is_demo_user']);

// 2. If not logged in or token expired, but default salon credentials exist, auto-authenticate
if (!$isLoggedIn && defined('DEFAULT_SALON_MOBILE') && defined('DEFAULT_SALON_PASSWORD')) {
    $api = new ScutsApiClient(''); // Empty token so no invalid auth header is sent to login
    $countryCode = defined('DEFAULT_SALON_COUNTRY_CODE') ? ltrim((string)DEFAULT_SALON_COUNTRY_CODE, '+') : '91';
    $loginRes = $api->loginWithPassword(DEFAULT_SALON_MOBILE, $countryCode, DEFAULT_SALON_PASSWORD);
    
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
