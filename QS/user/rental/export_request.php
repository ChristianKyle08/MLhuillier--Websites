<?php

include '../../config/config.php';

if(isset($_POST['request_export'])){
    $userName = $_SESSION['user_name'];
    $transaction_startDate = $_POST['start'];
    $transaction_endDate = $_POST['end'];

    $update = "UPDATE transactional SET export_status = 'Requested', requested_by = '$userName' WHERE status != 'Cancelled' AND export_status = 'Extracted' AND DATE_FORMAT(transaction_date, '%Y-%m') >= '$transaction_startDate' AND DATE_FORMAT(transaction_date, '%Y-%m') <= '$transaction_endDate'";
    $execute_query = mysqli_query($conn, $update);

    if($execute_query){
        // Success message displayed using SweetAlert
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>';
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '  Swal.fire({';
        echo "    icon: 'success',";
        echo "    title: 'Request Successfully Processed!',";
        echo "    text: 'Your export request has been successfully processed.',";
        echo '    }).then((result) => {';
        echo "    if (result.isConfirmed) {";
        echo "      window.location.href = 'contract_ledger.php';";
        echo '    }';
        echo '  });';
        echo '});';
        echo '</script>';
    } else {
        // Error message displayed using SweetAlert
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>';
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '  Swal.fire({';
        echo "    icon: 'error',";
        echo "    title: 'Request Processing Failed!',";
        echo "    text: 'There was an error processing your export request.',";
        echo '  });';
        echo '});';
        echo '</script>';
    }
}
?>
