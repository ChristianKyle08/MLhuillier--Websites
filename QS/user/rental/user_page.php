<?php
ob_start();
session_start();

if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
    header('Location: login_form.php');
    exit();
}

include '../../config/config.php';

$user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
$userRole = $_SESSION['user_role'] ?? '';

// --- PAGINATION SETUP ---
$limit = 50; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
// Get user info
$userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
$userResult = mysqli_query($conn, $userQuery);
$userRegion = $userArea = $userMainZone = '';
if ($userResult && mysqli_num_rows($userResult) > 0) {
  $userRow = mysqli_fetch_assoc($userResult);
  $userRole = $userRow['roles'];
  $userRegion = $userRow['region'];
  $userArea = $userRow['area'];
  $userMainZone = $userRow['mainzone'];
}
mysqli_free_result($userResult); // Clear memory

// Initialize counters
$createdCount = 0;
$withoutRFPCount = 0;
$returnedCount = 0;
$forReviewRMCount = 0;
$rfpPaymentSolution = 0;
$rfpReturnedCount = 0;
$rfpPDC = 0;
$approvedCount = 0;
$pendingTermanitionCount = 0;
$terminatedCount = 0;
$requestChangeCOLCount = 0;

$changeDueDateCount = 0;
$changeMobileCount = 0;
$cancelPaymentCount = 0;
$changeLessorCount = 0;

// Base queries
$returned_countQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE request_status = 'Created' AND status = 'Active' AND status != 'Terminated' AND (reviewer_note IS NOT NULL OR audit_note IS NOT NULL) AND (rfp_status IS NULL OR rfp_status = '') AND contract_number = 'VOID'";
$returnedRFP_countQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Created' AND status = 'Active' AND status != 'Terminated' AND (reviewer_note IS NOT NULL OR audit_note IS NOT NULL) AND contract_number = 'VOID'";
$created_countQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE request_status = 'Created' AND status = 'Active' AND status != 'Terminated' AND (reviewer_note IS NULL OR reviewer_note = '') AND (audit_note IS NULL OR audit_note = '') AND (rfp_status IS NULL OR rfp_status = '') AND contract_number != 'VOID'";
$withoutRFP_countQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE rfp_status = 'Reviewed' AND status = 'Active' AND status != 'Terminated' AND (request_status = 'Ready' OR request_status = 'Approved') AND NOT (YEAR(contract_end) = YEAR(end_date) AND MONTH(contract_end) = MONTH(end_date) AND request_status = 'Approved')";
$forReviewRM_countQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE (rfp_status IS NULL OR rfp_status = '') AND request_status = 'Prepared' AND status = 'Active' AND status != 'Terminated'";
$rfpPaymentSolutionQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status IN ('Created', 'Prepared', 'Reviewed', 'Checked', 'Approved') AND status = 'Active' AND mode_of_payment = 'PAYMENT SOLUTION' AND status != 'Terminated' AND NOT (YEAR(contract_end) = YEAR(end_date) AND MONTH(contract_end) = MONTH(end_date) AND request_status = 'Approved')";
$rfpPDCQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status IN ('Created', 'Prepared', 'Reviewed', 'Checked', 'Approved') AND status = 'Active' AND mode_of_payment = 'PDC' AND status != 'Terminated'";
$approved_countQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM create_contract WHERE rfp_status = 'Reviewed' AND status = 'Active' AND status != 'Terminated'";
$pendingTermanitionCountQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM transactional WHERE status IN ('Termination Requested', 'Termination Reviewed', 'Termination Checked')";
$terminatedCountQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM transactional WHERE status = 'Terminated'";
$requestChangeDueDateQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM transactional WHERE dueDate_request_type = 'change_due_date' AND (dueDate_request_status IS NOT NULL AND dueDate_request_status != 'Approved')";
$requestChangeMobileQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM transactional WHERE mobile_request_type = 'change_mobile' AND (mobile_request_status IS NOT NULL AND mobile_request_status != 'Approved')";
$requestChangeCancelQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM transactional WHERE cancel_request_type = 'cancel_payment' AND (cancel_request_status IS NOT NULL AND cancel_request_status != 'Approved')";
$requestChangeLessorQuery = "SELECT COUNT(DISTINCT branch_id) AS total FROM transactional WHERE lessor_request_type = 'change_lessor_name' AND (lessor_request_status IS NOT NULL AND lessor_request_status != 'Approved')";

// Apply filters (logic remains identical)
$queries = [&$returned_countQuery, &$returnedRFP_countQuery, &$created_countQuery, &$withoutRFP_countQuery, &$forReviewRM_countQuery, &$rfpPaymentSolutionQuery, &$rfpPDCQuery, &$approved_countQuery, &$pendingTermanitionCountQuery, &$terminatedCountQuery, &$requestChangeDueDateQuery, &$requestChangeMobileQuery, &$requestChangeCancelQuery, &$requestChangeLessorQuery];
foreach ($queries as &$q) {
    if ($userRole === 'Am-Creator') $q .= " AND mainzone = '$userMainZone' AND region = '$userRegion' AND area = '$userArea'";
    elseif ($userRole === 'Rm-Reviewer') $q .= " AND region = '$userRegion'";
    elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) $q .= " AND mainzone = '$userMainZone'";
}

// Helper function to fetch and free
function getCountAndFree($conn, $query) {
    $res = mysqli_query($conn, $query);
    $val = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;
    if($res) mysqli_free_result($res);
    return $val;
}

$createdCount = getCountAndFree($conn, $created_countQuery);
$withoutRFPCount = getCountAndFree($conn, $withoutRFP_countQuery);
$returnedCount = getCountAndFree($conn, $returned_countQuery);
$forReviewRMCount = getCountAndFree($conn, $forReviewRM_countQuery);
$rfpPaymentSolution = getCountAndFree($conn, $rfpPaymentSolutionQuery);
$returnedRFPCount = getCountAndFree($conn, $returnedRFP_countQuery);
$rfpPDC = getCountAndFree($conn, $rfpPDCQuery);
$approvedCount = getCountAndFree($conn, $approved_countQuery);
$pendingTermanitionCount = getCountAndFree($conn, $pendingTermanitionCountQuery);
$terminatedCount = getCountAndFree($conn, $terminatedCountQuery);
$changeDueDateCount = getCountAndFree($conn, $requestChangeDueDateQuery);
$changeMobileCount = getCountAndFree($conn, $requestChangeMobileQuery);
$cancelPaymentCount = getCountAndFree($conn, $requestChangeCancelQuery);
$changeLessorCount = getCountAndFree($conn, $requestChangeLessorQuery);
$requestChangeCOLCount = $changeDueDateCount + $changeMobileCount + $cancelPaymentCount + $changeLessorCount;

// Fetch Created Contracts
$createdContracts = [];
$baseContractFilter = " WHERE status = 'Active'";

switch ($userRole) {
  case 'Am-Creator':
    // CHANGED HERE: Added AND NOT (rfp_status = 'Reviewed' AND request_status = 'Created') to the subquery
    $baseContractFilter .= " AND (((rfp_status IS NULL OR rfp_status = '') AND request_status = 'Created') OR ((rfp_status = 'Reviewed') AND request_status = 'Created') OR (rfp_status = 'Reviewed' AND request_status = 'Ready') OR (rfp_status = 'Reviewed' AND request_status = 'Approved')) AND mainzone = '$userMainZone' AND region = '$userRegion' AND area = '$userArea' AND contract_number NOT IN (SELECT contract_number FROM create_contract WHERE DATE_FORMAT(contract_end, '%Y-%m') = DATE_FORMAT(end_date, '%Y-%m') AND NOT (rfp_status = 'Reviewed' AND request_status = 'Created')) AND contract_number != 'VOID'";
    break;
  case 'Rm-Reviewer':
    $baseContractFilter .= " AND (((rfp_status IS NULL OR rfp_status = '') AND request_status = 'Prepared') OR (rfp_status = 'Reviewed' AND request_status = 'Prepared')) AND region = '$userRegion'";
    break;
  case 'Vpo-Checker':
  case 'Vpo-Reviewer':
    $baseContractFilter .= " AND rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND mainzone = '$userMainZone'";
    break;
  case 'Vpo-Approver':
    $baseContractFilter .= " AND rfp_status = 'Reviewed' AND request_status = 'Checked' AND mainzone = '$userMainZone'";
    break;
}

// 1. Get total count for pagination links
$countSql = "SELECT COUNT(*) as total FROM create_contract" . $baseContractFilter;
$countRes = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countRes)['total'];
$totalPages = ceil($totalRecords / $limit);

// 2. Fetch limited data (Only 50 rows)
$queryContracts = "SELECT * FROM create_contract " . $baseContractFilter . " ORDER BY created_date DESC LIMIT $offset, $limit";
$resultContracts = mysqli_query($conn, $queryContracts);

$hasRemarks = false;

$remarkRoles = ['Rm-Reviewer', 'Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'];
while ($row = mysqli_fetch_assoc($resultContracts)) {
  if (!$hasRemarks && !empty($row['reviewer_note'])) $hasRemarks = true;
  $createdContracts[] = $row;
}
mysqli_free_result($resultContracts);

$filteredContracts = [];
$hasExpiring = false;

if ($userRole === 'Am-Creator') {
  $today = date('Y-m-d');
  $threeMonthsLater = date('Y-m-d', strtotime('+3 months'));
  
  // Applying LIMIT here as well to prevent the crash in the PDC loop
  $sql = "SELECT c.* FROM create_contract c 
          INNER JOIN (SELECT branch_id, MAX(series) AS latest_series FROM create_contract GROUP BY branch_id) latest 
          ON c.branch_id = latest.branch_id AND c.series = latest.latest_series 
          WHERE c.contract_end BETWEEN ? AND ? AND c.contract_end <> ? 
          AND c.status = 'Active' AND c.status != 'Terminated'
          LIMIT $offset, $limit";

  if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("sss", $today, $threeMonthsLater, $today);
      $stmt->execute();
      $result = $stmt->get_result(); // This $result is used in your HTML table loop
      $creatorRegion = $_SESSION['region'] ?? '';
      $creatorArea   = $_SESSION['area'] ?? '';
      
        while ($row = $result->fetch_assoc()) {
            if ($row['region'] === $creatorRegion && $row['area'] === $creatorArea) {
                
                // CHANGED HERE: PHP check for equality of Month/Year + Statuses
                $endDate_MY = isset($row['end_date']) ? date('Y-m', strtotime($row['end_date'])) : '';
                $contractEnd_MY = isset($row['contract_end']) ? date('Y-m', strtotime($row['contract_end'])) : '';
                
                if ($endDate_MY === $contractEnd_MY && $endDate_MY !== '1970-01' && $endDate_MY !== '') {
                    if (($row['rfp_status'] ?? '') === 'Reviewed' && ($row['request_status'] ?? '') === 'Created') {
                        $filteredContracts[] = $row;
                    }
                } else {
                    $filteredContracts[] = $row;
                }
                
            }
        }
        $stmt->close();
    }
    $hasExpiring = !empty($filteredContracts);
}
// 1. Get user info (Make sure to escape $user_email to prevent SQL injection)
$safe_user_email = mysqli_real_escape_string($conn, $user_email);
$userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE username = '$safe_user_email' OR email = '$safe_user_email'";
$userResult = mysqli_query($conn, $userQuery);

$userRole = $userRegion = $userArea = $userMainZone = '';
if ($userResult && mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);
    $userRole = $userRow['roles'];
    $userRegion = mysqli_real_escape_string($conn, $userRow['region']);
    $userArea = mysqli_real_escape_string($conn, $userRow['area']);
    $userMainZone = mysqli_real_escape_string($conn, $userRow['mainzone']);
}

// 2. Build the dynamic WHERE clause based on the user's role
$whereClause = "1=1"; // Default fallback (e.g., for SuperAdmins). Change to "1=0" if unmatched roles should see nothing.

if ($userRole === 'Am-Creator') {
    $whereClause = "mainzone = '$userMainZone' AND region = '$userRegion' AND area = '$userArea'";
} elseif ($userRole === 'Rm-Reviewer') {
    $whereClause = "region = '$userRegion'";
} elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
    $whereClause = "mainzone = '$userMainZone'";
}

// 3. Fetching contracts with Role-Based Access Control
$query = "SELECT contract_number, branch, rfp_status, request_status, end_date, contract_end, created_by, created_date, prepared_by, rfp_date
          FROM create_contract 
          WHERE $whereClause";
$result = mysqli_query($conn, $query);

// Explicitly segregated tracking arrays
$data_archiving = [];
$ready_for_rfp  = [];
$renewed_rfp    = [];
$rfp_pending    = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rfp_stat = $row['rfp_status'];
        $req_stat = $row['request_status'];
        $contract = $row['contract_number'] ?: 'Draft/No Number';
        $branch = $row['branch'] ?: 'Draft/No Branch';
        
        $end_date_my = !empty($row['end_date']) ? date('Y-m', strtotime($row['end_date'])) : null;
        $contract_end_my = !empty($row['contract_end']) ? date('Y-m', strtotime($row['contract_end'])) : null;

        // Fetching the newly added fields
        $created_by = !empty($row['created_by']) ? $row['created_by'] : 'N/A';
        $prepared_by = !empty($row['prepared_by']) ? $row['prepared_by'] : 'N/A';
        $created_date = !empty($row['created_date']) ? date('Y-m-d', strtotime($row['created_date'])) : 'N/A';
        $rfp_date = !empty($row['rfp_date']) ? date('Y-m-d', strtotime($row['rfp_date'])) : 'N/A';

        // 1. DATA ARCHIVING
        if (empty($rfp_stat) && $req_stat === 'Prepared') {
          $data_archiving[] = [
              'contract' => $contract, 
              'branch' => $branch, 
              'desc' => 'Awaiting Regional Manager approval',
              'created_by' => $created_by,
              'created_date' => $created_date,
              'prepared_by' => $prepared_by,
              'rfp_date' => $rfp_date
          ];
        } 
        // 2. READY FOR RFP
        elseif ($rfp_stat === 'Reviewed' && $req_stat === 'Ready') {
            $ready_for_rfp[] = [
                'contract' => $contract,
                'branch' => $branch, 
                'desc' => 'Ready for Request for Payment (RFP) generation',
                'created_by' => $created_by,
                'created_date' => $created_date,
                'prepared_by' => $prepared_by,
                'rfp_date' => $rfp_date
            ];
        } 
        // 3. RENEWED RFP
        elseif ($rfp_stat === 'Reviewed' && $req_stat === 'Approved' && $end_date_my !== $contract_end_my) {
            $renewed_rfp[] = [
                'contract' => $contract, 
                'branch' => $branch, 
                'desc' => 'Ready for RFP renewal processing',
                'created_by' => $created_by,
                'created_date' => $created_date,
                'prepared_by' => $prepared_by,
                'rfp_date' => $rfp_date
            ];
        } 
        // 4. RFP (Pending Approvals)
        elseif ($rfp_stat === 'Reviewed' && $req_stat === 'Prepared') {
            $rfp_pending[] = [
                'contract' => $contract, 
                'branch' => $branch, 
                'desc' => 'Awaiting Regional Manager approval',
                'created_by' => $created_by,
                'created_date' => $created_date,
                'prepared_by' => $prepared_by,
                'rfp_date' => $rfp_date
            ];
        } elseif ($rfp_stat === 'Reviewed' && $req_stat === 'Reviewed') {
            $rfp_pending[] = [
                'contract' => $contract, 
                'branch' => $branch, 
                'desc' => 'Awaiting VPO approval',
                'created_by' => $created_by,
                'created_date' => $created_date,
                'prepared_by' => $prepared_by,
                'rfp_date' => $rfp_date
            ];
        } elseif ($rfp_stat === 'Reviewed' && $req_stat === 'Checked') {
            $rfp_pending[] = [
                'contract' => $contract, 
                'branch' => $branch, 
                'desc' => 'Awaiting Vice President approval',
                'created_by' => $created_by,
                'created_date' => $created_date,
                'prepared_by' => $prepared_by,
                'rfp_date' => $rfp_date
            ];
        }
      }
  }
  
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Site made with Mobirise Website Builder v5.9.13, https://mobirise.com -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
  <meta name="description" content="">

  <title>HOME PAGE</title>
  <!-- ✅ Local Google Font -->
  <link href="../../assets/css/poppins.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap CSS -->
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap Icons -->
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- ✅ Your custom CSS should come AFTER font import -->
   <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/scrollbar.css">
<style>
/* ========== Card Styles ========== */
.card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 1rem;
  transition: transform 0.3s ease-in-out;
}

.card:hover {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

/* ========== Table Styles ========== */

.table thead {
  background-color: #d70c0c;
  color: #fff;
}
thead th {
  padding: 0.75rem 0.75rem !important;
}

.table-hover tbody tr:hover {
  background-color: #f9f9f9;
}

.badge {
  font-size: 0.75rem;
}

.card-body h5 {
  font-family: 'Poppins', sans-serif;
}
/* ========== Badges ========== */
.badge.bg-warning {
  background-color: #ffc107 !important;
  color: #333;
}

.badge.bg-success {
  background-color: #28a745 !important;
}

.badge.bg-danger {
  background-color: #d70c0c !important;
}

/* ========== Buttons ========== */
.btn-outline-primary,
.btn-outline-success {
  border-color: #d70c0c;
  color: #d70c0c;
}

.btn-outline-primary:hover,
.btn-outline-success:hover {
  background-color: #d70c0c;
  color: #fff;
}

/* ========== Modal ========== */
.modal-content {
  background: #fff;
  color: #333;
}
.btn-outline-view {
    border: 1px solid #0d6efd; /* Bootstrap Primary Blue */
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
    transition: all 0.3s;
}

.btn-outline-view:hover {
    background-color: rgba(13, 110, 253, 0.1);
}

.btn-outline-edit {
    border: 1px solid #fd7e14; /* Orange */
    color: #fd7e14;
    background-color: rgba(253, 126, 20, 0.1);
    transition: all 0.3s;
}

.btn-outline-edit:hover {
    background-color: rgba(253, 126, 20, 0.1);
}


.btn-outline-submit {
    border: 1px solid #198754; /* Bootstrap Success Green */
    color: #198754;
    background-color: rgba(25, 135, 84, 0.1);
    transition: all 0.3s;
}

.btn-outline-submit:hover {
    background-color: rgba(25, 135, 84, 0.1);
}
.contract-file-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    color: #6c757d;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
}

.contract-file-link:hover {
    color: #212529;
    text-decoration: underline;
}

.contract-file-link i {
    color: #dc3545;
    font-size: 1.2rem;
}
  /* Custom 5-column layout */
  @media (min-width: 768px) {
    .col-md-5th {
      flex: 0 0 20%;
      max-width: 20%;
    }
  }
 /* 🔴 Highlight empty required fields */
 .is-invalid {
    border: 2px solid #dc3545 !important; /* Red border */
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }

   /* Fix table header when scrolling */
   .table-responsive thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #ffcccc; /* same as header color */
  }

  /* Optional: smooth scrolling and nice look */
  .table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #dc3545 #f8f9fa;
  }

  .table-responsive::-webkit-scrollbar {
    width: 8px;
  }
  .table-responsive::-webkit-scrollbar-thumb {
    background-color: #dc3545;
    border-radius: 4px;
  }
    /* Reusable class for disabled buttons */
    .disabled-btn {
    border: 1px solid lightgray !important;
    background-color: transparent !important;
    color: gray !important;
    cursor: not-allowed !important;
    opacity: 0.6 !important;
  }
  .status-badge {
    border: 1px solid;
    background-color: transparent !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
/* Premium UI Enhancements */
.dashboard-modal-content {
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15) !important;
    }
    .custom-modal-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .custom-modal-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 8px;
    }
    .custom-modal-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }
    .modern-search-group {
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .modern-search-group:focus-within {
        border-color: #94a3b8;
        box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1);
    }
    .pipeline-header {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: #64748b;
    }
    .pipeline-count-pill {
        background: #e2e8f0;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 12px;
    }
    .pipeline-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        transition: all 0.2s ease;
        margin-bottom: 12px;
    }
    .pipeline-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        transform: translateY(-1px);
    }
    .modern-tag {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f1f5f9;
        color: #475569;
    }
    .tag-success { background: #ecfdf5; color: #059669; }
    .tag-indigo { background: #eef2ff; color: #4f46e5; }
    .tag-warning { background: #fff7ed; color: #c2410c; }
    
    .meta-info-block {
        border-left: 2px solid #f1f5f9;
        padding-left: 12px;
    }
    @media (max-width: 767.98px) {
        .meta-info-block {
            border-left: none;
            padding-left: 0;
            border-top: 1px dashed #e2e8f0;
            padding-top: 12px;
            margin-top: 12px;
        }
    }
    .meta-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        font-weight: 600;
        margin-bottom: 2px;
        display: block;
    }
    .meta-value {
        font-size: 0.8rem;
        color: #334155;
    }
    .status-badge-soft {
        font-size: 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 20px;
        color: #475569;
        font-weight: 500;
    }
    
</style>
</head>
<body>
<?php include ('navbar.php'); ?>
<div id="mainContent">
  <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
    <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
      <span class="fw-normal">Menu</span>
  </button>
  <div class="container py-1">
    
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 gap-3" style="border-bottom: 1px solid #f1f5f9;">
    <h3 class="dashboard-title m-0 fw-bold tracking-tight" style="color: #0f172a; font-size: 1.4rem; letter-spacing: -0.025em;">
        Rental Management System Dashboard
    </h3>
    
    <div class="d-flex flex-column align-items-end position-relative group-hover-badge">
        <div class="d-inline-flex align-items-center px-3 py-1.5 rounded-pill status-badge-btn shadow-sm" 
            data-bs-toggle="modal" data-bs-target="#premiumReviewModal"
            style="background: rgba(217, 119, 6, 0.06); border: 1px solid rgba(217, 119, 6, 0.2); cursor: pointer; transition: all 0.2s ease;">
            <span class="status-dot-wrapper me-2">
                <span class="status-dot-pulse"></span>
                <span class="status-dot-core"></span>
            </span>
            <span class="fw-bold text-uppercase tracking-wider text-nowrap py-2" style="color: #b45309; font-size: 10px;">
                Inquire branch status
            </span>
            <i class="bi bi-cursor-fill ms-2 text-warning dashboard-click-icon" style="font-size: 10px;"></i>
        </div>
        <small class="text-muted mt-1 px-1 d-none d-sm-block text-uppercase fw-semibold tracking-wide text-interactive-hint" style="font-size: 9px; letter-spacing: 0.03em;">
            <i class="bi bi-info-circle me-1"></i>Click to search branch transactions
        </small>
    </div>
</div>

<div class="modal fade shadow-sm" id="premiumReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" style="max-width: 900px;">
        <div class="modal-content dashboard-modal-content border-0 rounded-4" style="background: #ffffff;">
            
            <div class="modal-header border-bottom pb-3 pt-4 px-4 d-flex justify-content-between align-items-center" style="border-color: #f1f5f9 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 40px; height: 40px; background: #fffbeb; border: 1px solid #fef3c7;">
                        <i class="bi bi-shield-check text-warning" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark m-0" style="font-size: 1.15rem; letter-spacing: -0.01em;">Review Status Pipeline</h5>
                        <p class="text-muted m-0" style="font-size: 12px;">Categorized live items waiting workflow operational actions.</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none custom-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 custom-modal-scroll" style="max-height: 75vh; overflow-y: auto; background-color: #f8fafc;">
                
                <div class="mb-4">
                    <div class="input-group shadow-sm bg-white rounded-3 modern-search-group overflow-hidden">
                        <span class="input-group-text bg-transparent border-0 pe-1">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="branchSearchInput" class="form-control border-0 shadow-none py-2 px-2" placeholder="Enter Branch ID or Name to view transactions...">
                    </div>
                </div>

                <div id="searchPromptMessage" class="text-center py-5 shadow-sm" style="border: 1px dashed #cbd5e1; border-radius: 12px; background: #ffffff;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background: #f1f5f9;">
                        <i class="bi bi-search text-slate-400" style="font-size: 1.5rem; color: #94a3b8;"></i>
                    </div>
                    <h6 class="text-dark fw-bold mb-1">Search Required</h6>
                    <p class="text-muted mb-0" style="font-size: 13px;">Please search for a specific branch above to display its pending transactions.</p>
                </div>

                <div id="pipelineContainer" style="display: none;">
                    
                    <div class="mb-4 dashboard-pipeline-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pipeline-header text-uppercase cat-archiving">Data Archiving</span>
                                <span class="pipeline-count-pill"><?= count($data_archiving) ?></span>
                            </div>
                            <?php if(!empty($data_archiving)): ?><small class="text-muted font-monospace" style="font-size: 10px;"><i class="bi bi-arrows-expand me-1"></i>Scrollable Area</small><?php endif; ?>
                        </div>
                        <div class="card-container-group">
                            <div class="inner-scroll-viewport">
                                <?php if(empty($data_archiving)): ?>
                                    <div class="pipeline-card text-center text-muted py-4 shadow-sm" style="border-style: dashed;">No records are active inside Data Archiving pipeline.</div>
                                <?php else: ?>
                                    <?php foreach($data_archiving as $item): ?>
                                        <div class="pipeline-card d-flex flex-column flex-md-row align-items-md-center shadow-sm">
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 mb-md-0 me-md-3" style="min-width: 200px;">
                                                <span class="modern-tag"><i class="bi bi-file-earmark-text text-muted"></i> <?= htmlspecialchars($item['contract']) ?></span>
                                                <span class="modern-tag branch-name-target"><i class="bi bi-shop text-muted"></i> <?= htmlspecialchars($item['branch']) ?></span>
                                            </div>
                                            
                                            <div class="meta-info-block d-flex flex-row gap-4 flex-grow-1">
                                                <div>
                                                    <span class="meta-label">Created By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['created_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['created_date']) ?>)</span>
                                                </div>
                                                <div>
                                                    <span class="meta-label">RFP Req By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['prepared_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['rfp_date']) ?>)</span>
                                                </div>
                                            </div>

                                            <div class="mt-3 mt-md-0 ms-md-auto text-md-end">
                                                <span class="status-badge-soft d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-clock-history"></i> <?= htmlspecialchars($item['desc']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 dashboard-pipeline-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pipeline-header text-uppercase text-success cat-ready">New Contracts Ready For RFP</span>
                                <span class="pipeline-count-pill"><?= count($ready_for_rfp) ?></span>
                            </div>
                            <?php if(!empty($ready_for_rfp)): ?><small class="text-muted font-monospace" style="font-size: 10px;"><i class="bi bi-arrows-expand me-1"></i>Scrollable Area</small><?php endif; ?>
                        </div>
                        <div class="card-container-group">
                            <div class="inner-scroll-viewport">
                                <?php if(empty($ready_for_rfp)): ?>
                                    <div class="pipeline-card text-center text-muted py-4 shadow-sm" style="border-style: dashed;">No records are active inside Ready for RFP pipeline.</div>
                                <?php else: ?>
                                    <?php foreach($ready_for_rfp as $item): ?>
                                        <div class="pipeline-card d-flex flex-column flex-md-row align-items-md-center shadow-sm">
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 mb-md-0 me-md-3" style="min-width: 200px;">
                                                <span class="modern-tag tag-success"><i class="bi bi-file-earmark-check"></i> <?= htmlspecialchars($item['contract']) ?></span>
                                                <span class="modern-tag branch-name-target"><i class="bi bi-shop text-muted"></i> <?= htmlspecialchars($item['branch']) ?></span>
                                            </div>
                                            
                                            <div class="meta-info-block d-flex flex-row gap-4 flex-grow-1">
                                                <div>
                                                    <span class="meta-label">Created By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['created_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['created_date']) ?>)</span>
                                                </div>
                                                <div>
                                                    <span class="meta-label">RFP Req By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['prepared_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['rfp_date']) ?>)</span>
                                                </div>
                                            </div>

                                            <div class="mt-3 mt-md-0 ms-md-auto text-md-end">
                                                <span class="status-badge-soft d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-check-circle text-success"></i> <?= htmlspecialchars($item['desc']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 dashboard-pipeline-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pipeline-header text-uppercase" style="color: #4f46e5;">Renew RFP</span>
                                <span class="pipeline-count-pill"><?= count($renewed_rfp) ?></span>
                            </div>
                            <?php if(!empty($renewed_rfp)): ?><small class="text-muted font-monospace" style="font-size: 10px;"><i class="bi bi-arrows-expand me-1"></i>Scrollable Area</small><?php endif; ?>
                        </div>
                        <div class="card-container-group">
                            <div class="inner-scroll-viewport">
                                <?php if(empty($renewed_rfp)): ?>
                                    <div class="pipeline-card text-center text-muted py-4 shadow-sm" style="border-style: dashed;">No records are active inside Renew RFP pipeline.</div>
                                <?php else: ?>
                                    <?php foreach($renewed_rfp as $item): ?>
                                        <div class="pipeline-card d-flex flex-column flex-md-row align-items-md-center shadow-sm">
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 mb-md-0 me-md-3" style="min-width: 200px;">
                                                <span class="modern-tag tag-indigo"><i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars($item['contract']) ?></span>
                                                <span class="modern-tag branch-name-target"><i class="bi bi-shop text-muted"></i> <?= htmlspecialchars($item['branch']) ?></span>
                                            </div>
                                            
                                            <div class="meta-info-block d-flex flex-row gap-4 flex-grow-1">
                                                <div>
                                                    <span class="meta-label">Created By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['created_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['created_date']) ?>)</span>
                                                </div>
                                                <div>
                                                    <span class="meta-label">RFP Req By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['prepared_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['rfp_date']) ?>)</span>
                                                </div>
                                            </div>

                                            <div class="mt-3 mt-md-0 ms-md-auto text-md-end">
                                                <span class="status-badge-soft d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-arrow-clockwise" style="color: #4f46e5;"></i> <?= htmlspecialchars($item['desc']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2 dashboard-pipeline-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pipeline-header text-uppercase text-warning cat-rfp" style="color: #c2410c !important;">RFP Approval Queue</span>
                                <span class="pipeline-count-pill"><?= count($rfp_pending) ?></span>
                            </div>
                            <?php if(!empty($rfp_pending)): ?><small class="text-muted font-monospace" style="font-size: 10px;"><i class="bi bi-arrows-expand me-1"></i>Scrollable Area</small><?php endif; ?>
                        </div>
                        <div class="card-container-group">
                            <div class="inner-scroll-viewport">
                                <?php if(empty($rfp_pending)): ?>
                                    <div class="pipeline-card text-center text-muted py-4 shadow-sm" style="border-style: dashed;">No records are active inside RFP approval queue.</div>
                                <?php else: ?>
                                    <?php foreach($rfp_pending as $item): ?>
                                        <div class="pipeline-card d-flex flex-column flex-md-row align-items-md-center shadow-sm">
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 mb-md-0 me-md-3" style="min-width: 200px;">
                                                <span class="modern-tag tag-warning"><i class="bi bi-hourglass-split"></i> <?= htmlspecialchars($item['contract']) ?></span>
                                                <span class="modern-tag branch-name-target"><i class="bi bi-shop text-muted"></i> <?= htmlspecialchars($item['branch']) ?></span>
                                            </div>
                                            
                                            <div class="meta-info-block d-flex flex-row gap-4 flex-grow-1">
                                                <div>
                                                    <span class="meta-label">Created By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['created_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['created_date']) ?>)</span>
                                                </div>
                                                <div>
                                                    <span class="meta-label">RFP Req By</span>
                                                    <span class="meta-value fw-semibold"><?= htmlspecialchars($item['prepared_by']) ?></span> 
                                                    <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($item['rfp_date']) ?>)</span>
                                                </div>
                                            </div>

                                            <div class="mt-3 mt-md-0 ms-md-auto text-md-end">
                                                <span class="status-badge-soft d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-exclamation-circle text-warning"></i> <?= htmlspecialchars($item['desc']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
    <?php if ($userRole !== 'Finance'): ?>
    <!-- Data Archiving Section -->
    <h5 class="mt-2 mb-3 fw-bold">Rental Archiving</h5>
    <div class="row g-3">
    <?php
    $archive_data = [
      ['label' => 'Returned Contract', 'icon' => 'bi-arrow-counterclockwise', 'color' => 'text-primary', 'count' => $returnedCount, 'link' => 'returned_contract.php'],
      ['label' => 'Created Contract', 'icon' => 'bi-file-earmark-plus', 'color' => 'text-danger', 'count' => $createdCount, 'link' => 'created_contract.php'],
      ['label' => 'For Review By RM', 'icon' => 'bi-hourglass-split', 'color' => 'text-warning', 'count' => $forReviewRMCount, 'link' => 'for_review_col.php'],
      ['label' => 'Ready for RFP', 'icon' => 'bi-clock-history', 'color' => 'text-secondary', 'count' => $withoutRFPCount, 'link' => 'rfp_page_menu.php'],
      ['label' => 'Reviewed Contract By RM', 'icon' => 'bi-check2-circle', 'color' => 'text-success', 'count' => $approvedCount, 'link' => 'reviewed_col.php']
    ];

    foreach ($archive_data as $item): ?>
      <div class="col-6 col-md-5th">
        <a href="<?= $item['link']; ?>" class="card-link text-decoration-none text-dark">
          <div class="card p-3 h-100">
            <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="width:40px;height:40px;">
                <i class="bi <?= $item['icon']; ?> fs-5 <?= $item['color']; ?>"></i>
              </div>
              <div>
                <h6 class="mb-1"><?= $item['label']; ?></h6>
                <h5 class="mb-0"><?= $item['count']; ?></h5>
              </div>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<!-- RFP Section -->
<h5 class="mt-3 mb-3 fw-bold">Rental RFP</h5>
<div class="row g-3">
  <?php
  $rfp_data = [
    ['label' => 'Returned Contract', 'icon' => 'bi-arrow-counterclockwise', 'color' => 'text-primary', 'count' => $returnedRFPCount, 'link' => 'rfp_returned_contracts.php'],
    ['label' => 'Payment Solution',  'icon' => 'bi-credit-card-2-back', 'color' => 'text-danger',  'count' => $rfpPaymentSolution, 'link' => 'payment_solution.php'],
    ['label' => 'PDC',               'icon' => 'bi-receipt',           'color' => 'text-warning', 'count' => $rfpPDC, 'link' => 'pdc.php'],
    ['label' => 'RTA',               'icon' => 'bi-tools',             'color' => 'text-warning', 'count' => 0, 'link' => '#'],
    ['label' => 'MCash',             'icon' => 'bi-wallet2',           'color' => 'text-danger',  'count' => 0, 'link' => 'mcash_wallet.php']
  ];

  // ✅ If Finance, only show PDC and RTA
  if ($userRole === 'Finance') {
      $rfp_data = array_filter($rfp_data, function($item) {
          return in_array($item['label'], ['PDC', 'RTA']);
      });
  }

  foreach ($rfp_data as $item): ?>
    <div class="col-6 col-md-5th">
      <?php if ($item['label'] === 'RTA' || $item['label'] === 'MCash'): ?>
        <!-- 🔹 RTA Card - Coming Soon (not clickable) -->
        <div class="card p-3 h-100 border-0 rounded-4">
          <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                 style="width:40px;height:40px;">
              <i class="bi bi-tools fs-5 text-warning"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold text-warning">
                <?= $item['label']; ?> 
                <span class="badge bg-warning-subtle text-dark ms-1">Coming Soon</span>
              </h6>
              <h5 class="mb-0 text-muted"><?= $item['count']; ?></h5>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- 🔹 Other Cards (clickable) -->
        <a href="<?= $item['link']; ?>" class="card-link text-decoration-none text-dark">
          <div class="card p-3 h-100 border-1 rounded-4">
            <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="width:40px;height:40px;">
                <i class="bi <?= $item['icon']; ?> fs-5 <?= $item['color']; ?>"></i>
              </div>
              <div>
                <h6 class="mb-1"><?= $item['label']; ?></h6>
                <h5 class="mb-0"><?= $item['count']; ?></h5>
              </div>
            </div>
          </div>
        </a>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($userRole === 'Finance' || $userRole === 'Auditor' || $userRole === 'HO' ) { ?>
  <!-- Finance Section -->
  <div class="card border-0 rounded-4 mt-4 shadow-sm">
    <div class="card-header bg-danger text-white d-flex align-items-center">
      <i class="bi bi-cash-stack fs-4 me-3"></i>
      <h5 class="mb-0 fw-semibold text-white">PDC Request Overview</h5>
    </div>

    <div class="card-body p-2">
    <?php
    // 🔹 Fetch distinct filter values (only where mode_of_payment = 'PDC')
    $mainzoneQuery = $conn->query("
      SELECT DISTINCT mainzone 
      FROM transactional 
      WHERE mode_of_payment = 'PDC' 
        AND mainzone IS NOT NULL 
        AND mainzone != '' 
        AND pdc_status != 'Picked-up'
      ORDER BY mainzone ASC
    ");

    $regionQuery = $conn->query("
      SELECT DISTINCT region 
      FROM transactional 
      WHERE mode_of_payment = 'PDC' 
        AND region IS NOT NULL 
        AND region != '' 
        AND pdc_status != 'Picked-up'
      ORDER BY region ASC
    ");

    $areaQuery = $conn->query("
      SELECT DISTINCT area 
      FROM transactional 
      WHERE mode_of_payment = 'PDC' 
        AND area IS NOT NULL 
        AND area != '' 
        AND pdc_status != 'Picked-up'
      ORDER BY area ASC
    ");

    $branchQuery = $conn->query("
      SELECT DISTINCT branch 
      FROM transactional 
      WHERE mode_of_payment = 'PDC' 
        AND branch IS NOT NULL 
        AND branch != '' 
        AND pdc_status != 'Picked-up'
      ORDER BY branch ASC
    ");

    // 🔸 Build dynamic filters
    $filters = [];
    if (!empty($_GET['mainzone'])) {
      $filters[] = "mainzone = '" . $conn->real_escape_string($_GET['mainzone']) . "'";
    }
    if (!empty($_GET['region'])) {
      $filters[] = "region = '" . $conn->real_escape_string($_GET['region']) . "'";
    }
    if (!empty($_GET['area'])) {
      $filters[] = "area = '" . $conn->real_escape_string($_GET['area']) . "'";
    }
    if (!empty($_GET['branch'])) {
      $filters[] = "branch = '" . $conn->real_escape_string($_GET['branch']) . "'";
    }

    $whereClause = !empty($filters) ? " AND " . implode(" AND ", $filters) : "";

    // 🔹 Fetch filtered PDC transactions
    $sql = "
      SELECT contract_number,mainzone, region, area, branch, l1_firstname, l1_middlename, l1_lastname, 
            transaction_date, FORMAT(amount_lessor, 2) AS amount_lessor, pdc_status, pdc_prepared_by, pdc_prepared_date
      FROM transactional
      WHERE mode_of_payment = 'PDC' $whereClause AND contract_number != 'VOID'
      ORDER BY contract_number ASC, transaction_date ASC
    ";
    $result = $conn->query($sql);
    $currentContract = null;
    ?>

      <!-- 🔍 Filter Section -->
      <form method="GET" class="row g-3 mb-3 align-items-end">
        <!-- Mainzone -->
        <div class="col-md-3">
          <label for="mainzone" class="form-label fw-semibold">
            <i class="bi bi-globe2 me-2 text-danger"></i>Mainzone
          </label>
          <select id="mainzone" name="mainzone" class="form-select">
            <option value="">All Mainzones</option>
            <?php while ($mz = $mainzoneQuery->fetch_assoc()) { ?>
              <option value="<?= htmlspecialchars($mz['mainzone']) ?>"
                <?= (isset($_GET['mainzone']) && $_GET['mainzone'] === $mz['mainzone']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($mz['mainzone']) ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Region -->
        <div class="col-md-3">
          <label for="region" class="form-label fw-semibold">
            <i class="bi bi-map me-2 text-danger"></i>Region
          </label>
          <select id="region" name="region" class="form-select">
            <option value="">All Regions</option>
            <?php while ($reg = $regionQuery->fetch_assoc()) { ?>
              <option value="<?= htmlspecialchars($reg['region']) ?>"
                <?= (isset($_GET['region']) && $_GET['region'] === $reg['region']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($reg['region']) ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Area -->
        <div class="col-md-3">
          <label for="area" class="form-label fw-semibold">
            <i class="bi bi-geo-alt me-2 text-danger"></i>Area
          </label>
          <select id="area" name="area" class="form-select">
            <option value="">All Areas</option>
            <?php while ($ar = $areaQuery->fetch_assoc()) { ?>
              <option value="<?= htmlspecialchars($ar['area']) ?>"
                <?= (isset($_GET['area']) && $_GET['area'] === $ar['area']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($ar['area']) ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Checkbox for Branch Filter -->
        <div class="col-md-3 d-flex align-items-center">
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="showBranch" name="showBranch"
              <?= isset($_GET['branch']) && $_GET['branch'] !== '' ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="showBranch">
              <i class="bi bi-shop me-2 text-danger"></i>Filter by Branch
            </label>
          </div>
        </div>

        <!-- Branch Filter (Hidden by default) -->
        <div class="col-md-4" id="branchFilter" style="display: <?= isset($_GET['branch']) && $_GET['branch'] !== '' ? 'block' : 'none' ?>;">
          <label for="branch" class="form-label fw-semibold">
            <i class="bi bi-building me-2 text-danger"></i>Branch
          </label>
          <select id="branch" name="branch" class="form-select">
            <option value="">All Branches</option>
            <?php while ($br = $branchQuery->fetch_assoc()) { ?>
              <option value="<?= htmlspecialchars($br['branch']) ?>"
                <?= (isset($_GET['branch']) && $_GET['branch'] === $br['branch']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($br['branch']) ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Apply / Reset Buttons -->
        <div class="col-12 text-end">
          <button type="submit" class="btn btn-danger rounded-pill px-4">
            <i class="bi bi-funnel-fill me-2"></i>Apply Filter
          </button>
          <a href="<?= basename($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary rounded-pill px-4">
            <i class="bi bi-arrow-clockwise me-2"></i>Reset
          </a>
        </div>
      </form>

      <!-- 📋 PDC Data Table -->
      <!-- 📋 Scrollable PDC Data Table -->
      <div class="table-responsive" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.75rem;">
      <table class="table table-hover mb-0 text-nowrap align-middle text-center" style="border-collapse: collapse;">
  <thead class="text-center align-middle" style="background-color: #ffcccc;">
    <tr>
      <th><i class="bi bi-gear me-2"></i>Action</th>
      <th><i class="bi bi-file-earmark-text me-2"></i>Contract Number</th>
      <th><i class="bi bi-calendar-event me-2"></i>Monthly Due Date</th>
      <th><i class="bi bi-geo-alt me-2"></i>Mainzone</th>
      <th><i class="bi bi-geo-alt me-2"></i>Region</th>
      <th><i class="bi bi-geo-alt me-2"></i>Area</th>
      <th><i class="bi bi-shop me-2"></i>Branch</th>
      <th><i class="bi bi-person-badge me-2"></i>Lessor Name</th>
      <th><i class="bi bi-cash-stack me-2"></i>Amount to Lessor (₱)</th>
      <th><i class="bi bi-clipboard-check me-2"></i>Status</th>
      <th><i class="bi bi-person me-2"></i>Prepared By</th>
      <th><i class="bi bi-calendar-event me-2"></i>Prepared Date</th>
    </tr>
  </thead>

  <tbody>

<?php
$currentRegion = '';

if ($result && $result->num_rows > 0) {

while ($row = $result->fetch_assoc()) {

    // ---------- Role-based filter ----------
    $statusRaw = trim($row['pdc_status'] ?? '');

    if ($userRole === 'Finance' && !in_array($statusRaw, ['Audited', 'Ready for pickup'])) {
        continue;
    } elseif ($userRole === 'Auditor' && !empty($statusRaw)) {
        continue;
    } elseif ($userRole === 'HO' && $statusRaw !== 'Ready for pickup') {
        continue;
    }

    // ---------- REGION HEADER SEPARATION ----------
    if ($currentRegion !== $row['region']) {
        $currentRegion = $row['region'];
?>
    <tr style="background-color:#f8d7da;">
        <td colspan="12" class="text-start fw-bold fs-5 py-2 text-danger">
            <i class="bi bi-geo-alt-fill me-2"></i>
            REGION: <?= htmlspecialchars(strtoupper($currentRegion)); ?>
        </td>
    </tr>
<?php
    }

    $formattedDate = date("F d, Y", strtotime($row['transaction_date']));
    $payeeName = trim($row['l1_firstname'] . ' ' . $row['l1_middlename'] . ' ' . $row['l1_lastname']);

    // ---------- Contract Display ----------
    if ($currentContract !== $row['contract_number']) {
        $currentContract = $row['contract_number'];
        $contractDisplay = "<strong class='text-danger'>
                                <i class='bi bi-file-earmark-text me-2 text-danger'></i>" .
                                htmlspecialchars($row['contract_number']) .
                           "</strong>";
    } else {
        $contractDisplay = "";
    }

    // ---------- Status Badge ----------
    if (!empty($row['pdc_status'])) {
        if ($row['pdc_status'] === 'Audited') {
            $pdcStatus = "
                <span class='px-3 py-2 rounded-pill fw-semibold status-badge border-success text-success'>
                    <i class='bi bi-patch-check'></i> Audited
                </span>";
        } elseif ($row['pdc_status'] === 'Ready for pickup') {
            $pdcStatus = "
                <span class='px-3 py-2 rounded-pill fw-semibold status-badge border-primary text-primary'>
                    <i class='bi bi-check2-circle'></i> Ready for Pickup
                </span>";
        } elseif (strtolower($row['pdc_status']) === 'on hold') {
            $pdcStatus = "
                <span class='px-3 py-2 rounded-pill fw-semibold status-badge border-warning text-warning'>
                    <i class='bi bi-pause-circle'></i> On Hold
                </span>";
        } else {
            $pdcStatus = "
                <span class='px-3 py-2 rounded-pill fw-semibold status-badge border-info text-info'>
                    <i class='bi bi-info-circle'></i> " . htmlspecialchars($row['pdc_status']) . "
                </span>";
        }
    } else {
        $pdcStatus = "
            <span class='px-3 py-2 rounded-pill fw-semibold status-badge border-danger text-danger'>
                <i class='bi bi-exclamation-triangle'></i> On Process
            </span>";
    }
?>

    <tr>
      <td class="text-center">
        <?php if ($userRole === 'Auditor'): ?>
            <?php $canAudit = empty($row['pdc_status']) || $row['pdc_status'] === 'Audited'; ?>
            <button 
                class="btn btn-sm rounded-pill auditPdcBtn <?= $canAudit ? 'btn-outline-primary' : 'disabled-btn'; ?>"
                data-contract="<?= htmlspecialchars($row['contract_number']); ?>"
                data-date="<?= htmlspecialchars($formattedDate); ?>"
                data-branch="<?= htmlspecialchars($row['branch']); ?>"
                data-payee="<?= htmlspecialchars($payeeName); ?>"
                data-amount="<?= htmlspecialchars($row['amount_lessor']); ?>"
                data-bs-toggle="<?= $canAudit ? 'modal' : ''; ?>"
                data-bs-target="<?= $canAudit ? '#auditPdcModal' : ''; ?>"
                <?= $canAudit ? '' : 'disabled title="Only audited PDCs can be reviewed."'; ?>
            >
                <i class="bi bi-search me-1"></i>Audit
            </button>
        <?php elseif ($userRole === 'HO'): ?>
            <button 
                class="btn btn-sm rounded-pill btn-primary pickupBtn"
                data-contract="<?= htmlspecialchars($row['contract_number']); ?>"
                data-date="<?= htmlspecialchars($formattedDate); ?>"
                data-branch="<?= htmlspecialchars($row['branch']); ?>"
            >
                <i class="bi bi-hand-index"></i> Pickup
            </button>
            <?php elseif ($userRole === 'Finance' && $row['pdc_status'] === 'Ready for pickup'): ?>
            <button 
                type="button"
                class="btn btn-sm btn-primary rounded-pill printBtn"
                data-contract="<?= htmlspecialchars($row['contract_number'] ?? ''); ?>"
                data-date="<?= htmlspecialchars($formattedDate ?? ''); ?>"
                data-branch="<?= htmlspecialchars($row['branch'] ?? ''); ?>"
                data-region="<?= htmlspecialchars($row['region'] ?? ''); ?>"
                data-amount_lessor="<?= htmlspecialchars($row['amount_lessor'] ?? ''); ?>"
                data-rfp_number="<?= htmlspecialchars($row['rfp_number'] ?? ''); ?>"
            >
                <i class="bi bi-printer me-1"></i> Print
            </button>
        <?php else: ?>
            <?php $isAudited = isset($row['pdc_status']) && $row['pdc_status'] === 'Audited'; ?>
            <button 
                class="btn btn-sm rounded-pill addDetailsBtn <?= $isAudited ? 'btn-outline-danger' : 'disabled-btn'; ?>"
                data-contract="<?= htmlspecialchars($row['contract_number']); ?>"
                data-branch="<?= htmlspecialchars($row['branch']); ?>"
                data-payee="<?= htmlspecialchars($payeeName); ?>"
                data-amount="<?= htmlspecialchars($row['amount_lessor']); ?>"
                data-date="<?= $formattedDate; ?>"
                data-bs-toggle="<?= $isAudited ? 'modal' : ''; ?>"
                data-bs-target="<?= $isAudited ? '#addDetailsModal' : ''; ?>"
                <?= $isAudited ? '' : 'disabled title="Only available after audit."'; ?>
            >
                <i class="bi bi-cash-coin me-1"></i>Process PDC
            </button>
        <?php endif; ?>
      </td>

      <td class="text-start fw-semibold text-dark">
        <i class='bi bi-info-circle text-danger me-2'></i><?= htmlspecialchars($row['contract_number']); ?>
      </td>
      <td class="text-start">
        <i class="bi bi-calendar-check text-danger me-2"></i><?= $formattedDate; ?>
      </td>
      <td class="text-center"><i class="bi bi-geo-alt text-success me-2"></i><?= htmlspecialchars($row['mainzone']); ?></td>
      <td class="text-center"><i class="bi bi-geo-alt text-success me-2"></i><?= htmlspecialchars($row['region']); ?></td>
      <td class="text-center"><i class="bi bi-geo-alt text-success me-2"></i><?= htmlspecialchars($row['area']); ?></td>
      <td class="text-center"><i class="bi bi-shop text-primary me-2"></i><?= htmlspecialchars($row['branch']); ?></td>
      <td class="text-center"><i class="bi bi-person-circle text-danger me-2"></i><?= htmlspecialchars($payeeName); ?></td>
      <td class="fw-semibold text-success text-end">₱ <?= $row['amount_lessor']; ?></td>
      <td class="fw-semibold text-center"><?= $pdcStatus; ?></td>
      <td class="text-center">
          <?= htmlspecialchars($row['pdc_prepared_by'] ?? ''); ?>
      </td>

      <td class="text-center">
          <?= !empty($row['pdc_prepared_date']) 
              ? htmlspecialchars(date("F d, Y", strtotime($row['pdc_prepared_date']))) 
              : ''; ?>
      </td>

    </tr>

<?php
} 
} else {
?>

<tr>
  <td colspan="12" class="text-center text-muted py-4">
    <i class="bi bi-exclamation-circle text-warning me-2"></i>No PDC transactions found
  </td>
</tr>

<?php } ?>

  </tbody>
</table>
<nav aria-label="Page navigation" class="mt-3">
  <ul class="pagination justify-content-center">
    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
    </li>
    
    <?php 
    // Display up to 5 page numbers to keep it clean
    $start_loop = max(1, $page - 2);
    $end_loop = min($totalPages, $page + 2);
    
    for ($i = $start_loop; $i <= $end_loop; $i++): ?>
      <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
      </li>
    <?php endfor; ?>

    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
    </li>
  </ul>
</nav>

        <div class="modal fade" id="auditPdcModal" tabindex="-1" aria-labelledby="auditPdcLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 shadow">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="auditPdcLabel">
                  <i class="bi bi-search me-2 text-white"></i>Audit PDC
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                <form id="auditPdcForm" class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Due Date</label>
                    <input type="text" class="form-control" id="auditTransactionDate" readonly>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Contract Number</label>
                    <input type="text" class="form-control" id="auditContractNumber" readonly>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Branch</label>
                    <input type="text" class="form-control" id="auditBranch" readonly>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Payee Name (Lessor)</label>
                    <input type="text" class="form-control" id="auditPayee" readonly>
                  </div>


                  <div class="col-md-6">
                    <label class="form-label">Amount to Lessor</label>
                    <input type="text" class="form-control" id="auditAmountLessor" readonly>
                  </div>
                </form>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirmAuditBtn">Confirm</button>
              </div>
            </div>
          </div>
        </div>
        <!-- 🔹 Modal -->
        <div class="modal fade" id="addDetailsModal" tabindex="-1" aria-labelledby="addDetailsModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
              <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-semibold text-white"><i class="bi bi-journal-plus me-2"></i>Add Check Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <form id="checkDetailsForm">
                <div class="modal-body p-4">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Contract Number</label>
                      <input type="text" id="modalContract" name="contract_number" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Branch</label>
                      <input type="text" id="modalBranch" name="branch" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Payee Name (Lessor)</label>
                      <input type="text" id="modalPayee" name="payee_name" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Amount (₱)</label>
                      <input type="text" id="modalAmount" name="amount" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Check Date</label>
                        <input type="text"
                              id="modalDate"
                              name="check_date"
                              class="form-control"
                              readonly
                              required>
                    </div>


                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Check Number</label>
                      <input type="text" id="modalCheckNumber" name="check_number" class="form-control" placeholder="Enter Check Number" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Bank Name</label>
                      <input type="text" id="modalBankName" name="bank_name" class="form-control" placeholder="Enter Bank Name" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Bank Account Number</label>
                      <input type="text" id="modalBankAccount" name="bank_account" class="form-control" placeholder="Enter Bank Account No." required>
                    </div>
                    
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Close
                  </button>
                  <button type="submit" id="saveBtn" class="btn btn-danger rounded-pill px-4" disabled>
                    <i class="bi bi-save2 me-2"></i>Save Details
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <script>
          document.addEventListener("DOMContentLoaded", function () {

document.querySelectorAll(".printBtn").forEach(button => {
    button.addEventListener("click", function () {

        const contract = this.dataset.contract;
        const date = this.dataset.date;
        const branch = this.dataset.branch;
        const region = this.dataset.region;
        const amount_lessor = this.dataset.amount_lessor;
        const rfp_number = this.dataset.rfp_number;

        // Open print page in new tab
        const url = `print_check_voucher.php?contract=${encodeURIComponent(contract)}&date=${encodeURIComponent(date)}&branch=${encodeURIComponent(branch)}&region=${encodeURIComponent(region)}&amount_lessor=${encodeURIComponent(this.dataset.amount_lessor)}&rfp_number=${encodeURIComponent(this.dataset.rfp_number)}`;
        window.open(url, "_blank");

    });
});

});

document.addEventListener('DOMContentLoaded', function () {

    /* ======================================================
       PICKUP BUTTON HANDLER
    ====================================================== */
    document.querySelectorAll('.pickupBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const contract = this.dataset.contract || '';
            const branch   = this.dataset.branch || '';
            const region   = this.dataset.region || '';
            const date     = this.dataset.date || '';

            if (!contract || !date) {
                alert('Missing transaction data.');
                return;
            }

            if (!confirm('Mark this transaction as Picked-up?')) return;

            const formData = new FormData();
            formData.append('contract_number', contract);
            formData.append('branch', branch);
            formData.append('region', region);
            formData.append('transaction_date', date);

            fetch('update_pdc_pickedup.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                location.reload();
            })
            .catch(err => {
                console.error(err);
                alert('Failed to update pickup status.');
            });
        });
    });

    /* ======================================================
       ENABLE SAVE BUTTON WHEN FIELDS ARE FILLED
    ====================================================== */
    const checkNumber  = document.getElementById('modalCheckNumber');
    const bankName     = document.getElementById('modalBankName');
    const bankAccount  = document.getElementById('modalBankAccount');
    const saveBtn      = document.getElementById('saveBtn');

    if (checkNumber && bankName && bankAccount && saveBtn) {

        function toggleSaveButton() {
            const isFilled =
                checkNumber.value.trim() !== '' &&
                bankName.value.trim() !== '' &&
                bankAccount.value.trim() !== '';

            saveBtn.disabled = !isFilled;
        }

        checkNumber.addEventListener('input', toggleSaveButton);
        bankName.addEventListener('input', toggleSaveButton);
        bankAccount.addEventListener('input', toggleSaveButton);

        toggleSaveButton();
    }

    /* ======================================================
       AUDIT MODAL HANDLER
    ====================================================== */
    let selectedContract = '';
    let selectedDate     = '';

    document.querySelectorAll('.auditPdcBtn').forEach(button => {
        button.addEventListener('click', function () {

            selectedContract = this.dataset.contract || '';
            selectedDate     = this.dataset.date || '';

            document.getElementById('auditTransactionDate').value = selectedDate;
            document.getElementById('auditContractNumber').value  = selectedContract;
            document.getElementById('auditBranch').value          = this.dataset.branch || '';
            document.getElementById('auditPayee').value           = this.dataset.payee || '';
            document.getElementById('auditAmountLessor').value    = this.dataset.amount || '';
        });
    });

    const confirmAuditBtn = document.getElementById('confirmAuditBtn');

    if (confirmAuditBtn) {
        confirmAuditBtn.addEventListener('click', function () {

            if (!selectedContract || !selectedDate) {
                alert('Missing contract or date information.');
                return;
            }

            const bodyData =
                `contract_number=${encodeURIComponent(selectedContract)}` +
                `&transaction_date=${encodeURIComponent(selectedDate)}`;

            fetch('update_pdc_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyData
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                location.reload();
            })
            .catch(err => {
                console.error(err);
                alert('Audit update failed.');
            });
        });
    }

    /* ======================================================
       POPULATE PROCESS PDC MODAL
    ====================================================== */
    document.querySelectorAll('.addDetailsBtn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('modalContract').value = this.dataset.contract || '';
            document.getElementById('modalBranch').value   = this.dataset.branch || '';
            document.getElementById('modalPayee').value    = this.dataset.payee || '';
            document.getElementById('modalAmount').value   = this.dataset.amount || '';
            document.getElementById('modalDate').value     = this.dataset.date || '';
        });
    });

    /* ======================================================
       CHECK DETAILS FORM SUBMIT
    ====================================================== */
    const checkForm = document.getElementById('checkDetailsForm');

    if (!checkForm) {
        console.warn('checkDetailsForm not found.');
        return;
    }

    // Remove invalid style on typing
    checkForm.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
        });
    });

    checkForm.addEventListener('submit', function (e) {
        e.preventDefault();

        let isValid = true;

        this.querySelectorAll('input[required]').forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            alert('⚠️ Please fill out all required fields.');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');

        if (submitBtn) submitBtn.disabled = true;

        fetch('update_check_details.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (data.success === true) {
                alert('✅ Check details successfully updated!');

                const modalEl = document.getElementById('addDetailsModal');
                const modal   = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                location.reload();
            } else {
                alert(data.message || 'Update failed.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Saving failed.');
        })
        .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
        });
    });

});
</script>

      </div>
    </div>
  </div>

  <!-- 🧩 JS: Toggle Branch Filter Visibility -->
  <script>
    document.getElementById('showBranch').addEventListener('change', function() {
      const branchFilter = document.getElementById('branchFilter');
      branchFilter.style.display = this.checked ? 'block' : 'none';
      if (!this.checked) {
        document.getElementById('branch').value = '';
      }
    });
  </script>
<?php } ?>


<?php if ($userRole !== 'Finance' && $userRole !== 'Auditor' && $userRole !== 'HO'): ?>
  <!-- Termination Section -->
  <h5 class="mt-4 mb-3 fw-bold">Change/Termination of Contract</h5>
  <div class="row g-3">
    <?php
    $archive_data = [
      ['label' => 'Request Change of COL', 'icon' => 'bi bi-person-lines-fill', 'color' => 'text-danger', 'count' => $requestChangeCOLCount, 'link' => 'modify_contract.php'],
      ['label' => 'Pending for Termination', 'icon' => 'bi bi-hourglass-split', 'color' => 'text-danger', 'count' => $pendingTermanitionCount, 'link' => 'terminate_contract.php'],
      ['label' => 'Terminated', 'icon' => 'bi bi-slash-circle', 'color' => 'text-danger', 'count' => $terminatedCount, 'link' => 'terminated_contract.php']
    ];
    

    foreach ($archive_data as $item): ?>
      <div class="col-6 col-md-5th">
        <a href="<?= $item['link']; ?>" class="card-link text-decoration-none text-dark">
          <div class="card p-3 h-100">
            <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="width:40px;height:40px;">
                <i class="bi <?= $item['icon']; ?> fs-5 <?= $item['color']; ?>"></i>
              </div>
              <div>
                <h6 class="mb-1"><?= $item['label']; ?></h6>
                <h5 class="mb-0"><?= $item['count']; ?></h5>
              </div>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>


  <?php if ($userRole !== 'HO' && $userRole !== 'Auditor' && $userRole !== 'Finance'): ?>
  <!-- All Requests Table -->
  <div class="card border-1 rounded-4 mt-4">
  <div class="card-body">
    <h5 class="mb-2 fw-normal" style="color: #d70c0c;">
      <i class="bi bi-table me-2"></i>Request Overview
    </h5>
    
    <div class="table-responsive">
    <?php if ($userRole === 'Vpo-Approver'): ?>
      <div class="mt-0">
        <button type="button" id="approveSelected" class="btn btn-success rounded-pill px-4 mb-2">
          <i class="bi bi-check-circle-fill me-1"></i> Approve Selected
        </button>
      </div>
    <?php endif; ?>
    <table class="table table-hover align-middle mb-0 table-sm text-nowrap">
      <thead class="bg-light text-center">
        <tr class="align-middle">
          <?php if ($userRole === 'Vpo-Approver'): ?>
            <th class="fw-normal">
              <input type="checkbox" id="selectAll"> <!-- Master Checkbox -->
            </th>
          <?php endif; ?>
          <th class="fw-normal"><i class="bi bi-hash text-danger"></i></th>
          <th class="fw-normal"><i class="bi bi-gear-wide-connected me-1 text-danger"></i>Actions</th>
          <th class="fw-normal"><i class="bi bi-file-earmark-pdf me-1 text-danger"></i>Contract File</th>
          <th class="fw-normal"><i class="bi bi-file-text me-1 text-danger"></i>COL #</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>Effectivity Date</th>
          <th class="fw-normal"><i class="bi bi-calendar-x me-1 text-danger"></i>Expiry Date</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>Payment Due Date</th>
          <th class="fw-normal"><i class="bi bi-building me-1 text-danger"></i>Branch</th>
          <th class="fw-normal"><i class="bi bi-person-badge me-1 text-danger"></i>Lessor Type</th>
          <th class="fw-normal"><i class="bi bi-file-earmark-text me-1 text-danger"></i>Mode of Payment</th>
          <th class="fw-normal"><i class="bi bi-info-circle me-1 text-danger"></i>Status</th>
          <?php if ($hasRemarks): ?>
            <th class="fw-normal"><i class="bi bi-chat-left-text me-1 text-danger"></i>Remarks</th>
          <?php endif; ?>
          <th class="fw-normal"><i class="bi bi-person me-1 text-danger"></i>RFP Requested By</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>RFP Requested Date</th>
          <th class="fw-normal"><i class="bi bi-list-ul me-1 text-danger"></i>Category</th>
        </tr>
      </thead>

      <tbody class="text-center">
      <?php if (empty($createdContracts)): ?>
        <tr>
          <td colspan="16" class="text-muted py-4">
            <i class="bi bi-exclamation-circle-fill text-danger me-2 fs-5"></i>
            <strong>No transaction found.</strong>
          </td>
        </tr>
      <?php else: ?>
          <?php $index = 1; ?>
          <?php foreach ($createdContracts as $contract): ?>
            <tr>
              <?php if ($userRole === 'Vpo-Approver'): ?>
                <!-- Row Checkbox -->
                <td>
                  <input type="checkbox" class="transaction-checkbox" name="transaction_ids[]" value="<?= $contract['id']; ?>">
                </td>
              <?php endif; ?>
              <td><?= $index++; ?></td>
              <td>
                <div class="d-flex justify-content-center gap-2 fs-6">
                  <?php
                    $rfpStatus = $contract['rfp_status'];
                    $requestStatus = $contract['request_status'];
                    $contractId = $contract['id'];

                    // Check if user is Am-Creator and should see Make RFP
                    if (
                      $userRole === 'Am-Creator' &&
                      $rfpStatus === 'Reviewed' &&
                      $requestStatus === 'Ready' ||
                      $rfpStatus === 'Reviewed' &&
                      $requestStatus === 'Approved'||
                      $rfpStatus === 'Reviewed' &&
                      $requestStatus === 'Created'
                    ):
                  ?>
                   <button class="btn btn-sm btn-outline-view rounded-pill px-3 d-flex align-items-center view-btn"
                            data-id="<?= $contractId; ?>" 
                            data-bs-toggle="modal" 
                            data-bs-target="#viewContractModal"
                            title="Preview">
                      <i class="bi bi-eye-fill me-1"></i>
                    </button>
                    <a href="edit_contract.php?id=<?= $contractId; ?>" 
                        class="btn btn-sm btn-outline-edit rounded-pill px-3 d-flex align-items-center"
                        title="Edit">
                        <i class="bi bi-pencil-fill me-1"></i>
                      </a>
                  <a href="rfp_page.php?id=<?= $contractId; ?>&branch_id=<?= urlencode($contract['branch_id']); ?>&branch=<?= urlencode($contract['branch']); ?>&contract_number=<?= urlencode($contract['contract_number']); ?>"
                    class="btn btn-sm btn-danger rounded-pill px-3 d-flex align-items-center"
                    title="Make RFP">
                    <i class="bi bi-card-checklist me-1"></i> Make RFP
                  </a>
                  
                  <?php else: ?>
                    <!-- View Button -->
                    <button class="btn btn-sm btn-outline-view rounded-pill px-3 d-flex align-items-center view-btn"
                            data-id="<?= $contractId; ?>" 
                            data-bs-toggle="modal" 
                            data-bs-target="#viewContractModal"
                            title="Preview">
                      <i class="bi bi-eye-fill me-1"></i>
                    </button>

                    <!-- Edit Button - Only for Am-Creator -->
                    <?php if ($userRole === 'Am-Creator'): ?>
                      <a href="edit_contract.php?id=<?= $contractId; ?>" 
                        class="btn btn-sm btn-outline-edit rounded-pill px-3 d-flex align-items-center"
                        title="Edit">
                        <i class="bi bi-pencil-fill me-1"></i>
                      </a>
                    <?php endif; ?>

                    <!-- Remarks Button - Only for reviewer roles -->
                    <?php if (in_array($userRole, $remarkRoles)): ?>
                      <button type="button"
                              class="btn btn-sm btn-outline-danger rounded-pill px-3 d-flex align-items-center"
                              data-bs-toggle="modal"
                              data-bs-target="#remarksModal"
                              data-contract-id="<?= $contractId; ?>"
                              title="Make Remarks / Send Back">
                        <i class="bi bi-file-earmark-text me-1"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ($userRole !== 'Vpo-Approver'): ?>
                      <?php if (
                          $userRole === 'Vpo-Checker' &&                      // Only Vpo-Checker
                          ($contract['mode_of_payment'] ?? '') === 'PDC' &&   // Mode of Payment = PDC
                          empty($contract['rfp_number'])                     // RFP Number is empty
                      ): ?>

                          <!-- ADD RFP NUMBER BUTTON (Shown only for PDC & Vpo-Checker) -->
                          <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center"
                                  data-contract-number="<?= htmlspecialchars($contract['contract_number']); ?>"
                                  data-bs-toggle="modal"
                                  data-bs-target="#addRfpModal"
                                  title="Add RFP Number">
                              <i class="bi bi-plus-circle me-1"></i> Add RFP
                          </button>

                      <?php else: ?>

                          <!-- NORMAL SUBMIT BUTTON -->
                          <button class="btn btn-sm btn-outline-submit rounded-pill px-3 d-flex align-items-center submit-btn"
                                  data-id="<?= $contractId; ?>"
                                  data-bs-toggle="modal"
                                  data-bs-target="#reviewSubmitModal"
                                  title="Submit">
                              <i class="bi bi-send-check-fill me-1"></i> Submit
                          </button>

                      <?php endif; ?>

                      <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
              <!-- Add RFP Modal -->
              <div class="modal fade" id="addRfpModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">

                    <form method="POST" action="update_rfp_number.php">

                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">
                          <i class="bi bi-card-checklist me-2"></i>Add RFP Number
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>

                      <div class="modal-body">

                        <!-- Hidden Contract Number -->
                        <input type="hidden" name="contract_number" id="modal_contract_number">

                        <div class="mb-3">
                          <label class="form-label">RFP Number</label>
                          <input type="text"
                                name="rfp_number"
                                class="form-control"
                                placeholder="Enter RFP Number"
                                required>
                        </div>

                      </div>

                      <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">
                          <i class="bi bi-save me-1"></i> Save
                        </button>
                      </div>

                    </form>

                  </div>
                </div>
              </div>
              <td>
    <?php
    $fileLinks = [];
    // Existing contract files & attachments
    for ($i = 0; $i <= 16; $i++) {
        $filenameCol = '';
        if ($i === 0) { $filenameCol = 'contractFilename'; }
        elseif ($i <= 5) { $filenameCol = "contractFilename$i"; }
        elseif ($i === 16) { $filenameCol = "contractFilename16"; }
        else { $filenameCol = "attachment_{$i}_filename"; }

        // Note: For preview_contract_file.php to work with your previous mapping, 
        // we pass the column name as the 'file' parameter.
        if (!empty($contract[$filenameCol])) {
            $fileLinks[] = [
                'column' => $filenameCol,
                'filename' => $contract[$filenameCol]
            ];
        }
    }
    $fileCount = count($fileLinks);
    ?>

    <?php if ($fileCount > 0): ?>
        <?php if ($fileCount === 1): ?>
            <?php $file = $fileLinks[0]; ?>
            <a href="javascript:void(0)" 
               onclick="viewPDF(<?= $contract['id']; ?>, '<?= $file['column']; ?>', '<?= htmlspecialchars($file['filename']); ?>')" 
               class="text-decoration-none fw-medium">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                <span class="ms-1">1 file</span>
            </a>
        <?php else: ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#contractFilesModal<?= $contract['id']; ?>" class="text-decoration-none">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                <span class="ms-1"><?= $fileCount ?> files</span>
            </a>

            <div class="modal fade" id="contractFilesModal<?= $contract['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title">Select File to Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($fileLinks as $file): ?>
                                    <button type="button" 
                                            class="list-group-item list-group-item-action" 
                                            onclick="viewPDF(<?= $contract['id']; ?>, '<?= $file['column']; ?>', '<?= htmlspecialchars($file['filename']); ?>')">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                        <?= htmlspecialchars($file['filename']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <span class="text-muted fst-italic">No file</span>
    <?php endif; ?>
</td>
              <td><?= htmlspecialchars($contract['contract_number']); ?></td>
              <td>
                <?= !empty($contract['contract_start']) ? date('F d, Y', strtotime($contract['contract_start'])) : '—'; ?>
              </td>
              <td>
                <?= !empty($contract['contract_end']) ? date('F d, Y', strtotime($contract['contract_end'])) : '—'; ?>
              </td>
              <td>
              <?php
                if (!empty($contract['payment_due_date'])) {
                    $day = date('j', strtotime($contract['payment_due_date']));
                    $suffix = 'th';

                    if (!in_array(($day % 100), [11, 12, 13])) {
                        switch ($day % 10) {
                            case 1: $suffix = 'st'; break;
                            case 2: $suffix = 'nd'; break;
                            case 3: $suffix = 'rd'; break;
                        }
                    }

                    echo "Every {$day}{$suffix} day of the month";
                } else {
                    echo '—';
                }
              ?>
            </td>
              <td><?= !empty($contract['branch']) ? htmlspecialchars($contract['branch']) : '—'; ?></td>
              <td>
                <?= !empty($contract['lessor_type']) ? ($contract['lessor_type'] === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($contract['lessor_type'])) : '—'; ?>
              </td>
              <td><?= !empty($contract['mode_of_payment']) ? htmlspecialchars($contract['mode_of_payment']) : '—'; ?></td>
              
              <td>
                <div class="badge bg-warning-subtle fw-semibold text-dark px-3 py-2 rounded-pill text-start">
                  <div class="d-flex flex-column lh-sm">
                    <span>
                    <i class="bi bi-hourglass-split me-1"></i>
                      <?php
                        $rfpStatus = $contract['rfp_status'];
                        $requestStatus = $contract['request_status'];
                        if (empty($rfpStatus) && $requestStatus === 'Prepared' || $rfpStatus === 'Reviewed' && $requestStatus === 'Prepared') {
                            echo "For Review By RM";
                        } elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Reviewed') {
                            echo "For Review By VPO";
                        }
                        elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Checked') {
                            echo "For Approval";
                        }
                        elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready' || $rfpStatus === 'Reviewed' && $requestStatus === 'Approved') {
                            echo "Ready for RFP";
                        } else {
                            echo htmlspecialchars($requestStatus);
                        }
                      ?>
                    </span>

                    <?php
                      $reviewerNote = $contract['reviewer_note'] ?? '';
                      $auditNote = $contract['audit_note'] ?? '';

                      if (!empty($reviewerNote) || !empty($auditNote)) {
                          echo '<small class="text-danger text-center" style="font-size: 0.5rem;">Returned</small>';
                      }
                    ?>
                  </div>
                </div>
              </td>

              <?php if ($hasRemarks): ?>
                <td>
                  <?php if (!empty($contract['reviewer_note']) || !empty($contract['audit_note'])): ?>
                    <button 
                      class="btn btn-sm text-danger p-1 view-remarks-btn"
                      data-bs-toggle="modal" 
                      data-bs-target="#view_remarksModal"
                      data-remarks="<?= htmlspecialchars($contract['reviewer_note'] ?? '', ENT_QUOTES); ?>"
                      data-audit="<?= htmlspecialchars($contract['audit_note'] ?? '', ENT_QUOTES); ?>"
                      data-contract="<?= htmlspecialchars($contract['contract_number']); ?>"
                      title="View Remarks">
                      <i class="bi bi-chat-left-text fs-5 text-danger"></i>
                    </button>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              <?php endif; ?>

              <td><?= !empty($contract['prepared_by']) ? htmlspecialchars($contract['prepared_by']) : '---'; ?></td>

              <td>
                  <i class="bi bi-calendar-event me-1 text-danger"></i>
                  <?= !empty($contract['rfp_date']) ? date('F d, Y', strtotime($contract['rfp_date'])) : '---'; ?>
              </td>
              <td>
                <span class="badge bg-info-subtle text-dark fw-semibold px-3 py-2 rounded-pill">
                  <i class="bi bi-clipboard-data me-1"></i>
                  <?php
                  $rfpStatus = $contract['rfp_status'];
                  $requestStatus = $contract['request_status'];
                  if (((is_null($rfpStatus) || $rfpStatus === '') && ($requestStatus === 'Prepared' || $requestStatus === 'Created')) || ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready')) {
                      echo 'DATA ARCHIVING';
                  } elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Prepared' || $rfpStatus === 'Reviewed' && $requestStatus === 'Created' || $rfpStatus === 'Reviewed' && $requestStatus === 'Reviewed' || $rfpStatus === 'Reviewed' && $requestStatus === 'Checked' || $rfpStatus === 'Reviewed' && $requestStatus === 'Approved') {
                      echo 'RFP';
                  }
                  else {
                      echo htmlspecialchars($contract['category']);
                  }
                  ?>

                </span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <nav aria-label="Page navigation" class="mt-3">
  <ul class="pagination justify-content-center">
    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
    </li>
    
    <?php 
    // Display up to 5 page numbers to keep it clean
    $start_loop = max(1, $page - 2);
    $end_loop = min($totalPages, $page + 2);
    
    for ($i = $start_loop; $i <= $end_loop; $i++): ?>
      <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
      </li>
    <?php endfor; ?>

    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
    </li>
  </ul>
</nav>
    </div>

  </div>
</div>
<?php endif; ?>
</div>
<!-- Modal: Display Remarks -->
<div class="modal fade" id="view_remarksModal" tabindex="-1" aria-labelledby="view_remarksModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 rounded-4 shadow-sm">

      <!-- Modal Header -->
      <div class="modal-header bg-white rounded-top-4 border-bottom">
        <div class="d-flex align-items-center">
          <i class="bi bi-chat-dots-fill text-danger fs-4 me-2"></i>
          <h5 class="modal-title fw-semibold text-dark mb-0" id="view_remarksModalLabel">Remarks</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body px-4 py-3 bg-light-subtle">
        <!-- Contract Number -->
        <p id="view_remarksContractLabel" class="text-muted small mb-3"></p>

        <!-- Reviewer Note (RM) -->
        <div id="view_reviewerNoteWrapper" class="mb-3 d-none">
          <div class="d-flex align-items-center mb-1">
            <i class="bi bi-person-check-fill text-primary me-2"></i>
            <h6 class="fw-bold text-dark mb-0">RM Note</h6>
          </div>
          <p id="view_remarksContent" class="mb-0 text-dark lh-base fs-6" style="white-space: pre-line;"></p>
        </div>

        <!-- Audit Note (VPO) -->
        <div id="view_auditNoteWrapper" class="mb-2 d-none">
          <div class="d-flex align-items-center mb-1">
            <i class="bi bi-shield-check text-success me-2"></i>
            <h6 class="fw-bold text-dark mb-0">VPO Note</h6>
          </div>
          <p id="view_auditContent" class="mb-0 text-dark lh-base fs-6" style="white-space: pre-line;"></p>
        </div>
      </div>
    </div>
  </div>
</div>
<script>window.LAST_ONLINE_ENDPOINT = '../../fetch/last_online.php';</script>
<script src="../../assets/js/last-online-tracker.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

var addRfpModal = document.getElementById('addRfpModal');

addRfpModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var contractNumber = button.getAttribute('data-contract-number');
    document.getElementById('modal_contract_number').value = contractNumber;
});

});
document.addEventListener('show.bs.modal', function (event) {
    // Count already opened modals
    let openModals = document.querySelectorAll('.modal.show').length;
    let zIndex = 1055 + (10 * openModals);

    // Raise the modal itself
    event.target.style.zIndex = zIndex;

    // Fix backdrop *after* it is inserted
    event.target.addEventListener('shown.bs.modal', function () {
        let backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 0) {
            backdrops[backdrops.length - 1].style.zIndex = zIndex - 1;
        }
    }, { once: true });
});

  // ✅ Select/Deselect all
  document.getElementById("selectAll").addEventListener("change", function () {
    let checkboxes = document.querySelectorAll(".transaction-checkbox");
    checkboxes.forEach(cb => cb.checked = this.checked);
  });

  // ✅ Approve button click
  document.getElementById("approveSelected").addEventListener("click", function () {
    let selected = [];
    document.querySelectorAll(".transaction-checkbox:checked").forEach(cb => {
      selected.push(cb.value);
    });

    if (selected.length === 0) {
      Swal.fire({
        icon: "warning",
        title: "No Selection",
        text: "Please select at least one transaction.",
        confirmButtonText: "OK",
        confirmButtonColor: "#0d6efd"
      });
      return;
    }

    fetch("approve_transactions.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({ids: selected})
    })
    .then(res => {
      if (!res.ok) throw new Error("Network response was not ok");
      return res.json();
    })
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Approved!",
          text: "Transactions approved successfully.",
          confirmButtonText: "Great!",
          confirmButtonColor: "#198754"
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Approval Failed",
          text: data.error || "Something went wrong. Please try again.",
          confirmButtonText: "Close",
          confirmButtonColor: "#dc3545"
        });
      }
    })
    .catch(err => {
      console.error("Fetch error:", err);
      Swal.fire({
        icon: "error",
        title: "Request Failed",
        text: "We couldn’t complete your request. Please check your connection and try again.",
        confirmButtonText: "Close",
        confirmButtonColor: "#dc3545"
      });
    });
  });
</script>

<!-- Modal -->
<div class="modal fade" id="viewContractModal" tabindex="-1" aria-labelledby="viewContractModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="background-color: #fff; color: #333; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
      
      <div class="modal-header" style="background-color: #d70c0c; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title text-white" id="viewContractModalLabel">📄 Contract Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-4" id="contractDetailsContent">
        <div class="text-center py-5">
          <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;"></div>
          <p class="mt-3 fw-semibold" style="color: #555;">Loading contract details...</p>
        </div>
      </div>
      
      <div class="modal-footer" style="border-top: 1px solid #eee;">
        <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-bs-dismiss="modal" style="font-weight: 500;">
          Close
        </button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="reviewSubmitModal" tabindex="-1" aria-labelledby="reviewSubmitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Review Contract Before Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body" id="reviewModalBody">
        <!-- Content will be injected dynamically -->
        <p>Loading contract details...</p>
      </div>

      <div class="modal-footer">
        <form method="POST" action="submit_contract.php">
          <input type="hidden" name="contract_id" id="submitContractId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Confirm Submit</button>
        </form>
      </div>

    </div>
  </div>
</div>

<!-- Remarks Modal -->
<div class="modal fade" id="remarksModal" tabindex="-1" aria-labelledby="remarksModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="submit_remarks.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="remarksModalLabel">Submit Remarks</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="contract_id" id="remarksContractId">
          <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" id="remarks" rows="4" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Submit Remarks</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; background-color: #fff;">

      <!-- Vector image -->
      <div class="text-center pt-4">
        <img src="../../assets/images/check.jpg" alt="Notification Image"
             class="img-fluid" style="max-width: 120px;">
      </div>

      <div class="modal-header border-0 justify-content-center pt-3 pb-0">
        <h5 class="modal-title fw-bold d-flex align-items-center" id="messageModalLabel" style="color: #333;">
           Sent
        </h5>
      </div>

      <div class="modal-body text-center px-4 pb-2" style="color: #333; font-size: 1.1rem;">
        <div id="messageModalBody">
          <!-- Message is inserted dynamically -->
        </div>
      </div>

      <div class="modal-footer border-0 justify-content-center pb-4">
        <button type="button" class="btn px-4 py-2" style="background-color: #d70c0c; color: #fff; border-radius: 30px;" data-bs-dismiss="modal">
          <i class="bi bi-x-circle-fill me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>
<?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    const message = `<?php
      echo isset($_SESSION['success_message'])
        ? '<div class="fw-semibold"><i class="bi bi-check-circle-fill text-success me-2"></i><span style=\'color: #333;\'>'.$_SESSION['success_message'].'</span></div>'
        : '<div class="fw-semibold"><i class="bi bi-x-circle-fill text-danger me-2"></i><span style=\'color: #333;\'>'.$_SESSION['error_message'].'</span></div>';
    ?>`;
    document.getElementById('messageModalBody').innerHTML = message;
    modal.show();
  });
</script>
<?php
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
endif;
?>
<?php
// Only run for Am-Creator
if ($userRole === 'Am-Creator') {
    $today = date('Y-m-d');
    $threeMonthsLater = date('Y-m-d', strtotime('+3 months'));

    // Get creator's region & area from session
    $creatorRegion = $_SESSION['region'] ?? '';
    $creatorArea   = $_SESSION['area'] ?? '';

    $filteredContracts = [];

    // ✅ SQL: Get only the latest contract per branch_id
    $sql = "
        SELECT c.contract_number, c.branch, c.region, c.area, 
               c.contract_start, c.contract_end, c.branch_id, c.series
        FROM create_contract c
        INNER JOIN (
            SELECT branch_id, MAX(series) AS latest_series
            FROM create_contract
            GROUP BY branch_id
        ) latest
            ON c.branch_id = latest.branch_id AND c.series = latest.latest_series
        WHERE c.contract_end BETWEEN ? AND ?
          AND c.contract_end <> ?
          AND c.status = 'Active'
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $today, $threeMonthsLater, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($contract = $result->fetch_assoc()) {
                // ✅ Filter contracts by Am-Creator's region & area
                if ($contract['region'] === $creatorRegion && $contract['area'] === $creatorArea) {
                    $filteredContracts[] = $contract;
                }
            }
        }
        $stmt->close();
    }

    $hasExpiring = !empty($filteredContracts);
}
?>

<?php if (!empty($hasExpiring) && $userRole === 'Am-Creator'): ?>
<!-- Expiring Contracts Modal -->
<div class="modal fade" id="expiringContractsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="expiringContractsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-3 overflow-hidden">
      
      <div class="modal-header text-white" style="background: linear-gradient(90deg, #ffc107, #ff9800);">
        <h5 class="modal-title d-flex align-items-center gap-2" id="expiringContractsLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> Contracts of Lease nearly expire
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body bg-light">
        <p class="mb-3 text-secondary">
          <i class="bi bi-info-circle-fill text-primary me-2"></i> The following branches under your
          <strong>Region (<?= htmlspecialchars($creatorRegion) ?>) </strong>
          / <strong>Area (<?= htmlspecialchars($creatorArea) ?>)</strong>
          have contracts that will expire within the next <strong>3 months</strong>:
        </p>

        <div class="table-responsive">
          <table class="table table-hover align-middle bg-white rounded shadow-sm">
            <thead class="table-light">
              <tr>
                <th><i class="bi bi-file-earmark-text me-2"></i> Contract #</th>
                <th><i class="bi bi-shop me-2"></i> Branch</th>
                <th><i class="bi bi-geo-alt me-2"></i> Region</th>
                <th><i class="bi bi-grid-1x2 me-2"></i> Area</th>
                <th><i class="bi bi-calendar-event me-2"></i> Effectivity Date</th>
                <th><i class="bi bi-calendar-event me-2"></i> Expiry Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filteredContracts as $contract): ?>
                <tr class="border-start border-2 border-danger">
                  <td><span class="fw-semibold"><?= htmlspecialchars($contract['contract_number']) ?></span></td>
                  <td><?= htmlspecialchars($contract['branch']) ?></td>
                  <td><?= htmlspecialchars($contract['region']) ?></td>
                  <td><?= htmlspecialchars($contract['area']) ?></td>
                  <td><?= date("M d, Y", strtotime($contract['contract_start'])) ?></td>
                  <td>
                    <span class="badge bg-danger rounded-pill px-3 py-2">
                      <i class="bi bi-clock-fill me-2"></i> <?= date("M d, Y", strtotime($contract['contract_end'])) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-2"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Auto-trigger modal -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  var modalEl = document.getElementById('expiringContractsModal');
  if (modalEl) {
    var expiringModal = new bootstrap.Modal(modalEl);
    expiringModal.show();
  }
});
</script>
<?php endif; ?>
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" style="height: 90vh; border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="pdfPreviewTitle">📄 PDF Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="pdfFrame" src="" width="100%" height="100%" frameborder="0" style="min-height: 80vh;"></iframe>
      </div>
    </div>
  </div>
</div>
<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('branchSearchInput');
    const pipelineContainer = document.getElementById('pipelineContainer');
    const searchPrompt = document.getElementById('searchPromptMessage');

    if (!searchInput || !pipelineContainer || !searchPrompt) return;

    // Trigger instantly as the user types
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        // 1. If search is empty, hide pipelines and show the prompt
        if (searchTerm === '') {
            pipelineContainer.style.display = 'none';
            searchPrompt.style.display = 'block';
            return;
        }

        // 2. If user is typing, hide prompt and show pipelines
        pipelineContainer.style.display = 'block';
        searchPrompt.style.display = 'none';

        // 3. Filter loop: check every section for branch matches
        const sections = document.querySelectorAll('.dashboard-pipeline-section');
        
        sections.forEach(section => {
            let matchCount = 0;
            // Target the newly formatted pipeline cards
            const cards = section.querySelectorAll('.pipeline-card'); 
            // Target the newly formatted count pill
            const countPill = section.querySelector('.pipeline-count-pill');
            
            cards.forEach(card => {
                // Fetch the text of the branch specifically
                const branchDiv = card.querySelector('.branch-name-target');
                
                // If this is a default empty state card (no branch target), ignore it
                if (!branchDiv) return;

                const branchName = branchDiv.textContent.toLowerCase();
                
                // If it matches the search input, display it and increase count
                if (branchName.includes(searchTerm)) {
                    // Using setProperty with 'important' overrides Bootstrap's d-flex
                    card.style.setProperty('display', 'flex', 'important');
                    matchCount++;
                } else {
                    card.style.setProperty('display', 'none', 'important');
                }
            });

            // Update the pill indicator to show how many matched the search query
            if (countPill) {
                countPill.textContent = matchCount;
            }

            // Handle Empty State when searching yields zero matches in a populated array
            let noMatchMsg = section.querySelector('.no-match-search-row');
            const scrollViewport = section.querySelector('.inner-scroll-viewport');

            // Count only actual data cards (excluding default empty states)
            const dataCards = Array.from(cards).filter(c => c.querySelector('.branch-name-target'));

            if (dataCards.length > 0 && matchCount === 0) {
                if (!noMatchMsg && scrollViewport) {
                    noMatchMsg = document.createElement('div');
                    // Styled to match the new premium dashed-border empty states
                    noMatchMsg.className = 'pipeline-card no-match-search-row text-center text-muted py-4 shadow-sm';
                    noMatchMsg.style.borderStyle = 'dashed';
                    noMatchMsg.innerHTML = '<i class="bi bi-search me-2 text-slate-400"></i> No matching branches found for this pipeline.';
                    scrollViewport.appendChild(noMatchMsg);
                }
                if (noMatchMsg) noMatchMsg.style.setProperty('display', 'block', 'important');
            } else if (noMatchMsg) {
                noMatchMsg.style.setProperty('display', 'none', 'important');
            }
        });
    });
});
  function viewPDF(id, fileCol, filename) {
    const pdfFrame = document.getElementById('pdfFrame');
    const pdfTitle = document.getElementById('pdfPreviewTitle');
    
    if (pdfFrame && pdfTitle) {
        // Set the title and source
        pdfTitle.innerText = 'Preview: ' + filename;
        pdfFrame.src = `preview_contract_file.php?id=${id}&file=${fileCol}`;
        
        // Hide selection modal if it was open
        const openModal = document.querySelector('.modal.show');
        if (openModal && openModal.id !== 'pdfPreviewModal') {
            const bsModal = bootstrap.Modal.getInstance(openModal);
            if (bsModal) bsModal.hide();
        }

        // Show the Preview Modal
        const previewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
        previewModal.show();
    }
}

// Handle Modal Close Event
document.addEventListener('DOMContentLoaded', () => {
    const pdfPreviewModal = document.getElementById('pdfPreviewModal');
    if (pdfPreviewModal) {
        pdfPreviewModal.addEventListener('hidden.bs.modal', function () {
            // Clear iframe to stop loading
            document.getElementById('pdfFrame').src = '';
            // Refresh the page
            window.location.reload();
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('view_remarksModal');
  if (!modal) return;

  const contractLabel = document.getElementById('view_remarksContractLabel');
  const reviewerWrapper = document.getElementById('view_reviewerNoteWrapper');
  const remarksContent = document.getElementById('view_remarksContent');
  const auditWrapper = document.getElementById('view_auditNoteWrapper');
  const auditContent = document.getElementById('view_auditContent');

  modal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (!button) return;

    const contract = button.getAttribute('data-contract') || '';
    const remarks = button.getAttribute('data-remarks') || '';
    const audit = button.getAttribute('data-audit') || '';

    contractLabel.textContent = contract ? `Contract #: ${contract}` : '';

    // Show/hide RM Note
    if (remarks.trim()) {
      reviewerWrapper.classList.remove('d-none');
      remarksContent.textContent = remarks;
    } else {
      reviewerWrapper.classList.add('d-none');
    }

    // Show/hide VPO Note
    if (audit.trim()) {
      auditWrapper.classList.remove('d-none');
      auditContent.textContent = audit;
    } else {
      auditWrapper.classList.add('d-none');
    }
  });
});

  const remarksModal = document.getElementById('remarksModal');
  remarksModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const contractId = button.getAttribute('data-contract-id');
    const input = remarksModal.querySelector('#remarksContractId');
    input.value = contractId;
  });

  // Example: When opening modal, set contract ID
function openSubmitModal(contractId) {
  document.getElementById('submitContractId').value = contractId;
  // Optionally show the modal using Bootstrap
  const modal = new bootstrap.Modal(document.getElementById('submitModal'));
  modal.show();
}

  document.addEventListener('DOMContentLoaded', function () {
  const submitButtons = document.querySelectorAll('.submit-btn');

  submitButtons.forEach(button => {
    button.addEventListener('click', async function () {
      const contractId = this.getAttribute('data-id');
      document.getElementById('submitContractId').value = contractId;

      const response = await fetch(`get_contract_preview.php?id=${contractId}`);
      const html = await response.text();

      document.getElementById('reviewModalBody').innerHTML = html;
    });
  });
});

  document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("viewContractModal");
  const content = document.getElementById("contractDetailsContent");

  document.querySelectorAll(".view-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-id");

      // Show loading state
      content.innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2">Loading contract details...</p>
        </div>
      `;

      // Fetch contract details
      fetch(`fetch_contract_details.php?id=${id}`)
        .then(res => res.text())
        .then(html => {
          content.innerHTML = html;
        })
        .catch(err => {
          content.innerHTML = `<div class="alert alert-danger">Error loading contract details.</div>`;
        });
    });
  });
});

  const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
document.getElementById('logoutLink')?.addEventListener('click', function (e) {
  e.preventDefault();
  const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
    backdrop: 'static',
    keyboard: false
  });
  logoutModal.show();
  setTimeout(() => window.location.href = '../../logout.php', 2500);
});


</script>
  </body>
</html>
