<?php
 
if (!isset($_SESSION['is_admin'])) { exit('Access Denied'); }

$message = "";
 
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $uid = intval($_GET['id']);
    
    $conn->begin_transaction();
    try {
     
        $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE customer_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $acc_row = $stmt->get_result()->fetch_assoc();
        
        if ($acc_row) {
            $aid = $acc_row['account_id'];
            $conn->query("DELETE FROM transaction_records WHERE sender_id = $aid OR recipient_id = $aid");
            $conn->query("DELETE FROM accounts WHERE account_id = $aid");
        }
        
        $conn->query("DELETE FROM customers_basic_info WHERE customer_id = $uid");
        $conn->commit();
        $message = "<div class='alert success'>User ID $uid has been purged from the system.</div>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert error'>Failed to delete user.</div>";
        echo" <script type='text/javascript'>alert($message);
    }
}

 
$query = "SELECT c.customer_id, c.customer_full_name, c.customer_email, 
                 a.account_number, a.balance, a.account_status 
          FROM customers_basic_info c 
          JOIN accounts a ON c.customer_id = a.customer_id 
          ORDER BY c.customer_id DESC";
$result = $conn->query($query);
?>

<div class="user-management-header">
    <h2>User Management Console</h2>
    <p>View, manage, and modify active customer accounts.</p>
</div>

<?php echo $message; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Email Address</th>
            <th>Account Number</th>
            <th>Balance</th>
           
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $row['customer_id']; ?></td>
            <td style="font-weight:bold;"><?php echo htmlspecialchars($row['customer_full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['customer_email']); ?></td>
            <td style="font-family: monospace;"><?php echo $row['account_number']; ?></td>
            <td style="color: var(--accent); font-weight:bold;">₹<?php echo number_format($row['balance'], 2); ?></td>
            <td>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_dashboard.php?page=credit&acc=<?php echo $row['account_number']; ?>" 
                       style="color: var(--accent); text-decoration: none; font-size: 0.9rem;">
                       add 
                    </a>
                    
                    <a href="admin_dashboard.php?page=users&action=delete&id=<?php echo $row['customer_id']; ?>" 
                       onclick="return confirm('WARNING: Are you sure you want to PERMANENTLY delete this user and their entire history?')" 
                       style="color: var(--danger); text-decoration: none; font-size: 0.9rem;">
                       [delete]
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        
        <?php if($result->num_rows == 0): ?>
        <tr>
            <td colspan="6" style="text-align:center; padding: 40px; opacity:0.5;">No customers found in database.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .admin-table th { background: #f8f9fa; color: #7f8c8d; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; padding: 15px; text-align: left; border-bottom: 2px solid #eee; }
    .admin-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; font-size: 0.95rem; }
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
    .success { background: #d4efdf; color: #27ae60; border-left: 5px solid #27ae60; }
    .error { background: #fadbd8; color: #e74c3c; border-left: 5px solid #e74c3c; }
</style>
