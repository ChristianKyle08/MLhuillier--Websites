<?php
// Include your system configurations safely
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

// Set timezone to Philippine Time for all date/time functions
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$alert_message = '';
$alert_type = '';

// --- POST HANDLER: STATUS UPDATE ENGINE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_submit'])) {
    try {
        $record_id = intval($_POST['record_id'] ?? 0);
        $new_status = trim($_POST['new_status'] ?? '');
        $amount_paid = floatval($_POST['amount_paid'] ?? 0);
        // NEW: Capture the change amount from the submitted form
        $change_amount = floatval($_POST['change_amount'] ?? 0);

        if (empty($record_id) || empty($new_status)) {
            throw new Exception("Invalid request parameters. Cannot process status update.");
        }

        // NEW: Updated query to save amount_paid AND change_amount into the ledger
        $stmtUpdate = $pdo->prepare("UPDATE avail_services SET status = ?, amount_paid = ?, change_amount = ? WHERE id = ?");
        if ($stmtUpdate->execute([$new_status, $amount_paid, $change_amount, $record_id])) {
            $alert_message = "Transaction status successfully marked as {$new_status} with payment of ₱" . number_format($amount_paid, 2) . ".";
            $alert_type = "success";
        } else {
            throw new Exception("Failed to commit the status update to the database ledger.");
        }
    } catch (Exception $e) {
        $alert_message = $e->getMessage();
        $alert_type = "error";
    }
}

// --- DATA ACCESS LAYER: FETCH ALL AVAILMENTS ---
$availed_records = [];
$total_records = 0;
$pending_count = 0;
$paid_count = 0;

try {
    $stmt = $pdo->query("SELECT * FROM avail_services ORDER BY date_avail DESC");
    $availed_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics for the informative dashboard
    $total_records = count($availed_records);
    foreach($availed_records as $rec) {
        if(strtolower($rec['status']) === 'pending') {
            $pending_count++;
        } else {
            $paid_count++;
        }
    }
} catch (Exception $e) {
    error_log("Failed fetching availed records: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Manage Availed Services | Cattleya Premium Portal</title>
    <meta name="description" content="View, manage, and update the status of availed premium services, track financial mappings, and oversee reservation ledgers securely.">
    
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
                            light: '#f0f5f7'
                        }
                    },
                    boxShadow: {
                        'premium': '0 10px 30px -5px rgba(42, 98, 121, 0.08)',
                        'inner-premium': 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7f9; }
        
        /* Optimized scrollbar for large datasets */
        .table-scroll { max-height: 65vh; overflow-y: auto; }
        .table-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .table-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Sticky header for thousands of rows */
        thead th { position: sticky; top: 0; z-index: 10; background-color: rgba(248, 250, 252, 0.95); backdrop-filter: blur(8px); }
        
        /* Premium Gradients */
        .premium-gradient { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex flex-col justify-between">
    
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

    <main class="flex-1 p-4 md:p-6 lg:p-8 max-w-[90rem] mx-auto w-full flex flex-col gap-6" aria-label="Main Content">
        
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <span class="px-2.5 py-1 bg-brand-primary/10 text-brand-primary rounded-md text-[10px] font-bold uppercase tracking-widest mb-2 inline-block">Ledger Tracking</span>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900">Availed Product / Services</h1>
                <p class="text-slate-500 text-sm mt-1 max-w-2xl leading-relaxed">Manage your service pipeline and operational transaction states dynamically.</p>
            </div>
            
            <a href="/user/encoder/avail-services" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-brand-primary to-brand-dark text-white text-xs text-decoration-none font-bold uppercase tracking-wider rounded-lg hover:shadow-lg hover:shadow-brand-primary/30 transform hover:-translate-y-0.5 transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary">
                <i class="fa-solid fa-plus"></i> Avail New Product / Services
            </a>
        </header>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-4" aria-label="Quick Statistics">
            <article class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex items-center justify-between group hover:border-brand-primary/20 transition-colors">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Records</p>
                    <h3 class="text-3xl font-extrabold text-slate-800"><?= number_format($total_records) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </article>
            <article class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex items-center justify-between group hover:border-amber-500/20 transition-colors">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pending Settlements</p>
                    <h3 class="text-3xl font-extrabold text-amber-600"><?= number_format($pending_count) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </article>
            <article class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex items-center justify-between group hover:border-emerald-500/20 transition-colors">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Completed / Paid</p>
                    <h3 class="text-3xl font-extrabold text-emerald-600"><?= number_format($paid_count) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </article>
        </section>

        <?php if(!empty($alert_message)): ?>
            <div class="p-4 rounded-xl flex items-center gap-3 text-sm font-semibold border <?= $alert_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?> shadow-sm animate-fade-in" role="alert">
                <i class="fa-solid <?= $alert_type === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-circle-exclamation text-rose-500' ?> text-lg"></i>
                <span><?= htmlspecialchars($alert_message) ?></span>
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-2xl border border-slate-200 shadow-premium overflow-hidden flex-1 flex flex-col relative">
            
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="relative w-full sm:w-[28rem]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                    </div>
                    <input type="text" id="tableSearch" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-primary/30 focus:border-brand-primary transition-all sm:text-sm shadow-sm" placeholder="Search customer, service, GL code, or status...">
                </div>
                <div class="text-xs text-slate-500 font-medium w-full sm:w-auto text-right bg-white py-1.5 px-3 rounded-lg border border-slate-100 shadow-sm">
                    Showing <span id="visibleCount" class="font-bold text-brand-primary"><?= $total_records ?></span> entries
                </div>
            </div>

            <div class="table-scroll overflow-x-auto w-full">
                <table class="w-full text-left border-collapse whitespace-nowrap" id="servicesTable">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-widest text-slate-500 font-bold border-b border-slate-200 shadow-sm">
                            <th class="px-5 py-4 pl-6">Reference Details</th>
                            <th class="px-5 py-4">Service Matrix</th>
                            <th class="px-5 py-4">Schedules</th>
                            <th class="px-5 py-4 text-right">Base Fee</th>
                            <th class="px-5 py-4 text-center">Status</th>
                            <th class="px-5 py-4 text-center pr-6">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if(count($availed_records) > 0): ?>
                            <?php foreach($availed_records as $record): 
                                // Safely fetch optional data for details modal
                                $created_at = isset($record['created_at']) ? date('M d, Y h:i A', strtotime($record['created_at'])) : 'N/A';
                                $updated_at = isset($record['updated_at']) ? date('M d, Y h:i A', strtotime($record['updated_at'])) : 'N/A';
                                $amt_paid = isset($record['amount_paid']) ? number_format($record['amount_paid'], 2) : '0.00';
                                $change_amt = isset($record['change_amount']) ? number_format($record['change_amount'], 2) : '0.00';
                                $res_start = date('M d, Y h:i A', strtotime($record['reservation_start']));
                                $res_end = date('M d, Y h:i A', strtotime($record['reservation_end']));
                            ?>
                                <tr class="hover:bg-slate-50 transition-colors group data-row">
                                    <td class="px-5 py-3.5 pl-6">
                                        <p class="font-bold text-slate-800 customer-name"><?= htmlspecialchars($record['customer_fullname']) ?></p>
                                        <p class="text-[11px] text-slate-500 mt-0.5"><i class="fa-solid fa-phone text-slate-300 mr-1"></i> <?= htmlspecialchars($record['mobile_number']) ?></p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="font-bold text-brand-primary service-name"><?= htmlspecialchars($record['services_name']) ?></p>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600 border border-slate-200 gl-code">GL: <?= htmlspecialchars($record['gl_code']) ?></span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="text-[11px] text-slate-600"><span class="font-semibold text-slate-400 w-7 inline-block">IN:</span> <?= $res_start ?></p>
                                        <p class="text-[11px] text-slate-600 mt-0.5"><span class="font-semibold text-slate-400 w-7 inline-block">OUT:</span> <?= $res_end ?></p>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-700">
                                        ₱<?= number_format($record['fee'], 2) ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-center record-status">
                                        <?php if(strtolower($record['status']) === 'pending'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200 uppercase tracking-wide">
                                                <i class="fa-solid fa-clock"></i> Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 uppercase tracking-wide">
                                                <i class="fa-solid fa-check-double"></i> Paid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-center pr-6">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" onclick="openDetailsModal('<?= htmlspecialchars(addslashes($record['customer_fullname'])) ?>', '<?= htmlspecialchars(addslashes($record['services_name'])) ?>', '<?= htmlspecialchars(addslashes($record['gl_code'])) ?>', '<?= $res_start ?>', '<?= $res_end ?>', '<?= number_format($record['fee'], 2) ?>', '<?= htmlspecialchars(addslashes($record['status'])) ?>', '<?= $amt_paid ?>', '<?= $change_amt ?>', '<?= $created_at ?>', '<?= $updated_at ?>')" class="px-2 py-2 bg-blue-50 text-blue-600 border border-blue-200 rounded-lg text-[11px] font-bold hover:bg-blue-600 hover:text-white transition-all shadow-sm flex items-center justify-center focus:ring-2 focus:ring-offset-1 focus:ring-blue-500" title="View Full Details">
                                                <i class="fa-solid fa-eye text-[12px]"></i>
                                            </button>

                                            <?php if(strtolower($record['status']) === 'pending'): ?>
                                                <button type="button" onclick="openPaymentModal(<?= $record['id'] ?>, <?= $record['fee'] ?>, '<?= htmlspecialchars(addslashes($record['customer_fullname'])) ?>', '<?= htmlspecialchars(addslashes($record['services_name'])) ?>', '<?= htmlspecialchars(addslashes($record['gl_code'])) ?>')" class="px-3 py-2 bg-slate-800 text-white rounded-lg text-[11px] font-bold hover:bg-brand-primary transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-1.5 focus:ring-2 focus:ring-offset-1 focus:ring-slate-800" title="Process Payment">
                                                    <i class="fa-solid fa-file-invoice-dollar text-[12px]"></i> Process
                                                </button>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[11px] font-semibold flex items-center justify-center gap-1 bg-slate-50 py-1.5 px-3 rounded-lg border border-slate-100">
                                                    <i class="fa-solid fa-lock text-[10px]"></i> Settled
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if(count($availed_records) === 0): ?>
                            <tr id="emptyStateRow">
                                <td colspan="6" class="px-4 py-20 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 text-slate-300 mb-4 border border-slate-100 shadow-inner">
                                        <i class="fa-solid fa-folder-open text-2xl"></i>
                                    </div>
                                    <p class="text-slate-800 font-bold text-lg">No Service Records</p>
                                    <p class="text-slate-500 font-medium text-sm mt-1">Your ledger is currently empty.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr id="noSearchResultRow" style="display: none;">
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-50 text-rose-300 mb-3 border border-rose-100">
                                    <i class="fa-solid fa-magnifying-glass-minus text-xl"></i>
                                </div>
                                <p class="text-slate-800 font-bold text-base">No matches found</p>
                                <p class="text-slate-500 text-sm mt-1 mb-3">We couldn't find any records matching your search.</p>
                                <button type="button" onclick="document.getElementById('tableSearch').value=''; document.getElementById('tableSearch').dispatchEvent(new Event('keyup'));" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs hover:bg-slate-200 transition-colors font-bold">Clear Search</button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="detailsModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300 opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 border border-white/20" id="detailsModalContent">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-brand-primary text-white">
                <h3 class="text-lg font-extrabold flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info"></i> Full Transaction Details
                </h3>
                <button type="button" onclick="closeDetailsModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white hover:text-rose-500 transition-all focus:outline-none">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-6 bg-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Customer</p>
                            <p class="text-base font-extrabold text-slate-800" id="detCustomer"></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Product / Service</p>
                            <p class="text-sm font-bold text-brand-primary" id="detService"></p>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-mono bg-white text-slate-600 border border-slate-200" id="detGlCode"></span>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Status</p>
                            <p class="text-sm font-bold" id="detStatus"></p>
                        </div>
                    </div>
                    <div class="space-y-4 bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-xs font-bold text-slate-500">Base Fee:</span>
                            <span class="text-sm font-mono font-bold text-slate-800" id="detFee"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-xs font-bold text-slate-500">Amount Paid:</span>
                            <span class="text-sm font-mono font-bold text-emerald-600" id="detPaid"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs font-bold text-slate-500">Change Returned:</span>
                            <span class="text-sm font-mono font-bold text-amber-600" id="detChange"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200">
                    <div class="col-span-2 md:col-span-1">
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Reservation Schedule</p>
                        <p class="text-xs text-slate-600"><strong class="text-slate-400">IN:</strong> <span id="detIn"></span></p>
                        <p class="text-xs text-slate-600"><strong class="text-slate-400">OUT:</strong> <span id="detOut"></span></p>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-white flex justify-end">
                <button type="button" onclick="closeDetailsModal()" class="px-5 py-2.5 bg-slate-100 border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-200 transition-all focus:outline-none">Close Details</button>
            </div>
        </div>
    </div>

    <div id="paymentModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300 opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 border border-white/20" id="paymentModalContent">
            
            <div class="p-5 border-b border-slate-100 flex justify-between items-center premium-gradient">
                <h3 class="text-lg font-extrabold text-slate-800 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center text-sm">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    Billing Settlement
                </h3>
                <button type="button" onclick="closePaymentModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-all focus:outline-none shadow-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <form action="" method="POST" class="p-6 space-y-6" id="paymentForm">
                <input type="hidden" name="record_id" id="modalRecordId">
                <input type="hidden" name="new_status" value="Paid">
                <input type="hidden" name="change_amount" id="formChangeAmount" value="0">
                
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 shadow-inner-premium relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-brand-primary"></div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Billed To</p>
                            <p class="text-sm font-extrabold text-slate-800 truncate" id="modalCustomerName"></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Reference / GL</p>
                            <p class="text-sm font-bold text-slate-600 font-mono" id="modalGlCodeDisplay"></p>
                        </div>
                        <div class="col-span-2 pt-2 border-t border-slate-200/60">
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Service Particulars</p>
                            <p class="text-sm font-bold text-brand-primary" id="modalServiceNameDisplay"></p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center bg-gradient-to-r from-brand-dark to-brand-primary p-5 rounded-xl shadow-lg shadow-brand-primary/20 text-white">
                    <span class="text-sm font-semibold text-white/80 uppercase tracking-wider">Total Amount Due</span>
                    <span class="text-3xl font-extrabold font-mono tracking-tight drop-shadow-md" id="modalBaseFeeDisplay"></span>
                </div>

                <div class="space-y-4 pt-2">
                    <div>
                        <label for="receivedAmount" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Cash Received</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 font-bold font-mono text-lg">₱</span>
                                </div>
                                <input type="number" step="0.01" min="0" name="amount_paid" id="receivedAmount" class="block w-full pl-10 pr-4 py-3.5 border-2 border-slate-200 rounded-xl leading-5 bg-white placeholder-slate-300 focus:outline-none focus:ring-0 focus:border-brand-primary transition-colors text-xl font-mono font-bold text-slate-800 shadow-sm" placeholder="0.00" required>
                            </div>
                            
                            <button type="button" onclick="resetComputation()" class="px-4 py-3 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl hover:bg-slate-200 hover:text-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-300 shadow-sm" title="Compute Again">
                                <i class="fa-solid fa-rotate-right text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="changeAmount" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Change to Return</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-emerald-500/70 font-bold font-mono text-lg">₱</span>
                            </div>
                            <input type="text" id="changeAmount" class="block w-full pl-10 pr-4 py-3.5 border-2 border-emerald-100 rounded-xl leading-5 bg-emerald-50/70 text-emerald-700 transition-colors text-xl font-mono font-extrabold shadow-inner-premium" value="0.00" readonly>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex gap-3">
                    <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-3.5 bg-white border-2 border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition-all focus:outline-none">
                        Cancel
                    </button>
                    <button type="submit" name="update_status_submit" id="markPaidBtn" class="flex-[2] px-4 py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl text-sm font-bold hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/30 opacity-50 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" disabled>
                        <i class="fa-solid fa-shield-check mr-1.5"></i> Confirm Settlement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- GLOBAL JAVASCRIPT ENGINE ---
        let currentPaymentFee = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Search Logic
            const searchInput = document.getElementById('tableSearch');
            const tableRows = document.querySelectorAll('.data-row');
            const noSearchResultRow = document.getElementById('noSearchResultRow');
            const visibleCountDisplay = document.getElementById('visibleCount');

            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let visibleCount = 0;

                    tableRows.forEach(row => {
                        const textData = row.textContent.toLowerCase();
                        if (textData.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    visibleCountDisplay.textContent = visibleCount;
                    
                    // Fixed missing logic constraint 
                    if (visibleCount === 0 && tableRows.length > 0) {
                        noSearchResultRow.style.display = '';
                    } else {
                        noSearchResultRow.style.display = 'none';
                    }
                });
            }

            // 2. Real-time Payment & Change Calculation
            const receivedInput = document.getElementById('receivedAmount');
            const changeDisplay = document.getElementById('changeAmount');
            const changeHidden = document.getElementById('formChangeAmount');
            const confirmBtn = document.getElementById('markPaidBtn');

            if (receivedInput) {
                receivedInput.addEventListener('input', function() {
                    let cashReceived = parseFloat(this.value) || 0;
                    
                    if (cashReceived >= currentPaymentFee && currentPaymentFee > 0) {
                        let change = cashReceived - currentPaymentFee;
                        changeDisplay.value = change.toFixed(2);
                        changeHidden.value = change.toFixed(2);
                        
                        // Enable button
                        confirmBtn.disabled = false;
                        confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        changeDisplay.value = '0.00';
                        changeHidden.value = '0';
                        
                        // Disable button
                        confirmBtn.disabled = true;
                        confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                });
            }
        });

        // --- UTILITY & MODAL FUNCTIONS ---

        function resetComputation() {
            document.getElementById('receivedAmount').value = '';
            document.getElementById('changeAmount').value = '0.00';
            document.getElementById('formChangeAmount').value = '0';
            
            const btn = document.getElementById('markPaidBtn');
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            
            document.getElementById('receivedAmount').focus();
        }

        function openDetailsModal(customer, service, gl, start, end, fee, status, paid, change, created, updated) {
            // Map details to the DOM
            document.getElementById('detCustomer').textContent = customer;
            document.getElementById('detService').textContent = service;
            document.getElementById('detGlCode').textContent = 'GL: ' + gl;
            document.getElementById('detIn').textContent = start;
            document.getElementById('detOut').textContent = end;
            document.getElementById('detFee').textContent = '₱' + fee;
            document.getElementById('detPaid').textContent = '₱' + paid;
            document.getElementById('detChange').textContent = '₱' + change;

            // Beautifully format status badge
            const statusContainer = document.getElementById('detStatus');
            if(status.toLowerCase() === 'pending') {
                statusContainer.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200 uppercase tracking-wide"><i class="fa-solid fa-clock"></i> Pending</span>';
            } else {
                statusContainer.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 uppercase tracking-wide"><i class="fa-solid fa-check-double"></i> Paid</span>';
            }

            // Animate In
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsModalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);
        }

        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsModalContent');
            
            // Animate Out
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function openPaymentModal(id, fee, customer, service, gl) {
            document.getElementById('modalRecordId').value = id;
            currentPaymentFee = parseFloat(fee);
            
            document.getElementById('modalBaseFeeDisplay').textContent = '₱' + currentPaymentFee.toFixed(2);
            document.getElementById('modalCustomerName').textContent = customer;
            document.getElementById('modalServiceNameDisplay').textContent = service;
            document.getElementById('modalGlCodeDisplay').textContent = gl;
            
            resetComputation(); // clear any previous inputs

            // Animate In
            const modal = document.getElementById('paymentModal');
            const content = document.getElementById('paymentModalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);
        }

        function closePaymentModal() {
            const modal = document.getElementById('paymentModal');
            const content = document.getElementById('paymentModalContent');
            
            // Animate Out
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>