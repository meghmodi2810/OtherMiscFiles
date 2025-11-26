<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <script src="jquery-3.7.1.min.js" type="text/javascript"></script>
    <body>
        <form action="" method="POST">
            search box : 
            <input type="text" name="txtSearch" id="txtSearch" placeholder="Search here...">
        </form>
        <div id="txtResponse">
        </div>
        <script type="text/javascript">
            $(document).ready(function () {
                $("#txtSearch").keyup(function () {
                    var input = $(this).val();
                    if (input !== "") {
                        $.ajax({
                            url: "getData.php",
                            method: "get",
                            data: {input: input},
                            success: function (data) {
                                $("#txtResponse").html(data);
                            }
                        });
                    } else {
                        $('#txtResponse').html("");
                    }
                });
            });
        </script>
    </body>
</html>
