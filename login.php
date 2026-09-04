<?php
/**
 * ScutS Salon Portal - Login Page
 * Supports Password & OTP authentication via ScutS APIs
 * Compatible with Localhost and Live Environments
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (!empty($_SESSION['access_token']) || !empty($_SESSION['is_demo_user'])) {
    header('Location: index.php');
    exit;
}

$apiClient = new ScutsApiClient();
$errorMessage = null;
$successMessage = null;
$redirectUrl = $_GET['redirect'] ?? 'index.php';

// Check for logout message
if (isset($_GET['logged_out'])) {
    $successMessage = 'You have been successfully signed out.';
}

// -----------------------------------------------------------------------------
// Handle Form Submissions
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'password_login';

    // 1. Password Login (POST auth/salon/login/password)
    if ($action === 'password_login') {
        $countryCode = trim($_POST['country_code'] ?? '91');
        $countryCode = ltrim($countryCode, '+'); // Normalize without '+'
        $mobile = trim($_POST['mobile'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($mobile) || empty($password)) {
            $errorMessage = 'Please enter both your mobile number and password.';
        } else {
            $response = $apiClient->loginWithPassword($mobile, $countryCode, $password);

            if ($response && (isset($response['data']['accessToken']) || isset($response['accessToken']))) {
                $token = $response['data']['accessToken'] ?? $response['accessToken'];
                $_SESSION['access_token'] = $token;
                $_SESSION['salon_data'] = $response['data']['salonData'] ?? null;
                $_SESSION['salon_user'] = $response['data']['salonData'] ?? $response['data']['salon'] ?? $response['data'] ?? null;
                $_SESSION['is_demo_user'] = false;

                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $lastError = $apiClient->getLastError();
                $serverMsg = $response['message'] ?? $lastError['response']['message'] ?? null;
                $errorMessage = $serverMsg ?: 'Invalid mobile number or password. Please try again.';
            }
        }
    }

    // 2. Send OTP (POST auth/salon/send/verification-code)
    elseif ($action === 'send_otp') {
        $countryCode = trim($_POST['country_code'] ?? '91');
        $countryCode = ltrim($countryCode, '+');
        $mobile = trim($_POST['mobile'] ?? '');

        if (empty($mobile)) {
            $errorMessage = 'Please enter a mobile number to receive OTP.';
        } else {
            $response = $apiClient->sendVerificationCode($mobile, $countryCode);
            if ($response !== null) {
                $successMessage = 'Verification code sent to +' . $countryCode . ' ' . $mobile;
                $_SESSION['otp_mobile'] = $mobile;
                $_SESSION['otp_country_code'] = $countryCode;
            } else {
                $lastError = $apiClient->getLastError();
                $errorMessage = $response['message'] ?? $lastError['response']['message'] ?? 'Unable to send OTP. Please verify your mobile number.';
            }
        }
    }

    // 3. Verify OTP Login (POST auth/salon/login/mobile-verification-code)
    elseif ($action === 'verify_otp') {
        $countryCode = trim($_POST['country_code'] ?? ($_SESSION['otp_country_code'] ?? '91'));
        $countryCode = ltrim($countryCode, '+');
        $mobile = trim($_POST['mobile'] ?? ($_SESSION['otp_mobile'] ?? ''));
        $code = trim($_POST['verification_code'] ?? '');

        if (empty($mobile) || empty($code)) {
            $errorMessage = 'Please enter the verification code received on your phone.';
        } else {
            $response = $apiClient->loginWithVerificationCode($mobile, $countryCode, $code);

            if ($response && (isset($response['data']['accessToken']) || isset($response['accessToken']))) {
                $token = $response['data']['accessToken'] ?? $response['accessToken'];
                $_SESSION['access_token'] = $token;
                $_SESSION['salon_user'] = $response['data']['salon'] ?? $response['data']['user'] ?? $response['data'] ?? null;
                $_SESSION['is_demo_user'] = false;

                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $lastError = $apiClient->getLastError();
                $serverMsg = $response['message'] ?? $lastError['response']['message'] ?? null;
                $errorMessage = $serverMsg ?: 'Invalid verification code. Please try again.';
            }
        }
    }

    // 4. Quick Demo Mode Login (Localhost / Testing)
    elseif ($action === 'demo_login') {
        $_SESSION['is_demo_user'] = true;
        $_SESSION['access_token'] = null;
        $_SESSION['salon_user'] = [
            'name' => 'ScutS Demo Salon',
            'ownerName' => 'Harish',
            'ownerEmail' => 'Harish@gmail.com'
        ];

        header('Location: ' . $redirectUrl);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ScutS Salon Portal - Login</title>
  <meta name="description" content="Sign in to your ScutS Salon Dashboard" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="icon" type="image/svg+xml" href="assets/images/scuts-logo.svg" />
  <style>
    /* Login Page Dedicated Styling */
    body.login-body {
      background: radial-gradient(circle at 50% 20%, #1A132B 0%, #0D0A15 70%, #07050B 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      color: #FCFCFC;
    }

    .login-container {
      width: 100%;
      max-width: 440px;
      position: relative;
    }

    .login-glow-circle {
      position: absolute;
      width: 260px;
      height: 260px;
      background: #8466CF;
      border-radius: 50%;
      filter: blur(140px);
      opacity: 0.35;
      top: -40px;
      left: 50%;
      transform: translateX(-50%);
      pointer-events: none;
      z-index: 0;
    }

    .login-card {
      position: relative;
      z-index: 1;
      background: rgba(22, 17, 36, 0.85);
      border: 1px solid rgba(132, 102, 207, 0.25);
      border-radius: 20px;
      padding: 36px 32px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 30px rgba(132, 102, 207, 0.1);
      backdrop-filter: blur(16px);
    }

    .login-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .login-logo {
      display: inline-block;
      margin-bottom: 16px;
      transition: transform 0.2s ease;
    }
    .login-logo:hover {
      transform: scale(1.03);
    }

    .login-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #FCFCFC;
      margin-bottom: 6px;
    }

    .login-subtitle {
      font-size: 0.875rem;
      color: #A8A8A8;
    }

    /* Tabs */
    .login-tabs {
      display: flex;
      background: rgba(53, 41, 83, 0.4);
      border-radius: 12px;
      padding: 4px;
      margin-bottom: 24px;
      border: 1px solid rgba(132, 102, 207, 0.2);
    }

    .login-tab-btn {
      flex: 1;
      padding: 8px 12px;
      font-size: 0.875rem;
      font-weight: 500;
      color: #C4C4C4;
      border-radius: 8px;
      text-align: center;
      transition: all 0.2s ease;
    }

    .login-tab-btn.active {
      background: #8466CF;
      color: #FFFFFF;
      box-shadow: 0 2px 8px rgba(132, 102, 207, 0.3);
    }

    /* Alerts */
    .alert-box {
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 0.875rem;
      margin-bottom: 20px;
      line-height: 1.4;
    }

    .alert-danger {
      background: rgba(220, 38, 38, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #FCA5A5;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #6EE7B7;
    }

    /* Form Inputs */
    .form-group {
      margin-bottom: 18px;
    }

    .form-label {
      display: block;
      font-size: 0.8125rem;
      font-weight: 500;
      color: #EDE8F8;
      margin-bottom: 6px;
    }

    .phone-input-wrap {
      display: flex;
      gap: 8px;
    }

    .country-code-select {
      width: 80px;
      background: #1A132B;
      border: 1px solid rgba(132, 102, 207, 0.3);
      border-radius: 10px;
      color: #FCFCFC;
      padding: 10px 8px;
      font-family: inherit;
      font-size: 0.875rem;
      text-align: center;
      outline: none;
    }

    .form-input {
      width: 100%;
      background: #1A132B;
      border: 1px solid rgba(132, 102, 207, 0.3);
      border-radius: 10px;
      color: #FCFCFC;
      padding: 11px 14px;
      font-family: inherit;
      font-size: 0.9375rem;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-input:focus {
      border-color: #8466CF;
      box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.25);
    }

    .password-wrap {
      position: relative;
    }

    .password-toggle-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #A8A8A8;
      font-size: 0.8125rem;
      cursor: pointer;
      user-select: none;
    }
    .password-toggle-btn:hover {
      color: #FCFCFC;
    }

    /* Submit Button */
    .btn-submit {
      width: 100%;
      background: #8466CF;
      color: #FFFFFF;
      font-weight: 600;
      font-size: 1rem;
      padding: 12px 20px;
      border-radius: 10px;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 8px;
      box-shadow: 0 4px 14px rgba(132, 102, 207, 0.35);
    }

    .btn-submit:hover {
      background: #7353C4;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(132, 102, 207, 0.45);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    /* Demo Button */
    .demo-divider {
      display: flex;
      align-items: center;
      margin: 24px 0 16px 0;
      color: #71717A;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .demo-divider::before, .demo-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(132, 102, 207, 0.2);
    }
    .demo-divider span {
      padding: 0 10px;
    }

    .btn-demo {
      width: 100%;
      background: rgba(53, 41, 83, 0.5);
      border: 1px solid rgba(132, 102, 207, 0.3);
      color: #EDE8F8;
      font-weight: 500;
      font-size: 0.875rem;
      padding: 10px 16px;
      border-radius: 10px;
      transition: all 0.2s ease;
    }
    .btn-demo:hover {
      background: rgba(53, 41, 83, 0.8);
      border-color: #8466CF;
      color: #FFFFFF;
    }

    .login-footer-info {
      text-align: center;
      margin-top: 20px;
      font-size: 0.75rem;
      color: #71717A;
    }
    .login-footer-info a {
      color: #8466CF;
      text-decoration: underline;
    }
  </style>
</head>
<body class="login-body">

<div class="login-container">
  <div class="login-glow-circle"></div>

  <div class="login-card">
    <div class="login-header">
      <a href="login.php" class="login-logo" aria-label="ScutS Home">
        <img src="assets/images/scuts-logo.svg" alt="ScutS" width="120" height="30" />
      </a>
      <h1 class="login-title">Salon Portal Login</h1>
      <p class="login-subtitle">Sign in to manage your appointments and stylists</p>
    </div>

    <!-- Alert Notifications -->
    <?php if ($errorMessage): ?>
      <div class="alert-box alert-danger" role="alert">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
      <div class="alert-box alert-success" role="alert">
        <?= htmlspecialchars($successMessage) ?>
      </div>
    <?php endif; ?>

    <!-- Auth Method Tabs -->
    <div class="login-tabs" role="tablist">
      <button class="login-tab-btn active" id="tabPasswordBtn" type="button" onclick="switchTab('password')">Password</button>
      <button class="login-tab-btn" id="tabOtpBtn" type="button" onclick="switchTab('otp')">OTP Login</button>
    </div>

    <!-- 1. Password Login Form -->
    <form id="passwordLoginForm" method="POST" action="login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
      <input type="hidden" name="action" value="password_login" />

      <div class="form-group">
        <label class="form-label" for="pwdMobile">Mobile Number</label>
        <div class="phone-input-wrap">
          <input type="text" class="country-code-select" name="country_code" value="+91" required />
          <input type="tel" id="pwdMobile" class="form-input" name="mobile" placeholder="9876543210" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>" required autofocus />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="pwdField">Password</label>
        <div class="password-wrap">
          <input type="password" id="pwdField" class="form-input" name="password" placeholder="••••••••" required />
          <span class="password-toggle-btn" onclick="togglePasswordVisibility()">Show</span>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <span>Sign In</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"></line>
          <polyline points="12 5 19 12 12 19"></polyline>
        </svg>
      </button>
    </form>

    <!-- 2. OTP Login Form (Hidden by default) -->
    <form id="otpLoginForm" method="POST" action="login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" style="display: none;">
      <input type="hidden" name="action" id="otpFormAction" value="send_otp" />

      <div class="form-group">
        <label class="form-label" for="otpMobile">Mobile Number</label>
        <div class="phone-input-wrap">
          <input type="text" class="country-code-select" name="country_code" value="+91" required />
          <input type="tel" id="otpMobile" class="form-input" name="mobile" placeholder="9876543210" value="<?= htmlspecialchars($_SESSION['otp_mobile'] ?? $_POST['mobile'] ?? '') ?>" required />
        </div>
      </div>

      <div class="form-group" id="otpCodeGroup" style="<?= isset($_SESSION['otp_mobile']) ? '' : 'display: none;' ?>">
        <label class="form-label" for="otpCode">Verification Code (OTP)</label>
        <input type="text" id="otpCode" class="form-input" name="verification_code" placeholder="Enter 4 or 6-digit code" maxlength="8" />
      </div>

      <button type="submit" class="btn-submit" id="otpSubmitBtn">
        <span><?= isset($_SESSION['otp_mobile']) ? 'Verify & Sign In' : 'Send Verification Code' ?></span>
      </button>
    </form>

    <!-- Quick Demo Mode for Testing -->
    <div class="demo-divider">
      <span>Or Test Without Credentials</span>
    </div>

    <form method="POST" action="login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
      <input type="hidden" name="action" value="demo_login" />
      <button type="submit" class="btn-demo">
        ⚡ Continue in Demo Mode (Preview All Pages)
      </button>
    </form>

    <div class="login-footer-info">
      Connected to <code>api.Scuts.in/api/v1</code> &bull; Secure SSL
    </div>
  </div>
</div>

<script>
  function switchTab(tab) {
    const pwdForm = document.getElementById('passwordLoginForm');
    const otpForm = document.getElementById('otpLoginForm');
    const tabPwdBtn = document.getElementById('tabPasswordBtn');
    const tabOtpBtn = document.getElementById('tabOtpBtn');

    if (tab === 'password') {
      pwdForm.style.display = 'block';
      otpForm.style.display = 'none';
      tabPwdBtn.classList.add('active');
      tabOtpBtn.classList.remove('active');
    } else {
      pwdForm.style.display = 'none';
      otpForm.style.display = 'block';
      tabOtpBtn.classList.add('active');
      tabPwdBtn.classList.remove('active');
    }
  }

  function togglePasswordVisibility() {
    const pwd = document.getElementById('pwdField');
    const btn = document.querySelector('.password-toggle-btn');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      btn.textContent = 'Hide';
    } else {
      pwd.type = 'password';
      btn.textContent = 'Show';
    }
  }
</script>
</body>
</html>
