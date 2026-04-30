<?php
$res = $conn->query("SELECT SUM(balance) as total, COUNT(*) as u_count FROM accounts");
$data = $res->fetch_assoc();
?>
<h2>Bank stats Overview</h2>
<div style="margin-top:30px;">
    <p style="color: #7f8c8d; margin-bottom:5px;">Total Vault Balance</p>
    <h1 style="font-size: 3.5rem; color: var(--primary); margin:0;">₹<?php echo number_format($data['total'], 2); ?></h1>
    <p style="margin-top:20px; font-size: 2.0rem;">Total Registered Customers: <strong><?php echo $data['u_count']; ?></strong></p>
</div>
