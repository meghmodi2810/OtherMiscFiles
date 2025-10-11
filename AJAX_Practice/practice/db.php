<?php
$conn=mysqli_connect('localhost', 'root', '6640','civil_hospital');

if($conn->connect_error){
    echo $conn->connect_error;
}