<?php
session_start();
include 'dbconnect.php';

// Security check
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.html");
    exit();
}

$customer_id = $_SESSION['customer_id'];

  
$acc_query = "SELECT account_id, account_number, balance FROM accounts WHERE customer_id = ?";
$stmt = $conn->prepare($acc_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

if (!$account) {
    die("Error: Account details not found. Please contact support.");
}

$account_id = $account['account_id'];
$stmt->close();

// 2. Fetch transaction history including 'Bank System' (NULL) parties
$query_str = "
    SELECT t.*, 
           s.account_number AS sender_acc, 
           r.account_number AS recipient_acc,
           sc.customer_full_name AS sender_name,
           rc.customer_full_name AS recipient_name
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
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        :root { --primary: #2c3e50; --secondary: #34495e; --accent: #27ae60; --danger: #e74c3c; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        
        .history-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        /* Balance Card */
        .balance-hero {
            background: linear-gradient(135deg, var(--primary), #4ca1af);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(44, 62, 80, 0.2);
        }

        .txn-table-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px; color: #95a5a6; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid #f1f1f1; }
        td { padding: 18px; border-bottom: 1px solid #f9f9f9; font-size: 0.95rem; }

        /* Badges */
        .type-badge { padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; }
        .type-debit { background: #fee2e2; color: var(--danger); }
        .type-credit { background: #dcfce7; color: var(--accent); }
        
        .amt-debit { color: var(--danger); font-weight: 700; }
        .amt-credit { color: var(--accent); font-weight: 700; }

        .time-sub { color: #bdc3c7; font-size: 0.8rem; display: block; margin-top: 4px; }
        
        .back-btn { 
            text-decoration: none; color: white; border: 1.5px solid rgba(255,255,255,0.4); 
            padding: 10px 20px; border-radius: 10px; transition: 0.3s; font-weight: 600;
        }
        .back-btn:hover { background: white; color: var(--primary); }
        
        .system-text { color: #7f8c8d; font-style: italic; font-weight: 600; }
    </style>
</head>
<body>

<div class="history-container">
    <!-- Balance Overview -->
    <div class="balance-hero">
        <div>
            <p style="margin:0; opacity:0.8; font-size: 0.9rem;">Available Balance</p>
            <h1 style="margin:8px 0; font-size: 3.2rem;">₹<?php echo number_format($account['balance'], 2); ?></h1>
            <p style="margin:0; font-family: monospace; opacity: 0.9;">A/C: <?php echo $account['account_number']; ?></p>
        </div>
        <a href="dashboard.php" class="back-btn">← Back to Home</a>
    </div>

    <!-- Transaction List -->
    <div class="txn-table-card">
        <h3 style="margin-top: 0; margin-bottom: 25px; color: var(--primary);">Recent Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Reference / Details</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $transactions->fetch_assoc()): 
                    $is_debit = ($row['sender_id'] == $account_id);
                    $type_class = $is_debit ? "type-debit" : "type-credit";
                    $amt_class = $is_debit ? "amt-debit" : "amt-credit";
                    $symbol = $is_debit ? "-" : "+";
                    
                    // Logic to handle Admin/System Transactions where one party is NULL
                    if ($is_debit) {
                        $details = !empty($row['recipient_name']) ? htmlspecialchars($row['recipient_name']) : "<span class='system-text'>BANK WITHDRAWAL</span>";
                    } else {
                        $details = !empty($row['sender_name']) ? htmlspecialchars($row['sender_name']) : "<span class='system-text'>BANK DEPOSIT</span>";
                    }
                ?>
                <tr>
                    <td>
                        <strong><?php echo date('d M Y', strtotime($row['transaction_timestamp'])); ?></strong>
                        <span class="time-sub"><?php echo date('h:i A', strtotime($row['transaction_timestamp'])); ?></span>
                    </td>
                    <td>
                        <span class="type-badge <?php echo $type_class; ?>">
                            <?php echo $is_debit ? 'DEBIT' : 'CREDIT'; ?>
                        </span>
                    </td>
                    <td><?php echo $details; ?></td>
                    <td class="<?php echo $amt_class; ?>" style="text-align: right; font-size: 1.1rem;">
                        <?php echo $symbol; ?> ₹<?php echo number_format($row['amount'], 2); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if($transactions->num_rows == 0): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 50px; color: #95a5a6;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📂</div>
                        No transactions found for this account.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
