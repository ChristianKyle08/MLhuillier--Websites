<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';

// Possible Terms defined for the loop
$terms_list = ["OTS", "AT NEED BUYER", "1 Year", "2 Years", "3 Years", "5 Years", "6 Years", "7 Years", "8 Years", "9 Years", "10 Years"];

/**
 * Helper function to sort terms naturally (OTS first, then 1 Year, 2 Years, 10 Years, etc.)
 */
function sortPaymentTerms(&$termsArray) {
    usort($termsArray, function($a, $b) {
        // Define priority for non-numeric terms
        $priorities = ['OTS' => 0, 'AT NEED BUYER' => 1];
        
        $a_prio = isset($priorities[$a]) ? $priorities[$a] : 99;
        $b_prio = isset($priorities[$b]) ? $priorities[$b] : 99;

        if ($a_prio !== $b_prio) {
            return $a_prio - $b_prio;
        }

        // Natural sort for "1 Year", "2 Years", "10 Years"
        return strnatcasecmp($a, $b);
    });
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formatted_date = date('Y-m-d');
        $affected_rows = 0; 
        
        // New Global value for payment division
        $payment_division = $_POST['payment_division'] ?? 1;

        // Collect existing terms and any new terms added via the plus icon
        $all_submitted_terms = $terms_list;
        if (!empty($_POST['custom_terms'])) {
            foreach ($_POST['custom_terms'] as $custom_term) {
                if (!empty(trim($custom_term))) {
                    $all_submitted_terms[] = trim($custom_term);
                }
            }
        }

        // --- NEW LOGIC: REMOVE DELETED ROWS ---
        // Fetch current terms in DB before processing updates
        $stmt_existing = $pdo->query("SELECT release_day FROM commission_profiles");
        $db_terms_before = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);

        // Identify terms that exist in DB but are NOT in the submitted terms list
        $terms_to_delete = array_diff($db_terms_before, $all_submitted_terms);

        if (!empty($terms_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($terms_to_delete), '?'));
            $delete_stmt = $pdo->prepare("DELETE FROM commission_profiles WHERE release_day IN ($placeholders)");
            $delete_stmt->execute(array_values($terms_to_delete));
            $affected_rows += $delete_stmt->rowCount();
        }
        // --- END REMOVAL LOGIC ---

        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM commission_profiles WHERE release_day = :day");

        foreach ($all_submitted_terms as $term) {
            // Check if term is from static list or dynamic list
            $post_key = 'duration_' . str_replace(' ', '_', $term);
            
            // Handle values for dynamically added rows
            if (isset($_POST['custom_durations']) && !in_array($term, $terms_list)) {
                // Find index of custom term to get corresponding duration
                $idx = array_search($term, $_POST['custom_terms'] ?? []);
                $duration = $_POST['custom_durations'][$idx] ?? 'FULLCOMM';
            } else {
                $duration = $_POST[$post_key] ?? 'FULLCOMM';
            }

            // Check if this specific term exists
            $check_stmt->execute([':day' => $term]);
            $exists = $check_stmt->fetchColumn() > 0;

            if (!$exists) {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO commission_profiles (agent_pct, um_pct, broker_pct, release_day, start_date, duration, payment_division) 
                    VALUES (:agent, :um, :broker, :day, :start, :duration, :div)
                ");
                $insert_stmt->execute([
                    ':agent'    => $_POST['agent_pct'] ?? 0,
                    ':um'       => $_POST['um_pct'] ?? 0,
                    ':broker'   => $_POST['broker_pct'] ?? 0,
                    ':day'      => $term, 
                    ':start'    => $formatted_date, 
                    ':duration' => $duration,
                    ':div'      => $payment_division
                ]);
                $affected_rows += $insert_stmt->rowCount();
            } else {
                $update_stmt = $pdo->prepare("
                    UPDATE commission_profiles 
                    SET agent_pct = :agent, 
                        um_pct = :um, 
                        broker_pct = :broker, 
                        duration = :duration,
                        start_date = :start,
                        payment_division = :div
                    WHERE release_day = :day
                ");
                $update_stmt->execute([
                    ':agent'    => $_POST['agent_pct'] ?? 0,
                    ':um'       => $_POST['um_pct'] ?? 0,
                    ':broker'   => $_POST['broker_pct'] ?? 0,
                    ':duration' => $duration,
                    ':start'    => $formatted_date,
                    ':div'      => $payment_division,
                    ':day'      => $term
                ]);
                $affected_rows += $update_stmt->rowCount();
            }
        }
        
        header("Location: /cattleya/user/encoder/commission_config?success=1&count=" . $affected_rows);
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

// Fetch all profiles
try {
    // We fetch and then sort in PHP to ensure "10 Year" doesn't come before "2 Year" (Natural Sort)
    $all_profiles_stmt = $pdo->query("SELECT * FROM commission_profiles");
    $all_profiles = $all_profiles_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sort the viewing field array
    usort($all_profiles, function($a, $b) {
        $priorities = ['OTS' => 0, 'AT NEED BUYER' => 1];
        $a_val = $a['release_day'];
        $b_val = $b['release_day'];
        $a_prio = isset($priorities[$a_val]) ? $priorities[$a_val] : 99;
        $b_prio = isset($priorities[$b_val]) ? $priorities[$b_val] : 99;
        return ($a_prio !== $b_prio) ? ($a_prio - $b_prio) : strnatcasecmp($a_val, $b_val);
    });

    $mapped_profiles = [];
    foreach($all_profiles as $p) {
        $mapped_profiles[$p['release_day']] = $p['duration'];
    }

    $latest_stmt = $pdo->query("SELECT * FROM commission_profiles ORDER BY created_at DESC LIMIT 1");
    $latest = $latest_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_profiles = [];
    $latest = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Registry Hub | Cattleya Gardens</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-offset: 280px; --brand-teal: #1c5f66; --brand-dark: #0f172a; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; overflow-x: hidden; scroll-behavior: smooth; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .content-wrapper { margin-left: 10px; min-height: 100vh; transition: all 0.5s ease; }
        @media (max-width: 1024px) { .content-wrapper { margin-left: 0; } }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes scaleIn { from { transform: scale(0.98); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes slideInRight { from { transform: translateX(30px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        .animate-entrance { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .animate-scale { animation: scaleIn 0.4s ease-out forwards; }
        .animate-slide-right { animation: slideInRight 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        
        .compact-card { background: white; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03); transition: all 0.3s ease; }
        .compact-card:hover { box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.05); transform: translateY(-1px); }
        
        .input-pill { background: #f1f5f9; border: 1.5px solid transparent; border-radius: 0.6rem; transition: all 0.2s ease; }
        .input-pill:focus { border-color: var(--brand-teal); background: white; outline: none; box-shadow: 0 0 0 3px rgba(28, 95, 102, 0.1); }
        
        .btn-premium { background: var(--brand-dark); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .btn-premium:active { transform: scale(0.97); }
        .btn-premium:hover { background: var(--brand-teal); }

        .terms-container::-webkit-scrollbar { width: 4px; }
        .terms-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .glass-table thead th { background: #f8fafc; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.65rem; color: #64748b; padding: 0.75rem 1rem; border-bottom: 2px solid #f1f5f9; }
        .glass-table tbody tr:hover { background-color: #f8fafc; transform: scale(1.002); z-index: 10; }
    </style>
</head>
<body class="antialiased selection:bg-teal-100">
    <?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="p-4 lg:p-8 max-w-[1600px] mx-auto">

            <?php if (isset($_GET['success'])): ?>
            <div id="success-toast" class="mb-6 flex items-center justify-between p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm animate-entrance">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 bg-emerald-500 rounded-full flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-check text-white text-xs"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-emerald-900">Synchronized</h4>
                        <p class="text-[10px] text-emerald-600">Matrix updated successfully (<?= $_GET['count'] ?? 0 ?> rows affected).</p>
                    </div>
                </div>
                <button onclick="document.getElementById('success-toast').remove()" class="text-emerald-400 hover:text-emerald-600 p-2">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 animate-entrance">
                <div>
                    <div class="inline-flex items-center gap-2 px-2 py-0.5 rounded-full bg-teal-50 border border-teal-100 mb-2">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-teal-500"></span>
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-teal-700">Financial Node</span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Commission Setup</h1>
                </div>
                <button onclick="location.reload()" class="h-10 px-4 flex items-center gap-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 transition-all shadow-sm group">
                    <i class="fa-solid fa-arrows-rotate text-xs group-hover:rotate-180 transition-transform duration-700"></i>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Refresh System</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                <div class="lg:col-span-8 animate-scale">
                    <form action="" method="POST" class="compact-card p-6 border-t-4 border-t-slate-900">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Configuration Matrix</h3>
                            <i class="fa-solid fa-sliders text-slate-300"></i>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <?php 
                                $fields = [
                                    ['name' => 'agent_pct', 'label' => 'Agent %', 'val' => $latest['agent_pct'] ?? ''],
                                    ['name' => 'um_pct', 'label' => 'UM %', 'val' => $latest['um_pct'] ?? ''],
                                    ['name' => 'broker_pct', 'label' => 'Broker %', 'val' => $latest['broker_pct'] ?? '']
                                ];
                                foreach($fields as $f): ?>
                                <div class="group">
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase mb-1.5 ml-1"><?= $f['label'] ?></label>
                                    <div class="relative">
                                        <input type="number" step="0.01" id="<?= $f['name'] ?>_input" name="<?= $f['name'] ?>" value="<?= $f['val'] ?>" class="input-pill w-full p-3 font-black text-slate-800 text-base" placeholder="0.00" required>
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                                <label class="block text-[10px] font-black text-slate-700 uppercase tracking-widest mb-2">Schedule of Commission Distribution</label>
                                <div class="relative">
                                    <select name="payment_division" class="input-pill w-full p-3 font-bold text-slate-800 appearance-none cursor-pointer">
                                        <?php for($i=1; $i<=12; $i++): 
                                            $sel = (($latest['payment_division'] ?? 1) == $i) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $i ?>" <?= $sel ?>>Month <?= $i ?> Distribution Cycle</option>
                                        <?php endfor; ?>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                </div>
                                <p class="text-[9px] text-slate-400 mt-2 italic">* This selection will apply to all payment terms below upon saving.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-teal-900 rounded-xl text-white">
                                <div class="md:col-span-1">
                                    <p class="text-[8px] font-black uppercase text-teal-400">Net Distribution</p>
                                    <p class="text-xs text-teal-100/60 leading-tight mt-1">Total payout based on current inputs.</p>
                                </div>
                                <div class="flex flex-col items-center justify-center border-l border-teal-800">
                                    <span class="text-[10px] uppercase font-bold text-teal-400">Combined</span>
                                    <span id="total_pct_display" class="text-xl font-black mono">0.00%</span>
                                </div>
                                <div class="flex flex-col items-center justify-center border-l border-teal-800">
                                    <span class="text-[10px] uppercase font-bold text-teal-400">Company</span>
                                    <span id="company_pct_display" class="text-xl font-black mono text-emerald-400">100%</span>
                                </div>
                                <div class="flex items-center justify-center border-l border-teal-800">
                                    <div class="h-2 w-full bg-teal-800 rounded-full overflow-hidden mx-4">
                                        <div id="progress_bar_pct" class="h-full bg-emerald-400 transition-all duration-500" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-50/50 rounded-xl p-4 border border-slate-100">
                                <div class="flex items-center justify-between mb-3 px-2">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Payment Term</span>
                                    <div class="flex items-center gap-4">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase">Division Type</span>
                                        <button type="button" onclick="addNewTermRow()" class="h-5 w-5 bg-teal-600 text-white rounded-full flex items-center justify-center hover:bg-teal-700 transition-colors shadow-sm">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="dynamic-terms-wrapper" class="terms-container space-y-2 max-h-[320px] overflow-y-auto pr-2">
                                    <?php 
                                    // Combine static list and database unique terms
                                    $db_terms = array_column($all_profiles, 'release_day');
                                    $combined_terms = array_unique(array_merge($terms_list, $db_terms));
                                    
                                    // SORT THE UPDATING FIELDS
                                    sortPaymentTerms($combined_terms);

                                    foreach($combined_terms as $term): 
                                        $saved_duration = $mapped_profiles[$term] ?? 'FULLCOMM';
                                        $post_key = 'duration_' . str_replace(' ', '_', $term);
                                        $is_custom = !in_array($term, $terms_list);
                                    ?>
                                    <div class="grid grid-cols-2 gap-3 items-center group animate-entrance" style="animation-delay: 0.05s">
                                        <?php if($is_custom): ?>
                                            <input type="text" name="custom_terms[]" value="<?= $term ?>" class="p-2.5 bg-white border border-teal-200 rounded-lg text-[11px] font-bold text-teal-700 shadow-sm" readonly>
                                        <?php else: ?>
                                            <div class="p-2.5 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 shadow-sm group-hover:border-teal-200 transition-colors">
                                                <?= $term ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="flex gap-2">
                                            <select name="<?= $is_custom ? 'custom_durations[]' : $post_key ?>" class="input-pill w-full p-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                                                <?php 
                                                    $options = ['FULLCOMM', '3rd', '6th', '9th', '15th'];
                                                    foreach($options as $opt):
                                                        $selected = ($saved_duration == $opt) ? 'selected' : '';
                                                        $label = ($opt == 'FULLCOMM') ? 'Full Commission' : $opt . ' Division';
                                                        echo "<option value='$opt' $selected>$label</option>";
                                                    endforeach;
                                                ?>
                                            </select>
                                            <?php if($is_custom): ?>
                                                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-rose-400 hover:text-rose-600 p-1">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn-premium w-full text-white py-4 rounded-xl font-black text-[10px] uppercase tracking-[0.2em] shadow-lg shadow-slate-200 mt-2">
                                Push Registry Updates
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-4 space-y-6 animate-slide-right">
                    <div class="compact-card p-6 bg-slate-900 text-white border-none shadow-xl relative overflow-hidden group">
                        <div class="absolute -right-16 -top-16 h-48 w-48 bg-teal-500/10 rounded-full blur-2xl group-hover:bg-teal-500/20 transition-all duration-700"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-6">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Node Status</span>
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 text-[8px] font-bold rounded border border-emerald-500/30">ONLINE</span>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase mb-1">Last Modification</p>
                                    <p class="text-2xl font-black mono text-teal-400">
                                        <?php 
                                        if (isset($_GET['count'])) {
                                            echo $_GET['count'] . ' ROWS';
                                        } elseif ($latest) {
                                            $date = new DateTime($latest['created_at']);
                                            $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                            echo $date->format('H:i:s');
                                        } else {
                                            echo '--:--:--';
                                        }
                                        ?>
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="p-3 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                                        <p class="text-[8px] font-bold text-slate-500 uppercase mb-0.5">Total Nodes</p>
                                        <p class="text-lg font-black"><?= count($all_profiles) ?></p>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                                        <p class="text-[8px] font-bold text-slate-500 uppercase mb-0.5">Health</p>
                                        <p class="text-lg font-black text-emerald-400">98%</p>
                                    </div>
                                </div>
                                
                                <div class="pt-4 border-t border-white/10">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-[9px] text-slate-400 uppercase font-black">Integrity</p>
                                        <p class="text-[9px] text-teal-400 mono">92%</p>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/10 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-teal-500 to-emerald-400 w-[92%] transition-all duration-1000"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="compact-card p-6 bg-white">
                        <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-teal-600"></i> Logic Guide
                        </h4>
                        <ul class="space-y-4">
                            <li class="flex gap-3">
                                <div class="h-5 w-5 rounded bg-slate-100 flex items-center justify-center shrink-0">
                                    <span class="text-[10px] font-bold text-slate-600">01</span>
                                </div>
                                <p class="text-[10px] text-slate-500 leading-relaxed"><strong class="text-slate-700">OTS / At-Need:</strong> Immediate release configuration. No installment division required.</p>
                            </li>
                            <li class="flex gap-3">
                                <div class="h-5 w-5 rounded bg-slate-100 flex items-center justify-center shrink-0">
                                    <span class="text-[10px] font-bold text-slate-600">02</span>
                                </div>
                                <p class="text-[10px] text-slate-500 leading-relaxed"><strong class="text-slate-700">Division (Nth):</strong> Specifies which installment triggers the commission release for long-term plans.</p>
                            </li>
                            <li class="flex gap-3">
                                <div class="h-5 w-5 rounded bg-slate-100 flex items-center justify-center shrink-0">
                                    <span class="text-[10px] font-bold text-slate-600">03</span>
                                </div>
                                <p class="text-[10px] text-slate-500 leading-relaxed"><strong class="text-slate-700">Persistence:</strong> Updates apply to <span class="text-teal-600 font-bold">all active terms</span> simultaneously to ensure matrix parity.</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="animate-entrance" style="animation-delay: 0.1s">
                <div class="compact-card overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-800">Global Matrix View</h3>
                        <div class="flex gap-2">
                             <span class="hidden md:flex items-center text-[9px] font-bold text-slate-400 uppercase mr-4">
                                <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Auto-synced with DB
                             </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left glass-table">
                            <thead>
                                <tr>
                                    <th>Term Identifier</th>
                                    <th>Agent Payout</th>
                                    <th>UM Payout</th>
                                    <th>Broker Payout</th>
                                    <th>Trigger Mode</th>
                                    <th>Schedule</th>
                                    <th>Effective Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if($all_profiles): foreach($all_profiles as $row): ?>
                                <tr class="transition-all duration-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-1.5 rounded-full bg-teal-500"></div>
                                            <span class="font-bold text-slate-700 text-xs"><?= $row['release_day'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 mono text-xs text-slate-600"><?= number_format($row['agent_pct'], 2) ?>%</td>
                                    <td class="px-4 py-3 mono text-xs text-slate-600"><?= number_format($row['um_pct'], 2) ?>%</td>
                                    <td class="px-4 py-3 mono text-xs text-slate-600"><?= number_format($row['broker_pct'], 2) ?>%</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-slate-100 text-slate-600 border border-slate-200"><?= $row['duration'] ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-bold text-teal-600">Month <?= $row['payment_division'] ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-slate-400 font-bold"><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="7" class="p-12 text-center opacity-40">
                                        <i class="fa-solid fa-database text-2xl mb-2"></i>
                                        <p class="text-[10px] font-black uppercase tracking-widest">No Data</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <footer class="mt-12 py-6 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4 opacity-60">
                <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400">&copy; <?= date('Y') ?> Cattleya Gardens Infrastructure</p>
                <div class="flex gap-4">
                    <a href="#" class="text-[9px] font-bold text-slate-400 hover:text-teal-600 uppercase tracking-widest">Docs</a>
                    <a href="#" class="text-[9px] font-bold text-slate-400 hover:text-teal-600 uppercase tracking-widest">Logs</a>
                    <a href="#" class="text-[9px] font-bold text-slate-400 hover:text-teal-600 uppercase tracking-widest">Security</a>
                </div>
            </footer>
        </main>
    </div>

    <script>
        const agentIn = document.getElementById('agent_pct_input');
        const umIn = document.getElementById('um_pct_input');
        const brokerIn = document.getElementById('broker_pct_input');
        const totalDisplay = document.getElementById('total_pct_display');
        const companyDisplay = document.getElementById('company_pct_display');
        const progBar = document.getElementById('progress_bar_pct');

        function updateMath() {
            const a = parseFloat(agentIn.value) || 0;
            const u = parseFloat(umIn.value) || 0;
            const b = parseFloat(brokerIn.value) || 0;
            const total = a + u + b;
            const company = Math.max(0, 100 - total);

            totalDisplay.innerText = total.toFixed(2) + '%';
            companyDisplay.innerText = company.toFixed(2) + '%';
            progBar.style.width = total + '%';
            
            if(total > 100) {
                totalDisplay.classList.add('text-rose-400');
                progBar.classList.replace('bg-emerald-400', 'bg-rose-500');
            } else {
                totalDisplay.classList.remove('text-rose-400');
                progBar.classList.replace('bg-rose-500', 'bg-emerald-400');
            }
        }

        agentIn.addEventListener('input', updateMath);
        umIn.addEventListener('input', updateMath);
        brokerIn.addEventListener('input', updateMath);
        window.onload = updateMath;

        function addNewTermRow() {
            const wrapper = document.getElementById('dynamic-terms-wrapper');
            const newRow = document.createElement('div');
            newRow.className = "grid grid-cols-2 gap-3 items-center animate-entrance";
            newRow.innerHTML = `
                <input type="text" name="custom_terms[]" placeholder="Enter Term Name" required
                       class="p-2.5 bg-white border border-teal-200 rounded-lg text-[11px] font-bold text-teal-700 shadow-sm focus:outline-none focus:ring-1 focus:ring-teal-500">
                <div class="flex gap-2">
                    <select name="custom_durations[]" class="input-pill w-full p-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                        <option value="FULLCOMM">Full Commission</option>
                        <option value="3rd">3rd Division</option>
                        <option value="6th">6th Division</option>
                        <option value="9th">9th Division</option>
                        <option value="15th">15th Division</option>
                    </select>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-rose-400 hover:text-rose-600 p-1">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `;
            wrapper.appendChild(newRow);
        }
    </script>
</body>
</html>