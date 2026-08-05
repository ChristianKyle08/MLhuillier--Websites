<?php
    include '../../config/config.php';
    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        $conn = mysqli_connect($host, $username, $password, $database); 
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        $sql = "DELETE FROM user_form WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }
        $stmt->close();
        $conn->close();
    }
?>
