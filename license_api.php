<?php
// license_api.php - Place this on your web server
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

$db_file = 'licenses.json';

// Load licenses
function load_licenses() {
    global $db_file;
    if (file_exists($db_file)) {
        return json_decode(file_get_contents($db_file), true);
    }
    return [];
}

// Save licenses
function save_licenses($licenses) {
    global $db_file;
    file_put_contents($db_file, json_encode($licenses, JSON_PRETTY_PRINT));
}

// Get action from request
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Admin password (change this)
$admin_pass = "YourStrongAdminPassword2024@";

// Verify admin
function is_admin() {
    global $admin_pass;
    return isset($_REQUEST['admin_key']) && $_REQUEST['admin_key'] === $admin_pass;
}

// Handle actions
switch($action) {
    case 'create_license':
        // Create new license (admin only)
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $user_id = uniqid('USER_');
        $licenses = load_licenses();
        
        $license_data = [
            'user_id' => $user_id,
            'username' => $_REQUEST['username'],
            'status' => 'active',
            'created' => date('Y-m-d H:i:s'),
            'expiry' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'machine_id' => '',
            'last_active' => date('Y-m-d H:i:s')
        ];
        
        $licenses[$user_id] = $license_data;
        save_licenses($licenses);
        
        echo json_encode(['success' => true, 'license_key' => $user_id, 'data' => $license_data]);
        break;
        
    case 'verify_license':
        // Verify license (called from client)
        $license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';
        $machine_id = isset($_REQUEST['machine_id']) ? $_REQUEST['machine_id'] : '';
        
        $licenses = load_licenses();
        
        if (!isset($licenses[$license_key])) {
            echo json_encode(['success' => false, 'message' => 'Invalid license key']);
            break;
        }
        
        $license = $licenses[$license_key];
        
        // Check if disabled
        if ($license['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'License has been disabled by admin']);
            break;
        }
        
        // Check expiry
        if (strtotime($license['expiry']) < time()) {
            echo json_encode(['success' => false, 'message' => 'License has expired']);
            break;
        }
        
        // Update machine ID and last active
        $license['machine_id'] = $machine_id;
        $license['last_active'] = date('Y-m-d H:i:s');
        $licenses[$license_key] = $license;
        save_licenses($licenses);
        
        echo json_encode([
            'success' => true, 
            'message' => 'License valid',
            'expiry' => $license['expiry'],
            'username' => $license['username']
        ]);
        break;
        
    case 'disable_license':
        // Disable license (admin only)
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';
        $licenses = load_licenses();
        
        if (isset($licenses[$license_key])) {
            $licenses[$license_key]['status'] = 'disabled';
            save_licenses($licenses);
            echo json_encode(['success' => true, 'message' => 'License disabled']);
        } else {
            echo json_encode(['success' => false, 'message' => 'License not found']);
        }
        break;
        
    case 'enable_license':
        // Enable license (admin only)
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';
        $licenses = load_licenses();
        
        if (isset($licenses[$license_key])) {
            $licenses[$license_key]['status'] = 'active';
            save_licenses($licenses);
            echo json_encode(['success' => true, 'message' => 'License enabled']);
        } else {
            echo json_encode(['success' => false, 'message' => 'License not found']);
        }
        break;
        
    case 'extend_license':
        // Extend license (admin only)
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';
        $days = isset($_REQUEST['days']) ? intval($_REQUEST['days']) : 30;
        $licenses = load_licenses();
        
        if (isset($licenses[$license_key])) {
            $new_expiry = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
            $licenses[$license_key]['expiry'] = $new_expiry;
            save_licenses($licenses);
            echo json_encode(['success' => true, 'message' => 'License extended', 'new_expiry' => $new_expiry]);
        } else {
            echo json_encode(['success' => false, 'message' => 'License not found']);
        }
        break;
        
    case 'list_licenses':
        // List all licenses (admin only)
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $licenses = load_licenses();
        echo json_encode(['success' => true, 'licenses' => $licenses]);
        break;
        
    case 'delete_license':
        // Delete license (admin only)
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';
        $licenses = load_licenses();
        
        if (isset($licenses[$license_key])) {
            unset($licenses[$license_key]);
            save_licenses($licenses);
            echo json_encode(['success' => true, 'message' => 'License deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'License not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
