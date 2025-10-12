<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Search</title>
    </head>
    <body>
        <script>
            function LoadData(str) {
                if(str !== "") {
                    xhr = new XMLHttpRequest();
                    xhr.open("get", "getData.php?text="+str, true);
                    xhr.send();

                    xhr.onreadystatechange = function() {
                        document.getElementById("txtLoadData").innerHTML = xhr.responseText;
                    }
                } else {
                    document.getElementById("txtLoadData").innerHTML = "";
                }
            }
        </script>
        <form action="" method="POST">
            <input type="text" name="txtSearch" id="txtSearch" onkeyup="LoadData(this.value)">
        </form>
        <div id="txtLoadData" name="txtLoadData">
        </div>
    </body>
</html>
