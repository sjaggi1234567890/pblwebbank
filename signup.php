<?php 



include "dbconnect.php";


if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $uname = $_POST['first_name']." ".$_POST['last_name'];
    $lname = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];             
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    unset($_POST['password']);
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pancard = $_POST['pan_card'];
    $mobile_number = $_POST['mobile'];
    $pincode = $_POST['pincode'];
    $date_of_birth = $_POST['dob'];

    $customer_address  = $city.", ".$state., "+ $pincode;

        /* if(!($pan_card ) || !($email) || !($password) || 
       !($uname) || !($city) || 
       !($state) || 
       !($mobile_number) || 
       !($date_of_birth)){
        echo "All fields are required.";
        exit;   
    }else{          */
      //  if( !($pan_card === mysql_query( SELECT balance FROM customers_basic_info WHERE customer_pan_card = '$pancard' ))){



        $sql = "INSERT INTO customers_basic_info (customer_full_name, customer_email, customer_password, customer_pan_card, customer_mobile_number, customer_date_of_birth, customer_address, ) VALUES ('$uname', '$email', '$hashed_password', '$pancard','$mobile_number', '$date_of_birth')";
        if(mysqli_query($conn, $sql)){
            echo "New record created successfully";
        } else{
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }





    }
    









?>