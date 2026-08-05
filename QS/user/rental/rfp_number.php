<?php
session_start();
    include '../../config/config.php';

    if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    }

    if (isset($_GET['start_date'], $_GET['end_date'], $_GET['amount'], $_GET['branch'], $_GET['region'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        $amount = $_GET['amount'];
        $branch = $_GET['branch'];
        $region = $_GET['region'];
        $userName = $_SESSION['user_name'];

        // Calculate the next extraction series value based on the current maximum series
        $next_series_query = "SELECT IFNULL(MAX(SUBSTRING_INDEX(extraction_series, '-', -1)), 0) + 1 AS next_series FROM transactional";

        $result = mysqli_query($conn, $next_series_query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $next_series = $row['next_series'];
        } else {
            $next_series = 1; // Default to 1 if no existing series found
        }

        // Prepare the SQL query to update extract request status and increment extraction_series
        $query = "UPDATE transactional 
              SET extract_request_status = 'Requested', 
                  extraction_series = CONCAT('COL-EXTRACTION-', ?), 
                  extract_requested_by = ?
              WHERE (extract_request_status IS NULL OR extract_request_status = '')
              AND DATE_FORMAT(transaction_date, '%Y-%m') BETWEEN ? AND ?";

        // Append condition based on branch and region
        if ($branch !== "ALL" && empty($region)) {
            $query .= " AND branch = ?";
        } elseif ($branch === "ALL" && empty($region)) {
            $query .= "";
        }elseif ($branch === "ALL" && !empty($region)) {
            $query .= " AND region = ?";
        }elseif (!empty($branch) && !empty($region)) {
            $query .= " AND region = ? AND branch = ?";
        }
        // Prepare the statement
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            // Bind parameters to the prepared statement based on conditions
            if ($branch !== "ALL" && empty($region)) {
                mysqli_stmt_bind_param($stmt, 'issss', $next_series, $userName, $start_date, $end_date, $branch);
            } elseif ($branch === "ALL" && empty($region)) {
                mysqli_stmt_bind_param($stmt, 'isss', $next_series, $userName, $start_date, $end_date);
            } elseif ($branch === "ALL" && !empty($region)) {
                mysqli_stmt_bind_param($stmt, 'issss', $next_series, $userName, $start_date, $end_date, $region);
            } elseif (!empty($branch) && !empty($region)) {
                mysqli_stmt_bind_param($stmt, 'isssss',$next_series, $userName, $start_date, $end_date, $region, $branch);
            }
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                alert('Extract request status updated successfully!');
                window.location.href = 'request_data_extraction.php';
            </script>";
        } else {
            echo "<script>
                alert('Error updating extract request status!');
                window.location.href = 'request_data_extraction.php';
            </script>";
        }
        // Close the prepared statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error in preparing update statement: " . mysqli_error($conn);
    }
}
?>
