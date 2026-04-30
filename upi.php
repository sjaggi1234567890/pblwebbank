<?php
include"dbconnect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Check if the name matches the HTML input name
    $recipient_acc = isset($_POST['recipient_id']) ? mysqli_real_escape_string($conn, $_POST['recipient_id']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($amount <= 0 || empty($recipient_acc)) {
        echo "<script>alert('Error: Invalid amount or recipient.');</script>";
    } else {
        $conn->begin_transaction();
        try {
            // STEP A: Get Sender Info
            $sender_stmt = $conn->prepare("SELECT account_id, balance FROM accounts WHERE customer_id = ? FOR UPDATE");
            $sender_stmt->bind_param("i", $sender_id);
            $sender_stmt->execute();
            $sender_res = $sender_stmt->get_result();
            if($sender_res->num_rows === 0) throw new Exception("Your account was not found.");
            
            $sender_data = $sender_res->fetch_assoc();
            $sender_acc_id = $sender_data['account_id'];

            if ($sender_data['balance'] < $amount) throw new Exception("Insufficient balance.");

            // STEP B: Get Recipient Info
            $recipient_stmt = $conn->prepare("SELECT account_id FROM accounts WHERE account_number = ?");
            $recipient_stmt->bind_param("s", $recipient_acc);
            $recipient_stmt->execute();
            $recipient_res = $recipient_stmt->get_result();
            if($recipient_res->num_rows === 0) throw new Exception("Recipient account not found.");
            
            $recipient_acc_id = $recipient_res->fetch_assoc()['account_id'];

            if ($sender_acc_id == $recipient_acc_id) throw new Exception("Cannot send to yourself.");

            // STEP C: Perform Updates
            $u1 = $conn->query("UPDATE accounts SET balance = balance - $amount WHERE account_id = $sender_acc_id");
            if(!$u1) throw new Exception("Deduction failed: " . $conn->error);

            $u2 = $conn->query("UPDATE accounts SET balance = balance + $amount WHERE account_id = $recipient_acc_id");
            if(!$u2) throw new Exception("Deposit failed: " . $conn->error);

            // STEP D: Log Transaction
            $log_stmt = $conn->prepare("INSERT INTO transaction_records (sender_id, recipient_id, amount) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iid", $sender_acc_id, $recipient_acc_id, $amount);
            if(!$log_stmt->execute()) throw new Exception("Logging failed: " . $log_stmt->error);

            // SUCCESS
            $conn->commit();
            echo "<script>alert('✅ Success! Balance updated and logged.'); window.location.href='t_b_history.php';</script>";

        } catch (Exception $e) {
            $conn->rollback();
            $err = addslashes($e->getMessage());
            echo "<script>alert('❌ TRANSACTION REJECTED: $err');</script>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UPI Transfer - WebBank</title>
    <link rel="stylesheet" href="dashboardstyle.css">
    <style>
        .transfer-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin-top: 50px;
            flex-wrap: wrap;
        }

        .flex-card {
            background: white;
            padding: 30px;
            width: 320px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
        }

        /* Hover Interaction */
        .flex-card:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .sender-card { border-color: #3498db; }
        .recipient-card { border-color: #27ae60; }

        .avatar {
            font-size: 3rem;
            background: #f4f7f6;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
        }

        .transfer-arrow {
            font-size: 2.5rem;
            color: #bdc3c7;
            animation: slide 2s infinite;
        }

        @keyframes slide {
            0% { transform: translateX(0); opacity: 0.3; }
            50% { transform: translateX(10px); opacity: 1; }
            100% { transform: translateX(0); opacity: 0.3; }
        }

        .input-box {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .btn-pay {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-pay:hover { background: #219150; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align:center; margin-top:40px;">Secure UPI Transfer</h2>
    
    <form method="POST" action="upi.php">
        <div class="transfer-wrapper">
            
            <div class="flex-card sender-card">
                <div class="avatar">👤</div>
                <h3>From (You)</h3>
                <p><strong><?php echo htmlspecialchars($personal_data['customer_full_name']); ?></strong></p>
                <p style="color: #7f8c8d;"><?php echo htmlspecialchars($account_data['account_number']); ?></p>
                <hr>
                <p>Current Balance</p>
                <div style="font-size: 1.5rem; color: #2ecc71;">₹<?php echo number_format($account_data['balance'], 2); ?></div>
            </div>

            <div class="transfer-arrow">➔</div>

            <div class="flex-card recipient-card">
                <div class="avatar" id="receiver-icon">?</div>
                <h3>To Recipient</h3>
                <input type="text" name="recipient_id" class="input-box" placeholder="Enter UPI ID / Username" required id="upi-input">
                <input type="number" name="amount" class="input-box" placeholder="Enter Amount (₹)" step="0.01" required>
            </div>

        </div>

        <div style="text-align: center; margin-top: 40px;">
            <button type="submit" class="btn-pay">Initiate Transaction</button>
            <br><br>
            <a href="dashboard.php" style="color: #95a5a6; text-decoration: none;">Cancel and Go Back</a>
        </div>
    </form>
</div>

<script>
     
    const upiInput = document.getElementById('upi-input');
    const icon = document.getElementById('receiver-icon');
    
    upiInput.addEventListener('input', (e) => {
        if(e.target.value.length > 0) {
            icon.innerText = '👤';
            icon.style.background = '#eafaf1';
        } else {
            icon.innerText = '?';
            icon.style.background = '#f4f7f6';
        }
    });
</script>

</body>
</html>
