
<!DOCTYPE html>
<html lang="en">
<head>
    
    <title>Admin Portal | Login</title>
 
</head>
<body>

<div class="login-box">
    <h2>Admin Login</h2>
    
    <?php if($error): ?>
        <div class="error-banner"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Admin Username</label>
            <input type="text" name="username" placeholder="Enter username" required autofocus>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="login-btn">Login to System</button>
    </form>
</div>

</body>
</html>
<?php
session_start();
include 'dbconnect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 
 
    $query = "SELECT admin_id, full_name, password FROM admin_accounts WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) { 
        if ($password === $row['password']) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['full_name'];
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "Admin account not found.";
    }
}
?>

