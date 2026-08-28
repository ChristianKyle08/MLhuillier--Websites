<?php
ob_start();
require __DIR__ . '/../../../config/database.php';

// Ensure strict JSON header output
header('Content-Type: application/json');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $id = $_POST['id'] ?? null;
    if (!$id) throw new Exception("Missing user ID");

    $first    = trim($_POST['first_name'] ?? '');
    $last     = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? '');
    $status   = trim($_POST['status'] ?? '');

    // FIX 1: Ensure array indexes are reset to sequential order [0, 1, 2]
    // This guarantees json_encode produces a JSON Array ["a","b"] instead of an Object {"0":"a","2":"b"}
    $rawFeatures   = $_POST['features'] ?? [];
    $featuresArray = is_array($rawFeatures) ? array_values($rawFeatures) : [];
    $featuresJson  = json_encode($featuresArray);

    if (!$first || !$last || !$username || !$email) {
        throw new Exception("All fields are required");
    }

    // Validate role/status against allowed ENUM values
    $allowedRoles = ['encoder','admin','auditor','finance','cfo','cashier','operation_manager'];
    if (!in_array($role, $allowedRoles)) throw new Exception("Invalid role value");

    $allowedStatus = ['active','inactive','pending'];
    if (!in_array($status, $allowedStatus)) throw new Exception("Invalid status value");

    // Check if user exists
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $check->execute([$id]);
    if ($check->rowCount() === 0) throw new Exception("User not found");

    // Check duplicates
    $dup = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id != ?");
    $dup->execute([$username, $email, $id]);
    if ($dup->rowCount() > 0) throw new Exception("Username or email already exists");

    // Execute UPDATE
    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, username=?, email=?, role=?, status=?, features=? WHERE id=?");
    $success = $stmt->execute([$first, $last, $username, $email, $role, $status, $featuresJson, $id]);

    // FIX 2: Clear buffer so stray warnings or database whitespace don't break JSON response
    ob_clean();
    echo json_encode([
        "success" => $success,
        "message" => $success ? "User updated successfully" : "Update failed"
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "error" => "PDO Error: " . $e->getMessage()]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

exit;