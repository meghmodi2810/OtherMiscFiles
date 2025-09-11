<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX Practical</title>
        <script>
            function LoadData () {
                var REQ = new XMLHttpRequest();
                REQ.open("get", "GetDataAjax.php", true);
                REQ.send();
                REQ.onreadystatechange = function() {
                    if(REQ.readyState===4 && REQ.status===200) {
                        document.getElementById("txtLoadData").innerHTML = REQ.responseText;
                    }
                };
            }
        </script>
    </head>
    <body>
        <form>
            <input type="button" name="btnSubmit" id="btnSubmit"value="Click here!" onclick="LoadData()">
            <div id="txtLoadData">
            </div>
        </form>
        <?php
            
        ?>
    </body>
</html>
