<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX Search</title>
    </head>
    <body>
        <script>
            function LoadData(str) {
                if(str !== "") {
                    xhr = new XMLHttpRequest();
                    xhr.open("get", "getDataAjax.php?text="+str, true);
                    xhr.send();

                    xhr.onreadystatechange = function() {
                        document.getElementById('txtReturn').innerHTML = xhr.responseText;
                    };
                } else {
                    document.getElementById('txtReturn').innerHTML = "";
                }
            }
        </script>
        <form action="" method="POST">
            <input type="text" name="txtSearch" id="txtSearch" onkeyup="LoadData(this.value)">
        </form>
        <div id="txtReturn" name="txtReturn">
        </div>
    </body>
</html>
