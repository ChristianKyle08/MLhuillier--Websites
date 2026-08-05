<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
include '../../config/config.php';
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_email'])) {
    header('location:../../admin/rental/login_form.php');
    exit;
}

$adminEmail = $_SESSION['admin_email'];
$isMlhuillierEmail = preg_match('/@mlhuillier\.com$/i', $adminEmail); // ✅ check domain

date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d');

// ✅ Convert current date to the first day of the current month
$currentMonthStart = date('Y-m-01');

// ✅ Compute 5 months ahead (first day of that month)
$fiveMonthsLater = date('Y-m-01', strtotime('+5 months', strtotime($currentMonthStart)));

// ✅ Fetch expiring contracts within 5 months (month/year only basis)
// Added c.mainzone to the SELECT statement to allow grouping
$query = $conn->prepare("
    SELECT 
        c.id, c.contract_number, c.branch, c.mainzone, c.region, c.area,
        c.contract_start, c.contract_end, c.start_date, c.end_date,
        u.id_number, u.first_name, u.middle_name, u.last_name, u.email
    FROM create_contract c
    INNER JOIN (
        SELECT branch, MAX(end_date) AS latest_end_date
        FROM create_contract
        GROUP BY branch
    ) latest 
        ON c.branch = latest.branch AND c.end_date = latest.latest_end_date
    LEFT JOIN user_form u 
        ON c.region = u.region AND c.area = u.area
    WHERE DATE_FORMAT(c.end_date, '%Y-%m') BETWEEN DATE_FORMAT(?, '%Y-%m') AND DATE_FORMAT(?, '%Y-%m')
    ORDER BY c.mainzone ASC, c.end_date ASC
");
$query->bind_param("ss", $currentMonthStart, $fiveMonthsLater);
$query->execute();
$result = $query->get_result();
$notifCount = $result->num_rows;

// ✅ Group contracts and emails by Mainzone
$groupedData = [];

while ($row = $result->fetch_assoc()) {
    $mainzone = !empty($row['mainzone']) ? $row['mainzone'] : 'Unassigned Zone';
    
    if (!isset($groupedData[$mainzone])) {
        $groupedData[$mainzone] = [
            'emails' => [],
            'contracts' => []
        ];
    }
    
    if (!empty($row['email']) && preg_match('/@mlhuillier\.com$/i', trim($row['email']))) {
        // Prevent duplicate emails in the To: field for the same mainzone
        if (!in_array(trim($row['email']), $groupedData[$mainzone]['emails'])) {
            $groupedData[$mainzone]['emails'][] = trim($row['email']);
        }
    }
    
    $groupedData[$mainzone]['contracts'][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Monthly Rental RFP Notifications</title>
<link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
<link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
<link href="../../assets/css/poppins.css" rel="stylesheet">
<style>
body {
    background: #f4f7f6;
    font-family: 'Poppins', sans-serif;
    color: #333;
}
.page-header {
    background: #fff;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}
.zone-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    margin-bottom: 30px;
    overflow: hidden;
    background: #fff;
}
.zone-card-header {
    background: #fff;
    border-bottom: 1px solid #edf2f9;
    padding: 16px 24px;
}
.table-modern {
    margin-bottom: 0;
}
.table-modern thead {
    background: #f8fafc;
    color: #475569;
}
.table-modern th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    padding: 15px;
    border-bottom: 2px solid #edf2f9;
}
.table-modern td {
    padding: 15px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid #edf2f9;
    color: #4b5563;
}
.table-modern tbody tr:hover {
    background-color: #f8fafc;
}
.table-modern tbody tr:last-child td {
    border-bottom: none;
}
.back-btn {
    border-radius: 50px;
    border: 1px solid #dc2626;
    transition: all .3s;
    color: #dc2626;
    font-size: 13px;
    font-weight: 500;
    padding: 8px 20px;
}
.back-btn:hover {
    background: #dc2626;
    color: #fff;
    transform: translateX(-3px);
}
.btn-send-email {
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    padding: 8px 16px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.2);
}
.btn-send-email:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(220, 38, 38, 0.3);
}
.zone-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}
.badge-status {
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 500;
    font-size: 11px;
}
</style>
</head>
<body>

<div class="container my-4">
  <!-- Page Header -->
  <div class="page-header d-flex justify-content-between align-items-center">
    <a href="admin_page.php" class="btn back-btn">
      <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
    <h4 class="m-0 fw-bold"><i class="bi bi-bell-fill text-danger me-2"></i> Monthly Rental RFP Notifications</h4>
  </div>

  <?php if ($notifCount > 0): ?>
      <div class="alert alert-warning text-center shadow-sm rounded-3 mb-4 border-0" style="font-size: 14px; background-color: #fffbeb; color: #92400e;">
        <i class="bi bi-shield-lock-fill me-2 fs-5 align-middle"></i>
        Security Notice: You must use your authorized <strong>@mlhuillier.com</strong> email account to send these official notifications.
      </div>

      <!-- Loop through each Mainzone -->
      <?php foreach ($groupedData as $mainzone => $data): 
          
          // Generate a formatted list of contracts including COL Number and Branch Name
          $contractList = "";
          foreach ($data['contracts'] as $contract) {
              $colNumber = !empty($contract['contract_number']) ? $contract['contract_number'] : 'N/A';
              $branchName = !empty($contract['branch']) ? $contract['branch'] : 'Unknown Branch';
              $expiryDate = date("M Y", strtotime($contract['end_date']));
              
              $contractList .= "• COL Number: {$colNumber} | Branch: {$branchName} | RFP Expiry: {$expiryDate}\r\n";
          }
          
          // Construct the professional email body dynamically for this mainzone
          $defaultSubject = "Action Required: Monthly Rental (Request For Payment) - " . $mainzone;
          
          $defaultMessage = "Dear {$mainzone} Team,\r\n\r\n"
              . "This is an official notification regarding the upcoming expiration of Contract(s) of Lease (COL) RFP under your supervision. Prompt action is required to ensure uninterrupted operations.\r\n\r\n"
              . "Expiring Contracts (Next 1 to 5 Months):\r\n"
              . $contractList . "\r\n"
              . "Kindly review the listed contract(s) and initiate the necessary steps for renewal, extension, or closure as applicable. Please ensure that all corresponding documents are updated promptly in the system.\r\n\r\n"
              . "Should you have any questions or require assistance, please coordinate with the Rental Management Team.\r\n\r\n"
              . "Thank you for your immediate attention to this matter.\r\n\r\n"
              . "Best regards,\r\n\r\nML Rental Management Team\r\nMLhuillier Financial Services";

          $gmailLink = "https://mail.google.com/mail/?view=cm&fs=1"
              . "&to=" . urlencode(implode(',', $data['emails']))
              . "&su=" . urlencode($defaultSubject)
              . "&body=" . urlencode($defaultMessage);
      ?>

          <div class="card zone-card">
              <!-- Zone Header -->
              <div class="zone-card-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                      <div class="bg-danger text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                          <i class="bi bi-geo-alt-fill fs-5"></i>
                      </div>
                      <div>
                          <h5 class="zone-title"><?= htmlspecialchars($mainzone) ?></h5>
                          <small class="text-muted"><?= count($data['contracts']) ?> Expiring Contract(s)</small>
                      </div>
                  </div>
                  
                  <a href="<?= htmlspecialchars($gmailLink) ?>" target="_blank" class="btn btn-primary btn-send-email text-white">
                      <i class="bi bi-envelope-paper-fill me-2"></i> Email <?= htmlspecialchars($mainzone) ?> Team
                  </a>
              </div>

              <!-- Zone Data Table -->
              <div class="table-responsive">
                  <table class="table table-modern align-middle mb-0">
                      <thead>
                          <tr>
                              <th>Personnel ID</th>
                              <th>Assigned To</th>
                              <th>Branch</th>
                              <th>Region</th>
                              <th>Area</th>
                              <th>RFP Start</th>
                              <th>RFP End</th>
                              <th>Status</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($data['contracts'] as $row): 
                              $monthsLeft = (date('Y', strtotime($row['end_date'])) - date('Y')) * 12 + 
                                            (date('m', strtotime($row['end_date'])) - date('m'));
                                            
                              if ($monthsLeft < 0) {
                                  $status = '<span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Expired</span>';
                              } elseif ($monthsLeft <= 2) {
                                  $status = '<span class="badge-status bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-50">Critical: '.$monthsLeft.' month(s)</span>';
                              } else {
                                  $status = '<span class="badge-status bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Expiring in '.$monthsLeft.' month(s)</span>';
                              }
                          ?>
                          <tr>
                              <td><span class="text-muted fw-medium"><?= htmlspecialchars($row['id_number'] ?? '—') ?></span></td>
                              <td>
                                  <div class="fw-semibold text-dark"><?= htmlspecialchars(trim($row['first_name'].' '.$row['last_name'])) ?></div>
                                  <small class="text-muted"><?= htmlspecialchars($row['email'] ?? 'No email assigned') ?></small>
                              </td>
                              <td class="fw-medium"><?= htmlspecialchars($row['branch'] ?? '—') ?></td>
                              <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
                              <td><?= htmlspecialchars($row['area'] ?? '—') ?></td>
                              <td><span class="text-muted"><?= date("M Y", strtotime($row['start_date'])) ?></span></td>
                              <td class="fw-medium text-dark"><?= date("M Y", strtotime($row['end_date'])) ?></td>
                              <td><?= $status ?></td>
                          </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          </div>
      <?php endforeach; ?>

  <?php else: ?>
    <!-- Empty State -->
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body text-center p-5">
            <div class="mb-3 text-success">
                <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold text-dark">All Caught Up!</h5>
            <p class="text-muted mb-0">There are no contracts expiring within the next 5 months across any mainzone.</p>
        </div>
    </div>
  <?php endif; ?>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>