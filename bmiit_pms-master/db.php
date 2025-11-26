<?php

$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'bmiit_pms';
$conn = mysqli_connect($hostname, $username, $password);
mysqli_select_db($conn, $database);

if($conn->connect_error){
    die("Connection Failed");
}
