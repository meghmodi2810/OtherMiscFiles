<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
               $("#search").keyup(function() {
                  var a = $("#search").val(); 
                  $("#test").html(a);
                 
                  $.get("JQuery_return.php", {'id':a}, function(data, status) {
                    alert(status);
                    alert(data);
        
                    $("#test").html(data);
                  });
                  
               });
            });
        </script>
        <form>
            <input type="text" id="search"placeholder="Enter your text..">
        </form>
        <div id="test"></div>
        <?php
        ?>
    </body>
</html>
