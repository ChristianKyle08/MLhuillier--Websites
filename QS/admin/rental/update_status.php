<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
    include '../../config/config.php';
    $conn = mysqli_connect($host, $username, $password, $database); 
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    $id = $_POST['id'];
    $status = $_POST['status'];
    $query = "UPDATE user_form SET status = '$status' WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "Status updated successfully";
    } else {
        echo "Error updating status: " . mysqli_error($conn);
    }
?>