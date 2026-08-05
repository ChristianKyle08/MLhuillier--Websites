<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
include '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contractId = (int)$_POST['contract_id'];
  $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

  $query = "UPDATE create_contract SET reviewer_note = '$remarks', request_status = 'Created' WHERE id = $contractId";
  if (mysqli_query($conn, $query)) {
    header("Location: user_page.php?msg=Remarks submitted");
    exit;
  } else {
    echo "Error: " . mysqli_error($conn);
  }
}
?>
