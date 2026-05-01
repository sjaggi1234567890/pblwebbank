<?php
session_start();
include 'dbconnect.php';

// 1. Security check - Ensure user is logged in
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['account_id'])) {
    header("Location: login.html");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$account_id  = $_SESSION['account_id'];

// 2. Fetch current balance and account number for the header
$acc_query = "SELECT account_number, balance FROM accounts WHERE account_id = ?";
$stmt = $conn->prepare($acc_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

if (!$account) {
    die("Error: Account details not found.");
}
$stmt->close();

// 3. Fetch transaction history using the new archived columns
$query_str = "
    SELECT 
        t.*, 
        sc.customer_full_name AS sender_display_name,
        rc.customer_full_name AS recipient_display_name
    FROM transaction_records t
    LEFT JOIN accounts s ON t.sender_id = s.account_id
    LEFT JOIN customers_basic_info sc ON s.customer_id = sc.customer_id
    LEFT JOIN accounts r ON t.recipient_id = r.account_id
    LEFT JOIN customers_basic_info rc ON r.customer_id = rc.customer_id
    WHERE t.sender_id = ? OR t.recipient_id = ?
    ORDER BY t.transaction_timestamp DESC";

$stmt = $conn->prepare($query_str);
$stmt->bind_param("ii", $account_id, $account_id);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - WebBank</title>
    <link rel="stylesheet" href="t_b_historystyle.css">
</head>
<body>

<div class="main-wrapper">
    <header class="history-header">
        <div class="header-content">
            <h1>Transaction History</h1>
            <p>Review your recent activity and account statements</p>
        </div>
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </header>

    <div class="summary-card">
        <div class="acc-info">
            <span class="label">Primary Account</span>
            <span class="acc-number"><?php echo htmlspecialchars($account['account_number']); ?></span>
        </div>
        <div class="acc-balance">
            <span class="label">Current Balance</span>
            <span class="balance-amt">₹<?php echo number_format($account['balance'], 2); ?></span>
        </div>
    </div>

    <div class="table-container">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference / Note</th>
                    <th>Type</th>
                    <th class="txt-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while($row = $transactions->fetch_assoc()): 
                        // Logic moved inside the only loop that matters
                        $is_debit = ($row['sender_id'] == $account_id);
                        $type_label = $is_debit ? "Debit" : "Credit";
                        $status_class = $is_debit ? "status-debit" : "status-credit";
                        $sign = $is_debit ? "-" : "+";
                        
                        // Priority: Live Display Name > Archived Username > Account Number
                        if ($is_debit) {
                            $name = !empty($row['recipient_display_name']) ? $row['recipient_display_name'] : (!empty($row['recipient_username']) ? $row['recipient_username'] : $row['recipient_acc_number']);
                            $prefix = "To: ";
                        } else {
                            $name = !empty($row['sender_display_name']) ? $row['sender_display_name'] : (!empty($row['sender_username']) ? $row['sender_username'] : $row['sender_acc_number']);
                            $prefix = "From: ";
                        }

                        // Special case for Bank System deposits
                        if (empty($row['sender_id']) && !$is_debit) {
                            $display_text = "<strong>BANK DEPOSIT / SYSTEM CREDIT</strong>";
                        } else {
                            $display_text = "<strong>" . $prefix . htmlspecialchars($name) . "</strong>";
                        }
                    ?>
                    <tr>
                        <td class="date-col">
                            <span class="day"><?php echo date('d M Y', strtotime($row['transaction_timestamp'])); ?></span>
                            <span class="time"><?php echo date('h:i A', strtotime($row['transaction_timestamp'])); ?></span>
                        </td>
                        <td class="detail-col">
                            <?php echo $display_text; ?>
                            <p class="note"><?php echo htmlspecialchars($row['short_note'] ?? 'No note attached'); ?></p>
                        </td>
                        <td>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $type_label; ?></span>
                        </td>
                        <td class="amt-col <?php echo $status_class; ?>">
                            <?php echo $sign; ?> ₹<?php echo number_format($row['amount'], 2); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-state">
                            <p>No transactions found for this period.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
