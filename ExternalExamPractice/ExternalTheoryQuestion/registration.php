<html>
    <head>
        <meta charset="UTF-8">
        <title>Registration page</title>
    </head>
    <body>
        <?php
            session_start();
            require_once 'db_connect.php';
        ?>
        <form action="" method="POST">
            <table border="1" cellpadding="20px">
                <tr>
                    <td>
                        <label for="lblUsername">Username : </label>
                    </td>
                    <td>
                        <input type="text" name="txtUsername" id="txtUsername" style="height: 30px; width: 200px;" placeholder="Enter your username.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblEmail">Emal : </label>
                    </td>
                    <td>
                        <input type="email" name="txtEmail" id="txtEmail" style="height: 30px; width: 200px;" placeholder="Enter your email.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblPassword">Password : </label>
                    </td>
                    <td>
                        <input type="password" name="txtPassword" id="txtPassword" style="height: 30px; width: 200px;" placeholder="Enter your password.." required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" name="btnRegister" value="Register">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        Already a user? <a href="index.php">Login</a>
                    </td>
                </tr>
            </table>
            <?php
                if(isset($_POST['btnRegister'])) {
                    $stmt = $conn->prepare("INSERT INTO user VALUES(?, ?, ?)");
                    $stmt->bind_param("sss", $_POST['txtUsername'], $_POST['txtPassword'], $_POST['txtEmail']);
                    
                    if($stmt->execute()) {
                        echo 'Data inserted successfully!';
                    } else {
                        echo 'Error in inserting data!';
                    }
                    $stmt->close();
                    $conn->close();
                }
            ?>
        </form>
    </body>
</html>
