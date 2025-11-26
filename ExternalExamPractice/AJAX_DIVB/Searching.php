<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX Searching</title>
    </head>
    <body>
        <form action="" method="POST">
            Search Box : 
            <input type="text" name="txtSearch" id="txtSearch" onkeyup="LoadData(this.value)" style="width: 50%; font-size: 20px;">
        </form>
        <div id="loadData" name="loadData">
        </div>
        <script>
            function LoadData(str) {
                xhr = new XMLHttpRequest();
                xhr.open("GET", "getData.php?str=" + str, true);
                xhr.send();

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById('loadData').innerHTML = xhr.responseText;
                    }
                };
            }
        </script>
    </body>
</html>
