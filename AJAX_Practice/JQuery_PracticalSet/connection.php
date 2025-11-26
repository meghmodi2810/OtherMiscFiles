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
        <?php
        $host="localhost";
        $user="root";
        $pass="6640";
        $db="db_ajax";
        
        $connect= mysqli_connect($host,$user,$pass,$db);
        
        if(!$connect)
        {
            echo"database not connected!!".mysqli_connect_error();
        }
        ?>
    </body>
</html>
