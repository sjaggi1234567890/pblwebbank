<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['customer_id'])){
    header("Location: login.html");
    exit();
}

$sender_id = $_SESSION['customer_id'];
$check_sender = $conn->prepare("SELECT a.account_id, a.balance, a.account_status, c.customer_full_name, a.account_number 
                                FROM accounts a JOIN customers_basic_info c ON a.customer_id = c.customer_id  WHERE a.customer_id = ?");
$check_sender->bind_param("i", $sender_id);
$check_sender->execute();
$sender_res = $check_sender->get_result();

if ($sender_res->num_rows === 0){
echo"<script type='text/javascript'>alert('Your account not found from database, contanct admin');</script>";
    die("Error: Sender account record not found.");
}

$sender_data = $sender_res->fetch_assoc();

if ($sender_data['account_status'] !== 'active') {
    echo "<script>alert('Your account is currently " . $sender_data['account_status'] . ". Transactions are disabled.'); window.location.href='dashboard.php';</script>";
    exit();
}


$personal_data = ['customer_full_name' => $sender_data['customer_full_name']];
$account_data  = [
    'account_number' => $sender_data['account_number'],
    'balance' => $sender_data['balance']
];
 
if ($_SERVER["REQUEST_METHOD"] == "POST"){ 
    $recipient_acc = isset($_POST['recipient_id']) ? mysqli_real_escape_string($conn, $_POST['recipient_id']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
$short_note = isset($_POST['short_note']) ? mysqli_real_escape_string($conn, $_POST['short_note']) : '';


    if (empty($recipient_acc) || $amount <= 0) {
        echo "<script>alert('Error: Please enter a valid recipient and amount.');</script>";
    } else {
        $conn->begin_transaction();
        try{
           
            $stmt = $conn->prepare("SELECT account_id, balance FROM accounts WHERE customer_id = ? FOR UPDATE");
            $stmt->bind_param("i", $sender_id);
            $stmt->execute();
            $s_res = $stmt->get_result()->fetch_assoc();

            $sender_acc_id = $s_res['account_id'];

            if ($s_res['balance'] < $amount) throw new Exception("Insufficient balance.");
            
         
         
            $recipient_stmt = $conn->prepare("SELECT account_id, account_status FROM accounts WHERE username = ? OR account_number = ?");
            $recipient_stmt->bind_param("ss", $recipient_acc, $recipient_acc);
            $recipient_stmt->execute();
            $r_res = $recipient_stmt->get_result();

            if ($r_res->num_rows === 0) throw new Exception("Recipient not found.");
            
            $r_data = $r_res->fetch_assoc();
            $recipient_acc_id = $r_data['account_id'];

            if ($r_data['account_status'] !== 'active') throw new Exception("Recipient account is inactive.");
            if ($sender_acc_id == $recipient_acc_id) throw new Exception("Cannot send money to yourself.");

          
            $u1 = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
            $u1->bind_param("di", $amount, $sender_acc_id);
            if(!$u1->execute()){ throw new Exception("Deduction failed.");
            
echo"<script type='text/javascript'>alert('deduction failed because ');</script>";
            }

            $u2 = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
            $u2->bind_param("di", $amount, $recipient_acc_id);
            if(!$u2->execute()) throw new Exception("Deposit failed.");

          /* for loging into transaction table*/
          
            $log_stmt = $conn->prepare("INSERT INTO transaction_records (sender_id, recipient_id, amount, short_note) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("iids", $sender_acc_id, $recipient_acc_id, $amount, $short_note);
            if(!$log_stmt->execute()) throw new Exception("Logging failed.");

            $conn->commit();
            echo "<script>alert('Succesfully paid $recipent_name the amount of $amount'); window.location.href='t_b_history.php';</script>";
/*** */
        }catch (Exception $e) {
            $conn->rollback();
            $err = addslashes($e->getMessage());
            echo "<script>alert('TRANSACTION REJECTED: $err');</script>";
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

        
        .flex-card:hover{
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
                 <input type="text" name="short_note" class="input-box" placeholder="write a short note" style="color: #7f8c8e;">
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
        } else{
            icon.innerText = '?';
            icon.style.background = '#f4f7f6';
        }
    });
</script>

</body>
</html>
