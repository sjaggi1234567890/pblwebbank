<?php
session_start();
include 'dbconnect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    $user_input = $_POST['username']; 
    $pass_input = $_POST['password'];

   
    $sql = "SELECT a.customer_id, a.account_id, a.username, c.customer_password 
            FROM accounts a 
            JOIN customers_basic_info c ON a.customer_id = c.customer_id 
            WHERE a.username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($pass_input, $row['customer_password'])) {
             
            $_SESSION['customer_id'] = $row['customer_id'];
            $_SESSION['account_id'] = $row['account_id']; 
            $_SESSION['username'] = $row['username'];

            header("Location: dashboard.php");
            exit();
        }else{
            echo"<script>alert('Invalid Password'); window.location='login.html';</script>";
        }
    } else{
        echo"<script>alert('Username not found'); window.location='login.html';</script>";
    }
    $stmt->close();
}
$conn->close();
?>
    $stmt->close();
}
$conn->close();
?>
