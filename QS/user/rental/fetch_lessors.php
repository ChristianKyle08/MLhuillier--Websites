<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

$region = $_POST['region'];
$area = $_POST['area'];
$role = $_POST['role'];
$searchTerm = isset($_POST['searchTerm']) ? $_POST['searchTerm'] : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM lessor_profile WHERE 1=1";
if ($role !== 'HO') {
    $query .= " AND region = '$region' AND area = '$area'";
}
if (!empty($searchTerm)) {
    $query .= " AND (corporate_name LIKE '%$searchTerm%' OR first_name LIKE '%$searchTerm%' OR last_name LIKE '%$searchTerm%')";
}
$query .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo '<ul class="list_profile">';
    while ($row = $result->fetch_assoc()) {
        echo '<li class="list_p">' . htmlspecialchars($row['corporate_name'] . ' ' . $row['first_name'] . ' ' . $row['last_name']) . '</li>';
    }
    echo '</ul>';

    // Pagination
    $totalQuery = "SELECT COUNT(*) AS total FROM lessor_profile WHERE 1=1";
    if ($role !== 'HO') {
        $totalQuery .= " AND region = '$region' AND area = '$area'";
    }
    if (!empty($searchTerm)) {
        $totalQuery .= " AND (corporate_name LIKE '%$searchTerm%' OR first_name LIKE '%$searchTerm%' OR last_name LIKE '%$searchTerm%')";
    }
    $totalResult = $conn->query($totalQuery);
    $totalRow = $totalResult->fetch_assoc();
    $totalPages = ceil($totalRow['total'] / $limit);

    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        echo '<button class="page-btn" data-page="' . $i . '">' . $i . '</button> ';
    }
    echo '</div>';
} else {
    echo '<p>No lessors found.</p>';
}
?>
