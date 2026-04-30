<?php
session_start();
include 'dbconnect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT admin_id, password_hash FROM admin_accounts WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password_hash'])) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $row['admin_id'];
            header("Location: admin_dashboard.php");
            exit();
        }
    }
    echo "Invalid Admin Access.";
}
?>
