<?php

require 'db.php';

if (isset($_POST['register-patient'])) {
    $name = $_POST['name'];
    $disease = $_POST['disease'];
    $city = $_POST['city'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $con_pass = $_POST['con-password'];

    if (strlen($pass) == 8) {
        if ($pass == $con_pass) {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("insert into patient(pname,disease,city,email,password) values(?,?,?,?,?)");
            $stmt->bind_param('sssss', $name, $disease, $city, $email, $pass_hash);
            if ($stmt->execute()) {
                echo 'registeration successful.';
            } else {
                echo 'not successful.';
            }
        } else {
            echo 'password does not match';
        }
    } else {
        echo 'password must be of 8 chars.';
    }
}