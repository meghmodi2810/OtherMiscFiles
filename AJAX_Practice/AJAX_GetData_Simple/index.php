<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX Example</title>
    </head>
    <body>
        <script>
            function getData() {
                var xhr = new XMLHttpRequest();
                xhr.open("get", "getAjaxData.php", true);
                xhr.send();
                
                xhr.onreadystatechange = function() {
                    if(xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById('txtGetter').innerHTML = xhr.responseText;
                    }
                };
            }
        </script>
        <form action="" method="POST">
            <input type="button" name="btnGet" id="btnGet" onclick="getData()">
        </form>
        <div id="txtGetter">
            
        </div>
        <?php
            
        ?>
    </body>
</html>
