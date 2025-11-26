<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>JQuery AJAX</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    <body>
        <form action="" method="POST">
            TEXTBOX : 
            <input type="text" id="txtTextbox" placeholder="Enter anything here.."><BR>
            <input type="submit" id="btnSubmit" name="btnSubmit" value="Submit Button">
        </form>
        <div id="responseData">
        </div>
    </body>
    <script>
        $(document).ready(function() {
            $("#btnSubmit").click(function() {
                $.ajax({
                    url: 'getData.php',
                    type: 'post',
                    data: {text: $("#txtTextbox").val()},
                    success: function(data) {
                        $('#responseData').html(data);
                    }
                });
            });
        });
    </script>
</html>
