<?php
/**
 * AJAX Controller for Stylist Operations
 * Integrates directly with ScutS Backend APIs:
 * - POST salon/artist/add
 * - PUT salon/artist/add-with-sid
 * - DELETE salon/artist/{artistId}/delete
 * - PATCH salon/artist/{artistId}/update
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/portfolio_helper.php';

$api = new ScutsApiClient();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add_stylist':
        $name = trim($_POST['name'] ?? '');
        $rawMobile = trim($_POST['mobile'] ?? '');
        $mobile = preg_replace('/\D/', '', $rawMobile);
        $gender = strtoupper(trim($_POST['gender'] ?? 'UNISEX'));
        $serviceableGender = strtoupper(trim($_POST['serviceableGender'] ?? 'UNISEX'));
        
        // Profession mapping
        $profArray = $_POST['profession'] ?? ['Hair stylist'];
        if (!is_array($profArray)) {
            $profArray = [$profArray];
        }
        $hasHair = in_array('Hair stylist', $profArray);
        $hasBeauty = in_array('Beautician', $profArray);
        
        if ($hasHair && $hasBeauty) {
            $professionCode = 'BOTH';
        } elseif ($hasBeauty) {
            $professionCode = 'BEAUTY';
        } else {
            $professionCode = 'HAIR';
        }

        // Languages mapping
        $languages = $_POST['languages'] ?? ['English', 'Kannada'];
        if (!is_array($languages)) {
            $languages = explode(',', (string)$languages);
        }

        // Validation
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Stylist name is required.']);
            exit;
        }

        if (strlen($mobile) < 10) {
            echo json_encode(['success' => false, 'message' => 'Valid 10-digit mobile number is required.']);
            exit;
        }

        if (!in_array($gender, ['MALE', 'FEMALE', 'UNISEX'])) {
            $gender = 'UNISEX';
        }

        // Auto-generate standard ScutS sId
        $sid = 'S' . rand(100000, 999999) . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 4));

        $payload = [
            'name' => $name,
            'mobile' => substr($mobile, -10),
            'countryCode' => '91',
            'gender' => $gender,
            'profession' => $professionCode,
            'password' => 'Scuts@123',
            'sId' => $sid,
            'experience' => '2 years',
            'homeService' => '0',
            'whatsapp' => substr($mobile, -10),
            'address' => 'Salon Branch'
        ];

        // Process profileImage file upload if present
        $uploadedFile = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['image'];
        }

        $res = $api->addStylist($payload, $uploadedFile);

        if ($res && (!empty($res['data']['id']) || (!empty($res['success']) && $res['success'] === true))) {
            $artistData = $res['data'] ?? [];
            $newId = $artistData['id'] ?? ('st_' . time());
            $newSid = $artistData['sId'] ?? $sid;
            $rawImg = $artistData['profileImage'] ?? null;
            $imgUrl = !empty($rawImg) ? $api->formatImageUrl($rawImg, 'assets/images/user-avatar.png') : 'assets/images/user-avatar.png';

            // Handle portfolio uploads & existing photos for newly added stylist
            $portfolioList = [];
            $uploadDir = __DIR__ . '/../assets/uploads/portfolio/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            for ($i = 1; $i <= 3; $i++) {
                $fileKey = "portfolio_{$i}";
                if (!empty($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION)) ?: 'jpg';
                    $safeName = 'port_' . substr(md5($newId), 0, 8) . "_{$i}_" . time() . '.' . $ext;
                    $dest = $uploadDir . $safeName;
                    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
                        $portfolioList[] = 'assets/uploads/portfolio/' . $safeName;
                    }
                } elseif (!empty($_POST["portfolio_existing_{$i}"])) {
                    $portfolioList[] = trim($_POST["portfolio_existing_{$i}"]);
                }
            }
            if (!empty($portfolioList)) {
                save_stylist_portfolio($newId, $portfolioList);
            }

            save_stylist_meta($newId, [
                'serviceableGender' => $serviceableGender,
                'profession' => $profArray,
                'professionCode' => $professionCode
            ]);

            $returnData = [
                'id' => $newId,
                'sidCode' => $newSid,
                'name' => $artistData['name'] ?? $name,
                'mobile' => $artistData['mobile'] ?? $mobile,
                'gender' => $artistData['gender'] ?? $gender,
                'serviceableGender' => $serviceableGender,
                'image' => $imgUrl,
                'isOnLeave' => false,
                'status' => 1,
                'profession' => $profArray,
                'languages' => $languages,
                'portfolio' => $portfolioList
            ];

            echo json_encode([
                'success' => true,
                'data' => $returnData,
                'message' => 'Stylist added successfully to ScutS salon!'
            ]);
            exit;
        }

        // Handle error from API
        $lastErr = $api->getLastError();
        $errMsg = 'Failed to add stylist.';
        if (!empty($lastErr['response']['message'])) {
            $rawMsg = $lastErr['response']['message'];
            $errMsg = is_array($rawMsg) ? implode(', ', $rawMsg) : (string)$rawMsg;
        }

        echo json_encode([
            'success' => false,
            'message' => $errMsg
        ]);
        exit;

    case 'delete_stylist':
        $id = trim($_GET['id'] ?? $_POST['id'] ?? '');

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Stylist ID is required.']);
            exit;
        }

        // If UUID format (actual server record), call backend delete API
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $res = $api->deleteStylist($id);
            if ($res && !empty($res['success'])) {
                delete_stylist_portfolio($id);
                delete_stylist_meta($id);
                echo json_encode(['success' => true, 'message' => 'Stylist removed successfully from salon.']);
                exit;
            }
        }

        delete_stylist_portfolio($id);
        delete_stylist_meta($id);
        // For local/demo entries
        echo json_encode(['success' => true, 'message' => 'Stylist removed successfully.']);
        exit;

    case 'get_stylist_details':
        $id = trim($_GET['id'] ?? $_POST['id'] ?? '');
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Stylist ID is required.']);
            exit;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $detailsRes = $api->getStylistDetails($id);
            if (!empty($detailsRes['data'])) {
                $d = $detailsRes['data'];
                $meta = get_stylist_meta($id);

                // Determine profession
                $profCode = !empty($d['profession']) ? strtoupper($d['profession']) : ($meta['professionCode'] ?? null);
                if ($profCode === 'BOTH') {
                    $professions = ['Hair stylist', 'Beautician'];
                } elseif ($profCode === 'BEAUTY') {
                    $professions = ['Beautician'];
                } elseif ($profCode === 'HAIR') {
                    $professions = ['Hair stylist'];
                } elseif (!empty($meta['profession']) && is_array($meta['profession'])) {
                    $professions = $meta['profession'];
                } else {
                    $professions = ['Hair stylist'];
                }

                // Determine serviceable gender (backend does not have column, so use persisted meta)
                $serviceableGender = !empty($meta['serviceableGender']) 
                    ? strtoupper($meta['serviceableGender']) 
                    : strtoupper($d['serviceableGender'] ?? 'UNISEX');

                $rawImg = $d['profileImage'] ?? null;
                $imgUrl = !empty($rawImg) ? $api->formatImageUrl($rawImg, 'assets/images/user-avatar.png') : 'assets/images/user-avatar.png';

                // Extract portfolio images
                $portfolioList = [];
                if (!empty($d['portfolio']) && is_array($d['portfolio'])) {
                    foreach ($d['portfolio'] as $pItem) {
                        $pImg = is_array($pItem) ? ($pItem['image'] ?? $pItem['url'] ?? '') : (string)$pItem;
                        if (!empty($pImg)) {
                            $portfolioList[] = $api->formatImageUrl($pImg);
                        }
                    }
                }

                // Fallback to locally persisted portfolio if backend returned empty
                if (empty($portfolioList)) {
                    $portfolioList = get_stylist_portfolio($id);
                }

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $d['id'] ?? $id,
                        'name' => $d['name'] ?? '',
                        'mobile' => $d['mobile'] ?? '',
                        'gender' => strtoupper($d['gender'] ?? 'UNISEX'),
                        'serviceableGender' => $serviceableGender,
                        'profession' => $professions,
                        'languages' => !empty($d['languagesKnown']) ? $d['languagesKnown'] : ['English', 'Kannada'],
                        'image' => $imgUrl,
                        'portfolio' => $portfolioList
                    ]
                ]);
                exit;
            }
        }

        echo json_encode(['success' => false, 'message' => 'Stylist details not found.']);
        exit;

    case 'update_stylist':
        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $rawMobile = trim($_POST['mobile'] ?? '');
        $mobile = preg_replace('/\D/', '', $rawMobile);
        $gender = strtoupper(trim($_POST['gender'] ?? 'UNISEX'));
        $serviceableGender = strtoupper(trim($_POST['serviceableGender'] ?? 'UNISEX'));

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Stylist ID is required.']);
            exit;
        }

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Stylist name is required.']);
            exit;
        }

        // Profession mapping
        $profArray = $_POST['profession'] ?? ['Hair stylist'];
        if (!is_array($profArray)) {
            $profArray = [$profArray];
        }
        $hasHair = in_array('Hair stylist', $profArray);
        $hasBeauty = in_array('Beautician', $profArray);
        if ($hasHair && $hasBeauty) {
            $professionCode = 'BOTH';
        } elseif ($hasBeauty) {
            $professionCode = 'BEAUTY';
        } else {
            $professionCode = 'HAIR';
        }

        // Languages mapping
        $languages = $_POST['languages'] ?? ['English', 'Kannada'];
        if (!is_array($languages)) {
            $languages = array_filter(array_map('trim', explode(',', (string)$languages)));
        }
        if (empty($languages)) {
            $languages = ['English', 'Kannada'];
        }

        // Validate gender
        if (!in_array($gender, ['MALE', 'FEMALE', 'UNISEX'])) {
            $gender = 'UNISEX';
        }
        if (!in_array($serviceableGender, ['MALE', 'FEMALE', 'UNISEX'])) {
            $serviceableGender = 'UNISEX';
        }

        // Handle profile avatar file upload
        $uploadedFile = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['image'];
        }

        // Handle portfolio uploads & existing photos
        $portfolioList = [];
        $uploadDir = __DIR__ . '/../assets/uploads/portfolio/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        for ($i = 1; $i <= 3; $i++) {
            $fileKey = "portfolio_{$i}";
            if (!empty($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION)) ?: 'jpg';
                $safeName = 'port_' . substr(md5($id), 0, 8) . "_{$i}_" . time() . '.' . $ext;
                $dest = $uploadDir . $safeName;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
                    $portfolioList[] = 'assets/uploads/portfolio/' . $safeName;
                }
            } elseif (!empty($_POST["portfolio_existing_{$i}"])) {
                $portfolioList[] = trim($_POST["portfolio_existing_{$i}"]);
            }
        }

        // Persist portfolio changes for this stylist
        save_stylist_portfolio($id, $portfolioList);

        // Persist serviceableGender and profession for this stylist
        save_stylist_meta($id, [
            'serviceableGender' => $serviceableGender,
            'profession' => $profArray,
            'professionCode' => $professionCode
        ]);

        // Prepare backend payload
        $payload = [
            'name' => $name,
            'gender' => $gender,
            'serviceableGender' => $serviceableGender,
            'profession' => $professionCode,
            'languagesKnown' => $languages
        ];

        if (!empty($mobile) && strlen($mobile) >= 10) {
            $payload['mobile'] = substr($mobile, -10);
            $payload['whatsapp'] = substr($mobile, -10);
        }

        $res = null;
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $res = $api->updateStylist($id, $payload, $uploadedFile);
        }

        // Determine updated avatar URL
        $updatedAvatarUrl = null;
        if ($res && !empty($res['data']['profileImage'])) {
            $updatedAvatarUrl = $api->formatImageUrl($res['data']['profileImage'], 'assets/images/user-avatar.png');
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'name' => $name,
                'mobile' => $mobile,
                'gender' => $gender,
                'serviceableGender' => $serviceableGender,
                'profession' => $profArray,
                'languages' => $languages,
                'image' => $updatedAvatarUrl,
                'portfolio' => $portfolioList
            ],
            'message' => 'Stylist updated successfully!'
        ]);
        exit;

    case 'get_stylist_availability':
        $id = trim($_GET['id'] ?? $_POST['id'] ?? '');
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Stylist ID is required.']);
            exit;
        }

        $defaultDay = [
            'start' => '10:00',
            'end' => '21:00',
            'breaks' => [
                ['start' => '14:30', 'end' => '15:00']
            ]
        ];
        $fallbackPlan = [
            'sunday' => $defaultDay,
            'monday' => $defaultDay,
            'tuesday' => $defaultDay,
            'wednesday' => $defaultDay,
            'thursday' => $defaultDay,
            'friday' => $defaultDay,
            'saturday' => $defaultDay
        ];

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $availRes = $api->getStylistAvailability($id);
            if (!empty($availRes['data']) && is_array($availRes['data'])) {
                echo json_encode([
                    'success' => true,
                    'data' => $availRes['data']
                ]);
                exit;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $fallbackPlan
        ]);
        exit;

    case 'update_stylist_availability':
        $id = trim($_POST['id'] ?? '');
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Stylist ID is required.']);
            exit;
        }

        $rawPlan = $_POST['workingPlan'] ?? null;
        if (is_string($rawPlan)) {
            $workingPlan = json_decode($rawPlan, true);
        } else {
            $workingPlan = is_array($rawPlan) ? $rawPlan : [];
        }

        $todayAvailable = isset($_POST['todayAvailable']) ? (bool)$_POST['todayAvailable'] : true;
        $timeOffDates = trim($_POST['timeOffDates'] ?? '');

        // Standard 7 days validation & formatting
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $formattedWorkingPlan = [];
        $defaultDayObj = [
            'start' => '10:00',
            'end' => '21:00',
            'breaks' => [
                ['start' => '14:30', 'end' => '15:00']
            ]
        ];

        foreach ($days as $d) {
            if (isset($workingPlan[$d]) && $workingPlan[$d] !== null && $workingPlan[$d] !== false && $workingPlan[$d] !== 'false') {
                $formattedWorkingPlan[$d] = is_array($workingPlan[$d]) ? $workingPlan[$d] : $defaultDayObj;
            } else {
                $formattedWorkingPlan[$d] = null;
            }
        }

        // Check today's day
        $dayIndex = (int)date('w'); // 0 (Sun) to 6 (Sat)
        $todayKey = $days[$dayIndex];
        if (!$todayAvailable) {
            $formattedWorkingPlan[$todayKey] = null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            // Update weekly availability on live backend
            $res = $api->updateStylistAvailability($id, ['workingPlan' => $formattedWorkingPlan]);

            // If marked off for today, also trigger block slot
            if (!$todayAvailable) {
                $startSlot = date('Y-m-d') . 'T00:00:00Z';
                $endSlot = date('Y-m-d') . 'T23:59:59Z';
                $api->blockStylistSlot($id, $startSlot, $endSlot);
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'workingPlan' => $formattedWorkingPlan,
                'todayAvailable' => $todayAvailable,
                'timeOffDates' => $timeOffDates
            ],
            'message' => 'Stylist availability updated successfully!'
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;
}
