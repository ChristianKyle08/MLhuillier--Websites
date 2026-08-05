<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
    if (isset($_GET['fileId'])) {
        $fileName = urldecode($_GET['fileId']);
        
        // Fetch the contract file from the database
        $sql = "SELECT contract_file, mimeType FROM create_contract WHERE contractFilename = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $fileName);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($fileContent, $mimeType);
        $stmt->fetch();
        
        if ($fileContent) {
            // Set proper headers for the file
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            echo $fileContent; // Output the file content
        } else {
            echo "File not found.";
        }
    } else {
        echo "No file selected.";
    }
?>
