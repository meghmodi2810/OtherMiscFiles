<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?Php
        include_once 'base.php';
        ?>
        <form action="" method="POST">
            <input type="submit" name="btnCancel" value="Cancel Booking">
        </form>
        <?php
        session_start();
        if (!isset($_SESSION['username'])) {
            header('Location: index.php');
        }
        if (isset($_POST['btnCancel'])) {
            session_destroy();
            echo "booking canceled!";
        }
        ?>
    </body>
</html>
