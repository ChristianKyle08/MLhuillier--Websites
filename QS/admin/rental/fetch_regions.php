<?php
    include '../../config/config.php';
    if (isset($_GET['mainzone']) && !empty($_GET['mainzone'])) {
        $mainzone = mysqli_real_escape_string($conn, $_GET['mainzone']);
        $regionSql = "SELECT DISTINCT region FROM branch_insurance WHERE mainzone = '$mainzone' ORDER BY region ASC";
        $resultRegion = mysqli_query($conn, $regionSql);

        if ($resultRegion) {
            echo "<option value=''></option>";
            while ($rowRegion = mysqli_fetch_assoc($resultRegion)) {
                echo "<option value='" . htmlspecialchars($rowRegion['region']) . "'>" . htmlspecialchars($rowRegion['region']) . "</option>";
            }
        } else {
            echo "<option value=''>Error fetching regions</option>";
        }
    } else {
        echo "<option value=''>No mainzone selected</option>";
    }
?>
