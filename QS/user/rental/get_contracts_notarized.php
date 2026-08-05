<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
}

if (isset($_GET['branch'])) {
    $branch = mysqli_real_escape_string($conn, $_GET['branch']);

    $query = "
    SELECT contract_number, notarized
    FROM create_contract
    WHERE branch = '$branch'
";

$result = mysqli_query($conn, $query);

$contracts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $contracts[] = [
        'contract_number' => $row['contract_number'],
        'notarized' => $row['notarized']
    ];
}

echo json_encode(['contracts' => $contracts]);

}
?>
