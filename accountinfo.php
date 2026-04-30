<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['customer_id'])) {

    header("Location: admin_login.html");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$message = "";
  
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_email = $_POST['email'];
    $new_mobile = $_POST['mobile'];

    $update_query = "UPDATE customers_basic_info SET customer_email = ?, customer_mobile_number = ? WHERE customer_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $new_email, $new_mobile, $customer_id);
    
    if ($stmt->execute()) {
        $message = "<div class='msg success'>Profile updated successfully!</div>";
    } else {
        $message = "<div class='msg error'>Error updating profile.</div>";
    }
    $stmt->close();
}

 
$query = "SELECT c.*, a.account_number, a.username FROM customers_basic_info c 
          JOIN accounts a ON c.customer_id = a.customer_id WHERE c.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - SwiftBank</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        .profile-container { max-width: 700px; margin: 40px auto; }
        .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #666; font-weight: bold; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; background: #fff; }
        .form-group input[readonly] { background: #f9f9f9; color: #888; cursor: not-allowed; }
        .btn-save { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-size: 1rem; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 6px; text-align: center; }
        .success { background: #d4efdf; color: #27ae60; }
    </style>
</head>
<body>

<div class="profile-container">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
    
    <div class="form-card">
        <h2>Account Settings</h2>
        <?php echo $message; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name </label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['customer_full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Account Number </label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['account_number']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['customer_email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" name="mobile" value="<?php echo htmlspecialchars($user_data['customer_mobile_number']); ?>" required>
            </div>

            <div class="form-group">
                <label>UPI ID / Username (Locked)</label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
            </div>

            <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>
