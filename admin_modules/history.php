<?php
$query = "SELECT 
            t.*,  s.account_number AS sender_acc, s.username AS sender_user, r.account_number AS recipient_acc, r.username AS recipient_user 
          FROM transaction_records t 
          LEFT JOIN accounts s ON t.sender_id = s.account_id 
          LEFT JOIN accounts r ON t.recipient_id = r.account_id 
          ORDER BY t.transaction_timestamp DESC";

$logs = $conn->query($query);
?>

<h2>Transaction Timeline</h2>
<table class="admin-table">
    <thead>
        <tr>
            <th>Timestamp</th>
            <th>Sender Name</th>
            <th>Sender Acc No</th>
            <th>Recipient Name</th>
            <th>Recipient Acc No</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $logs->fetch_assoc()): ?>
        <tr>
            <td><?php echo date('d M, Y H:i', strtotime($row['transaction_timestamp'])); ?></td>
            
            <td><?php echo $row['sender_user']; ?></td>
            <td><?php echo $row['sender_acc']; ?></td>
            
            <td><?php echo htmlspecialchars($row['recipient_user']); ?></td>
            <td><?php echo htmlspecialchars($row['recipient_acc']); ?></td>
            
            <td>
                ₹ <?php echo number_format($row['amount'], 2); ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
