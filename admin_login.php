
<!DOCTYPE html>
<html lang="en">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 
 
    $query = "SELECT bank_admin_id,bank_admin_full_name, bank_admin_password FROM bank_admin_info WHERE bank_admin_full_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) { 
        if ($password === $row['bank_admin_password']) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $row['bank_admin_id'];
            $_SESSION['admin_name'] = $row['bank_admin_full_name'];
              echo"<script type='text/javascript'>alert('Welcom Admin'); > ";
            header("Location: admin_dashboard.php");
            exit();
        }else{
             echo"<script type='text/javascript'>alert('incorrect password'); > ";
        }
    } else{
          echo"<script type='text/javascript'>alert('account not foudn'); > ";
    }
}
?>

