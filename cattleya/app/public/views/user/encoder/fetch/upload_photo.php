<?php
require __DIR__ . '/../../../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    
    if ($productId && isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === 0) {
        try {
            $imgData = file_get_contents($_FILES['product_photo']['tmp_name']);
            
            // Note: Targeting 'product_profile' table based on your previous query context
            $sql = "UPDATE product_profile SET product_image = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$imgData, $productId])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}