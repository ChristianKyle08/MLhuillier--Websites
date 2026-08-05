<?php
include '../../config/config.php';

header('Content-Type: application/json');

// Ensure strict HO validation parameter access rule controls here
// if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'HO') {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized entry context.']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contract_number']) && isset($_POST['target_date'])) {
    
    $contract_number = $_POST['contract_number'];
    $target_date = $_POST['target_date'];
    
    // Status to update to
    $newStatus = 'PBB'; // Matches your logic mapping targeting 'Paid by Branch'
    
    // Query evaluates criteria balancing either baseline transaction dates or dynamic updates safely
    $updateQuery = "UPDATE transactional 
                    SET status = ? 
                    WHERE contract_number = ? 
                    AND (transaction_date = ? OR new_due_date = ?)";
                    
    if ($stmt = mysqli_prepare($conn, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, "ssss", $newStatus, $contract_number, $target_date, $target_date);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No matching active transaction records found.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failure.']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failure.']);
    }
    
    mysqli_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request framework setup context parameters.']);
}
?>