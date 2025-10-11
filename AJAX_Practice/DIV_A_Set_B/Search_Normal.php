<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX Search</title>
    </head>
    <body>
        <?php
        include_once 'db_connection.php';
        ?>
        <h1 align="center">Search Normal</h1>
        <form action="" method="POST">
            <select name="txtSelect">
                <OPTION value="" disabled selected>--SELECT--</option>
                <OPTION>AC</OPTION>
                <OPTION>Non-AC</option>
            </select>
            <input type="submit" name="btnSubmit">
        </form>
        <?php
        if (isset($_POST['btnSubmit'])) {
            $roomtype = $_POST['txtSelect'];
            $query = "SELECT * FROM feemaster "
                    . "JOIN hostelmaster ON "
                    . "hostelmaster.studentId = feemaster.studentId "
                    . "WHERE hostelmaster.RoomType = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $roomtype);

            $stmt->execute();
            $res = $stmt->get_result();

            echo "<TABLE border=1>";
            echo "<tr>";
            echo "<td>" . "StudentName" . "</td>";
            echo "<td>" . "RoomType" . "</td>";
            echo "<td>" . 'MonthlyFees' . "</td>";
            echo "<td>" . 'Duration' . "</td>";
            echo "<td>" . 'TotalFees' . "</td>";
            echo "</tr>";
            while ($r = $res->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $r['StudentName'] . "</td>";
                echo "<td>" . $r['RoomType'] . "</td>";
                echo "<td>" . $r['MonthlyFees'] . "</td>";
                echo "<td>" . $r['Duration'] . "</td>";
                echo "<td>" . $r['TotalFees'] . "</td>";
                echo "</tr>";
            }
            echo "</TABLE>";
            $stmt->close();
            $conn->close();
        }
        ?>
    </body>
</html>
