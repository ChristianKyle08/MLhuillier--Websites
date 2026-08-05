<?php
require '../../config/config.php';

if (!isset($_GET['contract_number'])) {
    exit("<div class='alert alert-danger text-center'>
      <i class='bi bi-exclamation-triangle-fill me-2'></i>
      Invalid contract request.
    </div>");
}

$contractNumber = $_GET['contract_number'];

$stmt = $conn->prepare("SELECT * FROM transactional WHERE contract_number = ? ORDER BY transaction_date ASC");
$stmt->bind_param("s", $contractNumber);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-warning d-flex align-items-center p-3 rounded shadow-sm'>
      <i class='bi bi-hourglass-split fs-4 text-warning me-3'></i>
      <div>
        <strong>Pending Approval</strong><br>
        This contract currently has no transaction records yet.
      </div>
    </div>";
    exit;
}
?>

<div class="table-responsive shadow-sm rounded-3">
  <table class="table table-hover align-middle mb-0">
    <thead class="bg-primary text-white text-center">
      <tr>
        <th><i class="bi bi-calendar-event me-1"></i>Date</th>
        <th><i class="bi bi-cash-stack me-1"></i>Amount</th>
        <th><i class="bi bi-receipt me-1"></i>Net of VAT</th>
        <th><i class="bi bi-percent me-1"></i>VAT</th>
        <th><i class="bi bi-scissors me-1"></i>WTax</th>
        <th><i class="bi bi-wallet2 me-1"></i>Lessor Amount</th>
        <th><i class="bi bi-credit-card me-1"></i>Mode of Payment</th>
        <th class="fw-normal"><i class="bi bi-person me-1"></i>RFP Requested By</th>
        <th class="fw-normal"><i class="bi bi-calendar-date me-1"></i>RFP Requested Date</th>
        <th><i class="bi bi-info-circle me-1"></i>Status</th>
      </tr>
    </thead>
    <tbody class="text-center">
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td>
            <?php if (!empty($row['transaction_date'])): ?>
              <?= date("F d, Y", strtotime($row['transaction_date'])) ?>
            <?php else: ?>
              <span class="text-warning fw-semibold">No Transaction Date</span>
            <?php endif; ?>
          </td>
          <td class="fw-semibold text-success">₱ <?= number_format($row['amount'], 2) ?></td>
          <td>₱ <?= number_format($row['net_of_vat'], 2) ?></td>
          <td>₱ <?= number_format($row['vat_amount'], 2) ?></td>
          <td>₱ <?= number_format($row['wtax'], 2) ?></td>
          <td class="fw-semibold text-primary">₱ <?= number_format($row['edit_amount_lessor'], 2) ?></td>
          <td>
            <span class="badge bg-light text-dark border">
              <i class="bi bi-credit-card-2-front me-1"></i>
              <?= htmlspecialchars($row['mode_of_payment']) ?>
            </span>
          </td>
          <td>
  <?php if (!empty($row['rfp_by'])): ?>
    <span class="badge bg-light text-dark border">
      <i class="bi bi-person me-1"></i>
      <?= htmlspecialchars($row['rfp_by']) ?>
    </span>
  <?php else: ?>
    <span class="text-muted small">Not Processed</span>
  <?php endif; ?>
</td>

<td>
  <?php if (!empty($row['rfp_date']) && $row['rfp_date'] !== '0000-00-00'): ?>
    <?= date("F d, Y", strtotime($row['rfp_date'])) ?>
  <?php else: ?>
    <span class="text-warning fw-semibold">No Transaction Date</span>
  <?php endif; ?>
</td>
          <td class="text-center">
            <?php
              $status = htmlspecialchars($row['status']);
              $statusLower = strtolower($status);

              // Assign custom color and icon dynamically
              switch ($statusLower) {
                case 'paid':
                  $badgeStyle = 'background-color: #28a745; color: white;'; // green
                  $icon = 'bi-check-circle-fill';
                  break;
                case 'unpaid':
                  $badgeStyle = 'background-color: #ffc107; color: black;'; // default yellow
                  $icon = 'bi-hourglass-split';
                  break;
                case 'pending':
                  $badgeStyle = 'background-color: #FFD700; color: black;'; // gold
                  $icon = 'bi-clock-history';
                  break;
                case 'processing':
                  $badgeStyle = 'background-color: #87CEFA; color: black;'; // light blue
                  $icon = 'bi-gear-fill';
                  break;
                case 'terminated':
                  $badgeStyle = 'background-color: #8B0000; color: white;'; // dark red
                  $icon = 'bi-exclamation-octagon-fill';
                  break;
                case 'cancelled':
                  $badgeStyle = 'background-color: #FF6B6B; color: white;'; // light red
                  $icon = 'bi-x-circle-fill';
                  break;
                default:
                  $badgeStyle = 'background-color: #6c757d; color: white;'; // gray (secondary)
                  $icon = 'bi-question-circle-fill';
                  break;
              }
            ?>
            <span class="badge px-3 py-2 d-inline-flex align-items-center gap-1" style="<?= $badgeStyle ?>">
              <i class="bi <?= $icon ?>"></i>
              <?= ucfirst($status) ?>
            </span>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
