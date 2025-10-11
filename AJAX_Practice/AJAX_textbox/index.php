<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX</title>
    </head>
    <script>
        function getData(str) {
            
            if(str.length !== 0) {
                xhr = new XMLHttpRequest();
                xhr.open("get", "getAjaxData.php?n=" + str, true);
                xhr.send();

                xhr.onreadystatechange = function () {
                    if(xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById("txtReturn").innerHTML = xhr.responseText;
                    }
                };
            } else {
                document.getElementById("txtReturn").innerHTML = "";
            }
        }
    </script>
    <form method="POST" action="">
        <input type="text" id="txtType" name="txtType" onkeyup="getData(this.value)">
    </form>
    <body>
        <div id="txtReturn">
            
        </div>
        <?php
            
        ?>
    </body>
</html>
