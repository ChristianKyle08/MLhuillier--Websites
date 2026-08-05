<?php
include('../../config/config.php');

$branchId = (int)($_POST['branch_id'] ?? 0);
$effectivity = $_POST['effectivity_date'] ?? '';
$expiry = $_POST['expiry_date'] ?? '';

$query = "SELECT DATE_FORMAT(contract_start, '%M %d, %Y') AS start_date,
                 DATE_FORMAT(contract_end, '%M %d, %Y') AS end_date
          FROM create_contract 
          WHERE branch_id = $branchId 
          AND (
              ('$effectivity' BETWEEN contract_start AND contract_end)
              OR
              ('$expiry' BETWEEN contract_start AND contract_end)
              OR
              (contract_start BETWEEN '$effectivity' AND '$expiry')
              OR
              (contract_end BETWEEN '$effectivity' AND '$expiry')
          )
          AND request_status != 'Terminated' LIMIT 1";

$result = $conn->query($query);

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'overlap' => true,
        'period' => $row['start_date'] . ' to ' . $row['end_date']
    ]);
} else {
    echo json_encode(['overlap' => false]);
}
