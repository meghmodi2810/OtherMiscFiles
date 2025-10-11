<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <script>
            function get_register(val) {
                var a = new XMLHttpRequest();
                a.open("get", 'get_register.php?val=' + val, true);
                
                a.onreadystatechange = function(){
                    if (a.readyState === 4 && a.status === 200) {
                        document.getElementById('main_form').innerHTML=a.responseText;
                    }
                };
                a.send();
            }
        </script>
    </head>
    <body>
        <form>
            User Type:~
            <select name='type' onchange='get_register(this.value)' required>
                <option value=''>--choose user type--</option>
                <option value='Patient'>Patient</option>
                <option value='Doctor'>Doctor</option>
            </select>
        </form>
        <div id='main_form'>
        </div>
        <?php
        // put your code here
        ?>
    </body>
</html>
