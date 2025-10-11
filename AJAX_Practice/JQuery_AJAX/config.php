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
        $db="db_selfcreation";
        
        $connect= mysqli_connect($host,$user,$pass,$db);
        
        if($connect)
        {
            echo "successfully connected";
        }
        else
        {
            die("Error:"). mysqli_connect_error();
        }
             ?>
    </body>
</html>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>