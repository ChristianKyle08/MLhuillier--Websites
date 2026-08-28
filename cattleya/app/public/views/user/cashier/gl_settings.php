<?php

require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';
// 2. Load Composer autoloader (Dynamically targets /var/www/html/vendor/autoload.php)
require_once dirname(__DIR__, 5) . '/vendor/autoload.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_id    = $_SESSION['user_id'];
$user_name  = $_SESSION['user_name'];

// --- GL CODE CONTROLLER ROUTING SYSTEM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action command processing.'];
    $user_session_name = $_SESSION['user_name'] ?? 'System Encoder';

    try {
        // --- 1. CSV/XLSX Bulk Import Action Method ---
        if ($_POST['action'] === 'import') {
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload failed or no file was selected.");
            }

            $originalTmpPath = $_FILES['import_file']['tmp_name'];
            $fileName = $_FILES['import_file']['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validate accepted extensions
            if (!in_array($fileExt, ['csv', 'xlsx'])) {
                throw new Exception("Invalid file extension. Please upload a .csv or .xlsx file.");
            }

            // FIX: Securely move the uploaded file out of the raw temporary directory to satisfy server security policies
            $fileTmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gl_import_' . md5(time() . $fileName) . '.' . $fileExt;
            if (!move_uploaded_file($originalTmpPath, $fileTmpPath)) {
                throw new Exception("Server engine failed to process the uploaded file securely.");
            }

            // Safe guard and logic for XLSX parsing using PhpSpreadsheet
            if ($fileExt === 'xlsx') {
                if (!class_exists('ZipArchive')) {
                    @unlink($fileTmpPath);
            
                    throw new Exception(
                        "PHP Zip extension is missing. Please install php-zip to import XLSX files."
                    );
                }
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();
                
                $importedCount = 0;
                $pdo->beginTransaction();
                
                try {
                    $stmtImport = $pdo->prepare("
                        INSERT INTO gl_code (gl_code, gl_description, gl_type, gl_category, add_by, add_date, status) 
                        VALUES (?, ?, ?, ?, ?, CURRENT_DATE(), 'Active')
                        ON DUPLICATE KEY UPDATE 
                            gl_description = VALUES(gl_description),
                            gl_type = VALUES(gl_type),
                            gl_category = VALUES(gl_category),
                            status = 'Active'
                    ");

                    // Start from index 1 to skip the first row (headers)
                    for ($i = 1; $i < count($sheetData); $i++) {
                        $data = $sheetData[$i];
                        
                        // Proceed only if row has at least 3 columns
                        if (is_array($data) && count($data) >= 3) {
                            // Extract only the first 3 columns, discarding the 4th/last column
                            $gl_code = trim((string)$data[0]);
                            $gl_desc = trim((string)$data[1]);
                            $gl_type = trim((string)$data[2]);

                            // Add gl_category conditional logic
                            $firstDigit = (int)substr($gl_code, 0, 1);
                            $gl_category = '';
                            if (in_array($firstDigit, [1, 2, 3])) {
                                $gl_category = "Balance Sheet (B/S)";
                            } elseif ($firstDigit >= 4) {
                                $gl_category = "Profit and Loss (P&L)";
                            }

                            if (!empty($gl_code) && !empty($gl_desc)) {
                                $stmtImport->execute([$gl_code, $gl_desc, $gl_type, $gl_category, $user_session_name]);
                                $importedCount++;
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $response = ['success' => true, 'message' => "Successfully imported/updated $importedCount GL Accounts via XLSX."];
                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    @unlink($fileTmpPath); // Cleanup secure temp file on error
                    throw new Exception("XLSX Import failed during database execution: " . $ex->getMessage());
                }

                @unlink($fileTmpPath); // Cleanup secure temp file on success
                echo json_encode($response);
                exit;
            }

            // Proceed with native CSV parsing
            if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                // Skip the first row (headers)
                fgetcsv($handle, 1000, ","); 
                
                $importedCount = 0;
                $pdo->beginTransaction();
                
                try {
                    // Use ON DUPLICATE KEY to gracefully update existing records while adding new ones
                    $stmtImport = $pdo->prepare("
                        INSERT INTO gl_code (gl_code, gl_description, gl_type, gl_category, add_by, add_date, status) 
                        VALUES (?, ?, ?, ?, ?, CURRENT_DATE(), 'Active')
                        ON DUPLICATE KEY UPDATE 
                            gl_description = VALUES(gl_description),
                            gl_type = VALUES(gl_type),
                            gl_category = VALUES(gl_category),
                            status = 'Active'
                    ");

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Ensure we have at least the first 3 columns, implicitly ignoring column 4 (index 3) and beyond
                        if (count($data) >= 3) {
                            // Extract only the first 3 columns
                            $gl_code = trim($data[0]);
                            $gl_desc = trim($data[1]);
                            $gl_type = trim($data[2]);

                            // Add gl_category conditional logic
                            $firstDigit = (int)substr($gl_code, 0, 1);
                            $gl_category = '';
                            if (in_array($firstDigit, [1, 2, 3])) {
                                $gl_category = "Balance Sheet (B/S)";
                            } elseif ($firstDigit >= 4) {
                                $gl_category = "Profit and Loss (P&L)";
                            }

                            if (!empty($gl_code) && !empty($gl_desc)) {
                                $stmtImport->execute([$gl_code, $gl_desc, $gl_type, $gl_category, $user_session_name]);
                                $importedCount++;
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $response = ['success' => true, 'message' => "Successfully imported/updated $importedCount GL Accounts via CSV."];
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    @unlink($fileTmpPath); // Cleanup secure temp file
                    throw new Exception("Import failed during database execution: " . $ex->getMessage());
                }
                fclose($handle);
            } else {
                @unlink($fileTmpPath); // Cleanup secure temp file
                throw new Exception("System could not read the uploaded CSV file.");
            }
            
            @unlink($fileTmpPath); // Cleanup secure temp file on success
            echo json_encode($response);
            exit;
        }

        // --- 2. Create / Insert Action Method ---
        if ($_POST['action'] === 'insert') {
            $gl_code = trim($_POST['gl_code'] ?? '');
            $gl_description = trim($_POST['gl_description'] ?? '');
            $gl_type = trim($_POST['gl_type'] ?? '');

            if (empty($gl_code) || empty($gl_description)) {
                throw new Exception("All parameters (GL Code & Description) are explicitly required to proceed.");
            }

            // Add gl_category conditional logic
            $firstDigit = (int)substr($gl_code, 0, 1);
            $gl_category = '';
            if (in_array($firstDigit, [1, 2, 3])) {
                $gl_category = "Balance Sheet (B/S)";
            } elseif ($firstDigit >= 4) {
                $gl_category = "Profit and Loss (P&L)";
            }

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM gl_code WHERE gl_code = ?");
            $stmtCheck->execute([$gl_code]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("GL Account Code [{$gl_code}] already exists within the ledger registry architecture.");
            }

            $stmt = $pdo->prepare("INSERT INTO gl_code (gl_code, gl_description, gl_type, gl_category, add_by, add_date, status) VALUES (?, ?, ?, ?, ?, CURRENT_DATE(), 'Active')");
            if ($stmt->execute([$gl_code, $gl_description, $gl_type, $gl_category, $user_session_name])) {
                $response = ['success' => true, 'message' => "GL Account [{$gl_code}] has been registered and verified successfully."];
            }
        }

        // --- 3. Update Action Method ---
        if ($_POST['action'] === 'update') {
            $gl_code = trim($_POST['gl_code'] ?? '');
            $gl_description = trim($_POST['gl_description'] ?? '');
            $gl_type = trim($_POST['gl_type'] ?? '');
            
            // MODIFIED: Fetch original key mapping to accommodate changes to the GL Code value itself
            $original_gl_code = trim($_POST['original_gl_code'] ?? $gl_code);

            if (empty($gl_code) || empty($gl_description)) {
                throw new Exception("Required structural configuration data is missing for record update updates.");
            }

            // Add gl_category conditional logic
            $firstDigit = (int)substr($gl_code, 0, 1);
            $gl_category = '';
            if (in_array($firstDigit, [1, 2, 3])) {
                $gl_category = "Balance Sheet (B/S)";
            } elseif ($firstDigit >= 4) {
                $gl_category = "Profit and Loss (P&L)";
            }

            // MODIFIED: Block conflict crashes if user changes GL Code to another code that is already taken
            if ($gl_code !== $original_gl_code) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM gl_code WHERE gl_code = ?");
                $stmtCheck->execute([$gl_code]);
                if ($stmtCheck->fetchColumn() > 0) {
                    throw new Exception("GL Account Code [{$gl_code}] already exists within the ledger registry architecture.");
                }
            }

            // MODIFIED: Bind values dynamically to ensure both updated attributes and historical target match correctly
            $stmt = $pdo->prepare("UPDATE gl_code SET gl_code = ?, gl_description = ?, gl_type = ?, gl_category = ? WHERE gl_code = ?");
            if ($stmt->execute([$gl_code, $gl_description, $gl_type, $gl_category, $original_gl_code])) {
                $response = ['success' => true, 'message' => "GL Account [{$gl_code}] structural layout description modified successfully."];
            }
        }

        // --- 4. Explicit Inactive Action Method ---
        if ($_POST['action'] === 'set_inactive') {
            $gl_code = trim($_POST['gl_code'] ?? '');
            if (empty($gl_code)) throw new Exception("Target system identification token missing. Cannot adjust entry state mapping.");

            $stmt = $pdo->prepare("UPDATE gl_code SET status = 'Inactive' WHERE gl_code = ?");
            if ($stmt->execute([$gl_code])) {
                $response = ['success' => true, 'message' => "GL Account entry code [{$gl_code}] state updated from Active to Inactive safely."];
            }
        }

        // --- 5. Explicit Active Action Method ---
        if ($_POST['action'] === 'set_active') {
            $gl_code = trim($_POST['gl_code'] ?? '');
            if (empty($gl_code)) throw new Exception("Target system identification token missing. Cannot adjust entry state mapping.");

            $stmt = $pdo->prepare("UPDATE gl_code SET status = 'Active' WHERE gl_code = ?");
            if ($stmt->execute([$gl_code])) {
                $response = ['success' => true, 'message' => "GL Account entry code [{$gl_code}] state updated from Inactive to Active safely."];
            }
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

// Fetch Active and Inactive Accounts for Display Viewports
$glAccounts = [];
try {
    $stmtFetch = $pdo->prepare("SELECT * FROM gl_code ORDER BY gl_code ASC");
    $stmtFetch->execute();
    $glAccounts = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("GL Table Fetch Error: " . $e->getMessage());
}

$glActiveCount = 0;
$glInactiveCount = 0;
foreach ($glAccounts as $acct) {
    if (($acct['status'] ?? 'Active') === 'Active') {
        $glActiveCount++;
    } else {
        $glInactiveCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>General Ledger Settings Matrix & Configuration Dashboard</title>
    
    <meta name="description" content="Optimize enterprise chart of accounts, adjust mapping configuration layers, manage structural accounts indexes safely.">
    <meta name="keywords" content="General Ledger, Enterprise Chart of Accounts, Asset System Settings, Account Matrix Mapping">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1"> 
    
    <meta property="og:type" content="website">
    <meta property="og:title" content="General Ledger Settings Matrix Dashboard">
    <meta property="og:description" content="Optimize enterprise chart of accounts, adjust mapping configuration layers, manage structural accounts indexes safely.">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="General Ledger Settings Matrix Dashboard">
    <meta name="twitter:description" content="Optimize enterprise chart of accounts, adjust mapping configuration layers, manage structural accounts indexes safely.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --c-teal: #1c5f66; 
            --c-teal-dark: #114146;
            --c-lime: #a6ce39; 
            --bg-main: #f8fafc; 
        }
        body {
            background-color: var(--bg-main);
            font-family: 'Inter', sans-serif;
            background-image:
                radial-gradient(circle at 6% 4%, rgba(28, 95, 102, 0.06), transparent 30%),
                radial-gradient(circle at 96% 12%, rgba(166, 206, 57, 0.08), transparent 26%),
                radial-gradient(circle at 50% 100%, rgba(28, 95, 102, 0.04), transparent 40%);
            background-attachment: fixed;
        }
        .font-ledger { font-family: 'JetBrains Mono', monospace; font-feature-settings: "tnum"; }
        .btn-teal {
            background-color: var(--c-teal);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-teal:hover {
            background-color: var(--c-teal-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -10px rgba(28, 95, 102, 0.4);
        }
        .text-teal { color: var(--c-teal); }
        .bg-lime-accent { background-color: var(--c-lime); }
        
        .bg-brand-600 { background-color: var(--c-teal); }
        .bg-brand-500 { background-color: #22737c; }
        .bg-brand-50 { background-color: #f0f7f8; }
        .text-brand-600 { color: var(--c-teal); }
        .border-brand-500 { border-color: var(--c-teal); }
        .focus\:border-brand-500:focus { border-color: var(--c-teal); }
        .focus\:ring-brand-500\/10:focus { --tw-ring-color: rgba(28, 95, 102, 0.1); }

        #glTableBody tr:nth-child(even) { background-color: rgba(248, 250, 252, 0.55); }

        .page-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .page-btn:not(:disabled):not(.page-btn-active):hover {
            border-color: #e2e8f0;
            box-shadow: 0 4px 10px -4px rgba(15, 23, 42, 0.12);
        }
        .page-btn-active {
            background: linear-gradient(135deg, var(--c-teal) 0%, var(--c-teal-dark) 100%);
            box-shadow: 0 8px 16px -8px rgba(28, 95, 102, 0.5);
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="text-slate-800 antialiased overflow-x-hidden bg-radial from-slate-50 to-slate-100">
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>
<div id="toastContainer" class="fixed top-5 right-5 z-[60] flex flex-col gap-3 pointer-events-none"></div>
<div id="sidebarOverlay" onclick="toggleSidebar(false)" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300 lg:hidden"></div>

<div class="flex-1 flex flex-col min-w-0 transition-all duration-500 bg-slate-50/30">
    
    <header class="lg:hidden flex items-center justify-between p-4 bg-white/80 backdrop-blur-md border-b border-slate-200/60 sticky top-0 z-30 shadow-sm">
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar(true)" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-700 hover:bg-slate-100 border border-slate-200/50 transition-all active:scale-95 shadow-sm">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            <h2 class="text-sm font-black tracking-tight text-slate-900">GL Configuration</h2>
        </div>
    </header>

    <main class="p-4 md:p-8 lg:p-10 space-y-8 max-w-7xl w-full mx-auto flex-1">
        
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white p-6 md:p-8 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-200/60 transition-all duration-300 hover:shadow-[0_20px_60px_-15px_rgba(0,0,0,0.08)] relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-brand-50 to-transparent rounded-full blur-3xl opacity-60 pointer-events-none -translate-y-1/2 translate-x-1/3"></div>

            <div class="space-y-2 relative z-10">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-600/80 pl-0.5 flex items-center gap-2">
                    <i class="fa-solid fa-building-columns text-[9px]"></i> Finance &middot; Chart of Accounts
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-3 h-8 rounded-full bg-gradient-to-b from-brand-400 to-brand-600 shadow-sm shadow-brand-500/40 animate-pulse"></div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-600 bg-clip-text text-transparent">GL Configuration Matrix</h1>
                </div>
                <p class="text-xs text-slate-500 font-medium max-w-xl leading-relaxed">Manage Chart of Accounts, system maps, and mass records import configurations seamlessly.</p>
            </div>
            
            <div class="flex gap-4 bg-slate-50/80 backdrop-blur-sm p-3.5 rounded-2xl border border-slate-200/80 w-full md:w-auto justify-between md:justify-start shadow-inner overflow-x-auto relative z-10">
                <div class="px-3 sm:px-5 py-1 flex-shrink-0">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold whitespace-nowrap">Total Entries</p>
                    <p id="statTotal" class="text-xl md:text-2xl font-black text-slate-900 tracking-tight mt-0.5 font-ledger"><?= count($glAccounts) ?></p>
                </div>
                <div class="border-r border-slate-200/80 my-1 flex-shrink-0"></div>
                <div class="px-3 sm:px-5 py-1 flex-shrink-0">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold whitespace-nowrap">Active</p>
                    <p class="text-xl md:text-2xl font-black text-emerald-600 tracking-tight mt-0.5 font-ledger"><?= $glActiveCount ?></p>
                </div>
                <div class="border-r border-slate-200/80 my-1 flex-shrink-0"></div>
                <div class="px-3 sm:px-5 py-1 flex-shrink-0">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold whitespace-nowrap">Inactive</p>
                    <p class="text-xl md:text-2xl font-black text-rose-500 tracking-tight mt-0.5 font-ledger"><?= $glInactiveCount ?></p>
                </div>
                <div class="border-r border-slate-200/80 my-1 flex-shrink-0"></div>
                <div class="px-3 sm:px-5 py-1 flex-shrink-0">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold whitespace-nowrap">System Status</p>
                    <p class="text-xs font-bold text-brand-700 bg-brand-100/50 px-2.5 py-1 rounded-lg mt-1 inline-flex items-center gap-1.5 border border-brand-200/50 whitespace-nowrap shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-ping absolute inline-flex opacity-75"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500 relative inline-flex"></span> Operational
                    </p>
                </div>
            </div>
        </section>

        <section class="flex flex-col sm:flex-row gap-4 items-center justify-between w-full">
            <div class="relative w-full sm:max-w-xs md:max-w-md group">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors duration-300">
                    <i class="fa-solid fa-magnifying-glass text-xs md:text-sm"></i>
                </span>
                <input type="text" id="tableSearch" placeholder="Search code, description, or type..." class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200/80 rounded-2xl focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 text-xs md:text-sm font-medium tracking-wide shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)] transition-all duration-300 placeholder:text-slate-400 hover:border-slate-300" onkeyup="filterGLTable()">
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <label class="flex items-center justify-center gap-2.5 flex-1 sm:flex-initial px-5 py-3 border border-emerald-200 bg-emerald-50/50 text-emerald-800 rounded-2xl cursor-pointer hover:bg-emerald-600 hover:text-white hover:border-emerald-600 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 font-bold text-xs uppercase tracking-wider shadow-sm active:scale-[0.98]">
                    <i class="fa-solid fa-file-excel text-emerald-600 text-sm drop-shadow-sm group-hover:text-white"></i>
                    <span class="whitespace-nowrap">Import CSV/XLSX</span>
                    <input type="file" id="importFile" accept=".csv, .xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="hidden" onchange="handleFileImport(event)">
                </label>

                <button onclick="openGLModal(false)" class="group bg-slate-900 hover:bg-brand-600 text-white flex items-center justify-center gap-2 flex-1 sm:flex-initial px-6 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider border border-slate-900 hover:border-brand-600 shadow-md hover:shadow-xl hover:shadow-brand-500/10 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200">
                    <i class="fa-solid fa-plus text-white text-xs"></i>
                    <span class="whitespace-nowrap">Add Code</span>
                </button>
            </div>
        </section>

        <section class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-200/60 overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[850px] md:min-w-full">
                    <thead>
                        <tr class="bg-gradient-to-b from-slate-50/80 to-slate-100/50 border-b border-slate-200/80 backdrop-blur-sm">
                            <th class="py-4 px-6 text-[10px] font-bold tracking-widest text-slate-500 uppercase w-[15%]">
                                <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-hashtag text-[9px] text-slate-400"></i> GL Code</span>
                            </th>
                            <th class="py-4 px-6 text-[10px] font-bold tracking-widest text-slate-500 uppercase w-[25%]">GL Description</th>
                            <th class="py-4 px-6 text-[10px] font-bold tracking-widest text-slate-500 uppercase w-[15%]">Category</th>
                            <th class="py-4 px-6 text-[10px] font-bold tracking-widest text-slate-500 uppercase w-[15%]">Account Type</th>
                            <th class="py-4 px-6 text-[10px] font-bold tracking-widest text-slate-500 uppercase w-[15%]">Status Mapping</th>
                            <th class="py-4 px-6 text-[10px] font-bold tracking-widest text-slate-500 uppercase w-[15%] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="glTableBody" class="divide-y divide-slate-100/80 text-xs md:text-sm font-medium text-slate-700 bg-white">
                        <?php if (!empty($glAccounts)): ?>
                            <?php foreach ($glAccounts as $account): ?>
                                <tr class="hover:bg-slate-50/60 hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)] hover:relative hover:z-10 transition-all duration-200 group">
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-50/80 border border-brand-100/80 font-ledger font-black text-brand-700 text-xs tracking-wider shadow-sm">
                                            <?= htmlspecialchars($account['gl_code']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-900 gl-desc-search tracking-tight">
                                        <?= htmlspecialchars($account['gl_description']) ?>
                                    </td>
                                    
                                    <td class="py-4 px-6">
                                        <?php 
                                        $cat = $account['gl_category'] ?? '';
                                        if ($cat === 'Balance Sheet (B/S)'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60 uppercase tracking-widest shadow-[0_2px_10px_-2px_rgba(99,102,241,0.15)]">
                                                <i class="fa-solid fa-scale-balanced text-indigo-500 text-[11px]"></i> B/S
                                            </span>
                                        <?php elseif ($cat === 'Profit and Loss (P&L)'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60 uppercase tracking-widest shadow-[0_2px_10px_-2px_rgba(245,158,11,0.15)]">
                                                <i class="fa-solid fa-chart-line text-amber-500 text-[11px]"></i> P&L
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[11px] italic font-medium">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="py-4 px-6 text-slate-500 font-semibold gl-type-search">
                                        <?= htmlspecialchars($account['gl_type'] ?? '--') ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if (($account['status'] ?? 'Active') === 'Active'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-widest bg-emerald-50/80 text-emerald-700 border border-emerald-200/60 shadow-sm uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shadow-sm shadow-emerald-500/50"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-widest bg-rose-50/80 text-rose-700 border border-rose-200/60 shadow-sm uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50"></span> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-1.5 opacity-70 group-hover:opacity-100 transition-opacity duration-300">
                                            <button onclick="openGLModal(true, '<?= htmlspecialchars($account['gl_code']) ?>', '<?= htmlspecialchars(addslashes($account['gl_description'])) ?>', '<?= htmlspecialchars(addslashes($account['gl_type'] ?? '')) ?>')" class="w-9 h-9 flex items-center justify-center text-slate-500 hover:text-brand-600 bg-slate-50 hover:bg-brand-50 border border-slate-200 hover:border-brand-200 rounded-xl transition-all duration-200 shadow-sm hover:shadow" title="Edit Entry">
                                                <i class="fa-solid fa-pen-to-square text-xs drop-shadow-sm"></i>
                                            </button>
                                            
                                            <?php if (($account['status'] ?? 'Active') === 'Active'): ?>
                                                <button onclick="setInactiveGLCode('<?= htmlspecialchars($account['gl_code']) ?>')" class="flex items-center justify-center gap-1.5 px-3 py-2 text-rose-700 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 rounded-xl transition-all duration-200 shadow-sm hover:shadow font-bold text-[10px] uppercase tracking-wider" title="Set to Inactive">
                                                    <i class="fa-solid fa-ban text-[10px]"></i> Inactive
                                                </button>
                                            <?php else: ?>
                                                <button onclick="setActiveGLCode('<?= htmlspecialchars($account['gl_code']) ?>')" class="flex items-center justify-center gap-1.5 px-3 py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 rounded-xl transition-all duration-200 shadow-sm hover:shadow font-bold text-[10px] uppercase tracking-wider" title="Set to Active">
                                                    <i class="fa-solid fa-check text-[10px]"></i> Active
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="emptyState" class="<?= empty($glAccounts) ? 'flex' : 'hidden' ?> p-16 flex-col items-center justify-center bg-slate-50/30">
                <div class="w-16 h-16 rounded-3xl bg-white border border-slate-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.06)] flex items-center justify-center text-slate-300 mb-5 relative">
                    <i class="fa-solid fa-folder-open text-2xl relative z-10"></i>
                    <div class="absolute inset-0 bg-brand-50 rounded-3xl opacity-50 blur-lg pointer-events-none"></div>
                </div>
                <h3 class="font-black text-slate-800 text-sm md:text-base tracking-tight">No accounts found</h3>
                <p class="text-slate-500 text-xs mt-1.5 max-w-sm text-center leading-relaxed font-medium">Try adjusting your tracking filter parameters or register a new GL code to populate this view.</p>
            </div>

            <div id="paginationBar" class="<?= empty($glAccounts) ? 'hidden' : 'flex' ?> flex-col sm:flex-row items-center justify-between gap-4 px-6 py-5 border-t border-slate-100/80 bg-slate-50/40 backdrop-blur-sm">
                <div class="flex items-center gap-4 text-xs font-bold tracking-wide text-slate-500 uppercase">
                    <span id="paginationInfo" class="font-ledger text-slate-700">Showing 0 of 0 entries</span>
                    <div class="hidden sm:block w-px h-4 bg-slate-300/60"></div>
                    <div class="hidden sm:flex items-center gap-2">
                        <span>Rows per page</span>
                        <select id="pageSizeSelect" onchange="changePageSize(this.value)" class="bg-white border border-slate-200/80 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 cursor-pointer shadow-sm hover:border-slate-300 transition-colors">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div id="paginationControls" class="flex items-center gap-1.5"></div>
            </div>
        </section>
    </main>
</div>

<div id="glModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-[0_20px_70px_-15px_rgba(0,0,0,0.2)] border border-slate-200/60 transform scale-95 transition-all duration-300 mx-4" id="modalContent">
        <div class="p-6 bg-gradient-to-b from-slate-50/80 to-white border-b border-slate-100 flex justify-between items-center relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 rounded-full blur-3xl opacity-60 pointer-events-none -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative z-10">
                <h3 id="modalTitle" class="font-black text-slate-900 tracking-tight text-xl">Register Entry</h3>
                <p class="text-[10px] uppercase font-bold tracking-widest text-brand-600 mt-1 drop-shadow-sm">Chart Mapping Adjustments</p>
            </div>
            <button onclick="closeGLModal()" type="button" class="w-9 h-9 rounded-2xl bg-white hover:bg-slate-100 border border-slate-200/60 shadow-sm flex items-center justify-center text-slate-500 transition-all active:scale-90 relative z-10">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        
        <form id="glForm" onsubmit="saveGLAccount(event)" class="p-7 space-y-5">
            <input type="hidden" id="isEditMode" value="false">
            <input type="hidden" id="originalGLCode" value="">

            <div class="space-y-2.5 relative group">
                <label for="formGLCode" class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">GL Account Code</label>
                <input type="text" id="formGLCode" required placeholder="e.g., 10101" class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200/80 font-mono font-black text-brand-700 rounded-2xl focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 text-sm transition-all placeholder:text-slate-300 hover:border-slate-300 disabled:opacity-60 disabled:bg-slate-100 shadow-inner">
                <p class="text-[10px] text-slate-400 font-medium leading-tight mt-1.5"><i class="fa-solid fa-circle-info mr-1 text-slate-300"></i> Code prefix automatically assigns <strong>Balance Sheet</strong> (1-3) or <strong>P&L</strong> (4+).</p>
            </div>

            <div class="space-y-2.5">
                <label for="formGLDescription" class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Account Description</label>
                <input type="text" id="formGLDescription" required placeholder="e.g., Cash Equivalents Petty Funds" class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200/80 font-bold text-slate-800 rounded-2xl focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 text-sm transition-all placeholder:text-slate-300 hover:border-slate-300 shadow-inner">
            </div>

            <div class="space-y-2.5">
                <label for="formGLType" class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Account Type</label>
                <input type="text" id="formGLType" placeholder="e.g., Cash, Expenses, Accounts Receivable" class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200/80 font-bold text-slate-800 rounded-2xl focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 text-sm transition-all placeholder:text-slate-300 hover:border-slate-300 shadow-inner">
            </div>

            <div class="pt-5 flex justify-end gap-3 border-t border-slate-100/80 mt-6">
                <button type="button" onclick="closeGLModal()" class="px-5 py-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-2xl transition-all active:scale-95 shadow-sm hover:shadow-md">Cancel</button>
                <button type="submit" class="group bg-slate-900 hover:bg-brand-600 text-white flex items-center justify-center gap-2 flex-1 sm:flex-initial px-6 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider border border-slate-900 hover:border-brand-600 shadow-md hover:shadow-xl hover:shadow-brand-500/10 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200">Save Record</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Handles clean side drawer toggle interfaces
    function toggleSidebar(show) {
        const overlay = document.getElementById('sidebarOverlay');
        if (show) {
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('opacity-100'), 10);
        } else {
            overlay.classList.remove('opacity-100');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
    }

    // Modular toast notification rendering interface component
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return; // Prevention check if container doesn't exist yet
        
        const toast = document.createElement('div');
        
        toast.className = `p-4 rounded-2xl shadow-xl border text-xs font-bold tracking-wide transition-all duration-300 transform translate-y-2 opacity-0 flex items-center gap-3 pointer-events-auto max-w-sm ${
            type === 'success' 
                ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
                : 'bg-rose-50 border-rose-200 text-rose-800'
        }`;
        
        const icon = type === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-rose-500';
        toast.innerHTML = `<i class="fa-solid ${icon} text-base"></i><span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => toast.classList.remove('opacity-0', 'translate-y-2'), 10);
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    // Open Form Modal with injected variables including Account Type
    function openGLModal(isEdit = false, code = '', description = '', type = '') {
        const modal = document.getElementById('glModal');
        const content = document.getElementById('modalContent');
        
        document.getElementById('isEditMode').value = isEdit ? 'true' : 'false';
        document.getElementById('modalTitle').innerText = isEdit ? 'Modify Account Structure' : 'Register New Ledger Entry';
        
        // ADDED: Set the original GL Code before the user edits it
        document.getElementById('originalGLCode').value = isEdit ? code : '';

        const codeInput = document.getElementById('formGLCode');
        codeInput.value = code;
        // MODIFIED: Ensure the input stays editable whether inserting or updating
        codeInput.disabled = false; 
        
        document.getElementById('formGLDescription').value = description;
        document.getElementById('formGLType').value = type;

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);
    }

    function closeGLModal() {
        const modal = document.getElementById('glModal');
        const content = document.getElementById('modalContent');
        
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    // Submit single account setup
    async function saveGLAccount(event) {
        event.preventDefault();
        const isEdit = document.getElementById('isEditMode').value === 'true';
        const code = document.getElementById('formGLCode').value;
        const description = document.getElementById('formGLDescription').value;
        const type = document.getElementById('formGLType').value;
        
        // ADDED: Fetch the original code from the hidden input
        const originalCode = document.getElementById('originalGLCode').value;

        const formData = new FormData();
        formData.append('action', isEdit ? 'update' : 'insert');
        formData.append('gl_code', code);
        formData.append('gl_description', description);
        formData.append('gl_type', type);

        // ADDED: Condition that passes the original GL code to the backend during an update
        if (isEdit) {
            formData.append('original_gl_code', originalCode);
        }

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server status ${response.status}: ${errorText}`);
            }

            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                closeGLModal();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Save Error:', error);
            showToast('System error during record saving. Check console for details.', 'error');
        }
    }

    // Process file imports asynchronously
    async function handleFileImport(event) {
        const file = event.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('import_file', file);

        showToast('Processing file import...', 'success');

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            // Read response as plain text first to prevent JSON parsing crashes
            const responseText = await response.text();

            if (!response.ok) {
                throw new Error(`Server status ${response.status}: ${responseText}`);
            }

            // Safely attempt to parse the text into JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                // This handles your exact error! It catches the HTML and logs it safely.
                console.error('The server returned HTML instead of JSON. Server output:\n\n', responseText);
                throw new Error('Server returned invalid data format (HTML instead of JSON). Check console for the backend error.');
            }
            
            if (result.success) {
                showToast(result.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Import Error:', error);
            showToast('System error during file import. Check console for details.', 'error');
        } finally {
            event.target.value = ''; // Reset input to allow re-upload of the same file
        }
    }

    // Safely update general ledger codes
    async function setInactiveGLCode(code) {
        if (!confirm(`Are you sure you want to set GL Code: ${code} to Inactive?`)) return;
        updateStatus(code, 'set_inactive');
    }

    async function setActiveGLCode(code) {
        if (!confirm(`Are you sure you want to activate GL Code: ${code}?`)) return;
        updateStatus(code, 'set_active');
    }

    async function updateStatus(code, action) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('gl_code', code);

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server status ${response.status}: ${errorText}`);
            }

            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Status Update Error:', error);
            showToast('Failed to execute state adjustment.', 'error');
        }
    }

    // ---- Pagination & search engine ----
    let allRows = [];
    let filteredRows = [];
    let currentPage = 1;
    let pageSize = 25;

    function initPagination() {
        allRows = Array.from(document.querySelectorAll('#glTableBody tr'));
        filteredRows = allRows.slice();
        renderTable();
    }

    function renderTable() {
        const totalItems = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        // Hide every row, then reveal only the current page's slice
        allRows.forEach(row => row.style.display = 'none');
        const start = totalItems === 0 ? 0 : (currentPage - 1) * pageSize;
        const end = Math.min(start + pageSize, totalItems);
        filteredRows.slice(start, end).forEach(row => row.style.display = '');

        const infoEl = document.getElementById('paginationInfo');
        if (infoEl) {
            infoEl.textContent = totalItems === 0
                ? 'Showing 0 of 0 entries'
                : `Showing ${start + 1}–${end} of ${totalItems} entries`;
        }

        const emptyState = document.getElementById('emptyState');
        const paginationBar = document.getElementById('paginationBar');
        if (emptyState) emptyState.style.display = totalItems === 0 ? 'flex' : 'none';
        if (paginationBar) paginationBar.style.display = totalItems === 0 ? 'none' : 'flex';

        renderPaginationControls(totalPages, totalItems);
    }

    function renderPaginationControls(totalPages, totalItems) {
        const container = document.getElementById('paginationControls');
        if (!container) return;
        container.innerHTML = '';

        const makeBtn = (label, page, opts = {}) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = label;
            btn.disabled = !!opts.disabled;
            btn.className = 'page-btn w-8 h-8 flex items-center justify-center rounded-lg font-bold text-xs border border-transparent ' + (
                opts.active
                    ? 'page-btn-active text-white'
                    : opts.disabled
                        ? 'text-slate-300 cursor-not-allowed'
                        : 'text-slate-500 bg-white hover:text-brand-600 border-slate-200'
            );
            if (!opts.disabled && !opts.active) btn.onclick = () => changePage(page);
            return btn;
        };

        container.appendChild(makeBtn('<i class="fa-solid fa-angle-left text-[10px]"></i>', currentPage - 1, { disabled: currentPage === 1 || totalItems === 0 }));

        if (totalItems > 0) {
            getPageRange(currentPage, totalPages).forEach(p => {
                if (p === '...') {
                    const span = document.createElement('span');
                    span.className = 'w-8 h-8 flex items-center justify-center text-slate-300 text-xs font-bold select-none';
                    span.textContent = '···';
                    container.appendChild(span);
                } else {
                    container.appendChild(makeBtn(p, p, { active: p === currentPage }));
                }
            });
        }

        container.appendChild(makeBtn('<i class="fa-solid fa-angle-right text-[10px]"></i>', currentPage + 1, { disabled: currentPage === totalPages || totalItems === 0 }));
    }

    // Builds a compact page-number sequence with ellipses, e.g. 1 ... 4 5 6 ... 12
    function getPageRange(current, total) {
        const range = [];
        const delta = 1;
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                range.push(i);
            } else if (range[range.length - 1] !== '...') {
                range.push('...');
            }
        }
        return range;
    }

    function changePage(page) {
        currentPage = page;
        renderTable();
        document.getElementById('glTableBody')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function changePageSize(size) {
        pageSize = parseInt(size, 10) || 25;
        currentPage = 1;
        renderTable();
    }

    // Local client-side search filtering, now feeding the pagination engine
    function filterGLTable() {
        const searchValue = document.getElementById('tableSearch').value.toLowerCase();
        filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(searchValue));
        currentPage = 1;
        renderTable();
    }

    document.addEventListener('DOMContentLoaded', initPagination);
</script>
</body>
</html>