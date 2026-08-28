<?php
session_start(); // Start session if not already started

// Base folder of the project
$base = '/cattleya';

// Get the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base folder from URI
if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

// Remove leading/trailing slashes
$uri = trim($uri, '/');

// Define routes
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
    
    // Dashboard
    'admin/dashboard'          => 'views/admin/dashboard.php',
    'user/finance/dashboard'        => 'views/user/finance/dashboard.php',
    'user/auditor/dashboard'        => 'views/user/auditor/dashboard.php',
    'user/vpo/dashboard'            => 'views/user/vpo/dashboard.php',
    'user/cashier/dashboard'        => 'views/user/cashier/dashboard.php',
    'user/operation_manager/dashboard'        => 'views/user/operation_manager/dashboard.php',
    'user/cfo/dashboard'        => 'views/user/cfo/dashboard.php',

    'views/includes/user/profile'   => 'views/includes/user/profile.php',
    'views/includes/admin/profile'  => 'views/includes/admin/profile.php',

    'user/encoder/inventory'             => 'views/user/encoder/inventory.php',
    'user/encoder/dashboard'             => 'views/user/encoder/dashboard.php',
    'user/encoder/payment'               => 'views/user/encoder/payment.php',
    'user/encoder/waive-penalty-request' => 'views/user/encoder/waive_penalty_request.php',
    'user/encoder/product'               => 'views/user/encoder/product.php',
    'user/encoder/commission-release'    => 'views/user/encoder/commission_release.php',

    'user/encoder/commission-config'     => 'views/user/encoder/commission_config.php',
    'user/encoder/registration'          => 'views/user/encoder/registration.php',
    'user/encoder/gl-settings'            => 'views/user/encoder/gl_settings.php',
    'user/encoder/services-control'      => 'views/user/encoder/services_control.php',
    'user/encoder/avail-services'         => 'views/user/encoder/avail_services.php',
    'user/encoder/availed-services'       => 'views/user/encoder/availed_services.php',
    'user/encoder/rfp-approval-flow'       => 'views/user/encoder/rfp_approval_flow.php',
    'user/encoder/all-pending-rfps'       => 'views/user/encoder/all_pending_rfps.php',

    // Fetch / API Routes
    'user/encoder/fetch/add-sales'             => 'views/user/encoder/fetch/add_sales.php',
    'user/encoder/fetch/cancel-reservation'    => 'views/user/encoder/fetch/cancel_reservation.php',
    'user/encoder/fetch/get-blocks'            => 'views/user/encoder/fetch/get_blocks.php',
    'user/encoder/fetch/get-lots'              => 'views/user/encoder/fetch/get_lot.php',
    'user/encoder/fetch/get-product-details'   => 'views/user/encoder/fetch/get_product_details.php',
    'user/encoder/fetch/get-sale-details'      => 'views/user/encoder/fetch/get_sale_details.php',
    'user/encoder/fetch/save-lot'              => 'views/user/encoder/fetch/save_lot.php',
    'user/encoder/fetch/save-product'          => 'views/user/encoder/fetch/save_product.php',
    'user/encoder/fetch/update-product'        => 'views/user/encoder/fetch/update_product.php',
    'user/encoder/fetch/update-sale-status'    => 'views/user/encoder/fetch/update_sale_status.php',
    'user/encoder/fetch/get-suggestions'       => 'views/user/encoder/fetch/get_suggestions.php',
    'user/encoder/fetch/upload-photo'          => 'views/user/encoder/fetch/upload_photo.php',
    'user/encoder/fetch/process-registration'  => 'views/user/encoder/fetch/process_registration.php',
    'user/encoder/fetch/get-next-customer-id'  => 'views/user/encoder/fetch/get_next_customer_id.php',
    'user/encoder/fetch/get-customers'         => 'views/user/encoder/fetch/get_customers.php',
    'user/encoder/fetch/process-payment'       => 'views/user/encoder/fetch/process_payment.php',
    'user/encoder/fetch/request-waiver'        => 'views/user/encoder/fetch/request_waiver.php',
    'user/encoder/fetch/process-waive'         => 'views/user/encoder/fetch/process_waive.php',
    'user/encoder/fetch/view-receipt'          => 'views/user/encoder/fetch/view_receipt.php',
    'user/encoder/fetch/process-bounce-check'  => 'views/user/encoder/fetch/process_bounce_check.php',
    'user/encoder/fetch/process-clear-check'   => 'views/user/encoder/fetch/process_clear_check.php',

    'user/cashier/payment'               => 'views/user/cashier/payment.php',
    'user/cashier/inventory'             => 'views/user/cashier/inventory.php',
    'user/cashier/waive-penalty-request' => 'views/user/cashier/waive_penalty_request.php',
    'user/cashier/product'               => 'views/user/cashier/product.php',
    'user/cashier/commission-release'    => 'views/user/cashier/commission_release.php',
    'user/cashier/commission-config'     => 'views/user/cashier/commission_config.php',
    'user/cashier/registration'          => 'views/user/cashier/registration.php',
    'user/cashier/gl-settings'            => 'views/user/cashier/gl_settings.php',
    'user/cashier/services-control'      => 'views/user/cashier/services_control.php',
    'user/cashier/avail-services'         => 'views/user/cashier/avail_services.php',
    'user/cashier/availed-services'       => 'views/user/cashier/availed_services.php',
    'user/cashier/rfp-approval-flow'       => 'views/user/cashier/rfp_approval_flow.php',
    'user/cashier/all-pending-rfps'       => 'views/user/cashier/all_pending_rfps.php',

    'user/auditor/payment'               => 'views/user/auditor/payment.php',
    'user/auditor/inventory'             => 'views/user/auditor/inventory.php',
    'user/auditor/waive-penalty-request' => 'views/user/auditor/waive_penalty_request.php',
    'user/auditor/product'               => 'views/user/auditor/product.php',
    'user/auditor/commission-release'    => 'views/user/auditor/commission_release.php',
    'user/auditor/commission-config'     => 'views/user/auditor/commission_config.php',
    'user/auditor/registration'          => 'views/user/auditor/registration.php',
    'user/auditor/gl-settings'            => 'views/user/auditor/gl_settings.php',
    'user/auditor/services-control'      => 'views/user/auditor/services_control.php',
    'user/auditor/avail-services'         => 'views/user/auditor/avail_services.php',
    'user/auditor/availed-services'       => 'views/user/auditor/availed_services.php',
    'user/auditor/rfp-approval-flow'       => 'views/user/auditor/rfp_approval_flow.php',
    'user/auditor/all-pending-rfps'       => 'views/user/auditor/all_pending_rfps.php',

    'user/operation_manager/payment'               => 'views/user/operation_manager/payment.php',
    'user/operation_manager/inventory'             => 'views/user/operation_manager/inventory.php',
    'user/operation_manager/waive-penalty-request' => 'views/user/operation_manager/waive_penalty_request.php',
    'user/operation_manager/product'               => 'views/user/operation_manager/product.php',
    'user/operation_manager/commission-release'    => 'views/user/operation_manager/commission_release.php',
    'user/operation_manager/commission-config'     => 'views/user/operation_manager/commission_config.php',
    'user/operation_manager/registration'          => 'views/user/operation_manager/registration.php',
    'user/operation_manager/gl-settings'            => 'views/user/operation_manager/gl_settings.php',
    'user/operation_manager/services-control'      => 'views/user/operation_manager/services_control.php',
    'user/operation_manager/avail-services'         => 'views/user/operation_manager/avail_services.php',
    'user/operation_manager/availed-services'       => 'views/user/operation_manager/availed_services.php',
    'user/operation_manager/rfp-approval-flow'       => 'views/user/operation_manager/rfp_approval_flow.php',
    'user/operation_manager/all-pending-rfps'       => 'views/user/operation_manager/all_pending_rfps.php',

    'user/cfo/payment'               => 'views/user/cfo/payment.php',
    'user/cfo/inventory'             => 'views/user/cfo/inventory.php',
    'user/cfo/waive-penalty-request' => 'views/user/cfo/waive_penalty_request.php',
    'user/cfo/product'               => 'views/user/cfo/product.php',
    'user/cfo/commission-release'    => 'views/user/cfo/commission_release.php',
    'user/cfo/commission-config'     => 'views/user/cfo/commission_config.php',
    'user/cfo/registration'          => 'views/user/cfo/registration.php',
    'user/cfo/gl-settings'            => 'views/user/cfo/gl_settings.php',
    'user/cfo/services-control'      => 'views/user/cfo/services_control.php',
    'user/cfo/avail-services'         => 'views/user/cfo/avail_services.php',
    'user/cfo/availed-services'       => 'views/user/cfo/availed_services.php',
    'user/cfo/rfp-approval-flow'       => 'views/user/cfo/rfp_approval_flow.php',
    'user/cfo/all-pending-rfps'       => 'views/user/cfo/all_pending_rfps.php',

    // Admin actions
    'admin/approve-user'   => 'views/admin/approve_user.php',
    'admin/delete-user'    => 'views/admin/delete_user.php',
    'admin/cancel-reset'   => 'views/admin/cancel_reset.php',
    'admin/reset-password' => 'views/admin/reset_password.php',
    'admin/users'          => 'views/admin/users.php',

    'user/signature'       => 'views/user/signature.php',
    'user/save-signature'  => 'views/user/save_signature.php'
];

// Check if route exists
if (array_key_exists($uri, $routes)) {
    
    // =========================================================================
    // SYSTEM FEATURE ACCESS CONTROL
    // =========================================================================
    if (isset($_SESSION['user_id'])) {
        
        // ADMIN ROLE BYPASS: Administrators have full access to all system features
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {

            // Routes that manage other users/accounts are always admin-only.
            // These are intentionally NOT part of $featureMap below, because
            // they aren't a grantable feature — no staff role should ever
            // reach them, no matter what features they've been assigned.
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
            
            // Fetch user's assigned features from database
            $stmt = $pdo->prepare("SELECT features FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $featureJson = $stmt->fetchColumn();
            
            $userFeatures = $featureJson ? json_decode($featureJson, true) : [];
            if (!is_array($userFeatures)) {
                $userFeatures = [];
            }

            // Map system URLs to non-admin feature permission keys
            $featureMap = [
                // 1. Dashboard
                'user/finance/dashboard'                          => 'finance_dashboard',
                'user/auditor/dashboard'                          => 'auditor_dashboard',
                'user/vpo/dashboard'                              => 'vpo_dashboard',
                'user/cashier/dashboard'                          => 'cashier_dashboard',
                'user/encoder/dashboard'                          => 'encoder_dashboard',
                'user/operation_manager/dashboard'                => 'operation_manager_dashboard',
                'user/cfo/dashboard'                              => 'cfo_dashboard',

                // 2. Inventory Management
                'user/encoder/inventory'                          => 'encoder_inventory',
                'user/cashier/inventory'                          => 'cashier_inventory',
                'user/auditor/inventory'                          => 'auditor_inventory',
                'user/cfo/inventory'                              => 'cfo_inventory',
                'user/vpo/inventory'                              => 'vpo_inventory',
                'user/operation_manager/inventory'                => 'operation_manager_inventory',
                'user/finance/inventory'                          => 'finance_inventory',

                // 3. Payment Processing
                'user/encoder/payment'                            => 'encoder_payment',
                'user/cashier/payment'                            => 'cashier_payment',
                'user/vpo/payment'                                => 'vpo_payment',
                'user/cfo/payment'                                => 'cfo_payment',
                'user/operation_manager/payment'                  => 'operation_manager_payment',
                'user/finance/payment'                            => 'finance_payment',
                'user/auditor/payment'                            => 'auditor_payment',

                // 4. Waive Penalty Configuration
                'user/encoder/waive-penalty-request'              => 'encoder_waive_penalty_request',
                'user/cashier/waive-penalty-request'              => 'cashier_waive_penalty_request',
                'user/auditor/waive-penalty-request'              => 'auditor_waive_penalty_request',
                'user/vpo/waive-penalty-request'                  => 'vpo_waive_penalty_request',
                'user/cfo/waive-penalty-request'                  => 'cfo_waive_penalty_request',
                'user/finance/waive-penalty-request'              => 'finance_waive_penalty_request',
                'user/operation_manager/waive-penalty-request'    => 'operation_manager_waive_penalty_request',

                // 5. Product Assignment
                'user/encoder/product'                            => 'encoder_product',
                'user/cashier/product'                            => 'cashier_product',
                'user/auditor/product'                            => 'auditor_product',
                'user/vpo/product'                                => 'vpo_product',
                'user/cfo/product'                                => 'cfo_product',
                'user/finance/product'                            => 'finance_product',
                'user/operation_manager/product'                  => 'operation_manager_product',

                // 6. Commission Configuration & Release
                'user/encoder/commission-config'                  => 'encoder_commission_config',
                'user/encoder/commission-release'                 => 'encoder_commission_release',
                 'user/vpo/commission-config'                     => 'vpo_commission_config',
                 'user/vpo/commission-release'                    => 'vpo_commission_release',
                 'user/cfo/commission-config'                     => 'cfo_commission_config',
                 'user/cfo/commission-release'                    => 'cfo_commission_release',
                 'user/finance/commission-config'                 => 'finance_commission_config',
                 'user/finance/commission-release'                => 'finance_commission_release',
                 'user/operation_manager/commission-config'       => 'operation_manager_commission_config',
                 'user/operation_manager/commission-release'      => 'operation_manager_commission_release',
                 'user/cashier/commission-config'                 => 'cashier_commission_config',
                'user/cashier/commission-release'                 => 'cashier_commission_release',
                'user/auditor/commission-config'                  => 'auditor_commission_config',
                'user/auditor/commission-release'                 => 'auditor_commission_release',
                
                // 7. User & Customer Registration
                'user/encoder/registration'                       => 'encoder_registration',
                'user/cashier/registration'                       => 'cashier_registration',
                'user/auditor/signature'                          => 'auditor_signature',
                'user/finance/save-signature'                     => 'finance_save_signature',
                'user/vpo/save-signature'                         => 'vpo_save_signature',
                'user/cfo/save-signature'                         => 'cfo_save_signature',
                'user/operation_manager/save-signature'           => 'operation_manager_save_signature',

                // 8. GL Settings
                'user/encoder/gl-settings'                        => 'encoder_gl_settings',
                'user/cashier/gl-settings'                        => 'cashier_gl_settings',
                'user/auditor/gl-settings'                        => 'auditor_gl_settings',
                'user/vpo/gl-settings'                            => 'vpo_gl_settings',
                'user/cfo/gl-settings'                            => 'cfo_gl_settings',
                'user/finance/gl-settings'                        => 'finance_gl_settings',
                'user/operation_manager/gl-settings'              => 'operation_manager_gl_settings',

                // 9. Services Control
                'user/encoder/services-control'                   => 'encoder_services_control',
                'user/encoder/avail-services'                     => 'encoder_avail_services',
                'user/encoder/availed-services'                   => 'encoder_availed_services',
                'user/auditor/services-control'                   => 'auditor_services_control',
                'user/auditor/avail-services'                     => 'auditor_avail_services',
                'user/auditor/availed-services'                   => 'auditor_availed_services',
                'user/vpo/services-control'                       => 'vpo_services_control',
                'user/vpo/avail-services'                         => 'vpo_avail_services',
                'user/vpo/availed-services'                       => 'vpo_availed_services',
                'user/cfo/services-control'                       => 'cfo_services_control',
                'user/cfo/avail-services'                         => 'cfo_avail_services',
                'user/cfo/availed-services'                       => 'cfo_availed_services',
                'user/finance/services-control'                   => 'finance_services_control',
                'user/finance/avail-services'                     => 'finance_avail_services',
                'user/finance/availed-services'                   => 'finance_availed_services',
                'user/operation_manager/services-control'         => 'operation_manager_services_control',
                'user/operation_manager/avail-services'           => 'operation_manager_avail_services',
                'user/operation_manager/availed-services'         => 'operation_manager_availed_services',
                 'user/cashier/services-control'                  => 'cashier_services_control',
                'user/cashier/avail-services'                     => 'cashier_avail_services',
                'user/cashier/availed-services'                   => 'cashier_availed_services'
            ];

            // If the requested route requires feature permissions and the staff user lacks it, block access
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
    // =========================================================================

    $file = __DIR__ . '/' . $routes[$uri];

    if (file_exists($file)) {
        require_once $file;
        exit;
    }

    // File not found for a valid route
    http_response_code(500);
    echo "<h1>500 - File not found</h1>";
    echo "<p>Route exists but file is missing: <strong>$file</strong></p>";
    exit;
}

// Route not defined
http_response_code(404);
echo "<h1>404 - Page not found</h1>";
echo "<p>No route defined for <strong>/$uri</strong></p>";
exit;