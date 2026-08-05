<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

if (isset($_POST['region'])) {
    $region = mysqli_real_escape_string($conn, $_POST['region']);

    // Query to fetch branches based on the selected region
    $branchSql = "SELECT DISTINCT sendout_branch FROM sendout WHERE sendout_branch != '' AND region = '$region' ORDER BY sendout_branch ASC";
    $resultBranch = mysqli_query($conn, $branchSql);

    if ($resultBranch) {
        echo "<option value=''>Select Branch</option>"; // Default option
        while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
            echo "<option value='" . htmlspecialchars($rowBranch['sendout_branch']) . "'>" . htmlspecialchars($rowBranch['sendout_branch']) . "</option>";
        }
    } else {
        echo "<option value=''>No branches available</option>";
    }
}
?>
