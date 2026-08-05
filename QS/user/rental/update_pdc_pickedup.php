<?php
include '../../config/config.php';

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['contract_number'], $_POST['transaction_date'])) {
    $contractNumber = mysqli_real_escape_string($conn, $_POST['contract_number']);
    $transactionDate = mysqli_real_escape_string($conn, $_POST['transaction_date']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch'] ?? '');

    $formattedDate = date("Y-m-d", strtotime($transactionDate));

    $sql = "UPDATE transactional 
            SET pdc_status = 'Picked-up' 
            WHERE contract_number = '$contractNumber' 
            AND DATE(transaction_date) = '$formattedDate'
            AND mode_of_payment = 'PDC'";

    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            echo "✅ PDC successfully marked as Picked-up for Contract #$contractNumber.";
        } else {
            echo "⚠️ No matching record found for Contract #$contractNumber on $formattedDate.";
        }
    } else {
        echo "❌ Error updating record: " . mysqli_error($conn);
    }
} else {
    echo "⚠️ Invalid request. Missing contract_number or transaction_date.";
}

mysqli_close($conn);
?>
