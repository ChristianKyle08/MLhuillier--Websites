<?php
session_start();

$base = '/cattleya';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

$uri = trim($uri, '/');
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle RFP file uploads stream
if (preg_match('#^/uploads/rfp_forms/(.+\.pdf)$#', $request_uri, $matches)) {
    $fileName = basename($matches[1]);
    $filePath = __DIR__ . '/../uploads/rfp_forms/' . $fileName;

    if (file_exists($filePath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "404 - File not found";
        exit;
    }
}

// Define system routes mapping to common files
$routes = [
    ''                         => 'public/index.php',
    'login'                    => 'views/auth/login.php',
    'register'                 => 'views/auth/register.php',
    'register-process'         => 'views/auth/register_process.php',
    'login-process'            => 'views/auth/login_process.php',
    'logout'                   => 'views/auth/logout.php',
    'forgot-password'          => 'views/auth/forgot_password.php',
    'forgot-password-process'  => 'views/auth/forgot_password_process.php',
    'change-password'          => 'views/auth/change_password.php',
    'check-default-password'   => 'views/auth/check_default_password.php',
    'auth/update-users'        => 'views/auth/update_user.php',
    
    // Admin Dashboard
    'admin/dashboard'          => 'views/admin/dashboard.php',

    // Role-specific URIs routed directly to common files
    // 1. Dashboards
    'user/encoder/dashboard'                  => 'views/user/common/dashboard.php',
    'user/cashier/dashboard'                  => 'views/user/common/dashboard.php',
    'user/auditor/dashboard'                  => 'views/user/common/dashboard.php',
    'user/finance/dashboard'                  => 'views/user/common/dashboard.php',
    'user/cfo/dashboard'                      => 'views/user/common/dashboard.php',
    'user/vpo/dashboard'                      => 'views/user/common/dashboard.php',
    'user/operation_manager/dashboard'        => 'views/user/common/dashboard.php',

    // 2. Encoder Routes
    'user/encoder/inventory'                  => 'views/user/common/inventory.php',
    'user/encoder/payment'                    => 'views/user/common/payment.php',
    'user/encoder/waive-penalty-request'      => 'views/user/common/waive_penalty_request.php',
    'user/encoder/product'                    => 'views/user/common/product.php',
    'user/encoder/commission-config'          => 'views/user/common/commission_config.php',
    'user/encoder/commission-release'         => 'views/user/common/commission_release.php',
    'user/encoder/registration'               => 'views/user/common/registration.php',
    'user/encoder/gl-settings'                 => 'views/user/common/gl_settings.php',
    'user/encoder/services-control'           => 'views/user/common/services_control.php',
    'user/encoder/avail-services'              => 'views/user/common/avail_services.php',
    'user/encoder/availed-services'            => 'views/user/common/availed_services.php',
    'user/encoder/rfp-approval-flow'          => 'views/user/common/rfp_approval_flow.php',
    'user/encoder/all-pending-rfps'           => 'views/user/common/all_pending_rfps.php',

    // 3. Cashier Routes
    'user/cashier/inventory'                  => 'views/user/common/inventory.php',
    'user/cashier/payment'                    => 'views/user/common/payment.php',
    'user/cashier/waive-penalty-request'      => 'views/user/common/waive_penalty_request.php',
    'user/cashier/product'                    => 'views/user/common/product.php',
    'user/cashier/commission-config'          => 'views/user/common/commission_config.php',
    'user/cashier/commission-release'         => 'views/user/common/commission_release.php',
    'user/cashier/registration'               => 'views/user/common/registration.php',
    'user/cashier/gl-settings'                 => 'views/user/common/gl_settings.php',
    'user/cashier/services-control'           => 'views/user/common/services_control.php',
    'user/cashier/avail-services'              => 'views/user/common/avail_services.php',
    'user/cashier/availed-services'            => 'views/user/common/availed_services.php',
    'user/cashier/rfp-approval-flow'          => 'views/user/common/rfp_approval_flow.php',
    'user/cashier/all-pending-rfps'           => 'views/user/common/all_pending_rfps.php',

    // 4. Auditor Routes
    'user/auditor/inventory'                  => 'views/user/common/inventory.php',
    'user/auditor/payment'                    => 'views/user/common/payment.php',
    'user/auditor/waive-penalty-request'      => 'views/user/common/waive_penalty_request.php',
    'user/auditor/product'                    => 'views/user/common/product.php',
    'user/auditor/commission-config'          => 'views/user/common/commission_config.php',
    'user/auditor/commission-release'         => 'views/user/common/commission_release.php',
    'user/auditor/registration'               => 'views/user/common/registration.php',
    'user/auditor/gl-settings'                 => 'views/user/common/gl_settings.php',
    'user/auditor/services-control'           => 'views/user/common/services_control.php',
    'user/auditor/avail-services'              => 'views/user/common/avail_services.php',
    'user/auditor/availed-services'            => 'views/user/common/availed_services.php',
    'user/auditor/rfp-approval-flow'          => 'views/user/common/rfp_approval_flow.php',
    'user/auditor/all-pending-rfps'           => 'views/user/common/all_pending_rfps.php',

    // 5. Finance Routes
    'user/finance/inventory'                  => 'views/user/common/inventory.php',
    'user/finance/payment'                    => 'views/user/common/payment.php',
    'user/finance/waive-penalty-request'      => 'views/user/common/waive_penalty_request.php',
    'user/finance/product'                    => 'views/user/common/product.php',
    'user/finance/commission-config'          => 'views/user/common/commission_config.php',
    'user/finance/commission-release'         => 'views/user/common/commission_release.php',
    'user/finance/registration'               => 'views/user/common/registration.php',
    'user/finance/gl-settings'                 => 'views/user/common/gl_settings.php',
    'user/finance/services-control'           => 'views/user/common/services_control.php',
    'user/finance/avail-services'              => 'views/user/common/avail_services.php',
    'user/finance/availed-services'            => 'views/user/common/availed_services.php',
    'user/finance/rfp-approval-flow'          => 'views/user/common/rfp_approval_flow.php',
    'user/finance/all-pending-rfps'           => 'views/user/common/all_pending_rfps.php',

    // 6. CFO Routes
    'user/cfo/inventory'                      => 'views/user/common/inventory.php',
    'user/cfo/payment'                        => 'views/user/common/payment.php',
    'user/cfo/waive-penalty-request'          => 'views/user/common/waive_penalty_request.php',
    'user/cfo/product'                        => 'views/user/common/product.php',
    'user/cfo/commission-config'              => 'views/user/common/commission_config.php',
    'user/cfo/commission-release'             => 'views/user/common/commission_release.php',
    'user/cfo/registration'                   => 'views/user/common/registration.php',
    'user/cfo/gl-settings'                     => 'views/user/common/gl_settings.php',
    'user/cfo/services-control'               => 'views/user/common/services_control.php',
    'user/cfo/avail-services'                 => 'views/user/common/avail_services.php',
    'user/cfo/availed-services'               => 'views/user/common/availed_services.php',
    'user/cfo/rfp-approval-flow'              => 'views/user/common/rfp_approval_flow.php',
    'user/cfo/all-pending-rfps'               => 'views/user/common/all_pending_rfps.php',

    // 7. VPO Routes
    'user/vpo/inventory'                      => 'views/user/common/inventory.php',
    'user/vpo/payment'                        => 'views/user/common/payment.php',
    'user/vpo/waive-penalty-request'          => 'views/user/common/waive_penalty_request.php',
    'user/vpo/product'                        => 'views/user/common/product.php',
    'user/vpo/commission-config'              => 'views/user/common/commission_config.php',
    'user/vpo/commission-release'             => 'views/user/common/commission_release.php',
    'user/vpo/registration'                   => 'views/user/common/registration.php',
    'user/vpo/gl-settings'                     => 'views/user/common/gl_settings.php',
    'user/vpo/services-control'               => 'views/user/common/services_control.php',
    'user/vpo/avail-services'                 => 'views/user/common/avail_services.php',
    'user/vpo/availed-services'               => 'views/user/common/availed_services.php',
    'user/vpo/rfp-approval-flow'              => 'views/user/common/rfp_approval_flow.php',
    'user/vpo/all-pending-rfps'               => 'views/user/common/all_pending_rfps.php',

    // 8. Operation Manager Routes
    'user/operation_manager/inventory'             => 'views/user/common/inventory.php',
    'user/operation_manager/payment'               => 'views/user/common/payment.php',
    'user/operation_manager/waive-penalty-request' => 'views/user/common/waive_penalty_request.php',
    'user/operation_manager/product'               => 'views/user/common/product.php',
    'user/operation_manager/commission-config'     => 'views/user/common/commission_config.php',
    'user/operation_manager/commission-release'    => 'views/user/common/commission_release.php',
    'user/operation_manager/registration'          => 'views/user/common/registration.php',
    'user/operation_manager/gl-settings'            => 'views/user/common/gl_settings.php',
    'user/operation_manager/services-control'      => 'views/user/common/services_control.php',
    'user/operation_manager/avail-services'         => 'views/user/common/avail_services.php',
    'user/operation_manager/availed-services'       => 'views/user/common/availed_services.php',
    'user/operation_manager/rfp-approval-flow'       => 'views/user/common/rfp_approval_flow.php',
    'user/operation_manager/all-pending-rfps'       => 'views/user/common/all_pending_rfps.php',

    // Profile & Signatures
    'views/includes/user/profile'   => 'views/includes/user/profile.php',
    'views/includes/admin/profile'  => 'views/includes/admin/profile.php',
    'user/signature'                => 'views/user/common/signature.php',
    'user/save-signature'           => 'views/user/common/save_signature.php',

    // API & Data Endpoint Routes
    'user/fetch/add-sales'             => 'views/user/fetch/add_sales.php',
    'user/fetch/cancel-reservation'    => 'views/user/fetch/cancel_reservation.php',
    'user/fetch/get-blocks'            => 'views/user/fetch/get_blocks.php',
    'user/fetch/get-lots'              => 'views/user/fetch/get_lot.php',
    'user/fetch/get-product-details'   => 'views/user/fetch/get_product_details.php',
    'user/fetch/get-sale-details'      => 'views/user/fetch/get_sale_details.php',
    'user/fetch/save-lot'              => 'views/user/fetch/save_lot.php',
    'user/fetch/save-product'          => 'views/user/fetch/save_product.php',
    'user/fetch/update-product'        => 'views/user/fetch/update_product.php',
    'user/fetch/update-sale-status'    => 'views/user/fetch/update_sale_status.php',
    'user/fetch/get-suggestions'       => 'views/user/fetch/get_suggestions.php',
    'user/fetch/upload-photo'          => 'views/user/fetch/upload_photo.php',
    'user/fetch/process-registration'  => 'views/user/fetch/process_registration.php',
    'user/fetch/get-next-customer-id'  => 'views/user/fetch/get_next_customer_id.php',
    'user/fetch/get-customers'         => 'views/user/fetch/get_customers.php',
    'user/fetch/process-payment'       => 'views/user/fetch/process_payment.php',
    'user/fetch/request-waiver'        => 'views/user/fetch/request_waiver.php',
    'user/fetch/process-waive'         => 'views/user/fetch/process_waive.php',
    'user/fetch/view-receipt'          => 'views/user/fetch/view_receipt.php',
    'user/fetch/process-bounce-check'  => 'views/user/fetch/process_bounce_check.php',
    'user/fetch/process-clear-check'   => 'views/user/fetch/process_clear_check.php',

    // Admin Actions
    'admin/approve-user'   => 'views/admin/approve_user.php',
    'admin/delete-user'    => 'views/admin/delete_user.php',
    'admin/cancel-reset'   => 'views/admin/cancel_reset.php',
    'admin/reset-password' => 'views/admin/reset_password.php',
    'admin/users'          => 'views/admin/users.php',
];

// Check route existence
if (array_key_exists($uri, $routes)) {
    
    // =========================================================================
    // SYSTEM FEATURE ACCESS CONTROL
    // =========================================================================
    if (isset($_SESSION['user_id'])) {
        
        // ADMIN BYPASS
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {

            $adminOnlyRoutes = [
                'admin/dashboard',
                'admin/users',
                'admin/approve-user',
                'admin/delete-user',
                'admin/cancel-reset',
                'admin/reset-password',
                'auth/update-users',
            ];

            if (in_array($uri, $adminOnlyRoutes, true)) {
                http_response_code(403);
                echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px; color: #0f172a;'>";
                echo "<h1 style='color: #b45309; font-size: 3rem; margin-bottom: 10px;'>403 - Access Denied</h1>";
                echo "<p style='font-size: 1.2rem;'>This area is restricted to administrators.</p>";
                echo "<a href='/cattleya/login' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #065f46; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Return to Safe Area</a>";
                echo "</div>";
                exit;
            }
            
            require_once __DIR__ . '/../config/database.php';
            
            $stmt = $pdo->prepare("SELECT features FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $featureJson = $stmt->fetchColumn();
            
            $userFeatures = $featureJson ? json_decode($featureJson, true) : [];
            if (!is_array($userFeatures)) {
                $userFeatures = [];
            }

            // Map System URIs directly to dynamic Feature Keys
            $featureMap = [
                // Dashboards
                'user/encoder/dashboard'                          => 'encoder_dashboard',
                'user/cashier/dashboard'                          => 'cashier_dashboard',
                'user/auditor/dashboard'                          => 'auditor_dashboard',
                'user/finance/dashboard'                          => 'finance_dashboard',
                'user/cfo/dashboard'                              => 'cfo_dashboard',
                'user/vpo/dashboard'                              => 'vpo_dashboard',
                'user/operation_manager/dashboard'                => 'operation_manager_dashboard',

                // Inventory
                'user/encoder/inventory'                          => 'encoder_inventory',
                'user/cashier/inventory'                          => 'cashier_inventory',
                'user/auditor/inventory'                          => 'auditor_inventory',
                'user/finance/inventory'                          => 'finance_inventory',
                'user/cfo/inventory'                              => 'cfo_inventory',
                'user/vpo/inventory'                              => 'vpo_inventory',
                'user/operation_manager/inventory'                => 'operation_manager_inventory',

                // Payment
                'user/encoder/payment'                            => 'encoder_payment',
                'user/cashier/payment'                            => 'cashier_payment',
                'user/auditor/payment'                            => 'auditor_payment',
                'user/finance/payment'                            => 'finance_payment',
                'user/cfo/payment'                                => 'cfo_payment',
                'user/vpo/payment'                                => 'vpo_payment',
                'user/operation_manager/payment'                  => 'operation_manager_payment',

                // Waive Penalty
                'user/encoder/waive-penalty-request'              => 'encoder_waive_penalty_request',
                'user/cashier/waive-penalty-request'              => 'cashier_waive_penalty_request',
                'user/auditor/waive-penalty-request'              => 'auditor_waive_penalty_request',
                'user/finance/waive-penalty-request'              => 'finance_waive_penalty_request',
                'user/cfo/waive-penalty-request'                  => 'cfo_waive_penalty_request',
                'user/vpo/waive-penalty-request'                  => 'vpo_waive_penalty_request',
                'user/operation_manager/waive-penalty-request'    => 'operation_manager_waive_penalty_request',

                // Products
                'user/encoder/product'                            => 'encoder_product',
                'user/cashier/product'                            => 'cashier_product',
                'user/auditor/product'                            => 'auditor_product',
                'user/finance/product'                            => 'finance_product',
                'user/cfo/product'                                => 'cfo_product',
                'user/vpo/product'                                => 'vpo_product',
                'user/operation_manager/product'                  => 'operation_manager_product',

                // Commissions
                'user/encoder/commission-config'                  => 'encoder_commission_config',
                'user/encoder/commission-release'                 => 'encoder_commission_release',
                'user/cashier/commission-config'                  => 'cashier_commission_config',
                'user/cashier/commission-release'                 => 'cashier_commission_release',
                'user/auditor/commission-config'                  => 'auditor_commission_config',
                'user/auditor/commission-release'                 => 'auditor_commission_release',
                'user/finance/commission-config'                  => 'finance_commission_config',
                'user/finance/commission-release'                 => 'finance_commission_release',
                'user/cfo/commission-config'                      => 'cfo_commission_config',
                'user/cfo/commission-release'                     => 'cfo_commission_release',
                'user/vpo/commission-config'                      => 'vpo_commission_config',
                'user/vpo/commission-release'                     => 'vpo_commission_release',
                'user/operation_manager/commission-config'        => 'operation_manager_commission_config',
                'user/operation_manager/commission-release'       => 'operation_manager_commission_release',

                // Registration
                'user/encoder/registration'                       => 'encoder_registration',
                'user/cashier/registration'                       => 'cashier_registration',
                'user/auditor/registration'                       => 'auditor_registration',
                'user/finance/registration'                       => 'finance_registration',
                'user/cfo/registration'                           => 'cfo_registration',
                'user/vpo/registration'                           => 'vpo_registration',
                'user/operation_manager/registration'             => 'operation_manager_registration',

                // Signatures
                'user/auditor/signature'                          => 'auditor_signature',
                'user/finance/save-signature'                     => 'finance_save_signature',
                'user/cfo/save-signature'                         => 'cfo_save_signature',
                'user/vpo/save-signature'                         => 'vpo_save_signature',
                'user/operation_manager/save-signature'           => 'operation_manager_save_signature',

                // GL Settings
                'user/encoder/gl-settings'                        => 'encoder_gl_settings',
                'user/cashier/gl-settings'                        => 'cashier_gl_settings',
                'user/auditor/gl-settings'                        => 'auditor_gl_settings',
                'user/finance/gl-settings'                        => 'finance_gl_settings',
                'user/cfo/gl-settings'                            => 'cfo_gl_settings',
                'user/vpo/gl-settings'                            => 'vpo_gl_settings',
                'user/operation_manager/gl-settings'              => 'operation_manager_gl_settings',

                // Services Controls
                'user/encoder/services-control'                   => 'encoder_services_control',
                'user/encoder/avail-services'                     => 'encoder_avail_services',
                'user/encoder/availed-services'                   => 'encoder_availed_services',
                'user/cashier/services-control'                   => 'cashier_services_control',
                'user/cashier/avail-services'                     => 'cashier_avail_services',
                'user/cashier/availed-services'                   => 'cashier_availed_services',
                'user/auditor/services-control'                   => 'auditor_services_control',
                'user/auditor/avail-services'                     => 'auditor_avail_services',
                'user/auditor/availed-services'                   => 'auditor_availed_services',
                'user/finance/services-control'                   => 'finance_services_control',
                'user/finance/avail-services'                     => 'finance_avail_services',
                'user/finance/availed-services'                   => 'finance_availed_services',
                'user/cfo/services-control'                       => 'cfo_services_control',
                'user/cfo/avail-services'                         => 'cfo_avail_services',
                'user/cfo/availed-services'                       => 'cfo_availed_services',
                'user/vpo/services-control'                       => 'vpo_services_control',
                'user/vpo/avail-services'                         => 'vpo_avail_services',
                'user/vpo/availed-services'                       => 'vpo_availed_services',
                'user/operation_manager/services-control'         => 'operation_manager_services_control',
                'user/operation_manager/avail-services'           => 'operation_manager_avail_services',
                'user/operation_manager/availed-services'         => 'operation_manager_availed_services',

                // RFP Workflows
                'user/encoder/rfp-approval-flow'                  => 'encoder_rfp_approval_flow',
                'user/encoder/all-pending-rfps'                   => 'encoder_all_pending_rfps',
                'user/cashier/rfp-approval-flow'                  => 'cashier_rfp_approval_flow',
                'user/cashier/all-pending-rfps'                   => 'cashier_all_pending_rfps',
                'user/auditor/rfp-approval-flow'                  => 'auditor_rfp_approval_flow',
                'user/auditor/all-pending-rfps'                   => 'auditor_all_pending_rfps',
                'user/finance/rfp-approval-flow'                  => 'finance_rfp_approval_flow',
                'user/finance/all-pending-rfps'                   => 'finance_all_pending_rfps',
                'user/cfo/rfp-approval-flow'                      => 'cfo_rfp_approval_flow',
                'user/cfo/all-pending-rfps'                       => 'cfo_all_pending_rfps',
                'user/vpo/rfp-approval-flow'                      => 'vpo_rfp_approval_flow',
                'user/vpo/all-pending-rfps'                       => 'vpo_all_pending_rfps',
                'user/operation_manager/rfp-approval-flow'       => 'operation_manager_rfp_approval_flow',
                'user/operation_manager/all-pending-rfps'       => 'operation_manager_all_pending_rfps',
            ];

            if (isset($featureMap[$uri])) {
                $requiredFeature = $featureMap[$uri];
                
                if (!in_array($requiredFeature, $userFeatures)) {
                    http_response_code(403);
                    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px; color: #0f172a;'>";
                    echo "<h1 style='color: #b45309; font-size: 3rem; margin-bottom: 10px;'>403 - Access Denied</h1>";
                    echo "<p style='font-size: 1.2rem;'>Your account does not have permission to access <strong>{$requiredFeature}</strong>.</p>";
                    echo "<a href='/cattleya/login' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #065f46; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Return to Safe Area</a>";
                    echo "</div>";
                    exit;
                }
            }
        }
    }

    $file = __DIR__ . '/' . $routes[$uri];

    if (file_exists($file)) {
        require_once $file;
        exit;
    }

    http_response_code(500);
    echo "<h1>500 - File not found</h1>";
    echo "<p>Route exists but file is missing: <strong>$file</strong></p>";
    exit;
}

http_response_code(404);
echo "<h1>404 - Page not found</h1>";
echo "<p>No route defined for <strong>/$uri</strong></p>";
exit;