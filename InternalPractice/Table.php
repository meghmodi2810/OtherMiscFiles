<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Multiplication Table</title>
    </head>
    <body>
        <form action="" method="POST">
            <input type="number" name="txtNumber" max="100" min="0" title="Enter between 0 to 100 only!" required>
            <input type="submit" name="btnSubmit">
        </form>
        <?php
            if(isset($_POST['btnSubmit'])) {
                $num = $_POST['txtNumber'];
                
                echo "<TABLE border='1' cellpadding='10px' cellspacing='0px'>";
                for($i=1; $i<=$num; $i++) {
                    echo "<TR>";
                    for($j=1; $j<=$num; $j++){
                        echo "<TD>" . $i . " * " . $j . " = " . $i*$j;
                    }
                    echo "</TR>";
                }
                echo "</TABLE>";
            }
        ?>
    </body>
</html>
