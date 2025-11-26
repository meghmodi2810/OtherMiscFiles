<html>
    <head>
        <meta charset="UTF-8">
        <title>Edit page</title>
    </head>
    <body>
        <?php
        require_once 'db_connect.php';
        $stmt = $conn->prepare("SELECT * FROM restaurant WHERE NAME = ?");
        $stmt->bind_param("s", $_GET['name']);
        $stmt->execute();

        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $name = $row["NAME"];
            $location = $row["LOCATION"];
            $capacity = $row["CAPACITY"];
        }
        $stmt->close();
        ?>
        <form action="" method="POST">
            <table border="1" cellpadding="20px">
                <tr>
                    <td>
                        <label for="lblName">Restaurant Name : </label>
                    </td>
                    <td>
                        <input type="text" name="txtName" id="txtName" style="height: 30px; width: 200px;" value="<?php echo $name; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblLocation">Location : </label>
                    </td>
                    <td>
                        <input type="text" name="txtLocation" id="txtLocation" style="height: 30px; width: 200px;" value="<?php echo $location; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblCapacity">capacity : </label>
                    </td>
                    <td>
                        <input type="text" name="txtCapacity" id="txtCapacity" style="height: 30px; width: 200px;" value="<?php echo $capacity; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="btnUpdate" value="Update">
                    </td>
                </tr>
            </table>
        </form>
        <?php
        if (isset($_POST['btnUpdate'])) {
            $stmt = $conn->prepare("UPDATE restaurant SET NAME=?, LOCATION=?, CAPACITY=? WHERE NAME=?");
            $stmt->bind_param("ssis", $_POST['txtName'], $_POST['txtLocation'], $_POST['txtCapacity'], $name);

            if ($stmt->execute()) {
                echo "Success!";
            } else {
                echo "Failure!";
            }
            header("Location: listing.php");
            $stmt->close();
        }
        $conn->close();
        ?>
    </body>
</html>
