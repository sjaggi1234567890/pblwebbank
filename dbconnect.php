<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'Banking_System';
 
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
echo " COnnection to the server Failed : "; 
$error_number = mysqli_connect_errno();
$error_message = mysqli_connect_error();
echo "Error Number: " . $error_number . "<br>";
echo "Error Message: " . $error_message . "<br>";
    die("Connection failed: ".mysqli_connect_error());
}
?>
