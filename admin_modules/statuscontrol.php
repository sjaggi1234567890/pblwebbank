<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['is_admin'])){
    die("unauthaurized access.");
}

if (isset($_GET['id']) && isset($_GET['set'])){
    $uid = intval($_GET['id']);
    $new_status = ($_GET['set'] == 'active') ? 'active' : 'deactive';

    $query = "UPDATE accounts SET account_status = ? WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $uid);

    if ($stmt->execute()){
        header("Location: admin_dashboard.php?page=users&msg=Status+Updated");
    }else{
        echo "Error updating record: " . $conn->error;
    }
    $stmt->close();
}
?>
