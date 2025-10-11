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
    <body align="center">
        <h1>live search</h1>
        <input type="text" name="search" id="live_search" placeholder="Search...">
        <div id="searchreasult"></div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                $("#live_search").keyup(function(){
                    var input =$(this).val();
                    //alert(input);
                    
                    if(input != ""){
                        $.ajax({
                            url:"result.php",
                            method:"POST",
                            data:{input:input},
                            
                            success:function(data)
                            {
                                $("#searchreasult").html(data);
                           }
                        });
                    }else{
                       $("#searchreasult").html();    
                    }
                  });
            }); 
        </script>
        <?php
        // put your code here
        ?>
    </body>
</html>
