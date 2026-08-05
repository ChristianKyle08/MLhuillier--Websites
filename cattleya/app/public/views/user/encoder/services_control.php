<?php
// Include your system configurations safely
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_session_name = $_SESSION['user_name'] ?? 'System Administrator';

// --- AJAX CRUD ROUTER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Action routing error.'];

    try {
        if ($_POST['action'] === 'insert') {
            $name = trim($_POST['services_name'] ?? '');
            $fee = floatval($_POST['fee'] ?? 0);
            $gl_code = trim($_POST['gl_code'] ?? '');

            if (empty($name) || empty($gl_code)) {
                throw new Exception("Service profile name and General Ledger configuration code are mandatory.");
            }

            $stmt = $pdo->prepare("INSERT INTO services (services_name, fee, gl_code, add_by, add_date) VALUES (?, ?, ?, ?, curdate())");
            if ($stmt->execute([$name, $fee, $gl_code, $user_session_name])) {
                $response = ['success' => true, 'message' => 'Service profile deployed and registered into database successfully.'];
            }
        } 
        elseif ($_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $name = trim($_POST['services_name'] ?? '');
            $fee = floatval($_POST['fee'] ?? 0);
            $gl_code = trim($_POST['gl_code'] ?? '');

            if (empty($id) || empty($name) || empty($gl_code)) {
                throw new Exception("Missing state definitions or parameters for operational record update.");
            }

            $stmt = $pdo->prepare("UPDATE services SET services_name = ?, fee = ?, gl_code = ?, update_by = ?, update_date = curdate() WHERE id = ?");
            if ($stmt->execute([$name, $fee, $gl_code, $user_session_name, $id])) {
                $response = ['success' => true, 'message' => 'Target service specifications modified and committed successfully.'];
            }
        } 
        elseif ($_POST['action'] === 'import_csv') {
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please upload a well-formed CSV file asset.");
            }

            $fileTmpPath = $_FILES['import_file']['tmp_name'];
            if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                $pdo->beginTransaction();
                fgetcsv($handle); // Skip structural columns schema line
                
                $stmt = $pdo->prepare("INSERT INTO services (services_name, fee, gl_code, add_by, add_date) VALUES (?, ?, ?, ?, curdate())");
                $recordsProcessed = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= 3 && !empty(trim($data[0])) && !empty(trim($data[2]))) {
                        $stmt->execute([trim($data[0]), floatval($data[1]), trim($data[2]), $user_session_name]);
                        $recordsProcessed++;
                    }
                }
                $pdo->commit();
                fclose($handle);
                $response = ['success' => true, 'message' => "Successfully integrated $recordsProcessed ledger profiles into active services."];
            } else {
                throw new Exception("Pipeline error encountered while streaming source asset rows.");
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// --- DATA ACCESS LAYER ENGINE ---
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY id DESC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Services schema loading breakdown: " . $e->getMessage());
}

// Dynamically source codes directly from the gl_code system configuration table
$gl_codes = [];
try {
    // Modified to select both gl_code and gl_description explicitly for your dropdown menu
    $stmt = $pdo->query("SELECT gl_code, gl_description, gl_type, gl_category,
                                (CASE WHEN COLUMN_EXISTS THEN gl_description ELSE 'Ledger Account Mapping' END) as gl_desc 
                         FROM (SELECT gl_code, 1 as COLUMN_EXISTS FROM gl_code LIMIT 1) as t, gl_code 
                         ORDER BY gl_description ASC");
    $gl_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Resilient fallback execution route updating column requirements
    try {
        $stmt = $pdo->query("SELECT gl_code, gl_description, gl_type, gl_category FROM gl_code WHERE status = 'Active' ORDER BY gl_description ASC");
        $gl_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $th) {
        error_log("Failed to fetch matching relational data lines: " . $th->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Ledger Framework</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#2a6279',
                            dark: '#1e4a5c',
                            accent: '#9dc44d',
                            surface: '#0f172a'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    
    <style>
        body { background-color: #fcfdfe; font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        
        /* Drawer Sheet Transitions */
        .drawer-overlay { opacity: 0; pointer-events: none; transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .drawer-overlay.active { opacity: 1; pointer-events: auto; }
        
        .drawer-sheet { transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .drawer-sheet.active { transform: translateX(0); }

        /* Custom Dropzone Interactive Animation States */
        .dropzone-pulse:hover .cloud-icon { transform: translateY(-4px) scale(1.05); color: #2a6279; }
        .cloud-icon { transition: all 0.2s ease; }
    </style>
</head>
<body class="text-slate-900 antialiased selection:bg-brand-primary/10 selection:text-brand-primary">
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div id="toastContainer" class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none"></div>

<!-- MAIN VIEWPORT CANVAS -->
<div class="min-h-screen flex flex-col w-full relative">
    <main class="w-full max-w-[1600px] mx-auto p-4 sm:p-8 lg:p-12 space-y-10 flex-1">
        
        <!-- BRAND NEW ASYMMETRICAL HEADER COCKPIT -->
        <header class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-gradient-to-br from-brand-primary to-brand-dark p-8 sm:p-8 rounded-[2.5rem] text-white shadow-2xl shadow-brand-primary/20 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-96 h-96 bg-brand-accent/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-0 right-1/4 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="lg:col-span-8 space-y-4 relative z-10">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-brand-accent font-semibold text-xs tracking-wider uppercase backdrop-blur-md">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-accent animate-ping"></span>
                    Operational Core Matrix
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-none">
                    Product / Services
                </h1>
                <p class="text-white/70 max-w-2xl text-xs xs:text-base font-medium leading-relaxed">
                    Adding and Updating Services and GL codes.
                </p>
            </div>
            
            <!-- Context Summary Matrix Widget -->
            <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 gap-4 relative z-10 w-full">
                <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <p class="text-xs font-bold text-white/50 uppercase tracking-widest">Active Services</p>
                    <p class="text-2xl font-black mt-2 text-brand-accent tracking-tight font-mono"><?= count($services) ?></p>
                </div>
                <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <p class="text-xs font-bold text-white/50 uppercase tracking-widest">GL Allocations</p>
                    <p class="text-2xl font-black mt-2 text-white tracking-tight font-mono"><?= count($gl_codes) ?></p>
                </div>
            </div>
        </header>

        <!-- OPERATIONS CONTROL DECK (SEARCH & HOOK ACTIONS) -->
        <section class="flex flex-col xl:flex-row items-center gap-2 justify-between bg-white border border-slate-100 p-4 rounded-3xl shadow-sm">
            <!-- Modern Search Configuration -->
            <div class="relative w-full xl:max-w-xl group">
                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-primary transition-colors">
                    <i class="fa-solid fa-layer-group text-base"></i>
                </span>
                <input type="text" id="searchInput" onkeyup="filterMatrixTable()" placeholder="Filter ledger registers by target strings, parameters, or GL components..." class="w-full pl-12 pr-5 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200/80 rounded-2xl focus:outline-none focus:bg-white focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 text-sm font-medium transition-all">
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto justify-end">
                <!-- Import Dispatch Activator -->
                <button onclick="toggleModalWindow('importCsvOverlay', true)" class="flex items-center justify-center gap-2.5 px-6 py-3 border border-slate-200 bg-white text-slate-700 rounded-2xl hover:bg-slate-50 hover:border-slate-300 transition-all font-bold text-xs uppercase tracking-wider shadow-sm active:scale-98">
                    <i class="fa-solid fa-arrow-up-from-bracket text-brand-primary text-sm"></i>
                    <span>Batch Import Assets</span>
                </button>

                <!-- Create Form Slider Trigger -->
                <button onclick="toggleDrawerControl('addServiceDrawer', true)" class="bg-brand-primary hover:bg-brand-dark text-white flex items-center justify-center gap-2.5 px-7 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider shadow-xl shadow-brand-primary/20 hover:shadow-brand-primary/30 transition-all active:scale-98">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Add New Services</span>
                </button>
            </div>
        </section>

        <!-- VIEWING MATRICES: DATA ARCHITECTURE LAYER -->
        <section class="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl shadow-slate-100/40 overflow-hidden">
            <div class="bg-slate-50/60 px-8 py-3 border-b border-slate-200/40 flex items-center gap-2 text-xs text-slate-500 font-medium">
                <i class="fa-solid fa-circle-info text-brand-primary"></i>
                <span>Quick Tip: To update or modify a service record's configuration profile, click the <i class="fa-solid fa-sliders text-brand-primary mx-0.5"></i> <strong>Sliders Icon</strong> under the Operations column.</span>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[950px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/50">
                            <th class="py-3 px-8 text-[11px] font-bold tracking-widest text-slate-400 uppercase font-sans">Identifier</th>
                            <th class="py-3 px-8 text-[11px] font-bold tracking-widest text-slate-400 uppercase font-sans">Service Name</th>
                            <th class="py-3 px-8 text-[11px] font-bold tracking-widest text-slate-400 uppercase font-sans">Fee (PHP)</th>
                            <th class="py-3 px-8 text-[11px] font-bold tracking-widest text-slate-400 uppercase font-sans">General Ledger Tag</th>
                            <th class="py-3 px-8 text-[11px] font-bold tracking-widest text-slate-400 uppercase font-sans">System Activity</th>
                            <th class="py-3 px-8 text-[11px] font-bold tracking-widest text-slate-400 uppercase font-sans text-right">Operations</th>
                        </tr>
                    </thead>
                    <tbody id="interactiveServiceRows" class="divide-y divide-slate-100 text-sm font-medium text-slate-600">
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="6" class="py-24 text-center">
                                    <div class="max-w-md mx-auto space-y-3">
                                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto text-xl">
                                            <i class="fa-solid fa-folder-open"></i>
                                        </div>
                                        <p class="text-slate-900 font-bold text-base">Operational ledger clear</p>
                                        <p class="text-xs text-slate-400">There are no existing services registered inside this data node cluster. Add one or parse a batch collection to build data.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($services as $service): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="py-3 px-8 text-slate-400 font-mono text-xs">
                                        SVC-<?= str_pad($service['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    
                                    <td class="py-3 px-8 font-semibold text-slate-900 data-search-string">
                                        <?= htmlspecialchars($service['services_name']) ?>
                                    </td>
                                    
                                    <td class="py-3 px-8 font-bold text-slate-900 font-mono">
                                        ₱<?= number_format($service['fee'], 2) ?>
                                    </td>
                                    
                                    <td class="py-3 px-8 data-search-string">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl text-xs font-bold bg-brand-primary/5 text-brand-primary border border-brand-primary/10 font-mono self-start">
                                                <span class="w-1 h-1 rounded-full bg-brand-accent"></span>
                                                <?= htmlspecialchars($service['gl_code']) ?>
                                            </span>
                                            
                                            <?php 
                                                $resolved_gl_description = 'INACTIVE';
                                                $resolved_gl_type = 'N/A';
                                                $resolved_gl_category = 'N/A';

                                                if (!empty($gl_codes)) {
                                                    foreach ($gl_codes as $gl_record) {
                                                        if ($gl_record['gl_code'] === $service['gl_code']) {

                                                            $resolved_gl_description = 
                                                                $gl_record['gl_description'] ?? 
                                                                $gl_record['gl_desc'] ?? 
                                                                'INACTIVE';

                                                            $resolved_gl_type = 
                                                                $gl_record['gl_type'] ?? 
                                                                'N/A';

                                                            $resolved_gl_category = 
                                                                $gl_record['gl_category'] ?? 
                                                                'N/A';

                                                            break;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <span class="text-[11px] font-bold text-slate-400 font-sans truncate max-w-[240px] pl-1">
                                                <?= htmlspecialchars($resolved_gl_description) ?>
                                            </span>

                                            <span class="text-[10px] font-semibold text-slate-500 pl-1">
                                                Type:
                                                <span class="text-brand-primary">
                                                    <?= htmlspecialchars($resolved_gl_type) ?> ( <?= htmlspecialchars($resolved_gl_category) ?> )
                                                </span>
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <td class="py-3 px-8 text-xs text-slate-400 space-y-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-regular fa-clock text-[10px]"></i>
                                            <span class="font-semibold text-slate-500">
                                                <?= !empty($service['update_date']) ? date('M d, Y', strtotime($service['update_date'])) : date('M d, Y', strtotime($service['add_date'])) ?>
                                            </span>
                                        </div>
                                        <div class="text-[10px] tracking-wide text-slate-400 truncate max-w-[140px]">
                                            by <?= htmlspecialchars($service['update_by'] ?? $service['add_by']) ?>
                                        </div>
                                    </td>
                                    
                                    <td class="py-3 px-8 text-right">
                                        <button onclick="launchRecordUpdateSheet(<?= $service['id'] ?>, '<?= htmlspecialchars(addslashes($service['services_name'])) ?>', <?= $service['fee'] ?>, '<?= htmlspecialchars(addslashes($service['gl_code'])) ?>')" class="w-10 h-10 inline-flex items-center justify-center text-slate-400 hover:text-brand-primary hover:bg-slate-100 rounded-xl transition-all" title="Modify record allocations">
                                            <i class="fa-solid fa-sliders text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<div id="addServiceDrawer" class="fixed inset-0 z-50 overflow-hidden pointer-events-none" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div onclick="toggleDrawerControl('addServiceDrawer', false)" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm drawer-overlay transition-opacity duration-300"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
            <div class="w-screen max-w-xl pointer-events-auto bg-white shadow-[0_0_50px_-12px_rgba(0,0,0,0.25)] flex flex-col justify-between border-l border-slate-200/50 sm:rounded-l-[2rem] drawer-sheet transition-transform duration-300 overflow-hidden">
                
                <div class="p-8 border-b border-slate-100 bg-white flex items-center justify-between relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-48 h-48 bg-brand-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="space-y-1.5 relative z-10">
                        <h2 class="text-2xl font-black tracking-tight text-slate-900" id="slide-over-title">Create Service Node</h2>
                        <p class="text-[13px] text-slate-500 font-medium">Instantiate a completely brand new manual line item configuration mapping.</p>
                    </div>
                    <button onclick="toggleDrawerControl('addServiceDrawer', false)" class="w-11 h-11 rounded-full bg-slate-50 border border-slate-200/60 text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center shadow-sm hover:rotate-90 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <form onsubmit="dispatchFormPipeline(event, 'insert', 'addServiceFormElement')" id="addServiceFormElement" class="flex-1 overflow-y-auto p-8 space-y-7 bg-slate-50/30">
                    <input type="hidden" name="action" value="insert">
                    
                    <div class="space-y-2.5">
                        <label class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Service Profile Name</label>
                        <input type="text" name="services_name" required placeholder="e.g., Specialized Clinical Scan Matrix" class="w-full px-4 py-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10 text-sm font-semibold text-slate-800 placeholder:text-slate-400 transition-all duration-200 shadow-sm">
                    </div>

                    <div class="space-y-2.5">
                        <label class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Assigned Operational Fee Mapping</label>
                        <div class="relative flex items-center">
                            <div class="absolute left-4 flex items-center justify-center text-slate-400 font-bold text-base pointer-events-none">₱</div>
                            <input type="number" step="0.01" min="0" name="fee" required placeholder="0.00" class="w-full pl-10 pr-4 py-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10 text-sm font-bold font-mono text-slate-800 placeholder:text-slate-300 transition-all duration-200 shadow-sm">
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <label class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">General Ledger Account Code Mapping</label>
                        <div class="relative">
                            <div class="relative w-full text-left font-sans" id="customGlDropdown">
                                <input type="hidden" name="gl_code" id="selectedGlCode" required value="">
                                
                                <button type="button" onclick="toggleGlDropdown()" class="w-full pl-4 pr-12 py-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10 text-sm transition-all duration-200 text-slate-700 flex items-center justify-between cursor-pointer shadow-sm group">
                                    <span id="glDropdownPlaceholder" class="font-medium text-slate-400 truncate group-focus:text-slate-800">Select Relational GL Ledger Key...</span>
                                    <div class="absolute right-4 text-slate-400 group-focus:text-brand-primary transition-colors">
                                        <svg id="glDropdownChevron" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </button>

                                <div id="glDropdownOptions" class="absolute z-50 left-0 mt-2 min-w-full w-max hidden max-h-[22rem] overflow-y-auto bg-white/95 backdrop-blur-2xl border border-slate-200/60 rounded-2xl shadow-[0_15px_50px_-12px_rgba(0,0,0,0.15)] p-1.5 opacity-0 translate-y-[-10px] transition-all duration-300">
                                    <?php foreach ($gl_codes as $gl): 
                                        $glCode = htmlspecialchars($gl['gl_code']);
                                        $glDesc = htmlspecialchars($gl['gl_description'] ?? $gl['gl_desc'] ?? 'INACTIVE');
                                        $glType = htmlspecialchars($gl['gl_type'] ?? 'N/A');
                                        $glCat  = htmlspecialchars($gl['gl_category'] ?? 'N/A');
                                        
                                        // Clean display value used when an option is selected
                                        $displayLabel = $glCode . " - " . $glDesc;
                                    ?>
                                        <div onclick="executeGlSelection('<?= addslashes($glCode) ?>', '<?= addslashes($displayLabel) ?>')" 
                                            class="group relative flex items-center justify-between gap-8 px-4 py-3 mb-1 last:mb-0 rounded-xl hover:bg-slate-50 cursor-pointer transition-all duration-200 overflow-hidden">
                                            
                                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-brand-primary opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-l-xl"></div>
                                            
                                            <div class="flex items-center gap-3 pl-1">
                                                <span class="px-2.5 py-1 bg-white group-hover:bg-white text-slate-600 group-hover:text-brand-primary shadow-sm font-mono font-bold text-xs rounded-lg border border-slate-200/60 transition-colors whitespace-nowrap">
                                                    <?= $glCode ?>
                                                </span>
                                                <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors whitespace-nowrap">
                                                    <?= $glDesc ?>
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200/50 group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-100/50 text-[10px] font-extrabold uppercase tracking-wider rounded-md transition-colors whitespace-nowrap">
                                                    <?= $glType ?>
                                                </span>
                                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200/50 group-hover:bg-purple-50 group-hover:text-purple-600 group-hover:border-purple-100/50 text-[10px] font-extrabold uppercase tracking-wider rounded-md transition-colors whitespace-nowrap">
                                                    <?= $glCat ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-start gap-2 mt-2">
                            <i class="fa-solid fa-circle-info text-[10px] text-slate-400 mt-0.5"></i>
                            <p class="text-[11px] font-medium text-slate-400 leading-tight">Sourced dynamically from the core relational GL ledger accounts table schema structure.</p>
                        </div>
                    </div>
                </form>

                <div class="p-6 border-t border-slate-100 bg-white flex items-center justify-end gap-3 z-20 shadow-[0_-10px_30px_rgba(0,0,0,0.02)]">
                    <button type="button" onclick="toggleDrawerControl('addServiceDrawer', false)" class="px-6 py-3.5 border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:text-slate-800 font-bold text-xs uppercase tracking-wider rounded-xl hover:bg-slate-50 transition-all duration-200">Cancel</button>
                    <button type="submit" form="addServiceFormElement" class="bg-brand-primary hover:bg-brand-dark text-white px-7 py-3.5 font-bold text-xs uppercase tracking-wider rounded-xl shadow-[0_8px_20px_-6px_rgba(var(--color-brand-primary),0.4)] hover:shadow-[0_12px_25px_-8px_rgba(var(--color-brand-primary),0.5)] hover:-translate-y-0.5 transition-all duration-200">Save Core Node</button>
                </div>

            </div>
        </div>
    </div>
</div>


<div id="updateServiceDrawer" class="fixed inset-0 z-50 overflow-hidden pointer-events-none" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div onclick="toggleDrawerControl('updateServiceDrawer', false)" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm drawer-overlay transition-opacity duration-300"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
            <div class="w-screen max-w-xl pointer-events-auto bg-white shadow-[0_0_50px_-12px_rgba(0,0,0,0.25)] flex flex-col justify-between border-l border-slate-200/50 sm:rounded-l-[2rem] drawer-sheet transition-transform duration-300 overflow-hidden">
                
                <div class="p-8 border-b border-slate-100 bg-white flex items-center justify-between relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-48 h-48 bg-brand-primary/5 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="space-y-1.5 relative z-10">
                        <h2 class="text-2xl font-black tracking-tight text-slate-900">Modify Specification Profile</h2>
                        <p class="text-[13px] text-slate-500 font-medium">Re-orient existing transactional structures or alter active balance keys.</p>
                    </div>
                    <button onclick="toggleDrawerControl('updateServiceDrawer', false)" class="w-11 h-11 rounded-full bg-slate-50 border border-slate-200/60 text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center shadow-sm hover:rotate-90 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <form onsubmit="dispatchFormPipeline(event, 'update', 'updateServiceFormElement')" id="updateServiceFormElement" class="flex-1 overflow-y-auto p-8 space-y-7 bg-slate-50/30">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="upd_record_id">
                    
                    <div class="space-y-2.5">
                        <label class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Service Profile Name</label>
                        <input type="text" name="services_name" id="upd_services_name" required class="w-full px-4 py-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10 text-sm font-semibold text-slate-800 transition-all duration-200 shadow-sm">
                    </div>

                    <div class="space-y-2.5">
                        <label class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Assigned Operational Fee Mapping</label>
                        <div class="relative flex items-center">
                            <div class="absolute left-4 flex items-center justify-center text-slate-400 font-bold text-base pointer-events-none">₱</div>
                            <input type="number" step="0.01" min="0" name="fee" id="upd_fee" required class="w-full pl-10 pr-4 py-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10 text-sm font-bold font-mono text-slate-800 transition-all duration-200 shadow-sm">
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <label class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">General Ledger Account Code Mapping</label>
                        <div class="relative w-full text-left font-sans" id="customUpdGlDropdown">
                            <input type="hidden" name="gl_code" id="upd_gl_code" required value="">
                            
                            <button type="button" onclick="toggleUpdGlDropdown()" class="w-full pl-4 pr-12 py-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10 text-sm transition-all duration-200 text-slate-700 flex items-center justify-between cursor-pointer shadow-sm group">
                                <span id="updGlDropdownPlaceholder" class="font-medium text-slate-400 truncate group-focus:text-slate-800">Select GL Ledger Key to Update...</span>
                                <div class="absolute right-4 text-slate-400 group-focus:text-brand-primary pointer-events-none transition-colors">
                                    <i id="updGlDropdownChevron" class="fa-solid fa-chevron-down text-xs transition-transform duration-300"></i>
                                </div>
                            </button>

                            <div id="updGlDropdownOptions" class="absolute z-50 left-0 mt-2 min-w-full w-max hidden max-h-[22rem] overflow-y-auto bg-white/95 backdrop-blur-2xl border border-slate-200/60 rounded-2xl shadow-[0_15px_50px_-12px_rgba(0,0,0,0.15)] p-1.5 opacity-0 translate-y-[-10px] transition-all duration-300">
                                <?php foreach ($gl_codes as $gl): 
                                    $glCode = htmlspecialchars($gl['gl_code']);
                                    $glDesc = htmlspecialchars($gl['gl_description'] ?? $gl['gl_desc'] ?? 'INACTIVE');
                                    $glType = htmlspecialchars($gl['gl_type'] ?? 'N/A');
                                    $glCat  = htmlspecialchars($gl['gl_category'] ?? 'N/A');
                                    // Clean display value used when an option is selected
                                    $displayLabel = $glCode . " - " . $glDesc;
                                ?>
                                    <div onclick="executeUpdGlSelection('<?= addslashes($glCode) ?>', '<?= addslashes($displayLabel) ?>')" 
                                        class="group relative flex items-center justify-between gap-8 px-4 py-3 mb-1 last:mb-0 rounded-xl hover:bg-slate-50 cursor-pointer transition-all duration-200 overflow-hidden">
                                        
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-brand-primary opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-l-xl"></div>
                                        
                                        <div class="flex items-center gap-3 pl-1">
                                            <span class="px-2.5 py-1 bg-white group-hover:bg-white text-slate-600 group-hover:text-brand-primary shadow-sm font-mono font-bold text-xs rounded-lg border border-slate-200/60 transition-colors whitespace-nowrap">
                                                <?= $glCode ?>
                                            </span>
                                            <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors whitespace-nowrap">
                                                <?= $glDesc ?>
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200/50 group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-100/50 text-[10px] font-extrabold uppercase tracking-wider rounded-md transition-colors whitespace-nowrap">
                                                <?= $glType ?>
                                            </span>
                                            <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200/50 group-hover:bg-purple-50 group-hover:text-purple-600 group-hover:border-purple-100/50 text-[10px] font-extrabold uppercase tracking-wider rounded-md transition-colors whitespace-nowrap">
                                                <?= $glCat ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="p-6 border-t border-slate-100 bg-white flex items-center justify-end gap-3 z-20 shadow-[0_-10px_30px_rgba(0,0,0,0.02)]">
                    <button type="button" onclick="toggleDrawerControl('updateServiceDrawer', false)" class="px-6 py-3.5 border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:text-slate-800 font-bold text-xs uppercase tracking-wider rounded-xl hover:bg-slate-50 transition-all duration-200">Cancel</button>
                    <button type="submit" form="updateServiceFormElement" class="bg-brand-primary hover:bg-brand-dark text-white px-7 py-3.5 font-bold text-xs uppercase tracking-wider rounded-xl shadow-[0_8px_20px_-6px_rgba(var(--color-brand-primary),0.4)] hover:shadow-[0_12px_25px_-8px_rgba(var(--color-brand-primary),0.5)] hover:-translate-y-0.5 transition-all duration-200">Commit Variations</button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- CONTAINER MODAL 3: BATCH CSV FILE INGESTION PORTION INTERFACE OVERLAY -->
<div id="importCsvOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white rounded-[2rem] w-full max-w-xl shadow-2xl border border-slate-200 scale-95 transition-all duration-300 modal-window-card mx-4 overflow-hidden">
        <div class="p-8 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <div class="space-y-1">
                <h3 class="font-bold text-slate-900 text-lg">Stream Aggregated Datasets</h3>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">CSV Parsing Engine Ingestion</p>
            </div>
            <button onclick="toggleModalWindow('importCsvOverlay', false)" class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors shadow-sm">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
        
        <form onsubmit="dispatchFormPipeline(event, 'import_csv', 'importFormContainerElement')" id="importFormContainerElement" class="p-8 space-y-6">
            <input type="hidden" name="action" value="import_csv">
            
            <div class="p-4 rounded-xl bg-brand-primary/5 border border-brand-primary/10 flex gap-3">
                <i class="fa-solid fa-compass text-brand-primary text-base mt-0.5"></i>
                <p class="text-xs text-brand-dark/90 font-medium leading-relaxed">
                    <strong>Structured CSV Architecture Requirements:</strong> Columns should match structural order schema verbatim: <code class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 font-bold">Service Name, Fee, GL Code</code>. The data ingestion scanner automatically purges the header index column row.
                </p>
            </div>

            <!-- Enhanced Custom Dropzone Area Layout Container -->
            <div class="flex items-center justify-center w-full">
                <label for="csv-dropzone-input" class="flex flex-col items-center justify-center w-full h-44 border-2 border-slate-300 border-dashed rounded-2xl cursor-pointer bg-slate-50/50 hover:bg-slate-50 hover:border-brand-primary group transition-all dropzone-pulse">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-400 group-hover:text-brand-primary transition-colors mb-3 cloud-icon"></i>
                        <p class="mb-1 text-sm text-slate-600 font-medium"><span class="font-bold text-brand-primary">Click to select asset</span> or drag operational table file</p>
                        <p class="text-xs text-slate-400 uppercase font-bold tracking-wider font-mono mt-1">.CSV arrays only</p>
                    </div>
                    <input id="csv-dropzone-input" name="import_file" type="file" accept=".csv" required class="hidden" onchange="renderDropzoneFilename(this)" />
                </label>
            </div>
            <div id="dropzoneFilenameDisplay" class="text-xs text-center font-mono font-bold text-brand-primary hidden p-3 rounded-xl bg-brand-primary/5 border border-brand-primary/10"></div>

            <div class="pt-2 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="toggleModalWindow('importCsvOverlay', false)" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl transition-all">Cancel</button>
                <button type="submit" class="bg-brand-primary hover:bg-brand-dark text-white px-6 py-3 font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-brand-primary/20">Execute Stream Import</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= SYSTEMS CONTROLLER CORE SCRIPTS ================= -->
<script>
   function toggleGlDropdown() {
    const menu = document.getElementById('glDropdownOptions');
    const chevron = document.getElementById('glDropdownChevron');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        // Small delay to trigger smooth structural animation entry
        setTimeout(() => {
            menu.classList.remove('opacity-0', 'translate-y-[-10px]');
            menu.classList.add('opacity-100', 'translate-y-0');
        }, 10);
        chevron.classList.add('rotate-180');
    } else {
        closeGlMenu();
    }
}

function closeGlMenu() {
    const menu = document.getElementById('glDropdownOptions');
    const chevron = document.getElementById('glDropdownChevron');
    
    // Reverse structural animation
    menu.classList.remove('opacity-100', 'translate-y-0');
    menu.classList.add('opacity-0', 'translate-y-[-10px]');
    chevron.classList.remove('rotate-180');
    
    // Wait for animation to finish before hiding display
    setTimeout(() => menu.classList.add('hidden'), 300);
}

function executeGlSelection(codeValue, displayString) {
    // Sets values exactly how a standard form submission expects them
    document.getElementById('selectedGlCode').value = codeValue;
    
    const displayLabel = document.getElementById('glDropdownPlaceholder');
    displayLabel.textContent = displayString;
    displayLabel.className = "font-bold text-slate-800 font-mono text-sm truncate";
    
    closeGlMenu();
}

// Global safety intercept window: Closes the active option map when clicking anywhere outside
document.addEventListener('click', function(e) {
    const component = document.getElementById('customGlDropdown');
    if (!component.contains(e.target)) {
        closeGlMenu();
    }
});

function toggleUpdGlDropdown() {
    const menu = document.getElementById('updGlDropdownOptions');
    const chevron = document.getElementById('updGlDropdownChevron');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        // Small delay to trigger smooth structural animation entry
        setTimeout(() => {
            menu.classList.remove('opacity-0', 'translate-y-[-10px]');
            menu.classList.add('opacity-100', 'translate-y-0');
        }, 10);
        chevron.classList.add('rotate-180');
    } else {
        closeUpdGlMenu();
    }
}

function closeUpdGlMenu() {
    const menu = document.getElementById('updGlDropdownOptions');
    const chevron = document.getElementById('updGlDropdownChevron');
    
    if (!menu.classList.contains('hidden')) {
        // Reverse structural animation
        menu.classList.remove('opacity-100', 'translate-y-0');
        menu.classList.add('opacity-0', 'translate-y-[-10px]');
        chevron.classList.remove('rotate-180');
        
        // Wait for animation to finish before hiding display
        setTimeout(() => menu.classList.add('hidden'), 300);
    }
}

function executeUpdGlSelection(codeValue, displayString) {
    // Sets values exactly how a standard form submission expects them
    document.getElementById('upd_gl_code').value = codeValue;
    
    const displayLabel = document.getElementById('updGlDropdownPlaceholder');
    displayLabel.textContent = displayString;
    displayLabel.className = "font-bold text-slate-800 font-mono text-sm truncate";
    
    closeUpdGlMenu();
}

// Global safety intercept window: Closes the active option map when clicking anywhere outside
document.addEventListener('click', function(e) {
    const component = document.getElementById('customUpdGlDropdown');
    if (component && !component.contains(e.target)) {
        closeUpdGlMenu();
    }
});

// Helper for your update logic: Call this when you open your edit modal to pre-fill the UI
function setUpdateDropdownValue(codeValue, displayString) {
    document.getElementById('upd_gl_code').value = codeValue;
    const displayLabel = document.getElementById('updGlDropdownPlaceholder');
    displayLabel.textContent = displayString;
    displayLabel.className = "font-bold text-slate-800 font-mono text-sm truncate";
}
    // System Notification Toaster Module Engine
    function showToast(message, statusStyle = 'success') {
        const container = document.getElementById('toastContainer');
        const alertCard = document.createElement('div');
        
        alertCard.className = `p-4 rounded-2xl shadow-xl border text-xs font-bold tracking-wide transition-all duration-300 transform translate-y-2 opacity-0 flex items-center gap-3.5 pointer-events-auto max-w-sm ${
            statusStyle === 'success' 
                ? 'bg-emerald-50 border-emerald-200 text-emerald-900' 
                : 'bg-rose-50 border-rose-200 text-rose-900'
        }`;
        
        const graphicIcon = statusStyle === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600';
        alertCard.innerHTML = `<i class="fa-solid ${graphicIcon} text-base"></i><span>${message}</span>`;
        container.appendChild(alertCard);
        
        setTimeout(() => alertCard.classList.remove('opacity-0', 'translate-y-2'), 10);
        setTimeout(() => {
            alertCard.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => alertCard.remove(), 300);
        }, 3500);
    }

    // Side-Drawer Slide Panel Layout Toggle Controller Module
    function toggleDrawerControl(drawerElementId, displayStatus) {
        const structuralDrawer = document.getElementById(drawerElementId);
        const glassOverlay = structuralDrawer.querySelector('.drawer-overlay');
        const actionSheet = structuralDrawer.querySelector('.drawer-sheet');
        
        if (displayStatus) {
            structuralDrawer.classList.remove('pointer-events-none');
            glassOverlay.classList.add('active');
            actionSheet.classList.add('active');
        } else {
            glassOverlay.classList.remove('active');
            actionSheet.classList.remove('active');
            setTimeout(() => {
                structuralDrawer.classList.add('pointer-events-none');
                // Clean the input elements state cleanly on structural dismiss rules
                const matchingFormId = drawerElementId === 'addServiceDrawer' ? 'addServiceFormElement' : 'updateServiceFormElement';
                document.getElementById(matchingFormId).reset();
            }, 400);
        }
    }

    // Centered Immersive Window Overlay Toggle Controller Module
    function toggleModalWindow(overlayId, showState) {
        const structuralOverlay = document.getElementById(overlayId);
        const modalInnerCard = structuralOverlay.querySelector('.modal-window-card');
        
        if (showState) {
            structuralOverlay.classList.remove('opacity-0', 'pointer-events-none');
            modalInnerCard.classList.remove('scale-95');
            modalInnerCard.classList.add('scale-100');
        } else {
            structuralOverlay.classList.add('opacity-0', 'pointer-events-none');
            modalInnerCard.classList.remove('scale-100');
            modalInnerCard.classList.add('scale-95');
            setTimeout(() => {
                document.getElementById('importFormContainerElement').reset();
                document.getElementById('dropzoneFilenameDisplay').classList.add('hidden');
            }, 300);
        }
    }

    // Map record profile fields and trigger update slider interface layout 
    function launchRecordUpdateSheet(id, profileName, rawFee, targetedGlKey) {
        document.getElementById('upd_record_id').value = id;
        document.getElementById('upd_services_name').value = profileName;
        document.getElementById('upd_fee').value = parseFloat(rawFee).toFixed(2);
        document.getElementById('upd_gl_code').value = targetedGlKey;
        toggleDrawerControl('updateServiceDrawer', true);
    }

    // Print active dropzone file verification string indicators
    function renderDropzoneFilename(fileInputHandle) {
        const graphicDisplayNode = document.getElementById('dropzoneFilenameDisplay');
        if (fileInputHandle.files && fileInputHandle.files[0]) {
            graphicDisplayNode.textContent = 'Selected Allocation Queue File: ' + fileInputHandle.files[0].name;
            graphicDisplayNode.classList.remove('hidden');
        } else {
            graphicDisplayNode.classList.add('hidden');
        }
    }

    // Unified Automated Form Submission Request Dispatch Module Engine (AJAX Core)
    async function dispatchFormPipeline(formSubmitEvent, actionRouterKeyword, sourceFormId) {
        formSubmitEvent.preventDefault();
        const targetedFormNode = document.getElementById(sourceFormId);
        const constructedFormPayload = new FormData(targetedFormNode);
        const functionalTriggerButton = targetedFormNode.querySelector('button[type="submit"]') || document.querySelector(`button[form="${sourceFormId}"]`);
        const historicalButtonInnerHtml = functionalTriggerButton.innerHTML;

        functionalTriggerButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin-pulse mr-2"></i> Syncing Node...';
        functionalTriggerButton.disabled = true;

        try {
            const dataTransmissionPipeline = await fetch(window.location.href, {
                method: 'POST',
                body: constructedFormPayload
            });
            const structuredResultObject = await dataTransmissionPipeline.json();
            
            if (structuredResultObject.success) {
                showToast(structuredResultObject.message, 'success');
                if (sourceFormId === 'importFormContainerElement') {
                    toggleModalWindow('importCsvOverlay', false);
                } else {
                    toggleDrawerControl(sourceFormId === 'addServiceFormElement' ? 'addServiceDrawer' : 'updateServiceDrawer', false);
                }
                // Structural real-time UI stream line compilation layout refresh
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(structuredResultObject.message, 'error');
            }
        } catch (structuralError) {
            showToast('Asynchronous execution pipeline breakdown or network validation loss.', 'error');
        } finally {
            functionalTriggerButton.innerHTML = historicalButtonInnerHtml;
            functionalTriggerButton.disabled = false;
        }
    }

    // Live Instant Matrix Table Text Filtering Functional Logic Engine
    function filterMatrixTable() {
        const filterInputString = document.getElementById("searchInput").value.toLowerCase();
        const processingRowCollections = document.querySelectorAll("#interactiveServiceRows tr");

        processingRowCollections.forEach(tableRowElement => {
            const textualDataTargets = tableRowElement.querySelectorAll('.data-search-string');
            if (textualDataTargets.length === 0) return; // Structural protection index line row guard clause

            let validationStateMatch = false;
            textualDataTargets.forEach(dataColumnCell => {
                if (dataColumnCell.textContent.toLowerCase().includes(filterInputString)) {
                    validationStateMatch = true;
                }
            });
            tableRowElement.style.display = validationStateMatch ? "" : "none";
        });
    }
</script>
</body>
</html>