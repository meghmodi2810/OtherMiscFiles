<html>
    <head>
        <meta charset="UTF-8">
        <title>Review</title>
    </head>
    <body>
        <?php
        session_start();
        require_once 'db_connect.php';
        include_once 'base.php';
        if (!isset($_SESSION['name'])) {
            header("Location: index.php");
        }
        ?>
        <form action="" method="POST">
            Restaurant Name : 
            <select name="txtRestaurant">
                <option value="" disabled selected>--SELECT--</option>
                <?php
                    $stmt = $conn->prepare("SELECT NAME FROM restaurant");
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    while($row = $res->fetch_assoc()) {
                        echo "<option>" . $row['NAME'] . "</option>";
                    }
                    
                    $stmt->close();
                ?>
            </select><BR><BR>
            
            Rating : 
            <input type="number" name="txtRating" id="txtRating" max="5" min="1" required><BR><BR>
            
            Description(optional) :
            <input type="text" name="txtDescription" id="txtDescription">
            
            <input type="submit" name="btnSubmit" value="Submit">
        </form>
        <?php
            if(isset($_POST['btnSubmit'])) {
                $stmt = $conn->prepare("INSERT INTO review VALUES(?, ?, ?, ?)");
                $stmt->bind_param("ssis", $_SESSION['name'], $_POST['txtRestaurant'], $_POST['txtRating'], $_POST['txtDescription']);
                
                if($stmt->execute()) {
                    echo "Review submitted!";
                } else {
                    echo "Review failed to submit!";
                }
                
                $stmt->close();
                $conn->close();
            }
        ?>
    </body>
</html>
