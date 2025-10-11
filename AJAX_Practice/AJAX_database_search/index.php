<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX</title>
    </head>
    <body>
        <script>
            function LoadData(str) {
                xhr = new XMLHttpRequest();
                xhr.open("get", "getAjaxData.php?text=" + str, true);
                xhr.send();
                
                xhr.onreadystatechange = function () {
                    document.getElementById('txtResponseText').innerHTML = xhr.responseText;
                };
            }
        </script>
        <form method="POST" action="">
            <input type="text" id="txtSearch" name="txtSearch" onkeyup="LoadData(this.value)">
        </form>
        <div id="txtResponseText">
        </div>
        <?php
        // put your code here
        ?>
    </body>
</html>
