<?php
// Include your system configurations safely
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_session_name = $_SESSION['user_name'] ?? 'System Administrator';

// --- NEW SYSTEM CONDITION: CORE DATA INSERTION ROUTER ENGINE ---
$alert_message = '';
$alert_type = ''; // Options: 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avail_service_submit'])) {
    try {
        $service_id = intval($_POST['service_id'] ?? 0);
        $customer_fullname = trim($_POST['customer_fullname'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $reservation_start = trim($_POST['reservation_start'] ?? '');
        $reservation_end = trim($_POST['reservation_end'] ?? '');

        // --- NEW CONDITION: ON-THE-SPOT / 1-DAY RESERVATION OVERRIDE ---
        // This dynamically injects valid dates to pass your existing strict empty() and chronology validations below
        if (isset($_POST['is_on_the_spot']) && $_POST['is_on_the_spot'] == '1') {
            $ph_tz = new DateTimeZone('Asia/Manila');
            $dt_start = new DateTime('now', $ph_tz);
            $dt_end = clone $dt_start;
            $dt_end->modify('+1 day'); // Guarantees a valid 1-day computation diff
            
            $reservation_start = $dt_start->format('Y-m-d H:i:s');
            $reservation_end = $dt_end->format('Y-m-d H:i:s');
        }

        if (empty($service_id) || empty($customer_fullname) || empty($mobile_number) || empty($reservation_start) || empty($reservation_end)) {
            throw new Exception("All service selection, customer profile name, mobile details, and reservation dates are strictly mandatory.");
        }
        
        // Ensure the end time is chronologically after the start time
        if (strtotime($reservation_start) >= strtotime($reservation_end)) {
            throw new Exception("Date validation failed: The reservation end time must be after the start time.");
        }

        // Fetch targets configurations and perform validation checks before execution
        $stmtCheck = $pdo->prepare("
            SELECT s.services_name, s.fee, s.gl_code, COALESCE(g.status, 'Active') as gl_status 
            FROM services s 
            LEFT JOIN gl_code g ON s.gl_code = g.gl_code 
            WHERE s.id = ?
        ");
        $stmtCheck->execute([$service_id]);
        $targetService = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$targetService) {
            throw new Exception("The selected service catalog record data layer could not be found.");
        }

        if (strtolower($targetService['gl_status']) === 'inactive') {
            throw new Exception("Operational security lockout: The linked General Ledger mapping tracking channel is inactive.");
        }

        // --- NEW COMPUTATION: Calculate Total based on Reservation Days ---
        $seconds_diff = strtotime($reservation_end) - strtotime($reservation_start);
        $reservation_days = max(1, ceil($seconds_diff / 86400)); // Rounds up to full days, minimum 1 day
        $total_computed_fee = $targetService['fee'] * $reservation_days;

        // --- Generate precise Philippine Date and Time ---
        $ph_timezone = new DateTimeZone('Asia/Manila');
        $datetime_now = new DateTime('now', $ph_timezone);
        $date_avail_ph = $datetime_now->format('Y-m-d H:i:s'); // Formats to standard DB datetime format

        // Execute transaction write to your avail_services destination table schema layout
        // Injecting the strictly calculated $total_computed_fee into the insertion stream
        $stmtInsert = $pdo->prepare("
            INSERT INTO avail_services (services_name, gl_code, fee, customer_fullname, mobile_number, date_avail, reservation_start, reservation_end, process_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");

        if ($stmtInsert->execute([
            $targetService['services_name'],
            $targetService['gl_code'],
            $total_computed_fee, // Dynamically computed Base Fee x Days
            $customer_fullname,
            $mobile_number,
            $date_avail_ph,
            $reservation_start,
            $reservation_end,
            $user_session_name
        ])) {
            $alert_message = "Success! Service has been allocated and committed to your transaction log pipeline layout.";
            $alert_type = "success";
        } else {
            throw new Exception("An internal pipeline stream fault occurred during the relational ledger injection run.");
        }
    } catch (Exception $e) {
        $alert_message = $e->getMessage();
        $alert_type = "error";
    }
}

// --- DATA ACCESS LAYER ---
$services_json = "[]";
try {
    // Fetch services joined with gl_code table to get description and status in one clean query
    $stmt = $pdo->query("
        SELECT 
            s.id, 
            s.services_name, 
            s.fee, 
            s.gl_code, 
            COALESCE(g.gl_description, 'General Account Matrix Line') as gl_description, gl_type, gl_category,
            COALESCE(g.status, 'Active') as gl_status
        FROM services s
        LEFT JOIN gl_code g ON s.gl_code = g.gl_code
        ORDER BY s.services_name ASC
    ");
    $services_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to JSON so JavaScript can handle the live change states instantly without reloading
    $services_json = json_encode($services_data);
} catch (Exception $e) {
    error_log("Failed loading available services ecosystem: " . $e->getMessage());
}

// --- SECURE & ACCURATE LIVE COUNT FOR PENDING STATUS ---
$pending_count = 0; 
try {
    // Reusing the initialized global $pdo wrapper seamlessly to prevent duplication errors
    $stmtCount = $pdo->prepare("SELECT COUNT(*) AS pending_count FROM `avail_services` WHERE `status` = :status");
    $stmtCount->execute(['status' => 'Pending']);
    $row = $stmtCount->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $pending_count = (int)$row['pending_count'];
    }
} catch (Exception $e) {
    // Fail gracefully in production environments, record issues securely
    error_log("Ecosystem layout exception caught during data count operation: " . $e->getMessage());
    $pending_count = 0; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avail Services | Cattleya Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#2a6279',
                            dark: '#1e4a5c',
                            accent: '#9dc44d',
                            glass: 'rgba(255, 255, 255, 0.75)',
                        }
                    },
                    boxShadow: {
                        'premium': '0 25px 60px -15px rgba(30, 74, 92, 0.12)',
                        'glow': '0 0 25px rgba(157, 196, 77, 0.4)',
                        'glass-inset': 'inset 0 2px 4px 0 rgba(255, 255, 255, 0.3)',
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .premium-blur { backdrop-filter: blur(32px) saturate(180%); -webkit-backdrop-filter: blur(32px) saturate(180%); }
        .gradient-bg { background: linear-gradient(145deg, #1e4a5c 0%, #2a6279 100%); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body class="text-slate-900 antialiased min-h-screen flex flex-col justify-between relative">
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

    <?php if($alert_type === 'success'): ?>
    <div id="redirect_overlay" class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-500 opacity-100">
        <div class="absolute inset-0 bg-slate-900/60 premium-blur"></div>
        <div class="absolute w-96 h-96 bg-brand-primary/30 rounded-full blur-[80px] animate-pulse pointer-events-none"></div>
        
        <div class="relative bg-white/95 backdrop-blur-3xl rounded-[2.5rem] p-10 max-w-md w-full border border-white/60 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] shadow-glass-inset text-center space-y-8 scale-100 transition-transform duration-500 z-10">
            
            <div class="relative w-24 h-24 mx-auto">
                <div class="absolute inset-0 bg-brand-accent/20 rounded-full animate-ping opacity-75"></div>
                <div class="relative w-full h-full bg-gradient-to-tr from-brand-primary to-brand-accent rounded-full flex items-center justify-center text-white text-4xl shadow-glow border-4 border-white">
                    <i class="fa-solid fa-check animate-[bounce_2s_infinite]"></i>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-2xl font-black text-slate-800 tracking-tight">Allocation Secured</h3>
                <p class="text-slate-500 text-sm font-medium leading-relaxed px-2">Your transaction processing sequence completed successfully. Routing to your secure dashboard matrix...</p>
            </div>

            <div class="w-full bg-slate-100/80 h-3 rounded-full overflow-hidden p-[2px] border border-slate-200 shadow-inner">
                <div id="redirect_progress_bar" class="bg-gradient-to-r from-brand-primary via-[#4b8fae] to-brand-accent h-full rounded-full w-0 transition-all ease-out duration-[18ms] shadow-glow"></div>
            </div>

            <div class="flex justify-between items-center text-[11px] uppercase tracking-widest font-extrabold text-slate-400 font-mono px-2">
                <span class="flex items-center gap-2"><i class="fa-solid fa-rotate animate-spin text-brand-primary"></i> Synchronizing</span>
                <span id="progress_percent" class="text-brand-primary">0%</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="flex-1 p-4 md:p-12 max-w-6xl mx-auto w-full flex flex-col justify-center my-auto">
        
        <div class="bg-white rounded-[2.5rem] border border-slate-200/50 shadow-premium overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[600px] transition-all duration-300 hover:border-slate-300/40">
            
            <div class="lg:col-span-5 gradient-bg p-8 md:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-[-10%] right-[-10%] w-72 h-72 bg-brand-accent/25 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-[-10%] left-[-10%] w-56 h-56 bg-black/15 rounded-full blur-2xl"></div>
                
                <div class="space-y-4 relative z-10">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 backdrop-blur-md text-brand-accent border border-white/10 rounded-full text-[10px] font-bold uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-accent animate-ping"></span> Client Portal
                    </span>
                    <h1 class="text-3xl md:text-5xl font-black tracking-tight leading-none text-white drop-shadow-sm">Request <br><span class="text-brand-accent">New Service</span></h1>
                    <p class="text-slate-200/90 text-xs md:text-sm leading-relaxed max-w-sm font-medium">Select your requested enterprise module from our active directory ledger system setup below.</p>
                </div>

                <div class="pt-12 lg:pt-0 relative z-10 space-y-4 text-xs text-slate-200 border-t border-white/10 pt-6 p-4">
                    <div class="flex items-center gap-3 group">
                        <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-brand-accent group-hover:scale-110 transition-transform"><i class="fa-solid fa-bolt text-sm"></i></div>
                        <span class="font-medium tracking-wide">Instant automated financial allocation mapping.</span>
                    </div>
                    <div class="flex items-center gap-3 group">
                        <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-brand-accent group-hover:scale-110 transition-transform"><i class="fa-solid fa-shield-halved text-sm"></i></div>
                        <span class="font-medium tracking-wide">Secured audit trails monitored natively.</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7 p-8 md:p-12 flex flex-col justify-between bg-gradient-to-b from-white to-slate-50/60 relative">
                
                <?php if(!empty($alert_message)): ?>
                    <div class="mb-6 p-4 rounded-2xl flex items-center gap-3 text-xs font-semibold border transition-all transform duration-300 scale-100 <?= $alert_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800 shadow-sm' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center <?= $alert_type === 'success' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' ?>">
                            <i class="fa-solid <?= $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> text-base"></i>
                        </div>
                        <span><?= htmlspecialchars($alert_message) ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="availServiceForm" class="space-y-6 my-auto">
                    <input type="hidden" name="avail_service_submit" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2 group">
                            <label for="customer_fullname" class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-focus-within:text-brand-primary ml-1">Customer Full Name</label>
                            <input type="text" id="customer_fullname" name="customer_fullname" required placeholder="Enter primary target full name" class="w-full px-5 py-4 bg-white border border-slate-200/80 shadow-sm rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 text-sm font-semibold transition-all text-slate-800 placeholder-slate-400/70 hover:border-slate-300">
                        </div>
                        <div class="space-y-2 group">
                            <label for="mobile_number" class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-focus-within:text-brand-primary ml-1">Mobile Number</label>
                            <input type="text" id="mobile_number" name="mobile_number" required placeholder="e.g. 09XXXXXXXXX" class="w-full px-5 py-4 bg-white border border-slate-200/80 shadow-sm rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 text-sm font-semibold transition-all text-slate-800 placeholder-slate-400/70 hover:border-slate-300">
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="flex items-center gap-3 p-4 border border-brand-primary/30 bg-brand-primary/5 rounded-2xl cursor-pointer hover:bg-brand-primary/10 transition-colors shadow-sm">
                            <input type="checkbox" id="is_on_the_spot" name="is_on_the_spot" value="1" class="w-5 h-5 text-brand-primary rounded focus:ring-brand-primary cursor-pointer border-slate-300" onchange="toggleSpotPayment(this)">
                            <span class="text-xs font-black text-brand-dark uppercase tracking-widest">On-the-Spot / 1-Day Cash Payment (Auto-fill Schedule)</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" id="reservation_dates_container">
                        <div class="space-y-2 group">
                            <label for="reservation_start" class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-focus-within:text-brand-primary ml-1">Reservation Start</label>
                            <input type="datetime-local" id="reservation_start" name="reservation_start" required class="w-full px-5 py-4 bg-white border border-slate-200/80 shadow-sm rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 text-sm font-semibold transition-all text-slate-800 hover:border-slate-300 cursor-pointer">
                        </div>
                        <div class="space-y-2 group">
                            <label for="reservation_end" class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-focus-within:text-brand-primary ml-1">Reservation End</label>
                            <input type="datetime-local" id="reservation_end" name="reservation_end" required class="w-full px-5 py-4 bg-white border border-slate-200/80 shadow-sm rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 text-sm font-semibold transition-all text-slate-800 hover:border-slate-300 cursor-pointer">
                        </div>
                    </div>

                    <div class="space-y-2 group">
                        <label for="service_selector" class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-focus-within:text-brand-primary ml-1">Choose Service Profile</label>
                        <div class="relative">
                            <select id="service_selector" name="service_id" onchange="handleServiceSelection(this.value)" required class="w-full px-5 py-4 bg-white border border-slate-200/80 shadow-sm rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 text-sm font-semibold transition-all text-slate-800 appearance-none cursor-pointer hover:border-slate-300 relative z-10">
                                <option value="" disabled selected>Select an option from catalog...</option>
                                <?php foreach ($services_data as $srv): ?>
                                    <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['services_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none z-20 transition-transform group-focus-within:rotate-180 duration-300">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </span>
                        </div>
                    </div>

                    <div id="details_card" class="bg-gradient-to-br from-white to-slate-50/60 rounded-[1.75rem] border border-slate-200/80 p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)] space-y-5 hidden transition-all duration-500 transform translate-y-2 opacity-0">
    
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100 transition-all">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Standard Base Fee</span>
                            </div>
                            <span id="display_base_fee" class="text-xl font-bold text-slate-700 font-mono tracking-tight">₱0.00</span>
                        </div>

                        <div class="flex justify-between items-center pb-1">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Duration Breakdown</span>
                            </div>
                            <span id="display_duration" class="text-xs font-black text-brand-primary bg-brand-primary/10 border border-brand-primary/5 px-3 py-1 rounded-lg uppercase tracking-wider shadow-sm">--</span>
                        </div>

                        <div class="flex justify-between items-center bg-slate-900 rounded-2xl p-5 shadow-[inset_0_2px_4px_rgba(0,0,0,0.2),0_10px_30px_-10px_rgba(15,23,42,0.3)] mt-2 border border-slate-950 relative overflow-hidden group">
                            <div class="absolute top-0 left-0 right-0 h-[1px] bg-gradient-to-r from-transparent via-slate-700/50 to-transparent"></div>
                            
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2 relative z-10">
                                <i class="fa-solid fa-calculator text-brand-accent animate-pulse"></i> Total Amount Payable
                            </span>
                            <span id="display_total_fee" class="text-3xl font-black text-brand-accent font-mono tracking-tight drop-shadow-[0_2px_8px_rgba(var(--color-brand-accent),0.2)] relative z-10">₱0.00</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-4 border-t border-slate-200/60 mt-3">

                            <div class="md:col-span-3 space-y-2 flex flex-col justify-between">
                                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <i class="fa-solid fa-fingerprint text-[10px] text-slate-300"></i> GL Code
                                </span>
                                <div id="display_gl_code" class="w-full text-xs font-bold bg-slate-100/70 text-brand-primary font-mono border border-slate-200/50 shadow-inner rounded-xl px-3.5 min-h-[46px] flex items-center justify-center md:justify-start transition-all duration-200 hover:border-slate-300/80">
                                    --
                                </div>
                            </div>
                            
                            <div class="md:col-span-3 space-y-2 flex flex-col justify-between">
                                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <i class="fa-solid fa-folder-tree text-[10px] text-slate-300"></i> GL Category
                                </span>
                                <div id="display_gl_category" class="text-xs text-slate-600 font-bold leading-relaxed bg-slate-50/80 px-3.5 py-2 rounded-xl border border-slate-200/50 shadow-sm flex items-center min-h-[46px] w-full transition-all duration-200 hover:border-slate-300/80">
                                    No category available.
                                </div>
                            </div>

                            <div class="md:col-span-6 space-y-2 flex flex-col justify-between">
                                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <i class="fa-solid fa-file-invoice text-[10px] text-slate-300"></i> GL Description
                                </span>
                                <div id="display_gl_desc" class="text-xs text-slate-400 font-medium italic leading-relaxed bg-slate-50/80 px-3.5 py-2 rounded-xl border border-slate-200/50 shadow-sm flex items-center min-h-[46px] w-full transition-all duration-200 hover:border-slate-300/80">
                                    No description available.
                                </div>
                            </div>

                        </div>
                    </div>

                    <div id="inactive_warning_alert" class="hidden p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-start gap-4 shadow-sm animate-[pulse_3s_infinite]">
                        <div class="w-8 h-8 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-600 shrink-0">
                            <i class="fa-solid fa-triangle-exclamation text-base"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="font-extrabold text-rose-900 tracking-wide uppercase text-[10px]">General Ledger Account Code is Inactive</p>
                            <p class="text-rose-700/90 leading-relaxed font-semibold">This service request pipeline cannot be processed at this time. Please contact the system administrator immediately to restore this functional matrix track.</p>
                        </div>
                    </div>
                    <div class="pt-2 space-y-3">
                        <button type="submit" id="submit_action_btn" disabled class="w-full py-4 bg-slate-100 text-slate-400 font-bold text-xs uppercase tracking-widest rounded-2xl cursor-not-allowed transition-all shadow-none flex items-center justify-center gap-2 border border-slate-200/40">
                            <i class="fa-solid fa-cart-plus"></i> Avail Selected Service
                        </button>
                        
                        <a href="/user/encoder/availed-services" class="relative w-full py-4 bg-white text-slate-700 text-decoration-none font-bold text-xs uppercase tracking-widest rounded-2xl transition-all shadow-sm hover:shadow-md hover:bg-slate-50 hover:border-slate-300 flex items-center justify-center gap-2 border border-slate-200/80">
                            Go to Availed Services <i class="fa-solid fa-arrow-right text-slate-400"></i> 

                            <?php if ($pending_count > 0): ?>
                                <span class="absolute -top-1.5 -right-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-red-600 px-1 text-[10px] font-black tracking-normal text-white ring-4 ring-white shadow-sm">
                                    <?= $pending_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </main>

    <script>
        // Injection vectors containing pre-compiled database schema layers
        const serviceRepository = <?= $services_json ?>;

        // Computation Engine Setup: Trigger calculations when user changes dates
        document.getElementById('reservation_start').addEventListener('change', calculateTotalDetails);
        document.getElementById('reservation_end').addEventListener('change', calculateTotalDetails);

        // --- NEW CONDITION ENGINE: ON-THE-SPOT DATE OVERRIDE UI LOGIC ---
        function toggleSpotPayment(checkbox) {
            const startInput = document.getElementById('reservation_start');
            const endInput = document.getElementById('reservation_end');
            const datesContainer = document.getElementById('reservation_dates_container');
            
            if (checkbox.checked) {
                // Set to Local Time
                const now = new Date();
                const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000); // Guarantees 1-day cost
                
                // Format to HTML datetime-local (YYYY-MM-DDThh:mm)
                const formatToLocal = (date) => {
                    const pad = (n) => n.toString().padStart(2, '0');
                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
                };
                
                startInput.value = formatToLocal(now);
                endInput.value = formatToLocal(tomorrow);
                
                // Dim and protect fields
                startInput.setAttribute('readonly', 'true');
                endInput.setAttribute('readonly', 'true');
                datesContainer.classList.add('opacity-40', 'pointer-events-none');
                
            } else {
                // Restore fields
                startInput.removeAttribute('readonly');
                endInput.removeAttribute('readonly');
                datesContainer.classList.remove('opacity-40', 'pointer-events-none');
                startInput.value = '';
                endInput.value = '';
            }
            
            // Re-trigger visual math engine
            calculateTotalDetails();
        }

        function handleServiceSelection(selectedId) {
            const detailsCard = document.getElementById('details_card');
            const displayGlCode = document.getElementById('display_gl_code');
            const displayGlDesc = document.getElementById('display_gl_desc');
            const displayGlCat = document.getElementById('display_gl_category');
            const warningAlert = document.getElementById('inactive_warning_alert');
            const submitBtn = document.getElementById('submit_action_btn');

            // Find the object from our lookup repository array matching target identifier keys
            const selectedService = serviceRepository.find(item => item.id == selectedId);

            if (selectedService) {
                // Populate structural mapping components
                displayGlCode.innerText = selectedService.gl_code;
                displayGlDesc.innerText = selectedService.gl_description;
                displayGlCat.innerText = selectedService.gl_category;
                
                // Trigger dynamic calculation
                calculateTotalDetails();

                // Unhide and animate entry details layout container safely
                detailsCard.classList.remove('hidden');
                setTimeout(() => {
                    detailsCard.classList.remove('translate-y-2', 'opacity-0');
                }, 50);

                // Check Operational Lifecycle Security Configurations State definitions
                if (selectedService.gl_status.toLowerCase() === 'inactive') {
                    // Show Inactive State Restrictions UI indicators
                    warningAlert.classList.remove('hidden');
                    
                    // Enforce operational form element lockout procedures safely
                    submitBtn.disabled = true;
                    submitBtn.className = "w-full py-4 bg-slate-100 text-slate-400 font-bold text-xs uppercase tracking-widest rounded-2xl cursor-not-allowed transition-all shadow-none flex items-center justify-center gap-2 border border-slate-200/40";
                } else {
                    // Clear warning alerts tracking status records
                    warningAlert.classList.add('hidden');
                    
                    // Activate Action buttons and update properties to match Premium Brand specs
                    submitBtn.disabled = false;
                    submitBtn.className = "w-full py-4 bg-brand-accent text-brand-dark font-extrabold text-xs uppercase tracking-widest rounded-2xl hover:brightness-105 hover:shadow-glow active:scale-[0.995] transition-all duration-300 cursor-pointer flex items-center justify-center gap-2";
                }
            } else {
                // Revert state indicators back to default values gracefully
                detailsCard.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => detailsCard.classList.add('hidden'), 300);
                warningAlert.classList.add('hidden');
                submitBtn.disabled = true;
                submitBtn.className = "w-full py-4 bg-slate-100 text-slate-400 font-bold text-xs uppercase tracking-widest rounded-2xl cursor-not-allowed transition-all shadow-none flex items-center justify-center gap-2 border border-slate-200/40";
            }
        }

        // Dedicated Real-Time Computation Engine for Pricing based on dates
        function calculateTotalDetails() {
            const startInput = document.getElementById('reservation_start').value;
            const endInput = document.getElementById('reservation_end').value;
            const serviceId = document.getElementById('service_selector').value;
            
            const displayBase = document.getElementById('display_base_fee');
            const displayDuration = document.getElementById('display_duration');
            const displayTotal = document.getElementById('display_total_fee');

            if (!serviceId) return;

            const selectedService = serviceRepository.find(item => item.id == serviceId);
            const baseFee = parseFloat(selectedService.fee);
            
            // Render Base Fee visually
            displayBase.innerText = '₱' + baseFee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            let totalDays = 0;

            if (startInput && endInput) {
                const startDate = new Date(startInput);
                const endDate = new Date(endInput);

                if (endDate > startDate) {
                    // Calculate precise millisecond differences and convert to days (Rounding Up)
                    const diffTime = Math.abs(endDate - startDate);
                    totalDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
                    
                    displayDuration.innerText = totalDays + (totalDays > 1 ? ' Days' : ' Day');
                    displayDuration.className = "text-sm font-extrabold text-brand-primary bg-brand-primary/10 px-3 py-1 rounded-full uppercase tracking-wider shadow-sm";
                } else {
                    displayDuration.innerText = 'Invalid Date Sequence';
                    displayDuration.className = "text-xs font-extrabold text-rose-500 bg-rose-50 border border-rose-100 px-3 py-1 rounded-full uppercase tracking-wider";
                }
            } else {
                displayDuration.innerText = 'Awaiting Selection';
                displayDuration.className = "text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full uppercase tracking-wider";
            }

            // Execute final total logic (Base * Multiplier)
            const finalCalculatedAmount = baseFee * (totalDays > 0 ? totalDays : 0);
            displayTotal.innerText = '₱' + finalCalculatedAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    </script>
    
    <?php if($alert_type === 'success'): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const progressBar = document.getElementById('redirect_progress_bar');
            const percentLabel = document.getElementById('progress_percent');
            
            const totalDuration = 1800; // Synchronize precisely with PHP micro-timers
            const frameRate = 18; 
            let elapsedTime = 0;

            const updateInterval = setInterval(function() {
                elapsedTime += frameRate;
                let currentPercentage = Math.min(Math.round((elapsedTime / totalDuration) * 100), 100);
                
                if (progressBar) progressBar.style.width = currentPercentage + '%';
                if (percentLabel) percentLabel.innerText = currentPercentage + '%';

                if (elapsedTime >= totalDuration) {
                    clearInterval(updateInterval);
                    window.location.href = '/user/encoder/availed-services';
                }
            }, frameRate);
        });
    </script>
    <?php endif; ?>
</body>
</html>