<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
$data = json_decode(file_get_contents("php://input"), true);

$rows = $data['escalation_rows'] ?? [];

$col_number = $data['col_number'] ?? '';
$mainzone = $data['mainzone'] ?? '';
$zone = $data['zone'] ?? '';
$region = $data['region'] ?? '';
$area = $data['area'] ?? '';
$branch_id = $data['branch_id'] ?? '';
$branch = $data['branch'] ?? '';
$effectivity_date = $data['effectivity_date'] ?? '';
$expiry_date = $data['expiry_date'] ?? '';
$monthly_due_date = $data['monthly_due_date'] ?? ''; // Optional
$vat_type = $data['vat_type'] ?? '';
$created_by = $data['created_by'] ?? 'system';
$status = ''; // default
$created_date = date('Y-m-d');

if (empty($rows)) {
  echo json_encode(['success' => false, 'message' => 'No escalation data provided.']);
  exit;
}

$stmt = $conn->prepare("
  INSERT INTO escalation (
    col_number, mainzone, zone, region, area, branch_id, branch,
    effectivity_date, expiry_date, start_date, end_date, monthly_due_date,
    monthly_rental, vat_type, vat, vat_percent, net_of_vat,
    wtax_type, wtax_percent, wtax, amount_to_lessor,
    escalation_percent, fixed_amount, increase, yearly_amount,
    created_date, created_by, status
  )
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($rows as $row) {
  $stmt->bind_param(
    "ssssssssssssssssssssddddsss",
    $col_number,
    $mainzone,
    $zone,
    $region,
    $area,
    $branch_id,
    $branch,
    $effectivity_date,
    $expiry_date,
    $row['start_date'],
    $row['end_date'],
    $monthly_due_date,
    $row['rental'],
    $vat_type,
    $row['vat'],
    $row['wtax_percent'], // use vat_percent if you need this separately
    $row['net_vat'],
    $row['wtax_type'],
    $row['wtax_percent'],
    $row['wtax_amount'],
    $row['amount_to_lessor'],
    $row['escalation_percent'],
    $row['fixed_amount'],
    $row['increase'],
    $row['yearly_total'],
    $created_date,
    $created_by,
    $status
  );

  $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true]);