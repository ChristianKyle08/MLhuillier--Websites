<?php
include '../../config/config.php';

$region = mysqli_real_escape_string($conn, $_GET['region_description'] ?? '');

$sql = "
    SELECT region_code, gl_region, region_desc_kp7, region_desc_kpx
    FROM region_masterfile
    WHERE region_description = '$region'
    LIMIT 1
";

$result = mysqli_query($conn, $sql);
echo json_encode(mysqli_fetch_assoc($result) ?: []);
