<?php
 
include 'dbconnect.php';

 
   

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $dob = $_POST['dob'];
    $pan = $_POST['pan_card'];
    $state = $_POST['state'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

   
    $date1 = new DateTime($dob);
    $date2 = new DateTime();
    $interval = $date1->diff($date2);
    if($interval->y < 18) {
        die("Error: age should be  atleast 18 year old");
    }
    
       $sql = "INSERT INTO customers_basic_info (customer_full_name, customer_last_name, customer_mobile_number, customer_date_of_birth, customer_email, customer_password, customer_pan_card, state) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $fname, $lname, $mobile, $dob, $email, $password, $pan, $state);
    
     
  if (!$stmt) {
    echo "<script>alert('Failed connection to DB');</script>";
    die("Prepare failed: " . $conn->error);
}

    $stmt->bind_param("ssssssss", $fname, $lname, $mobile, $dob, $email, $password, $pan, $state);

    if ($stmt->execute()){
        $new_customer_id = $conn->insert_id;
 
        $pan_last_two = substr($pan, -2);
        $base_username = strtolower($fname.$lname.$pan_last_two);
        $base_username = str_replace(' ', '', $base_username); 

        $final_username = $base_username;
        $counter = 1;
        $exists = true;


        while ($exists){
            $check_sql = "SELECT account_id FROM accounts WHERE username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $final_username);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0){
                $final_username = $base_username.$counter;
                $counter++;
            } else{
                $exists = false;
            }
            $check_stmt->close();
        }
            
        $acc_num = rand(1000000000, 9999999999);
$exists = true;
while ($exists) {
    $check_sql = "SELECT account_id FROM accounts WHERE account_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $acc_num);
    $check_stmt->execute();
    $check_stmt->store_result();

    if($check_stmt->num_rows > 0) {
        
        $acc_num++; 
    }else{ $exists = false;
    }
    $check_stmt->close();
}

        $acc_sql = "INSERT INTO accounts (customer_id, account_number, username, balance) VALUES (?, ?, ?, 5000.00)";
        $acc_stmt = $conn->prepare($acc_sql);
        
        if(!$acc_stmt){
            die("Accounts Prepare failed: " . $conn->error);
        }

        $acc_stmt->bind_param("iis", $new_customer_id, $acc_num, $final_username);
        
        if ($acc_stmt->execute()){
            echo "<h2>Registration Successful!</h2>";
            echo "Welcome, " . htmlspecialchars($fname) . "!<br>";
            echo "Your Username: <strong>" . $final_username . "</strong><br>";
            echo "Your Account Number: <strong>" . $acc_num . "</strong><br>";
            echo "<a href='login.html' target='_blank'> Click here to Login</a>";
        } else {
            $message= "account creation failed";
            echo "<script type='text/javascript'>alert('$message'. $acc_stmt->error);</script>";
              
        }
        $acc_stmt->close();
    } else {
                $message = "Error while inserting to database, check for duplication or any left fields again".$stmt->error;

    echo "<script type='text/javascript'>alert('$message'); </script>";

    }
    
    $stmt->close();
}

$conn->close();
?>


  
