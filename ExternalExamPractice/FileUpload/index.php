<html>
    <head>
        <meta charset="UTF-8">
        <title>Multi File Upload</title>
    </head>
    <body>
        <form action="" method="POST" enctype="multipart/form-data">
            Upload File : 
            <input type="file" name="txtFile[]" id="txtFile" multiple required><BR><BR>

            <input type="submit" name="btnSubmit" value="Upload">
        </form>
        <?php
        if (isset($_POST['btnSubmit'])) {
            if (!file_exists("uploads")) {
                mkdir("uploads", 0777, true);
            }
            $counter = count($_FILES['txtFile']['name']);
            for($i = 0; $i < $counter; $i++) {
                $source = $_FILES['txtFile']['tmp_name'][$i];
                $filename = $_FILES['txtFile']['name'][$i];
                $destination = "uploads/" . $filename;
                
                if(move_uploaded_file($source, $destination)) {
                    echo "File : '{$filename}' : has been uploaded!<BR>";
                } else {
                    echo "File failed '{$filename}'!<BR>";
                }
            }
        }
        ?>
    </body>
</html>
