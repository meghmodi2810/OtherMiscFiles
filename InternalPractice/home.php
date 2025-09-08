<?php
session_start();
if(!isset($_SESSION['Name'])) {
    header("Location: login.php");
}
echo "Welcome ", $_SESSION['Name'];