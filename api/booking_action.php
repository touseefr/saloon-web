<?php
/**
 * AJAX Controller for Booking Actions and Details
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api.php';

$api = new ScutsApiClient();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Sample fallback data for demonstration/preview when testing upcoming/accept/reject
function getDemoAppointmentData(string $demoId = 'demo_upcoming'): array {
    return [
        'id' => $demoId,
        'idx' => '#BI123456',
        'status' => 'upcoming',
        'statusLabel' => 'Upcoming',
        'dateTime' => '22 JUN 2026 | 03:30 PM',
        'date' => '29 JUL 2026',
        'user' => [
            'name' => 'Earl Turner',
            'gender' => 'Male',
            'mobile' => '+91 9876543210',
            'avatar' => 'assets/images/booking-user-1.png'
        ],
        'slots' => [
            ['time' => '11:00 AM', 'selected' => false],
            ['time' => '02:00 PM', 'selected' => true],
            ['time' => '05:00 PM', 'selected' => false]
        ],
        'stylists' => [
            ['id' => '1', 'name' => 'Intajar'],
            ['id' => '2', 'name' => 'Arpan'],
            ['id' => '3', 'name' => 'Akram'],
            ['id' => '4', 'name' => 'Sameer Salmani'],
            ['id' => '5', 'name' => 'Sunitha']
        ],
        'beauticians' => [
            ['id' => 'b1', 'name' => 'Sandhya'],
            ['id' => 'b2', 'name' => 'Sunitha']
        ],
        'selectedStylistId' => '1',
        'selectedBeauticianId' => '',
        'services' => [
            [
                'name' => 'Hair cut',
                'category' => 'Haircut',
                'price' => '₹ 250.00'
            ],
            [
                'name' => 'Hair color',
                'category' => 'Hair Color',
                'price' => '₹ 250.00'
            ]
        ],
        'totalLabel' => 'Estimated Total',
        'totalAmount' => '₹ 500.00'
    ];
}

switch ($action) {
    case 'get_details':
        $appointmentId = trim($_GET['id'] ?? $_POST['id'] ?? '');

        if (empty($appointmentId)) {
            echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
            exit;
        }

        // Check if demo/mock ID requested
        if (str_starts_with($appointmentId, 'demo_') || $appointmentId === 'sample') {
            echo json_encode([
                'success' => true,
                'data' => getDemoAppointmentData($appointmentId)
            ]);
            exit;
        }

        // Fetch live appointment details from API
        $res = $api->getAppointmentDetails($appointmentId);

        if (!$res || empty($res['data'])) {
            // If live API returns empty or not found, check if it's in served or cancel
            echo json_encode([
                'success' => false,
                'message' => $api->getLastError()['response']['message'] ?? 'Appointment details not found'
            ]);
            exit;
        }

        $raw = $res['data'];

        // Normalize status
        $rawStatus = strtolower($raw['orderStatus'] ?? 'upcoming');
        $uiStatus = 'upcoming';
        $statusLabel = 'Upcoming';

        if (in_array($rawStatus, ['completed', 'served'])) {
            $uiStatus = 'completed';
            $statusLabel = 'Completed';
        } elseif (in_array($rawStatus, ['user_cancelled', 'salon_cancelled', 'cancelled'])) {
            $uiStatus = 'cancelled';
            $statusLabel = 'Cancelled';
        } elseif (in_array($rawStatus, ['confirmed', 'salon_confirmed'])) {
            $uiStatus = 'confirmed';
            $statusLabel = 'Confirmed';
        } elseif (in_array($rawStatus, ['salon_rejected', 'rejected'])) {
            $uiStatus = 'cancelled';
            $statusLabel = 'Rejected';
        }

        // Format datetime
        $startsAt = $raw['appointment']['startsAt'] ?? $raw['finalizedAt'] ?? $raw['createdAt'] ?? null;
        $dtObj = $startsAt ? @date_create($startsAt) : null;
        $formattedDateTime = $dtObj ? strtoupper(date_format($dtObj, 'd M Y | h:i A')) : 'N/A';
        $formattedDate = $dtObj ? strtoupper(date_format($dtObj, 'd M Y')) : date('d M Y');

        // Extract slots
        $slots = [];
        $selectedSlots = $raw['appointment']['selectedSlots'] ?? [];
        if (!empty($selectedSlots) && is_array($selectedSlots)) {
            foreach ($selectedSlots as $slotStr) {
                $st = @date_create($slotStr);
                if ($st) {
                    $slots[] = [
                        'time' => date_format($st, 'h:i A'),
                        'raw' => $slotStr,
                        'selected' => true
                    ];
                }
            }
        }
        if (empty($slots) && $dtObj) {
            $slots[] = [
                'time' => date_format($dtObj, 'h:i A'),
                'raw' => $startsAt,
                'selected' => true
            ];
        }

        // Extract stylists
        $hairStylists = [];
        $beautyStylists = [];
        if (!empty($raw['appointment']['artists']['Hair'])) {
            foreach ($raw['appointment']['artists']['Hair'] as $st) {
                $hairStylists[] = [
                    'id' => $st['id'] ?? '',
                    'name' => $st['name'] ?? 'Stylist'
                ];
            }
        }
        if (!empty($raw['appointment']['artists']['Beauty'])) {
            foreach ($raw['appointment']['artists']['Beauty'] as $st) {
                $beautyStylists[] = [
                    'id' => $st['id'] ?? '',
                    'name' => $st['name'] ?? 'Beautician'
                ];
            }
        }

        // Fallback to salon stylists list if not nested
        if (empty($hairStylists)) {
            $salonStylists = $api->getSalonStylists();
            if (!empty($salonStylists['data']) && is_array($salonStylists['data'])) {
                foreach ($salonStylists['data'] as $s) {
                    $hairStylists[] = [
                        'id' => $s['id'] ?? '',
                        'name' => $s['name'] ?? 'Stylist'
                    ];
                }
            }
        }

        // Extract services items
        $services = [];
        $calcTotal = 0;
        if (!empty($raw['items']) && is_array($raw['items'])) {
            foreach ($raw['items'] as $item) {
                $srv = $item['service'] ?? $item;
                $p = (float)($srv['price'] ?? 0);
                $calcTotal += $p;
                $catName = !empty($srv['categories'][0]['name']) ? $srv['categories'][0]['name'] : 'Haircut';
                $services[] = [
                    'name' => $srv['name'] ?? 'Hair Cut',
                    'category' => $catName,
                    'price' => '₹ ' . number_format($p, 2)
                ];
            }
        }

        $orderAmount = (float)($raw['orderAmount'] ?? $calcTotal);
        $totalLabel = ($uiStatus === 'completed') ? 'Billed Amount' : 'Estimated Total';

        // Customer gender format
        $rawGender = strtoupper($raw['user']['gender'] ?? 'MALE');
        $genderFormatted = ($rawGender === 'FEMALE') ? 'Female' : 'Male';

        $profileImg = $api->formatImageUrl($raw['user']['profileImage'] ?? null, 'assets/images/user-avatar.png');

        $selectedStylistId = $raw['appointment']['stylistIds'][0] ?? ($hairStylists[0]['id'] ?? '');

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $raw['appointmentId'] ?? $raw['id'] ?? $appointmentId,
                'idx' => '#' . ($raw['idx'] ?? 'BI123456'),
                'status' => $uiStatus,
                'statusLabel' => $statusLabel,
                'dateTime' => $formattedDateTime,
                'date' => $formattedDate,
                'user' => [
                    'name' => $raw['user']['name'] ?? 'Customer',
                    'gender' => $genderFormatted,
                    'mobile' => ($raw['user']['countryCode'] ? '+' . $raw['user']['countryCode'] . ' ' : '') . ($raw['user']['mobile'] ?? ''),
                    'avatar' => $profileImg
                ],
                'slots' => $slots,
                'stylists' => $hairStylists,
                'beauticians' => $beautyStylists,
                'selectedStylistId' => $selectedStylistId,
                'selectedBeauticianId' => '',
                'services' => $services,
                'totalLabel' => $totalLabel,
                'totalAmount' => '₹ ' . number_format($orderAmount, 2),
                'rejectionReason' => $raw['rejectionReason']['label'] ?? $raw['rejectionRemark'] ?? null
            ]
        ]);
        break;

    case 'get_reasons':
        $res = $api->getRejectionReasons();
        if ($res && !empty($res['data'])) {
            echo json_encode([
                'success' => true,
                'data' => $res['data']
            ]);
        } else {
            // Default fallback reasons matching Figma
            echo json_encode([
                'success' => true,
                'data' => [
                    ['id' => '73414a8c-f270-4cfd-bb98-bdd7cc625b7b', 'code' => 'STYLIST_NOT_AVAILABLE', 'label' => 'Stylist Slot Not Available'],
                    ['id' => '7874d27e-1990-4fb0-a1ae-524b2460a4f0', 'code' => 'SLOT_NOT_AVAILABLE', 'label' => 'Salon is Fully Occupied'],
                    ['id' => 'f65a4f55-bc56-48e6-a764-429b460f9601', 'code' => 'OTHER', 'label' => 'Other']
                ]
            ]);
        }
        break;

    case 'accept':
        $appointmentId = trim($_POST['id'] ?? '');
        $stylistId = trim($_POST['stylistId'] ?? '');
        $startsAt = trim($_POST['startsAt'] ?? '');

        if (empty($appointmentId)) {
            echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
            exit;
        }

        // Demo test mode check
        if (str_starts_with($appointmentId, 'demo_') || $appointmentId === 'sample') {
            echo json_encode([
                'success' => true,
                'message' => 'Booking successfully confirmed!'
            ]);
            exit;
        }

        $stylistIds = !empty($stylistId) ? [$stylistId] : null;
        $res = $api->updateAppointmentStatus($appointmentId, 'confirmed', $stylistIds, $startsAt ?: null);

        if ($res !== null) {
            echo json_encode([
                'success' => true,
                'message' => 'Booking successfully confirmed!'
            ]);
        } else {
            $err = $api->getLastError();
            $msg = $err['response']['message'] ?? 'Failed to accept booking';
            echo json_encode(['success' => false, 'message' => is_array($msg) ? implode(', ', $msg) : $msg]);
        }
        break;

    case 'reject':
        $appointmentId = trim($_POST['id'] ?? '');
        $reasonId = trim($_POST['reasonId'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($appointmentId)) {
            echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
            exit;
        }

        // Demo test mode check
        if (str_starts_with($appointmentId, 'demo_') || $appointmentId === 'sample') {
            echo json_encode([
                'success' => true,
                'message' => 'Booking successfully rejected!'
            ]);
            exit;
        }

        $res = $api->updateAppointmentStatus($appointmentId, 'salon_rejected', null, null, $reasonId ?: null, $reason ?: null);

        if ($res !== null) {
            echo json_encode([
                'success' => true,
                'message' => 'Booking successfully rejected!'
            ]);
        } else {
            $err = $api->getLastError();
            $msg = $err['response']['message'] ?? 'Failed to reject booking';
            echo json_encode(['success' => false, 'message' => is_array($msg) ? implode(', ', $msg) : $msg]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
