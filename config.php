<?php
/**
 * ScutS API Configuration
 * Based on API_DOCUMENTATION (1).md
 */

// Base URLs
define('API_BASE_URL', 'https://api.Scuts.in/api/v1/');
define('IMAGE_BASE_URL', 'https://api.Scuts.in/');

// Default Salon Credentials (used for auto-connecting live API)
define('DEFAULT_SALON_MOBILE', '9663777636');
define('DEFAULT_SALON_COUNTRY_CODE', '91');
define('DEFAULT_SALON_PASSWORD', '12345678');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('AUTH_TOKEN')) {
    define('AUTH_TOKEN', $_SESSION['access_token'] ?? '');
}
