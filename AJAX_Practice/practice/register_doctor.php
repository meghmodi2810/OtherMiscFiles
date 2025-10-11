<?php

require 'db.php';

if (isset($_POST['register-doctor'])) {
    $name = $_POST['name'];
    $specialist = $_POST['specialist'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $con_pass = $_POST['con-password'];

    if (strlen($pass) == 8) {
        if ($pass == $con_pass) {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("insert into doctor(dname,specialist,email,password) values(?,?,?,?)");
            $stmt->bind_param('ssss', $name, $specialist, $email, $pass_hash);
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