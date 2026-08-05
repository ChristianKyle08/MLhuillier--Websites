<?php
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../../../../config/database.php';

    if (!isset($pdo)) {
        throw new Exception("Database connection failed.");
    }

    $year = date("Y"); // 2026, then 2027, etc.

    /** * RESET LOGIC:
     * We look for the highest ID where the created_at year matches the current year.
     * This ensures that when the year changes, the count starts over at 1.
     */
    $query = "SELECT COUNT(*) as yearly_count 
              FROM customers 
              WHERE YEAR(created_at) = :current_year";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['current_year' => $year]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // The next number is current count + 1
    $nextNumber = ($result['yearly_count'] ?? 0) + 1;
    
    // Format: CUST-2026-000001
    $series = str_pad($nextNumber, 6, "0", STR_PAD_LEFT);
    $formattedId = "CUST-" . $year . "-" . $series;

    echo json_encode([
        'success' => true, 
        'next_id' => $formattedId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
exit;