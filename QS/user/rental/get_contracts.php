<?php
ob_start(); 
session_start();
include '../../config/config.php';

header('Content-Type: application/json');

// Prevent any PHP warnings/notices from leaking into the JSON
error_reporting(0);
ini_set('display_errors', 0);

$response = ['contracts' => []];

if (isset($_SESSION['user_name']) && isset($_GET['branch']) && $_GET['branch'] !== '') {
    $branch = mysqli_real_escape_string($conn, $_GET['branch']);

    // Removed the "AND c.request_status = 'Approved'" filter 
    // Added c.request_status to the SELECT statement
    $query = "
        SELECT DISTINCT t.contract_number, c.request_status
        FROM transactional t
        INNER JOIN create_contract c 
            ON t.contract_number = c.contract_number
        WHERE t.branch = '$branch'
          AND t.contract_number IS NOT NULL
        ORDER BY t.contract_number ASC
    ";

    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $response['contracts'][] = [
                'contract_number' => $row['contract_number'],
                'request_status' => $row['request_status'] // Pass status to JS
            ];
        }
    }
}

// Clear any accidental output (like spaces or warnings) before sending JSON
ob_end_clean(); 
echo json_encode($response);
exit;