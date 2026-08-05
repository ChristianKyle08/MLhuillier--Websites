<?php
    include '../../config/config.php';
    if (isset($_GET['area']) && !empty($_GET['area'])) {
        $region = mysqli_real_escape_string($conn, $_GET['region']); 
        $area = mysqli_real_escape_string($conn, $_GET['area']); 
        $branchSql = "SELECT DISTINCT branch_name FROM branch_insurance 
                    WHERE region = '$region' AND area = '$area' AND branch_name != '' 
                    ORDER BY branch_name ASC";
        $resultBranch = mysqli_query($conn, $branchSql);
        if ($resultBranch) {
            echo "<option value=''></option>";
            while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                echo "<option value='" . htmlspecialchars($rowBranch['branch_name']) . "'>" . htmlspecialchars($rowBranch['branch_name']) . "</option>";
            }
        } else {
            echo "<option value=''>Error fetching branches</option>";
        }
    } else {
        echo "<option value=''>No area selected</option>";
    }
?>