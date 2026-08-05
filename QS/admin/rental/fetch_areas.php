<?php
    include '../../config/config.php';
    if (isset($_GET['region']) && !empty($_GET['region'])) {
        $region = mysqli_real_escape_string($conn, $_GET['region']); // Prevent SQL injection
        $areaSql = "SELECT DISTINCT area FROM branch_insurance WHERE region = '$region' AND area != '' ORDER BY area ASC";
        $resultArea = mysqli_query($conn, $areaSql);
        if ($resultArea) {
            echo "<option value=''></option>"; // Default empty option
            while ($rowArea = mysqli_fetch_assoc($resultArea)) {
                echo "<option value='" . htmlspecialchars($rowArea['area']) . "'>" . htmlspecialchars($rowArea['area']) . "</option>";
            }
        } else {
            echo "<option value=''>Error fetching areas</option>";
        }
    } else {
        echo "<option value=''>No region selected</option>"; // Handle no selection
    }
?>