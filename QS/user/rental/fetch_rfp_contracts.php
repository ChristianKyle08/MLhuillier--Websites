<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

$branch_id = $_GET['branch_id'] ?? null;

if ($branch_id) {
    // Only return contracts not fully covered
    $sql = "
        SELECT DISTINCT contract_number 
        FROM create_contract 
        WHERE branch_id = ? 
        AND (request_status = 'Ready' OR request_status = 'Approved')
        AND NOT EXISTS (
            SELECT 1 
            FROM create_contract AS c 
            WHERE c.contract_number = create_contract.contract_number 
            AND YEAR(c.contract_end) = YEAR(c.end_date) 
            AND MONTH(c.contract_end) = MONTH(c.end_date)
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $contracts = [];
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }

    echo json_encode($contracts);
}

$conn->close();
?>
