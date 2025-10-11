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
        <h1 align="center">Search AJAX</h1>
        <script>
            function LoadData(str) {
                if(str !== "") {
                    xhr = new XMLHttpRequest();
                    xhr.open("get", "LoadDataAjax.php?text="+str, true);
                    xhr.send();

                    xhr.onreadystatechange = function() {
                        document.getElementById('loadData').innerHTML = xhr.responseText;
                    };
                } else {
                    document.getElementById('loadData').innerHTML = "";
                }
            }
        </script>
        <form action="" method="POST">
            <input type="text" name="txtSearch" id="txtSearch" onkeyup="LoadData(this.value)" style="width: 100%; font-size: 22px;">
        </form>
        
        <div id="loadData" name="loadData">
        </div>
    </body>
</html>
