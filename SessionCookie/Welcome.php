<?php
    session_start();
    if(!isset($_SESSION['usr'])) {
        header('Location: demo.php');
    }
    echo 'Hello, ', $_SESSION['usr'];


