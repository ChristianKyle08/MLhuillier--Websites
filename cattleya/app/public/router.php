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
    ''                  => 'public/index.php',
    'login'             => 'views/auth/login.php',
    'register'          => 'views/auth/register.php',
    'register-process'  => 'views/auth/register_process.php',
    'login-process'     => 'views/auth/login_process.php',
    'logout'            => 'views/auth/logout.php',
    'forgot-password' => 'views/auth/forgot_password.php',
    'forgot-password-process' => 'views/auth/forgot_password_process.php',
    'change-password' => 'views/auth/change_password.php',
    'check-default-password' => 'views/auth/check_default_password.php',
    'auth/update-users' => 'views/auth/update_user.php',
    // Dashboard
    'admin/dashboard'   => 'views/admin/dashboard.php',
    'finance/dashboard' => 'views/finance/dashboard.php',
    'auditor/dashboard' => 'views/auditor/dashboard.php',
    'vpo/dashboard'     => 'views/vpo/dashboard.php',
    'cashier/dashboard' => 'views/cashier/dashboard.php',

    'views/includes/user/profile'    => 'views/includes/user/profile.php',
    'views/includes/admin/profile'    => 'views/includes/admin/profile.php',

    'user/encoder/inventory'    => 'views/user/encoder/inventory.php',
    'user/encoder/dashboard'    => 'views/user/encoder/dashboard.php',
    'user/encoder/payment'    => 'views/user/encoder/payment.php',
    'user/encoder/waive-penalty-request'    => 'views/user/encoder/waive_penalty_request.php',
    'user/encoder/product'    => 'views/user/encoder/product.php',
    'user/encoder/commission-release'    => 'views/user/encoder/commission_release.php',

    'user/encoder/commission-config'    => 'views/user/encoder/commission_config.php',
    'user/encoder/registration'    => 'views/user/encoder/registration.php',
    'user/encoder/gl-settings'    => 'views/user/encoder/gl_settings.php',
    'user/encoder/services-control'    => 'views/user/encoder/services_control.php',
    'user/encoder/avail-services'    => 'views/user/encoder/avail_services.php',
    'user/encoder/availed-services'    => 'views/user/encoder/availed_services.php',

    
    'user/encoder/fetch/add-sales'           => 'views/user/encoder/fetch/add_sales.php',
    'user/encoder/fetch/cancel-reservation'           => 'views/user/encoder/fetch/cancel_reservation.php',
    'user/encoder/fetch/get-blocks'           => 'views/user/encoder/fetch/get_blocks.php',
    'user/encoder/fetch/get-lots'           => 'views/user/encoder/fetch/get_lot.php',
    'user/encoder/fetch/get-product-details'           => 'views/user/encoder/fetch/get_product_details.php',
    'user/encoder/fetch/get-sale-details'           => 'views/user/encoder/fetch/get_sale_details.php',
    'user/encoder/fetch/save-lot'           => 'views/user/encoder/fetch/save_lot.php',
    'user/encoder/fetch/save-product'    => 'views/user/encoder/fetch/save_product.php',
    'user/encoder/fetch/update-product'    => 'views/user/encoder/fetch/update_product.php',
    'user/encoder/fetch/update-sale-status'           => 'views/user/encoder/fetch/update_sale_status.php',
    'user/encoder/fetch/get-suggestions'           => 'views/user/encoder/fetch/get_suggestions.php',
    'user/encoder/fetch/upload-photo'           => 'views/user/encoder/fetch/upload_photo.php',
    'user/encoder/fetch/process-registration'           => 'views/user/encoder/fetch/process_registration.php',
    'user/encoder/fetch/get-next-customer-id'           => 'views/user/encoder/fetch/get_next_customer_id.php',
    'user/encoder/fetch/get-customers'           => 'views/user/encoder/fetch/get_customers.php',
    'user/encoder/fetch/process-payment'           => 'views/user/encoder/fetch/process_payment.php',
    'user/encoder/fetch/request-waiver'           => 'views/user/encoder/fetch/request_waiver.php',
    'user/encoder/fetch/process-waive'           => 'views/user/encoder/fetch/process_waive.php',
    'user/encoder/fetch/view-receipt'           => 'views/user/encoder/fetch/view_receipt.php',
    'user/encoder/fetch/process-bounce-check'           => 'views/user/encoder/fetch/process_bounce_check.php',
    'user/encoder/fetch/process-clear-check'           => 'views/user/encoder/fetch/process_clear_check.php',

    // Admin actions
    'admin/approve-user' => 'views/admin/approve_user.php',
    'admin/delete-user'  => 'views/admin/delete_user.php',
    'admin/cancel-reset' => 'views/admin/cancel_reset.php',
    'admin/reset-password' => 'views/admin/reset_password.php',
    'admin/users'        => 'views/admin/users.php',

    'user/signature' => 'views/user/signature.php',
    'user/save-signature' => 'views/user/save_signature.php'

];

// Check if route exists
if (array_key_exists($uri, $routes)) {
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
