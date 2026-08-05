<?php
ob_start(); 
session_start();

ini_set('display_errors', 0); 
error_reporting(E_ALL);

include '../../config/config.php'; 

if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

$branchId = $_GET['branch_id'] ?? '';
$contracts = [];

try {
    if (!empty($branchId) && isset($conn) && $conn) {
        
        $sql = "SELECT DISTINCT contract_number 
                FROM create_contract 
                WHERE branch_id = ? 
                AND (request_status = 'Approved' OR request_status = 'Ready') 
                AND (
                    -- Check if date is truly NULL
                    end_date IS NULL 
                    -- Check if the value is '0' using a numeric cast (safer for Strict Mode)
                    OR CAST(end_date AS CHAR) = '0000-00-00 00:00:00'
                    OR CAST(end_date AS CHAR) = '0000-00-00'
                    -- The main logic: Only compare months if end_date is a valid date
                    OR (
                        end_date > '1970-01-01 00:00:00' 
                        AND DATE_FORMAT(contract_end, '%Y-%m') != DATE_FORMAT(end_date, '%Y-%m')
                    )
                )
                ORDER BY contract_number ASC";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $branchId);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $contracts[] = $row;
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception(mysqli_error($conn));
        }
    }
    echo json_encode($contracts);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
exit();