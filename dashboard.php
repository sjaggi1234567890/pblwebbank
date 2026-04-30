<?php
session_start();
include 'dbconnect.php';

// 1. Security: Ensure only a LOGGED-IN CUSTOMER can access this
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.html");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// 2. Fetch User and Account Details
$query = "SELECT c.customer_full_name, a.balance, a.account_number 
          FROM customers_basic_info c 
          JOIN accounts a ON c.customer_id = a.customer_id 
          WHERE c.customer_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user actually exists in the accounts table
if ($result->num_rows === 0) {
    echo "Account data not found. Please contact admin.";
    exit();
}

$data = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Personal Banking</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        :root { --primary: #2c3e50; --secondary: #34495e; --accent: #27ae60; --text: #ecf0f1; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .nav-panels {
            display: flex;
            justify-content: space-around;
            gap: 25px;
            perspective: 1000px;
        }
        .panel-card {
            flex: 1;
            background: white;
            padding: 35px 20px;
            border-radius: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #eee;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .panel-card:hover {
            transform: translateY(-15px) rotateX(5deg) rotateY(5deg);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .icon-circle {
            width: 70px;
            height: 70px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            transition: 0.3s;
        }
        .panel-card:hover .icon-circle {
            background: var(--accent);
            color: white;
        }
        .logout-btn {
            display: inline-block;
            margin-top: 20px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="welcome-section">
        <h2>Welcome back, <?php echo htmlspecialchars($data['customer_full_name']); ?>!</h2>
        <p style="opacity: 0.8;">Account Number: <?php echo htmlspecialchars($data['account_number']); ?></p>
        <div style="margin-top: 20px;">
            <small>Available Balance</small>
            <h1 style="font-size: 3.5rem; margin: 0;">₹<?php echo number_format($data['balance'], 2); ?></h1>
        </div>
    </div>

    <div class="nav-panels">
        <a href="upi.php" class="panel-card">
            <div class="icon-circle">💸</div>
            <h3>Send Money</h3>
            <p>Quick UPI Transfer</p>
        </a>

        <a href="t_b_history.php" class="panel-card">
            <div class="icon-circle">📜</div>
            <h3>Transactions</h3>
            <p>History & Statement</p>
        </a>

        <a href="account_info.php" class="panel-card">
            <div class="icon-circle">⚙️</div>
            <h3>Settings</h3>
            <p>Profile & Security</p>
        </a>
    </div>

    <div style="text-align: center; margin-top: 50px;">
        <a href="logout.php" class="logout-btn">Secure Logout</a>
    </div>
</div>

</body>
</html>