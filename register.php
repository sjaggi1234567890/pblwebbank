<?php
// 1. Database Connection
include 'dbconnect.php';

 
   

if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $fname    = $_POST['first_name'];
    $lname    = $_POST['last_name'];
    $email    = $_POST['email'];
    $mobile   = $_POST['mobile'];
    $dob      = $_POST['dob'];
    $pan      = $_POST['pan_card']; 
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

   
    $date1 = new DateTime($dob);
    $date2 = new DateTime();
    $interval = $date1->diff($date2);
    if($interval->y < 18) {
        die("Error: You must be at least 18 years old to open an account.");
    }

    
    $sql = "INSERT INTO customers_basic_info (customer_full_name, customer_last_name, customer_mobile_number, customer_date_of_birth, customer_email, customer_password, customer_pan_card) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssssss", $fname, $lname, $mobile, $dob, $email, $password, $pan);

    if ($stmt->execute()) {
        $new_customer_id = $conn->insert_id;

        // 5. Generate Username (FirstName + LastName + Last 2 of PAN)
        $pan_last_two = substr($pan, -2);
        $base_username = strtolower($fname . $lname . $pan_last_two);
        $base_username = str_replace(' ', '', $base_username); // Remove spaces

        $final_username = $base_username;
        $counter = 1;
        $exists = true;

        // Check for duplicate usernames
        while ($exists) {
            $check_sql = "SELECT account_id FROM accounts WHERE username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $final_username);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $final_username = $base_username . $counter;
                $counter++;
            } else {
                $exists = false;
            }
            $check_stmt->close();
        }

        // 6. Generate Account Number and Insert into accounts
        $acc_num = "$fname" . rand(10000000, 99999999);
        
        // Ensure your 'accounts' table has the 'username' column!
        $acc_sql = "INSERT INTO accounts (customer_id, account_number, username, balance) VALUES (?, ?, ?, 0.00)";
        $acc_stmt = $conn->prepare($acc_sql);
        
        if (!$acc_stmt) {
            die("Accounts Prepare failed: " . $conn->error);
        }

        $acc_stmt->bind_param("iss", $new_customer_id, $acc_num, $final_username);
        
        if ($acc_stmt->execute()){
            echo "<h2>Registration Successful!</h2>";
            echo "Welcome, " . htmlspecialchars($fname) . "!<br>";
            echo "Your Username: <strong>" . $final_username . "</strong><br>";
            echo "Your Account Number: <strong>" . $acc_num . "</strong><br>";
            echo "<a href='login.html'>Click here to Login</a>";
        } else {
            echo "Account creation failed: " . $acc_stmt->error;
        }
        $acc_stmt->close();
    } else {
        echo "Customer Info Error: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>