<?php
session_start();
    include '../../config/config.php';
    if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_escalation'])) {
        if (!empty($_POST['deleted_ids'])) {
            $idsToDelete = explode(',', $_POST['deleted_ids']);
            $sanitizedIds = array_filter(array_map('intval', $idsToDelete), fn($id) => $id > 0);
    
            if (!empty($sanitizedIds)) {
                $idList = implode(',', $sanitizedIds);
                $deleteQuery = "DELETE FROM escalation WHERE id IN ($idList)";
                $result = mysqli_query($conn, $deleteQuery);
    
                if (!$result) {
                    error_log("Delete query error: " . mysqli_error($conn));
                    echo "<script>alert('Error deleting escalation rows.');</script>";
                }
            }
        }
        foreach ($_POST['start_date'] as $index => $startMonth) {
            $id = $_POST['row_id'][$index] ?? '';
            $startDate = mysqli_real_escape_string($conn, $startMonth ? ($startMonth . '-01') : '');
            $endDate = mysqli_real_escape_string($conn, isset($_POST['end_date'][$index]) ? ($_POST['end_date'][$index] . '-01') : '');
    
            $monthlyRental = mysqli_real_escape_string($conn, $_POST['monthly_rental'][$index] ?? '0');
            $vat = mysqli_real_escape_string($conn, $_POST['vat_percent'][$index] ?? '0');
            $netOfVat = mysqli_real_escape_string($conn, $_POST['net_of_vat'][$index] ?? '0');
            $vatPercent = mysqli_real_escape_string($conn, $_POST['vat'][$index] ?? '0');
            $wtax = mysqli_real_escape_string($conn, $_POST['wtax'][$index] ?? '0');
            $wtaxPercent = mysqli_real_escape_string($conn, $_POST['wtax_percent'][$index] ?? '0');
            $amountToLessor = mysqli_real_escape_string($conn, $_POST['amount_to_lessor'][$index] ?? '0');
            $escalationPercent = mysqli_real_escape_string($conn, $_POST['escalation_percent'][$index] ?? '0');
            $fixedAmount = mysqli_real_escape_string($conn, $_POST['fixed_amount'][$index] ?? '0');
            $increase = mysqli_real_escape_string($conn, $_POST['yearly_increase'][$index] ?? '0');
            $yearlyAmount = mysqli_real_escape_string($conn, $_POST['yearly_amount'][$index] ?? '0');
    
            $colNumber = mysqli_real_escape_string($conn, $_GET['contract_number'] ?? '');
            $mainzone = mysqli_real_escape_string($conn, $_POST['mainzone'] ?? '');
            $zone = mysqli_real_escape_string($conn, $_POST['zone'] ?? '');
            $region = mysqli_real_escape_string($conn, $_POST['region'] ?? '');
            $branch = mysqli_real_escape_string($conn, $_POST['branch'] ?? '');
            $area = mysqli_real_escape_string($conn, $_POST['area'] ?? '');
            $branchId = mysqli_real_escape_string($conn, $_POST['branch_id'] ?? '');
            $effectivityDate = mysqli_real_escape_string($conn, $_POST['effectivity_date'] ?? '');
            $expiryDate = mysqli_real_escape_string($conn, $_POST['expiry_date'] ?? '');
            $monthlyDueDate = mysqli_real_escape_string($conn, $_POST['monthly_due_date_complete'] ?? '');
            $vatType = mysqli_real_escape_string($conn, $_POST['vat_type'] ?? '');
            $wtaxType = mysqli_real_escape_string($conn, $_POST['wtax_type'] ?? '');
            $created_by = mysqli_real_escape_string($conn, $_POST['created_by'] ?? '');
            // ✅ Update create_contract based on selected col_number
            if (!empty($colNumber)) {

                $updateContractQuery = "
                    UPDATE create_contract cc
                    JOIN (
                        SELECT e2.start_date, e2.end_date
                        FROM escalation e2
                        WHERE e2.col_number = '$colNumber'
                          AND e2.start_date > (
                                SELECT MAX(e1.end_date)
                                FROM escalation e1
                                WHERE e1.col_number = '$colNumber'
                                  AND e1.status = 'Approved'
                          )
                        ORDER BY e2.start_date ASC
                        LIMIT 1
                    ) nxt ON 1=1
                    SET
                        cc.start_date = nxt.start_date,
                        cc.end_date = nxt.end_date,
                        cc.amount = '$monthlyRental',
                        cc.vat_amount = '$vat',
                        cc.net_of_vat = '$netOfVat',
                        cc.wtax = '$wtax',
                        cc.amount_lessor = '$amountToLessor',
                        cc.edit_amount_lessor = '$amountToLessor',
                        cc.total_month_rental = '$monthlyRental'
                    WHERE cc.contract_number = '$colNumber'
                ";
            
                $contractResult = mysqli_query($conn, $updateContractQuery);
            
                if (!$contractResult) {
                    error_log('Create_contract update error: ' . mysqli_error($conn));
                }
            }            
            

            if (!empty($id)) {
                // Update existing row
                $updateQuery = "
                    UPDATE escalation SET 
                        start_date = '$startDate',
                        end_date = '$endDate',
                        monthly_rental = '$monthlyRental',
                        vat = '$vat',
                        wtax_type = '$wtaxType',
                        net_of_vat = '$netOfVat',
                        vat_percent = '$vatPercent',
                        wtax = '$wtax',
                        wtax_percent = '$wtaxPercent',
                        amount_to_lessor = '$amountToLessor',
                        escalation_percent = '$escalationPercent',
                        fixed_amount = '$fixedAmount',
                        increase = '$increase',
                        yearly_amount = '$yearlyAmount'
                    WHERE id = '$id'
                ";
                mysqli_query($conn, $updateQuery);
            } else {
                // Insert new row
                $insertQuery = "
                    INSERT INTO escalation (
                        col_number, mainzone, zone, region, area, branch_id, branch, effectivity_date, expiry_date, start_date, end_date, monthly_due_date, monthly_rental, vat_type, vat, net_of_vat, vat_percent,
                        wtax_type, wtax, wtax_percent, amount_to_lessor, escalation_percent, fixed_amount,
                        increase, yearly_amount, created_date, created_by
                    ) VALUES (
                        '$colNumber', '$mainzone', '$zone', '$region', '$area', '$branchId', '$branch', '$effectivityDate', '$expiryDate', '$startDate', '$endDate', '$monthlyDueDate', '$monthlyRental', '$vatType', '$vat', '$netOfVat', '$vatPercent',
                        '$wtaxType', '$wtax', '$wtaxPercent', '$amountToLessor', '$escalationPercent', '$fixedAmount',
                        '$increase', '$yearlyAmount', NOW(), '$created_by'
                    )
                ";
                mysqli_query($conn, $insertQuery);
            }
        }
    
        echo "<script>alert('Escalation rows updated successfully.'); window.location.href = window.location.href;</script>";
    }
    
?>

<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
            <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
            <meta name="description" content="">
            <title>ML Rental - Edit Escalation</title>
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
    </head>
    <style>
        .inp{
            width: 150px;
            padding: 5px;
        }
        /* Common button style */
        .custom-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #fff;
            background-color: #333; /* default background */
        }

        /* Hover effect */
        .custom-btn:hover {
            background-color: #d70c0c; /* hover red */
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }

        /* Icon style */
        .custom-btn i {
            font-size: 16px;
        }

        /* Add some focus effect for accessibility */
        .custom-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(215,12,12,0.5);
        }
    </style>
<body>
<?php include ('navbar.php'); ?>
<div id="mainContent">
<button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
        <script>
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
<div class="container py-1">
    <form method="GET" action="" class="mb-3">
        <div class="row align-items-end">
            <!-- Contract Number Input -->
            <div class="col-md-8">
                <label for="contract_number" class="form-label fw-bold">
                    <i class="bi bi-file-earmark-text me-2 text-danger"></i> Select Contract Number:
                </label>

                <input 
                    type="text"
                    class="form-control"
                    autocomplete="off"
                    list="contractNumbers"
                    name="contract_number"
                    id="contract_number"
                    value="<?= isset($_GET['contract_number']) ? htmlspecialchars($_GET['contract_number']) : '' ?>"
                    placeholder="Type or select a contract number"
                    required
                >

                <datalist id="contractNumbers">
                    <?php
                    $userRole     = $_SESSION['user_role'] ?? '';
                    $userRegion   = $_SESSION['region'] ?? '';
                    $userArea     = $_SESSION['area'] ?? '';
                    $userMainzone = $_SESSION['mainzone'] ?? '';

                    $query  = "";
                    $params = [];
                    $types  = "";

                    /* ================= ROLE-BASED FILTER ================= */

                    if ($userRole === 'Am-Creator') {

                        if ($userRegion && $userArea) {
                            $query = "
                                SELECT DISTINCT
                                    contract_number,
                                    region,
                                    area,
                                    branch
                                FROM create_contract
                                WHERE region = ?
                                AND area = ?
                                ORDER BY contract_number ASC
                            ";
                            $params = [$userRegion, $userArea];
                            $types  = "ss";
                        }

                    } elseif ($userRole === 'Rm-Reviewer') {

                        if ($userMainzone && $userRegion) {
                            $query = "
                                SELECT DISTINCT
                                    contract_number,
                                    region,
                                    area,
                                    branch
                                FROM create_contract
                                WHERE mainzone = ?
                                AND region = ?
                                ORDER BY contract_number ASC
                            ";
                            $params = [$userMainzone, $userRegion];
                            $types  = "ss";
                        }

                    } elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {

                        if ($userMainzone) {
                            $query = "
                                SELECT DISTINCT
                                    contract_number,
                                    region,
                                    area,
                                    branch
                                FROM create_contract
                                WHERE mainzone = ?
                                ORDER BY contract_number ASC
                            ";
                            $params = [$userMainzone];
                            $types  = "s";
                        }

                    } elseif ($userRole === 'HO') {

                        $query = "
                            SELECT DISTINCT
                                contract_number,
                                region,
                                area,
                                branch
                            FROM create_contract
                            ORDER BY contract_number ASC
                        ";

                    } else {
                        echo '<option value="">Access denied</option>';
                    }

                    /* ================= EXECUTION ================= */

                    if (!empty($query)) {
                        if ($stmt = mysqli_prepare($conn, $query)) {

                            if (!empty($params)) {
                                mysqli_stmt_bind_param($stmt, $types, ...$params);
                            }

                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {

                                    $contract = htmlspecialchars($row['contract_number']);
                                    $region   = htmlspecialchars($row['region']);
                                    $area     = htmlspecialchars($row['area']);
                                    $branch   = htmlspecialchars($row['branch']);

                                    echo "
                                        <option 
                                            value=\"{$contract}\" 
                                            label=\"{$region} | {$area} | {$branch}\">
                                        </option>
                                    ";
                                }
                            } else {
                                echo '<option value="">No contracts found</option>';
                            }

                            mysqli_stmt_close($stmt);
                        } else {
                            error_log(mysqli_error($conn));
                            echo '<option value="">Query error</option>';
                        }
                    }
                    ?>
                    </datalist>

            </div>


            <!-- Submit Button -->
            <div class="col-md-4">
                <button type="submit" name="search_esc" id="search_esc" class="btn btn-danger w-100">
                    <i class="bi bi-search me-2"></i> Load Escalation
                </button>
            </div>
        </div>
    </form>

    <form action="" method="POST">
        <input type="hidden" name="deleted_ids" id="deleted_ids" value="">

        <?php
        if (isset($_GET['contract_number'])) {
            $contractNumber = mysqli_real_escape_string($conn, $_GET['contract_number']);

            // Get contract info
            $infoQuery = "
                SELECT *
                FROM escalation 
                WHERE col_number = '$contractNumber' 
                LIMIT 1";
            $infoResult = mysqli_query($conn, $infoQuery);
            $contractInfo = mysqli_fetch_assoc($infoResult);

            if ($contractInfo) {
                $effectivityMonth = date('Y-m', strtotime($contractInfo['effectivity_date']));
                $expiryMonth = date('Y-m', strtotime($contractInfo['expiry_date']));

                // Get escalation rows
                $escalationQuery = "
                    SELECT * FROM escalation 
                    WHERE col_number = '$contractNumber' 
                    ORDER BY start_date ASC";
                $escalationResult = mysqli_query($conn, $escalationQuery);

                $vatComputedVisible = false;
                $allRows = [];

                while ($row = mysqli_fetch_assoc($escalationResult)) {
                    if (!empty($row['vat_computed_amount'])) {
                        $vatComputedVisible = true;
                    }
                    $allRows[] = $row;
                }

                if (count($allRows) > 0) {
                    // Display contract info with Bootstrap
                    echo "
                    <div class='card shadow-sm border-0 mb-2' style='background-color:#fff; color:#333;'>
                        <div class='card-header d-flex align-items-center bg-danger text-white'>
                            <i class='bi bi-file-earmark-text me-2'></i>
                            <h5 class='mb-0 text-white'>Contract Information</h5>
                        </div>

                        <div class='card-body'>
                            <input type='hidden' name='created_by' value='" . htmlspecialchars($contractInfo['created_by']) . "' />

                            <div class='row mb-2'>
                                <!-- Branch -->
                                <div class='col-md-2'>
                                    <label class='form-label fw-bold'>
                                        <i class='bi bi-building me-1 text-danger'></i> Branch:
                                    </label>
                                    <div class='form-control-plaintext'>
                                        <span class='branch_span'>" . htmlspecialchars($contractInfo['branch']) . "</span>
                                    </div>
                                    <input type='hidden' name='mainzone' value='" . htmlspecialchars($contractInfo['mainzone']) . "' />
                                    <input type='hidden' name='zone' value='" . htmlspecialchars($contractInfo['zone']) . "' />
                                    <input type='hidden' name='region' value='" . htmlspecialchars($contractInfo['region']) . "' />
                                    <input type='hidden' name='branch' value='" . htmlspecialchars($contractInfo['branch']) . "' />
                                    <input type='hidden' name='area' value='" . htmlspecialchars($contractInfo['area']) . "' />
                                    <input type='hidden' name='branch_id' value='" . htmlspecialchars($contractInfo['branch_id']) . "' />
                                </div>

                                <!-- Effectivity Date -->
                                <div class='col-md-2'>
                                    <label class='form-label fw-bold'>
                                        <i class='bi bi-calendar-check me-1 text-danger'></i> Effectivity Date:
                                    </label>
                                    <div class='form-control-plaintext'>
                                        <span class='effectivity_span'>" . date('F d, Y', strtotime(htmlspecialchars($contractInfo['effectivity_date']))) . "</span>
                                    </div>
                                    <input type='hidden' name='effectivity_date' value='" . htmlspecialchars($contractInfo['effectivity_date']) . "' />
                                </div>

                                <!-- Expiry Date -->
                                <div class='col-md-2'>
                                    <label class='form-label fw-bold'>
                                        <i class='bi bi-calendar-x me-1 text-danger'></i> Expiry Date:
                                    </label>
                                    <div class='form-control-plaintext'>
                                        <span class='expiry_span'>" . date('F d, Y', strtotime(htmlspecialchars($contractInfo['expiry_date']))) . "</span>
                                    </div>
                                    <input type='hidden' name='expiry_date' value='" . htmlspecialchars($contractInfo['expiry_date']) . "' />
                                </div>
                            </div>";
                            // Monthly Due Date as input (number input for the day of the month)
                            $dueDay = (int) date('j', strtotime($contractInfo['monthly_due_date']));
                            $dueDayComplete = date('Y-m-d', strtotime($contractInfo['monthly_due_date']));

                            // Compute ordinal suffix
                            $suffix = 'th';
                            if (!in_array($dueDay % 100, [11, 12, 13])) {
                                $lastDigit = $dueDay % 10;
                                $suffix = match ($lastDigit) {
                                    1 => 'st',
                                    2 => 'nd',
                                    3 => 'rd',
                                    default => 'th',
                                };
                            }
                            echo"
                            <div class='row mb-2'>
                                <!-- Monthly Due Date -->
                                <div class='col-md-2'>
                                    <label class='form-label fw-bold'>
                                        <i class='bi bi-calendar-event me-1 text-danger'></i> Monthly Due Date:
                                    </label>
                                    <div class='form-control-plaintext'>
                                        <span class='monthly_due_date_span'>Every {$dueDay}{$suffix} day of the month</span>
                                    </div>
                                    <input type='hidden' name='monthly_due_date' min='1' max='31' value='" . $dueDay . "' />
                                    <input type='hidden' name='monthly_due_date_complete' min='1' max='31' value='" . $dueDayComplete . "' />
                                </div>

                                <!-- VAT Type -->
                                <div class='col-md-2'>
                                    <label class='form-label fw-bold'>
                                        <i class='bi bi-percent me-1 text-danger'></i> VAT Type:
                                    </label>
                                    <div class='form-control-plaintext'>
                                        <span class='vat_type_span'>" . htmlspecialchars($contractInfo['vat_type']) . "</span>
                                    </div>
                                    <input type='hidden' name='vat_type' id='vat_type' value='" . htmlspecialchars($contractInfo['vat_type']) . "' />
                                </div>

                                <!-- Wtax Type -->
                                <div class='col-md-2'>
                                    <label class='form-label fw-bold'>
                                        <i class='bi bi-cash-coin me-1 text-danger'></i> Wtax Type:
                                    </label>
                                    <div class='form-control-plaintext'>
                                        <span class='wtax_type_span'>" . htmlspecialchars($contractInfo['wtax_type']) . "</span>
                                    </div>
                                    <input type='hidden' name='wtax_type' id='wtax_type' value='" . htmlspecialchars($contractInfo['wtax_type']) . "' />
                                </div>
                            </div>
                        </div>
                    </div>
                    ";
                    // Information message
                    echo "
                    <div class='alert alert-info d-flex justify-content-between align-items-start shadow-sm mb-2' role='alert'>
                        <div class='d-flex'>
                            <i class='bi bi-info-circle-fill me-2 fs-5'></i>
                            <div id='alert-text'>
                                <strong>Tip:</strong> This table has many columns and may not fit on your screen.  
                                <ul class='mb-2'>
                                    <li><strong>Desktop/Laptop:</strong> Use the scroll bar below to move left and right.  
                                        For faster scrolling, hold <kbd>Shift</kbd> while rolling the mouse wheel.</li>
                                    <li><strong>Mobile/Tablet:</strong> Swipe left or right on the table with your finger to see hidden columns.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Translate Button at the right corner -->
                        <button type='button' id='translateBtn' 
                                class='btn btn-sm fw-bold shadow-sm d-flex align-items-center justify-content-center'
                                style='color: #055160; border: 1px solid #055160; background-color: transparent; transition: all 0.3s ease;'>
                            <i class='bi bi-translate fs-6 me-2'></i>
                            <span>Translate to Tagalog</span>
                        </button>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const translateBtn = document.getElementById('translateBtn');
                        const alertText = document.getElementById('alert-text');

                        // English message
                        const englishText = `
                            <strong>Tip:</strong> This table has many columns and may not fit on your screen.  
                            <ul class='mb-2'>
                                <li><strong>Desktop/Laptop:</strong> Use the scroll bar below to move left and right.  
                                    For faster scrolling, hold <kbd>Shift</kbd> while rolling the mouse wheel.</li>
                                <li><strong>Mobile/Tablet:</strong> Swipe left or right on the table with your finger to see hidden columns.</li>
                            </ul>
                        `;

                        // Simple Tagalog message
                        const tagalogText = `
                        <strong>Payo:</strong> Malapad ang talahanayan na ito kaya baka hindi agad makita ang lahat ng hanay.  
                        <ul class='mb-2'>
                            <li><strong>Desktop/Laptop:</strong> Gamitin ang scroll bar sa ibaba para makita ang ibang bahagi ng table.  
                                Para mas mabilis, I-hold ang <kbd>Shift</kbd><br> habang gumagalaw ang 
                                <i class='bi bi-mouse'></i> scroll bar.</li>
                            <li><strong>Mobile/Tablet:</strong> I-swipe pakaliwa o pakanan gamit ang daliri para makita ang mga nakatagong hanay.</li>
                        </ul>
                    `;


                        let isEnglish = true;

                        translateBtn.addEventListener('click', function () {
                            if (isEnglish) {
                                alertText.innerHTML = tagalogText;
                                translateBtn.innerHTML = `<i class='bi bi-translate fs-6 me-2'></i><span>Translate to English</span>`;
                            } else {
                                alertText.innerHTML = englishText;
                                translateBtn.innerHTML = `<i class='bi bi-translate fs-6 me-2'></i><span>Translate to Tagalog</span>`;
                            }
                            isEnglish = !isEnglish;
                        });
                    });
                    </script>
                    ";
                    // Escalation table (Bootstrap modern, border-collapse, nowrap)
                    echo "
                    <div class='table-responsive shadow-sm table-container'>
                        <table class='table escalation-table table-hover table-bordered align-middle text-nowrap' style='border-collapse: collapse;'>
                            <thead style='background: linear-gradient(90deg, #d70c0c, #a70a0a); color: #fff;'>
                                <tr class='text-center'>
                                    <th scope='col'><i class='bi bi-calendar-event me-1'></i> Start Date</th>
                                    <th scope='col'><i class='bi bi-calendar-x me-1'></i> End Date</th>
                                    <th scope='col'><i class='bi bi-cash-stack me-1'></i> Monthly Rental</th>
                                    <th scope='col'><i class='bi bi-receipt me-1'></i> VAT</th>
                                    <th scope='col'><i class='bi bi-percent me-1'></i> Net of VAT</th>
                                    <th scope='col'><i class='bi bi-pie-chart me-1'></i> VAT %</th>
                                    <th scope='col'><i class='bi bi-cash me-1'></i> WTax Amount</th>
                                    <th scope='col'><i class='bi bi-percent me-1'></i> WTax %</th>
                                    <th scope='col'><i class='bi bi-wallet2 me-1'></i> Amount to Lessor (Monthly)</th>
                                    <th scope='col'><i class='bi bi-graph-up me-1'></i> Escalation %</th>
                                    <th scope='col'><i class='bi bi-currency-exchange me-1'></i> Fixed Amount</th>
                                    <th scope='col'><i class='bi bi-arrow-up-circle me-1'></i> Increase Yearly</th>
                                    <th scope='col'><i class='bi bi-calendar3 me-1'></i> Yearly Amount</th>
                                    <th scope='col'><i class='bi bi-clock-history me-1'></i> Created Date</th>
                                    <th scope='col'><i class='bi bi-gear me-1'></i> Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
                        $userRole = $_SESSION['user_role'] ?? '';
                        // ✅ Added Vpo-Checker condition here
                        $isAmCreator = ($userRole === 'Am-Creator' || $userRole === 'Vpo-Checker'); 
                        
                        foreach ($allRows as $index => $row) {
                            $startDate    = date('Y-m', strtotime($row['start_date']));
                            $endDate      = date('Y-m', strtotime($row['end_date']));
                            $isApproved   = isset($row['status']) && strtolower($row['status']) === 'approved';
                            
                            // Apply read-only styling and attribute if approved
                            $readonlyAttr  = $isApproved ? 'readonly' : '';
                            $readonlyStyle = $isApproved ? 'style="background-color: #e0e0e0;color: gray;"' : '';
                        
                            // end_date should be readonly if not Am-Creator or Vpo-Checker
                            $endDateReadonly = (!$isAmCreator) ? 'readonly' : '';
                            
                            echo "<tr>";
                            echo "<input type='hidden' name='row_id[$index]' value='" . htmlspecialchars($row['id']) . "' />";

                            // Start Date
                            echo "<td><input type='month' name='start_date[$index]' class='form-control form-control-sm start-date inp' 
                                    value='$startDate' min='$effectivityMonth' max='$expiryMonth' readonly $readonlyStyle /></td>";

                            // End Date
                            echo "<td><input type='month' name='end_date[$index]' class='form-control form-control-sm end-date inp' data-index='$index' 
                                    value='$endDate' min='$effectivityMonth' max='$expiryMonth' $endDateReadonly $readonlyStyle /></td>";

                            // Monthly Rental
                            echo "<td><input type='text' name='monthly_rental[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['monthly_rental']) . "' data-original-rental='" . htmlspecialchars($row['monthly_rental']) . "' 
                                    $readonlyAttr $readonlyStyle /></td>";

                            // VAT %
                            echo "<td><input type='text' name='vat_percent[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['vat']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // Net of VAT
                            echo "<td><input type='text' name='net_of_vat[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['net_of_vat']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // VAT
                            echo "<td><input type='text' name='vat[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['vat_percent']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // WTax Amount
                            echo "<td><input type='text' name='wtax[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['wtax']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // WTax %
                            echo "<td><input type='text' name='wtax_percent[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['wtax_percent']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // Amount to Lessor
                            echo "<td><input type='text' name='amount_to_lessor[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['amount_to_lessor']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // Escalation Percent
                            echo "<td>
                                    <input 
                                        type='text'
                                        name='escalation_percent[$index]' 
                                        class='form-control form-control-sm text-end escalation-percent inp'
                                        value='" . htmlspecialchars($row['escalation_percent']) . "'
                                        placeholder='0.0'
                                        maxlength='4'
                                        oninput=\"
                                            this.value = this.value
                                                .replace(/[^0-9.]/g, '')   // allow only numbers + dot
                                                .replace(/(\\..*)\\./g, '$1') // prevent multiple dots
                                                .substring(0,4);            // enforce max length
                                        \"
                                        " . ($isApproved ? "readonly style='background-color:#e0e0e0;'" : "") . " 
                                        $readonlyAttr $readonlyStyle
                                    />
                                </td>";

                            // Fixed Amount
                            echo "<td><input type='text' name='fixed_amount[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['fixed_amount']) . "' $readonlyAttr $readonlyStyle /></td>";

                            // Yearly Increase
                            echo "<td><input type='text' name='yearly_increase[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['increase']) . "' readonly $readonlyStyle /></td>";

                            // Yearly Amount
                            echo "<td><input type='text' name='yearly_amount[$index]' class='form-control form-control-sm text-end inp' 
                                    value='" . htmlspecialchars($row['yearly_amount']) . "' readonly $readonlyStyle /></td>";

                            // Created Date (keep center/left)
                            echo "<td><input type='text' class='form-control form-control-sm text-center inp' 
                                    readonly value='" . date('F d, Y', strtotime($row['created_date'])) . "' $readonlyStyle /></td>";

                            // Delete button
                            echo "<td class='text-center'>";
                            if (!$isApproved && $index === count($allRows) - 1) {
                                echo "<button type='button' class='btn btn-sm btn-outline-danger delete-row'>
                                        <i class='bi bi-trash'></i>
                                      </button>";
                            }
                            echo "</td>";
                            

                            echo "</tr>";


                        }
                        echo"<div id='validationMessage' style='margin-top:10px;'></div>";
                    echo "</tbody></table>";
                    
                    // ✅ Added Vpo-Checker condition here
                    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'Am-Creator' || $_SESSION['user_role'] === 'Vpo-Checker')) {
                        echo "
                            <form method='POST'>
                                <div class='esc_buttons my-3'>
                                    <button type='button' id='manuallyAddRow' class='custom-btn'>
                                        <i class='bi bi-plus-lg'></i> Add Row
                                    </button>

                                    <button type='submit' name='update_escalation' id='updateEsc' class='custom-btn'>
                                        <i class='bi bi-save2'></i> Update/Save
                                    </button>
                                </div>
                            </form>
                        ";
                    }

                    echo "</div>";

                } else {
                    echo "<p>No escalation records found for this contract.</p>";
                }
            } else {
                echo "<p>Contract not found.</p>";
            }
        }
    ?>
    </form>
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
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
function validateEndDates() {
    const rows = document.querySelectorAll('.escalation-table tbody tr');
    const addRowBtn = document.getElementById("manuallyAddRow");
    const updateBtn = document.getElementById("updateEsc");
    const messageBox = document.getElementById("validationMessage"); 

    let hasError = false;
    const seenEndDates = new Set();

    rows.forEach((row, index) => {
        const startDateInput = row.querySelector('.start-date');
        const endDateInput   = row.querySelector('.end-date');

        if (!endDateInput || !endDateInput.value) return;

        const endDateVal = endDateInput.value;

        // ✅ duplicate check
        if (seenEndDates.has(endDateVal)) {
            endDateInput.style.backgroundColor = "#f8d7da";
            hasError = true;
        } else {
            seenEndDates.add(endDateVal);
        }

        // ✅ same row (end < start)
        if (startDateInput && startDateInput.value) {
            const [sy, sm] = startDateInput.value.split("-");
            const [ey, em] = endDateVal.split("-");
            const startYM = parseInt(sy + sm.padStart(2, "0"));
            const endYM   = parseInt(ey + em.padStart(2, "0"));
            if (endYM < startYM) {
                startDateInput.style.backgroundColor = "#f8d7da";
                endDateInput.style.backgroundColor = "#f8d7da";
                hasError = true;
            }
        }

        // ✅ overlap with previous row
        if (index > 0 && startDateInput && startDateInput.value) {
            const prevEndInput = rows[index - 1].querySelector('.end-date');
            if (prevEndInput && prevEndInput.value) {
                const [py, pm] = prevEndInput.value.split("-");
                const [cy, cm] = startDateInput.value.split("-");
                const prevYM = parseInt(py + pm.padStart(2, "0"));
                const currYM = parseInt(cy + cm.padStart(2, "0"));
                if (currYM <= prevYM) {
                    startDateInput.style.backgroundColor = "#f8d7da";
                    prevEndInput.style.backgroundColor = "#f8d7da";
                    hasError = true;
                }
            }
        }
    });

    // ✅ expiry_date logic
    const expiryInput = document.querySelector("input[name='expiry_date']");
    if (expiryInput && expiryInput.value && rows.length > 0) {
        const expiry = new Date(expiryInput.value);
        const expiryYM = parseInt(
            expiry.getFullYear() + String(expiry.getMonth() + 1).padStart(2, "0")
        );

        const lastRow = rows[rows.length - 1];
        const lastEndInput = lastRow.querySelector('.end-date');
        if (lastEndInput && lastEndInput.value) {
            const [ly, lm] = lastEndInput.value.split("-");
            const lastYM = parseInt(ly + lm.padStart(2, "0"));

            if (lastYM > expiryYM) {
                lastEndInput.style.backgroundColor = "#f8d7da";
                hasError = true;
                if (addRowBtn) addRowBtn.style.display = "none";
                if (updateBtn) updateBtn.style.display = "none";
            } else if (lastYM === expiryYM) {
                if (!hasError) {
                    if (addRowBtn) addRowBtn.style.display = "none";
                    if (updateBtn) updateBtn.style.display = "inline-block";
                }
            } else {
                if (!hasError) {
                    if (addRowBtn) addRowBtn.style.display = "inline-block";
                    if (updateBtn) updateBtn.style.display = "none";
                }
            }
        }
    }

    // ✅ show message
    if (messageBox) {
        if (hasError) {
            messageBox.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ⚠️ Please delete the highlighted row (use the delete button in the last column).
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            messageBox.style.display = "block";
            if (addRowBtn) addRowBtn.style.display = "none";
            if (updateBtn) updateBtn.style.display = "none";
        } else {
            messageBox.innerHTML = "";
            messageBox.style.display = "none";
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const contractExpiryDate = "<?= date('Y-m', strtotime($contractInfo['expiry_date'])) ?>";
    const addRowBtn = document.getElementById("manuallyAddRow");

    addRowBtn.addEventListener('click', addNewRow);

    document.querySelector('.escalation-table').addEventListener('click', function (event) {
        const deleteButton = event.target.closest('.delete-row');
        if (!deleteButton) return;

        const row = deleteButton.closest('tr');
        const rows = Array.from(document.querySelectorAll('.escalation-table tbody tr'));
        const isLastRow = row === rows[rows.length - 1];

        if (!isLastRow) {
            alert('You can only delete the last row.');
            return;
        }

        // Get the row ID
        const rowIdInput = row.querySelector("input[name^='row_id']");
        if (rowIdInput && rowIdInput.value) {
            const deletedIdsInput = document.getElementById('deleted_ids');
            let currentDeleted = deletedIdsInput.value ? deletedIdsInput.value.split(',') : [];

            if (!currentDeleted.includes(rowIdInput.value)) {
                currentDeleted.push(rowIdInput.value);
            }

            deletedIdsInput.value = currentDeleted.join(',');
        }

        row.remove();
        // Call your existing functions if needed here:
        updateDeleteButtons();
        
        attachEndDateListeners();
        
    });

    
    attachEndDateListeners();

    function addNewRow() {
    const tableBody = document.querySelector('.escalation-table tbody');
    const rows = tableBody.querySelectorAll('tr');
    const lastRow = rows[rows.length - 1];

    let lastEndDate = null;
    if (lastRow) {
        const lastEndDateInput = lastRow.querySelector('.end-date');
        if (lastEndDateInput && lastEndDateInput.value) {
            lastEndDate = new Date(lastEndDateInput.value + "-01");
        }
    }

    if (!lastEndDate) {
        lastEndDate = new Date(contractExpiryDate + "-01");
    }

    const newStartDate = new Date(lastEndDate);
    newStartDate.setMonth(newStartDate.getMonth() + 1);

    const newEndDate = new Date(newStartDate);
    newEndDate.setMonth(newEndDate.getMonth() + 11);

    const contractExpiry = new Date(contractExpiryDate + "-01");
    if (newEndDate > contractExpiry) {
        newEndDate.setFullYear(contractExpiry.getFullYear(), contractExpiry.getMonth(), contractExpiry.getDate());
    }

    const newIndex = rows.length;
    const prevInputs = lastRow.querySelectorAll('input, select');
    const newRow = document.createElement('tr');

    let rowHtml = "";

    // Get previous escalation percent
    const prevEscalationSelect = lastRow.querySelector("input[name^='escalation_percent']");
    const prevEscalationPercent = prevEscalationSelect ? parseInt(prevEscalationSelect.value) : null;

    prevInputs.forEach(input => {
        const match = input.name.match(/^([^\[]+)\[\d+\]$/);
        if (!match) return;

        const baseName = match[1];

        if (baseName === 'row_id') return; // skip hidden ID

        if (baseName === 'start_date') {
            rowHtml += `
                <td>
                    <input type="month" name="start_date[${newIndex}]" 
                           class="form-control form-control-sm start-date inp" 
                           value="${newStartDate.toISOString().slice(0, 7)}" readonly>
                </td>`;
            return;
        }

        if (baseName === 'end_date') {
            rowHtml += `
                <td>
                    <input type="month" name="end_date[${newIndex}]" 
                           class="form-control form-control-sm end-date inp" 
                           data-index="${newIndex}" 
                           value="${newEndDate.toISOString().slice(0, 7)}">
                </td>`;
            return;
        }

        if (input.tagName === 'SELECT') {
            rowHtml += `
                <td>
                    <select name="${baseName}[${newIndex}]" class="form-select form-select-sm inp">
                        ${[...Array(11).keys()].map(i =>
                            `<option value="${i}" ${i == prevEscalationPercent ? "selected" : ""}>${i}%</option>`
                        ).join("")}
                    </select>
                </td>`;
        } else if (input.hasAttribute('readonly')) {
            rowHtml += `
                <td>
                    <input type="text" name="${baseName}[${newIndex}]" 
                           class="form-control form-control-sm text-end inp" 
                           value="${input.value}" readonly>
                </td>`;
        } else {
            rowHtml += `
                <td>
                    <input type="text" name="${baseName}[${newIndex}]" 
                           class="form-control form-control-sm text-end inp" 
                           value="${input.value}">
                </td>`;
        }
    });

    rowHtml += `
        <td>
            <input type="text" class="form-control form-control-sm text-center inp" readonly 
                   value="<?= date('F d, Y') ?>">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger delete-row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    newRow.innerHTML = rowHtml;
    tableBody.appendChild(newRow);

    // reattach listeners
    updateDeleteButtons();
    
    attachEndDateListeners();
    attachEscalationListeners();
    handleFixedAmountChange();
    handleEscalationChange();
    recomputeRow();
    validateEndDates();
}

    function updateDeleteButtons() {
        const rows = document.querySelectorAll('.escalation-table tbody tr');
        rows.forEach((row, index) => {
            const actionCell = row.querySelector('td:last-child');
            const existingBtn = actionCell.querySelector('.delete-row');

            if (index === rows.length - 1) {
                if (!existingBtn) {
                    actionCell.innerHTML = `<button type='button' class='btn btn-sm btn-outline-danger delete-row'>
                                        <i class='bi bi-trash'></i>
                                    </button>`;
                }
            } else if (existingBtn) {
                existingBtn.remove();
            }
        });
        validateEndDates();

    }

    function attachEndDateListeners() {
        const endDateInputs = document.querySelectorAll('.end-date');
        endDateInputs.forEach((input, index) => {
            input.removeEventListener('change', endDateChangeHandler);
            input.addEventListener('change', endDateChangeHandler);
        });
        validateEndDates();
    }

    function endDateChangeHandler(event) {
        const changedInput = event.target;
        const changedRow = changedInput.closest('tr');
        const allRows = Array.from(document.querySelectorAll('.escalation-table tbody tr'));
        const changedIndex = allRows.indexOf(changedRow);

        let prevEndDate = new Date(changedInput.value + "-01");

        for (let i = changedIndex + 1; i < allRows.length; i++) {
            const row = allRows[i];
            const startDateInput = row.querySelector('.start-date');
            const endDateInput = row.querySelector('.end-date');

            const newStartDate = new Date(prevEndDate);
            newStartDate.setMonth(newStartDate.getMonth() + 1);

            const newEndDate = new Date(newStartDate);
            newEndDate.setMonth(newEndDate.getMonth() + 11);

            startDateInput.value = newStartDate.toISOString().slice(0, 7);
            endDateInput.value = newEndDate.toISOString().slice(0, 7);

            prevEndDate = newEndDate;
        }
        validateEndDates();
        
    }
});

function attachEscalationListeners() {
    document.querySelectorAll("input[name^='escalation_percent']").forEach(input => {
        input.removeEventListener('change', handleEscalationChange);
        input.addEventListener('change', handleEscalationChange);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Attach event listeners to all fixed_amount inputs
    document.querySelectorAll("input[name^='fixed_amount']").forEach(input => {
        input.addEventListener('input', handleFixedAmountChange);
    });

    // Attach to escalation selects
    document.querySelectorAll("input[name^='escalation_percent']").forEach(select => {
        select.addEventListener('change', handleEscalationChange);
    });
});
function handleFixedAmountChange(event) {
    const changedInput = event.target;
    const changedRow = changedInput.closest('tr');
    const allRows = Array.from(document.querySelectorAll('.escalation-table tbody tr'));
    const changedIndex = allRows.indexOf(changedRow);

    if (changedInput.value.trim() === '') {
            changedInput.value = '0.00';
        }
    // Get base rental: from dataset or input
    const rentalInput = changedRow.querySelector("input[name^='monthly_rental']");
    let baseRental = parseFloat(rentalInput.dataset.originalRental || rentalInput.value.replace(/,/g, '')) || 0;

    // Recompute current row with base rental
    let newRental = recomputeRow(changedRow, baseRental);

    // Recompute all rows below
    for (let i = changedIndex + 1; i < allRows.length; i++) {
        const row = allRows[i];
        const prevRow = allRows[i - 1];
        const prevRentalInput = prevRow.querySelector("input[name^='monthly_rental']");
        const prevRental = parseFloat(prevRentalInput?.value.replace(/,/g, '')) || 0;

        newRental = recomputeRow(row, prevRental);
    }
}
document.addEventListener('input', function (event) {
    if (event.target.matches("input[name^='fixed_amount']")) {
        const changedInput = event.target;
        const changedRow = changedInput.closest('tr');
        const allRows = Array.from(document.querySelectorAll('.escalation-table tbody tr'));
        const changedIndex = allRows.indexOf(changedRow);

        if (changedInput.value.trim() === '') {
            changedInput.value = '0.00';
        }

        if (changedIndex > 0) {
            const prevRow = allRows[changedIndex - 1];
            const prevRental = parseFloat(prevRow.querySelector("input[name^='monthly_rental']")?.value.replace(/,/g, '')) || 0;

            // Recompute this row using previous row's rental
            const newRental = recomputeRow(changedRow, prevRental);

            // Recompute all following rows using updated value
            let base = newRental;
            for (let i = changedIndex + 1; i < allRows.length; i++) {
                base = recomputeRow(allRows[i], base);
            }
        }
    }
});

function handleEscalationChange(event) {
    const changedSelect = event.target;
    const changedRow = changedSelect.closest('tr');
    const allRows = Array.from(document.querySelectorAll('.escalation-table tbody tr'));
    const changedIndex = allRows.indexOf(changedRow);

    const percent = parseFloat(changedSelect.value) || 0;

    // Get the previous row's rental as base
    let baseRental;
    if (changedIndex === 0) {
        // If first row, get its original rental
        const rentalInput = changedRow.querySelector("input[name^='monthly_rental']");
        baseRental = parseFloat(rentalInput.dataset.originalRental || rentalInput.value.replace(/,/g, '')) || 0;
    } else {
        const prevRow = allRows[changedIndex - 1];
        const prevRentalInput = prevRow.querySelector("input[name^='monthly_rental']");
        baseRental = parseFloat(prevRentalInput?.value.replace(/,/g, '')) || 0;
    }

    // Recompute current row using baseRental and new percent
    let newRental = recomputeRow(changedRow, baseRental);

    // Update all rows below with the new values
    for (let i = changedIndex + 1; i < allRows.length; i++) {
        const row = allRows[i];
        newRental = recomputeRow(row, newRental);
    }
}


// recomputeRow now only takes the row and the base rental from previous row
function recomputeRow(row, baseRental) {
    const vatType = document.getElementById('vat_type')?.value;
    const wtaxType = document.getElementById('wtax_type')?.value;
    const vatRate = 12;

    const rentalInput = row.querySelector("input[name^='monthly_rental']");
    const increaseInput = row.querySelector("input[name^='yearly_increase']");
    const yearlyAmountInput = row.querySelector("input[name^='yearly_amount']");
    const netOfVatInput = row.querySelector("input[name^='net_of_vat']");
    const vatAmountInput = row.querySelector("input[name^='vat']");
    const vatPercentInput = row.querySelector("input[name^='vat_percent']");
    const wtaxAmountInput = row.querySelector("input[name^='wtax']");
    const wtaxPercentInput = row.querySelector("input[name^='wtax_percent']");
    const amountToLessorInput = row.querySelector("input[name^='amount_to_lessor']");
    const fixedAmountInput = row.querySelector("input[name^='fixed_amount']");
    const percentSelect = row.querySelector("input[name^='escalation_percent']");

    let fixedAmount = parseFloat(fixedAmountInput?.value.replace(/,/g, '')) || 0;
    let percent = parseFloat(percentSelect?.value) || 0;

    // Calculate escalated rental for this row based on baseRental and own fixed amount/percent
    const escalatedRental = fixedAmount > 0
    ? baseRental + fixedAmount
    : (percent === 0 ? baseRental : baseRental + (baseRental * percent / 100));


    if (rentalInput) rentalInput.value = escalatedRental.toFixed(2);

    const increase = (escalatedRental - baseRental) * 12;
    if (increaseInput) increaseInput.value = increase.toFixed(2);
    if (yearlyAmountInput) yearlyAmountInput.value = (escalatedRental * 12).toFixed(2);

    // Compute VAT and Net of VAT
    let vatAmount = 0;
    let netOfVat = escalatedRental;

    if (vatType === 'Vatable') {
            netOfVat = escalatedRental / (1 + vatRate / 100);
            vatAmount = escalatedRental - netOfVat;
    }

    if (vatPercentInput) {
        vatPercentInput.value = (vatType === 'Vatable') ? vatRate.toFixed(2) : '0.00';
    }

    if (vatAmountInput) vatAmountInput.value = vatAmount.toFixed(2);
    if (netOfVatInput) netOfVatInput.value = netOfVat.toFixed(2);

    // Compute WTax
    let wtaxPercent = parseFloat(wtaxPercentInput?.value) || 0;
    let amountToLessor = 0;
    let wtaxAmount = 0;

    // Vatable logic
    if (wtaxType === 'net_wtax' && vatType === 'Vatable') {
        wtaxAmount = (netOfVat * wtaxPercent) / 100;
        amountToLessor = netOfVat;
    }
    if (wtaxType === 'less_wtax' && vatType === 'Vatable') {
        wtaxAmount = (netOfVat * wtaxPercent) / 100;
        amountToLessor = netOfVat - wtaxAmount;
    }

    // Non-Vatable logic
    if (wtaxType === 'less_wtax' && vatType === 'Non-Vatable') {
        wtaxAmount = (netOfVat * wtaxPercent) / 100;
        amountToLessor = netOfVat - wtaxAmount;
    }
    if (wtaxType === 'net_wtax' && vatType === 'Non-Vatable') {
        const wtaxAmountComp = (netOfVat / 0.95) / 100;
        wtaxAmount = wtaxAmountComp * wtaxPercent;
        amountToLessor = netOfVat;
    }

    // Vat Exempt logic
    if (wtaxType === 'less_wtax' && vatType === 'Vat Exempt') {
        wtaxAmount = (netOfVat * wtaxPercent) / 100;
        amountToLessor = netOfVat - wtaxAmount;
    }
    if (wtaxType === 'net_wtax' && vatType === 'Vat Exempt') {
        const wtaxAmountComp = (netOfVat / 0.95) / 100;
        wtaxAmount = wtaxAmountComp * wtaxPercent;
        amountToLessor = netOfVat;
    }

    if (wtaxAmountInput) wtaxAmountInput.value = wtaxAmount.toFixed(2);
    if (amountToLessorInput) amountToLessorInput.value = amountToLessor.toFixed(2);

    return escalatedRental;
    
}

</script>

</body>
</html>