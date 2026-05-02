<?php

if (!isset($_SESSION['is_admin'])){ exit('Access Denied'); }

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adjust_balance'])) {
    $acc_no = mysqli_real_escape_string($conn, $_POST['acc_no']);
    $amount = floatval($_POST['amount']);
    $action = $_POST['action_type']; // for adding money or debt

    if ($amount > 0){
        $conn->begin_transaction();
        try {
            if ($action == 'add') {
               
                $sql = "UPDATE accounts SET balance = balance + ? WHERE account_number = ?";
                $log_msg = "Manual System Deposit";
                $sender = NULL; 
                $recipient_query = "(SELECT account_id FROM accounts WHERE account_number = ?)";
                $recipient_param = $acc_no;
            } else {
          
                $sql = "UPDATE accounts SET balance = balance - ? WHERE account_number = ? AND balance >= ?";
                $log_msg = "Manual System Deduction";
                
                $sender_query = "(SELECT account_id FROM accounts WHERE account_number = ?)";
                $recipient = NULL;
            }

            
            $stmt = $conn->prepare($sql);
            if ($action == 'add') {
                $stmt->bind_param("ds", $amount, $acc_no);
            } else {
                $stmt->bind_param("dsd", $amount, $acc_no, $amount);
            }
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
               
                $log_sql = "INSERT INTO transaction_records (sender_id, recipient_id, amount) VALUES (?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                
                if ($action == 'add') {
  
    $res = $conn->query("SELECT account_id FROM accounts WHERE account_number = '$acc_no'");
    $recipient_id_val = $res->fetch_assoc()['account_id'];
    
   
    $sender = NULL; 
    $log_stmt->bind_param("iid", $sender, $recipient_id_val, $amount);
} else {
 
    $res = $conn->query("SELECT account_id FROM accounts WHERE account_number = '$acc_no'");
    $sender_id_val = $res->fetch_assoc()['account_id'];
 
    $recipient = NULL;
    $log_stmt->bind_param("iid", $sender_id_val, $recipient, $amount);
}
                $log_stmt->execute();
                $conn->commit();
                $message = "<div class='alert success'>Successfully updated A/C $acc_no.</div>";
            } else {
                throw new Exception("Account not found or insufficient funds for deduction.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert error'>Transaction Failed: " . $e->getMessage() . "</div>";
        }
    }
}


$prefilled_acc = isset($_GET['acc']) ? $_GET['acc'] : '';
?>


<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adjust_balance'])) {
    $acc_no = $_POST['acc_no'];
    $amount = floatval($_POST['amount']);
    $action = $_POST['action_type'];

 
    if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

    if ($action == 'add'){
        $query = "UPDATE accounts SET balance = balance + $amount WHERE account_number = '$acc_no'";
    }else{
        $query = "UPDATE accounts SET balance = balance - $amount WHERE account_number = '$acc_no'";
    }

    if ($conn->query($query)) {
        if ($conn->affected_rows > 0) {
             
            $res = $conn->query("SELECT account_id FROM accounts WHERE account_number = '$acc_no'");
            $acc_data = $res->fetch_assoc();
            $acc_id = $acc_data['account_id'];

            
            $log_query = ($action == 'add') 
                ? "INSERT INTO transaction_records (sender_id, recipient_id, amount) VALUES (NULL, $acc_id, $amount)"
                : "INSERT INTO transaction_records (sender_id, recipient_id, amount) VALUES ($acc_id, NULL, $amount)";
            
            $conn->query($log_query);
            echo "<div class='alert success'>Success! Updated A/C $acc_no</div>";
        } else {
            echo "<div class='alert error'>Error: Account Number '$acc_no' not found in database.</div>";
        }
    } else {
        echo "<div class='alert error'>SQL Error: " . $conn->error . "</div>";
    }
}
?>
<div class="module-header">
    <h2>Balance Adjustment (Credit/Debit)</h2>
    <p>Manual override for customer accounts.</p>
</div>

<?php echo $message; ?>

<div class="card" style="max-width: 500px;">
    <form method="POST">
        <div class="form-group">
            <label>Transaction Type</label>
            <select name="action_type" class="admin-input">
                <option value="add">Add Money (Credit)</option>
                <option value="deduct">Deduct Money (Debit)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Customer Account Number</label>
            <input type="text" name="acc_no" class="admin-input" 
                   value="<?php echo htmlspecialchars($prefilled_acc); ?>" required>
        </div>

        <div class="form-group">
            <label>Amount (₹)</label>
            <input type="number" name="amount" step="0.01" class="admin-input" placeholder="0.00" required>
        </div>

        <button type="submit" name="adjust_balance" class="btn btn-success">Execute Adjustment</button>
    </form>
</div>

<style>
    .admin-input { 
        width: 100%; padding: 12px; margin: 8px 0 20px; 
        border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; 
    }
    .form-group label{ font-weight: bold; color: var(--primary); }
</style>
