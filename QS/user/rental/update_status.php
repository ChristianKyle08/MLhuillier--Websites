<?php
if(isset($_POST['controlNumber']) && !empty($_POST['controlNumber'])) {
    include '../../config/config.php';

    $controlNumber = mysqli_real_escape_string($conn, $_POST['controlNumber']);

    $sql = "UPDATE transactional SET extract_request_status = 'Received' WHERE extraction_series = '$controlNumber'";
    
    if(mysqli_query($conn, $sql)) {
        echo json_encode(array('status' => 'success', 'message' => 'Successfully Received the request.'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Error updating status: ' . mysqli_error($conn)));
    }
} else {
    echo json_encode(array('status' => 'error', 'message' => 'Control number is required.'));
}
?>
