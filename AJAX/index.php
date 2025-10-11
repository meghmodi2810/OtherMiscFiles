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
            function LoadData(id) {
                if (id != "") {
                    var REQ = new XMLHttpRequest();
                    REQ.open("get", "GetDataAjax.php?id=" + id, true);
                    REQ.send();
                    REQ.onreadystatechange = function () {
                        if (REQ.readyState === 4 && REQ.status === 200) {
                            document.getElementById("txtLoadData").innerHTML = REQ.responseText;
                        }
                    };
                } else {
                    document.getElementById("txtLoadData").innerHTML = "";
                }
            }
        </script>
    </head>
    <body>
        <form>
            <input type="text" name="txtSearch" id="txtSearch" onkeyup="LoadData(this.value)">
            <div id="txtLoadData">
            </div>
        </form>
        <?php
        ?>
    </body>
</html>
