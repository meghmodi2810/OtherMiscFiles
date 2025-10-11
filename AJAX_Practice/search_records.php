<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <script>
        function getData(str)
        {
            if(str.length === 0)
            {
                document.getElementById('getData').innerHTML = "";
            }
            else
            {
                var a = new XMLHttpRequest();
                a.open("get","LoadData.php?n=" + str,true);
                a.send();

                a.onreadystatechange = function(){
                    if(a.readyState===4 && a.status===200)
                    {
                        document.getElementById('getData').innerHTML = a.responseText;
                    }
                };
            }
        }
    </script>
    <body>
        <form>
            <input type="search" onkeyup="getData(this.value)" >
            <div id="getData">
                
            </div>
        </form>
        <?php
        // put your code here
        ?>
    </body>
</html>
