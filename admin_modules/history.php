<?php
$query = "SELECT t.*, s.account_number as sender, r.account_number as receiver 
          FROM transaction_records t 
          LEFT JOIN accounts s ON t.sender_id = s.account_id 
          LEFT JOIN accounts r ON t.recipient_id = r.account_id 
          ORDER BY t.transaction_timestamp DESC";
$logs = $conn->query($query);
?>
<h2>Global Transaction Timeline</h2>
<table>
    <thead>
        <tr>
            <th>Timestamp</th>
            <th>Sender</th>
            <th>Recipient</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $logs->fetch_assoc()): ?>
        <tr>
            <td><?php echo date('d M, Y H:i', strtotime($row['transaction_timestamp'])); ?></td>
            <td><?php echo $row['sender'] ?? '<span style="color:#7f8c8d;">SYSTEM</span>'; ?></td>
            <td><?php echo $row['receiver']; ?></td>
            <td style="font-weight:bold; color:var(--accent);">₹<?php echo number_format($row['amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>