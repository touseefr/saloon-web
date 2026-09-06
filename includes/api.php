<?php
/**
 * ScutS API Service Helper
 * Implements API integration based on API_DOCUMENTATION (1).md
 */

require_once __DIR__ . '/../config.php';

class ScutsApiClient {
    private string $baseUrl;
    private string $imageBaseUrl;
    private ?string $token;
    private array $lastError = [];

    public function __construct(?string $token = null) {
        $this->baseUrl = API_BASE_URL;
        $this->imageBaseUrl = IMAGE_BASE_URL;
        $this->token = $token ?: ($_SESSION['access_token'] ?? (defined('AUTH_TOKEN') && !empty(AUTH_TOKEN) ? AUTH_TOKEN : null));
    }

    public function hasValidToken(): bool {
        return !empty($this->token);
    }

    public function getToken(): ?string {
        return $this->token;
    }

    public function setToken(?string $token): void {
        $this->token = $token;
    }

    public function getLastError(): array {
        return $this->lastError;
    }

    /**
     * Attempts to refresh token or re-authenticate with default credentials
     */
    public function refreshTokenOrRelogin(): ?string {
        if (!defined('DEFAULT_SALON_MOBILE') || !defined('DEFAULT_SALON_PASSWORD')) {
            return null;
        }

        $savedToken = $this->token;
        $this->token = null; // Clear token so no expired Bearer header is sent to login

        $countryCode = defined('DEFAULT_SALON_COUNTRY_CODE') ? ltrim((string)DEFAULT_SALON_COUNTRY_CODE, '+') : '91';

        $payload = [
            'mobile' => (string)DEFAULT_SALON_MOBILE,
            'countryCode' => $countryCode,
            'password' => (string)DEFAULT_SALON_PASSWORD,
            'fcmToken' => 'web-fcm-' . md5(uniqid('', true)),
            'deviceId' => 'web-device-' . md5($_SERVER['HTTP_USER_AGENT'] ?? 'scuts-device')
        ];

        $reauthRes = $this->request('auth/salon/login/password', 'POST', [], $payload, true);
        if (!empty($reauthRes['data']['accessToken'])) {
            $this->token = $reauthRes['data']['accessToken'];
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['access_token'] = $this->token;
                if (!empty($reauthRes['data']['salonData'])) {
                    $_SESSION['salon_data'] = $reauthRes['data']['salonData'];
                    $_SESSION['salon_user'] = $reauthRes['data']['salonData'];
                }
                $_SESSION['is_demo_user'] = false;
            }
            return $this->token;
        }

        // Restore token if re-auth failed
        $this->token = $savedToken;
        return null;
    }

    /**
     * Executes an HTTP request to ScutS API
     */
    public function request(string $endpoint, string $method = 'GET', array $params = [], array $data = [], bool $isRetry = false): ?array {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        $noAuthEndpoints = [
            'auth/salon/login/password',
            'auth/salon/send/verification-code',
            'auth/salon/verify/verification-code',
            'auth/salon/login/mobile-verification-code',
            'auth/artist/login/password',
            'auth/refresh'
        ];

        if ($this->token && !in_array($endpoint, $noAuthEndpoints, true)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
            $headers[] = 'x-access-token: ' . $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // On local Windows WAMP without curl.cainfo configured, prevent SSL certificate failures
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH' || $method === 'PUT' || $method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->lastError = ['type' => 'network', 'message' => $curlError];
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $decoded ?: ['status' => true];
        }

        // Auto re-authenticate on 401/403 or invalid token and retry request once
        $isAuthError = ($httpCode === 401 || $httpCode === 403)
            || (isset($decoded['statusCode']) && ($decoded['statusCode'] === 401 || $decoded['statusCode'] === 403))
            || (isset($decoded['message']) && preg_match('/invalid.*token|token.*expired|unauthorized|missing authorization/i', (string)$decoded['message']));

        if ($isAuthError && !in_array($endpoint, $noAuthEndpoints, true) && !$isRetry) {
            $newToken = $this->refreshTokenOrRelogin();
            if ($newToken) {
                // Retry original request with fresh token
                return $this->request($endpoint, $method, $params, $data, true);
            }
        }

        $this->lastError = [
            'httpCode' => $httpCode,
            'response' => $decoded ?: $response
        ];
        return null;
    }

    /**
     * Executes a multipart/form-data request to ScutS API (supports file uploads)
     */
    public function requestMultipart(string $endpoint, string $method = 'POST', array $fields = [], bool $isRetry = false): ?array {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept: application/json'
        ];

        $noAuthEndpoints = [
            'auth/salon/login/password',
            'auth/salon/send/verification-code',
            'auth/salon/verify/verification-code',
            'auth/salon/login/mobile-verification-code',
            'auth/artist/login/password',
            'auth/refresh'
        ];

        if ($this->token && !in_array($endpoint, $noAuthEndpoints, true)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
            $headers[] = 'x-access-token: ' . $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->lastError = ['type' => 'network', 'message' => $curlError];
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $decoded ?: ['status' => true];
        }

        // Auto re-authenticate on 401/403 or invalid token and retry request once
        $isAuthError = ($httpCode === 401 || $httpCode === 403)
            || (isset($decoded['statusCode']) && ($decoded['statusCode'] === 401 || $decoded['statusCode'] === 403))
            || (isset($decoded['message']) && preg_match('/invalid.*token|token.*expired|unauthorized|missing authorization/i', (string)$decoded['message']));

        if ($isAuthError && !in_array($endpoint, $noAuthEndpoints, true) && !$isRetry) {
            $newToken = $this->refreshTokenOrRelogin();
            if ($newToken) {
                return $this->requestMultipart($endpoint, $method, $fields, true);
            }
        }

        $this->lastError = [
            'httpCode' => $httpCode,
            'response' => $decoded ?: $response
        ];
        return $decoded ?: null;
    }

    /**
     * Format Image URL with base URL if relative path
     */
    public function formatImageUrl(?string $path, string $fallback = ''): string {
        if (empty($path)) {
            return $fallback;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return rtrim($this->imageBaseUrl, '/') . '/' . ltrim($path, '/');
    }

    // =========================================================================
    // Authentication APIs (No Auth Required)
    // =========================================================================

    /**
     * POST auth/salon/login/password
     */
    public function loginWithPassword(string $mobile, string $countryCode, string $password, ?string $fcmToken = null, ?string $deviceId = null): ?array {
        $payload = [
            'mobile' => (string)$mobile,
            'countryCode' => (string)$countryCode,
            'password' => (string)$password,
            'fcmToken' => $fcmToken ?: ('web-fcm-' . md5(uniqid('', true))),
            'deviceId' => $deviceId ?: ('web-device-' . md5($_SERVER['HTTP_USER_AGENT'] ?? 'scuts-device'))
        ];
        return $this->request('auth/salon/login/password', 'POST', [], $payload);
    }

    /**
     * POST auth/salon/send/verification-code
     */
    public function sendVerificationCode(string $mobile, string $countryCode): ?array {
        return $this->request('auth/salon/send/verification-code', 'POST', [], [
            'mobile' => (string)$mobile,
            'countryCode' => (string)$countryCode
        ]);
    }

    /**
     * POST auth/salon/login/mobile-verification-code
     */
    public function loginWithVerificationCode(string $mobile, string $countryCode, string $code, ?string $fcmToken = null, ?string $deviceId = null): ?array {
        $payload = [
            'mobile' => (string)$mobile,
            'countryCode' => (string)$countryCode,
            'verificationCode' => (string)$code,
            'fcmToken' => $fcmToken ?: ('web-fcm-' . md5(uniqid('', true))),
            'deviceId' => $deviceId ?: ('web-device-' . md5($_SERVER['HTTP_USER_AGENT'] ?? 'scuts-device'))
        ];
        return $this->request('auth/salon/login/mobile-verification-code', 'POST', [], $payload);
    }

    // =========================================================================
    // Dynamic API Endpoints
    // =========================================================================

    /**
     * 1. GET salon/profile
     */
    public function getSalonProfile(): ?array {
        return $this->request('salon/profile');
    }

    /**
     * 2. GET salon/dashboard/analytics?distribution={distribution}
     * API accepts: daily, weekly, monthly, all_time
     */
    public function getDashboardAnalytics(string $distribution = 'all_time'): ?array {
        $map = [
            'all' => 'all_time',
            'today' => 'daily',
            'this_week' => 'weekly',
            'this_month' => 'monthly',
            'all_time' => 'all_time',
            'daily' => 'daily',
            'weekly' => 'weekly',
            'monthly' => 'monthly'
        ];
        $apiDist = $map[$distribution] ?? 'all_time';
        return $this->request('salon/dashboard/analytics', 'GET', ['distribution' => $apiDist]);
    }

    /**
     * 3. GET salon/artist/list
     */
    public function getSalonStylists(): ?array {
        return $this->request('salon/artist/list');
    }

    /**
     * 4. GET salon/appointments/pending-appointments?distribution={distribution}
     * API accepts: today, tomorrow, yesterday, this_week, this_month, this_year, all_time, custom
     */
    public function getPendingAppointments(string $distribution = 'today'): ?array {
        $map = [
            'all' => 'all_time',
            'today' => 'today',
            'this_week' => 'this_week',
            'this_month' => 'this_month',
            'all_time' => 'all_time'
        ];
        $apiDist = $map[$distribution] ?? 'today';
        return $this->request('salon/appointments/pending-appointments', 'GET', ['distribution' => $apiDist]);
    }

    /**
     * 5. GET salon/appointments/served-appointments?distribution={distribution}
     */
    public function getServedAppointments(string $distribution = 'all_time'): ?array {
        $map = [
            'all' => 'all_time',
            'today' => 'today',
            'this_week' => 'this_week',
            'this_month' => 'this_month',
            'all_time' => 'all_time'
        ];
        $apiDist = $map[$distribution] ?? 'all_time';
        return $this->request('salon/appointments/served-appointments', 'GET', ['distribution' => $apiDist]);
    }

    /**
     * 6. GET salon/appointments/cancel-appointments?distribution={distribution}
     */
    public function getCancelledAppointments(string $distribution = 'today'): ?array {
        $map = [
            'all' => 'all_time',
            'today' => 'today',
            'this_week' => 'this_week',
            'this_month' => 'this_month',
            'all_time' => 'all_time'
        ];
        $apiDist = $map[$distribution] ?? 'today';
        return $this->request('salon/appointments/cancel-appointments', 'GET', ['distribution' => $apiDist]);
    }

    /**
     * 7. GET salon/appointments/{appointmentId}
     */
    public function getAppointmentDetails(string $appointmentId): ?array {
        return $this->request('salon/appointments/' . urlencode($appointmentId));
    }

    /**
     * 8. GET salon/appointments/rejection-reason
     */
    public function getRejectionReasons(): ?array {
        return $this->request('salon/appointments/rejection-reason');
    }

    /**
     * 9. PUT salon/appointments/{appointmentId}/status
     * Accept: status='confirmed', stylistIds=[...], startsAt=...
     * Reject: status='salon_rejected', rejectionReasonId=..., reason=...
     */
    public function updateAppointmentStatus(
        string $appointmentId,
        string $status,
        ?array $stylistIds = null,
        ?string $startsAt = null,
        ?string $rejectionReasonId = null,
        ?string $reason = null
    ): ?array {
        $payload = [
            'status' => $status
        ];

        if (!empty($stylistIds)) {
            $payload['stylistIds'] = $stylistIds;
        }

        if (!empty($startsAt)) {
            $payload['startsAt'] = $startsAt;
        }

        if (!empty($rejectionReasonId)) {
            $payload['rejectionReasonId'] = $rejectionReasonId;
        }

        if (!empty($reason)) {
            $payload['reason'] = $reason;
            $payload['rejectionRemark'] = $reason;
        }

        return $this->request('salon/appointments/' . urlencode($appointmentId) . '/status', 'PUT', [], $payload);
    }

    /**
     * 10. GET salon/transactions/settled
     */
    public function getSettledTransactions(?string $distribution = null): ?array {
        $params = [];
        if ($distribution && $distribution !== 'all' && $distribution !== 'all_time') {
            $params['distribution'] = $distribution;
        }
        return $this->request('salon/transactions/settled', 'GET', $params);
    }

    /**
     * 11. GET salon/transactions/unsettled
     */
    public function getUnsettledTransactions(?string $distribution = null): ?array {
        $params = [];
        if ($distribution && $distribution !== 'all' && $distribution !== 'all_time') {
            $params['distribution'] = $distribution;
        }
        return $this->request('salon/transactions/unsettled', 'GET', $params);
    }

    /**
     * 12. POST salon/dashboard/request-recharge
     */
    public function requestRecharge(): ?array {
        return $this->request('salon/dashboard/request-recharge', 'POST', [], []);
    }

    /**
     * 13. POST salon/artist/add
     */
    public function addStylist(array $data, ?array $file = null): ?array {
        $postFields = $data;
        if ($file && !empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $mime = $file['type'] ?: 'image/jpeg';
            $name = $file['name'] ?: 'avatar.jpg';
            $postFields['profileImage'] = new CURLFile($file['tmp_name'], $mime, $name);
        } else {
            $defaultAvatar = __DIR__ . '/../assets/images/user-avatar.png';
            if (file_exists($defaultAvatar)) {
                $postFields['profileImage'] = new CURLFile($defaultAvatar, 'image/png', 'user-avatar.png');
            }
        }
        return $this->requestMultipart('salon/artist/add', 'POST', $postFields);
    }

    /**
     * 13b. PUT salon/artist/add-with-sid
     */
    public function addStylistWithSid(string $sid): ?array {
        return $this->request('salon/artist/add-with-sid', 'PUT', [], ['sId' => $sid]);
    }

    /**
     * 14. PATCH salon/artist/{artistId}/update
     */
    public function updateStylist(string $artistId, array $data, ?array $file = null): ?array {
        $endpoint = 'salon/artist/' . urlencode($artistId) . '/update';
        if ($file && !empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $postFields = $data;
            $mime = $file['type'] ?: 'image/jpeg';
            $name = $file['name'] ?: 'avatar.jpg';
            $postFields['image'] = new CURLFile($file['tmp_name'], $mime, $name);
            return $this->requestMultipart($endpoint, 'PATCH', $postFields);
        }
        return $this->request($endpoint, 'PATCH', [], $data);
    }

    /**
     * 14b. GET salon/artist/{artistId}/details
     */
    public function getStylistDetails(string $artistId): ?array {
        return $this->request('salon/artist/' . urlencode($artistId) . '/details');
    }

    /**
     * 15. DELETE salon/artist/{artistId}/delete
     */
    public function deleteStylist(string $artistId): ?array {
        return $this->request('salon/artist/' . urlencode($artistId) . '/delete', 'DELETE');
    }

    /**
     * 16. GET salon/availability/artist/{artistId}/availability
     */
    public function getStylistAvailability(string $artistId): ?array {
        return $this->request('salon/availability/artist/' . urlencode($artistId) . '/availability');
    }

    /**
     * 17. PATCH salon/availability/artist/{artistId}/availability
     */
    public function updateStylistAvailability(string $artistId, array $availability): ?array {
        $payload = isset($availability['workingPlan']) ? $availability : ['workingPlan' => $availability];
        return $this->request('salon/availability/artist/' . urlencode($artistId) . '/availability', 'PATCH', [], $payload);
    }

    /**
     * 18. POST salon/availability/artist/{artistId}/block-slot
     */
    public function blockStylistSlot(string $artistId, string $start, string $end): ?array {
        return $this->request('salon/availability/artist/' . urlencode($artistId) . '/block-slot', 'POST', [], [
            'start' => $start,
            'end' => $end
        ]);
    }

    /**
     * 19. DELETE salon/availability/artist/{artistId}/block-slot/{blockId}
     */
    public function deleteBlockedSlot(string $artistId, string $blockId): ?array {
        return $this->request('salon/availability/artist/' . urlencode($artistId) . '/block-slot/' . urlencode($blockId), 'DELETE');
    }
}
