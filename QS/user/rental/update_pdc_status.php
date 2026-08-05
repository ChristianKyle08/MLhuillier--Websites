<?php
include '../../config/config.php';

// Database connection
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if POST data exists
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['contract_number'], $_POST['transaction_date'])) {
    $contractNumber = mysqli_real_escape_string($conn, $_POST['contract_number']);
    $transactionDate = mysqli_real_escape_string($conn, $_POST['transaction_date']);

    // ✅ Convert transaction_date to Y-m-d format
    $formattedDate = date("Y-m-d", strtotime($transactionDate));

    // ✅ Update query with formatted date
    $sql = "
        UPDATE transactional 
        SET pdc_status = 'Audited'
        WHERE contract_number = '$contractNumber'
        AND DATE(transaction_date) = '$formattedDate'
        AND mode_of_payment = 'PDC'
    ";

    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            echo "✅ PDC successfully audited for Contract #$contractNumber on $formattedDate.";
        } else {
            echo "⚠️ No matching record found for Contract #$contractNumber on $formattedDate.";
        }
    } else {
        echo "❌ Error updating record: " . mysqli_error($conn);
    }
} else {
    echo "⚠️ Invalid request. Missing contract_number or transaction_date.";
}

// Close connection
mysqli_close($conn);
?>
