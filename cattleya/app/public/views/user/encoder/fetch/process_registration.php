<?php
ob_start(); 
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../../../../config/database.php';

$response = ['status' => 'error', 'message' => 'Invalid request.'];

/**
 * Helper function to ensure the ID suffix is always 6 digits.
 * Input: "BRK-1" or "BRK-45"
 * Output: "BRK-000001" or "BRK-000045"
 */
function formatPersonnelId($id) {
    if (empty($id)) return $id;
    
    // Check if there's a hyphen (e.g., BRK-1)
    if (strpos($id, '-') !== false) {
        $parts = explode('-', $id);
        $prefix = $parts[0];
        $number = $parts[1];
        
        // Pad the number part to 6 digits
        if (is_numeric($number)) {
            return $prefix . '-' . str_pad($number, 6, "0", STR_PAD_LEFT);
        }
    }
    return $id; // Return as-is if format is unexpected
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = !empty($_POST['id']) ? $_POST['id'] : null;
    $role_type      = $_POST['role_type'] ?? 'broker';
    
    // Format the Custom ID to 6 digits
    $custom_id      = formatPersonnelId(htmlspecialchars(trim($_POST['custom_id'] ?? '')));
    
    $firstname      = htmlspecialchars(trim($_POST['firstname'] ?? ''));
    $middlename     = htmlspecialchars(trim($_POST['middlename'] ?? ''));
    $lastname       = htmlspecialchars(trim($_POST['lastname'] ?? ''));
    $gender         = $_POST['gender'] ?? null;
    $address        = htmlspecialchars(trim($_POST['address'] ?? ''));
    $contact        = htmlspecialchars(trim($_POST['contact_number'] ?? ''));
    $email          = htmlspecialchars(trim($_POST['email_address'] ?? ''));
    
    $current_user   = $_SESSION['user_name'] ?? 'System'; 
    $status         = 'Active';

    // Get the Numeric PKs for lookups
    $broker_pk_id   = !empty($_POST['broker_id_val']) ? (int)$_POST['broker_id_val'] : null;
    $um_pk_id       = !empty($_POST['um_id_val']) ? (int)$_POST['um_id_val'] : null;

    try {
        $broker_code = null;
        $um_code     = null;

        // Lookup alphanumeric codes for hierarchy
        if ($broker_pk_id) {
            $stmt = $pdo->prepare("SELECT broker_id FROM brokers WHERE id = ?");
            $stmt->execute([$broker_pk_id]);
            $broker_code = $stmt->fetchColumn();
        }

        if ($um_pk_id) {
            $stmt = $pdo->prepare("SELECT um_id FROM unit_managers WHERE id = ?");
            $stmt->execute([$um_pk_id]);
            $um_code = $stmt->fetchColumn();
        }

        // Logic based on Role Type
        if ($role_type === 'broker') {
            if ($id) {
                $sql = "UPDATE brokers SET broker_id=?, firstname=?, middlename=?, lastname=?, gender=?, address=?, contact_number=?, email_address=?, updated_by=? WHERE id=?";
                $params = [$custom_id, $firstname, $middlename, $lastname, $gender, $address, $contact, $email, $current_user, $id];
            } else {
                $sql = "INSERT INTO brokers (broker_id, firstname, middlename, lastname, gender, address, contact_number, email_address, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)";
                $params = [$custom_id, $firstname, $middlename, $lastname, $gender, $address, $contact, $email, $status, $current_user];
            }
        } 
        elseif ($role_type === 'unit_manager') {
            if (!$broker_code) throw new Exception("Invalid Broker selection.");
            
            if ($id) {
                $sql = "UPDATE unit_managers SET um_id=?, broker_id=?, firstname=?, middlename=?, lastname=?, gender=?, address=?, contact_number=?, email_address=?, updated_by=? WHERE id=?";
                $params = [$custom_id, $broker_code, $firstname, $middlename, $lastname, $gender, $address, $contact, $email, $current_user, $id];
            } else {
                $sql = "INSERT INTO unit_managers (um_id, broker_id, firstname, middlename, lastname, gender, address, contact_number, email_address, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                $params = [$custom_id, $broker_code, $firstname, $middlename, $lastname, $gender, $address, $contact, $email, $status, $current_user];
            }
        } 
        elseif ($role_type === 'agent') {
            if (!$broker_code || !$um_code) throw new Exception("Agents require a valid Broker and Unit Manager.");

            if ($id) {
                $sql = "UPDATE agents SET agent_id=?, um_id=?, broker_id=?, firstname=?, middlename=?, lastname=?, gender=?, address=?, contact_number=?, email_address=?, updated_by=? WHERE id=?";
                $params = [$custom_id, $um_code, $broker_code, $firstname, $middlename, $lastname, $gender, $address, $contact, $email, $current_user, $id];
            } else {
                $sql = "INSERT INTO agents (agent_id, um_id, broker_id, firstname, middlename, lastname, gender, address, contact_number, email_address, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                $params = [$custom_id, $um_code, $broker_code, $firstname, $middlename, $lastname, $gender, $address, $contact, $email, $status, $current_user];
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $response = ['status' => 'success', 'message' => $id ? 'Profile updated!' : 'Member registered!'];

    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

ob_end_clean();
echo json_encode($response);