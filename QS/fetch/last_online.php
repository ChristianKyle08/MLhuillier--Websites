<?php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// config.php has already run its "every page" hook by this point, which
// stamped last_online = NOW() for whichever role is logged in (see the
// bottom of config.php). All that's left here is to tell the caller
// (the heartbeat / sendBeacon call in last-online-tracker.js) whether
// there actually was a logged-in session to update, so the JS can stop
// pinging if the session has expired or the user logged out elsewhere.
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    http_response_code(200);
    echo json_encode([
        'success'   => true,
        'timestamp' => date('c'),
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session.',
    ]);
}