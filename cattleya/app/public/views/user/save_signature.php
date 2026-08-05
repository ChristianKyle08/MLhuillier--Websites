<?php
// Ensure session is started to access user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

// 1. Check Login
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User session expired. Please login again.']);
    exit;
}

// 2. Check File Upload
if(!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['success' => false, 'message' => 'File upload error. Please try a different image.']);
    exit;
}

$fileTmpPath = $_FILES['signature']['tmp_name'];
$fileName = $_FILES['signature']['name'];
$fileType = mime_content_type($fileTmpPath);

// 3. Updated Validation: Accept ALL image types
// This checks if the MIME type starts with "image/"
if(strpos($fileType, 'image/') !== 0){
    echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload an image file.']);
    exit;
}

// 4. Read file content and encode
$fileData = file_get_contents($fileTmpPath);
$base64Data = base64_encode($fileData);

try {
    // 5. Update user signature in DB
    $stmt = $pdo->prepare("UPDATE users SET signature = ?, signature_name = ?, signature_type = ? WHERE id = ?");
    $result = $stmt->execute([$base64Data, $fileName, $fileType, $_SESSION['user_id']]);

    if($result) {
        echo json_encode(['success' => true, 'message' => 'Signature saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update record.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}