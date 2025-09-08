<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        session_start();
        $mysqli = new mysqli("localhost", "root", "6640", "db_phpdatabase");

        if ($mysqli->connect_error) {
            die("Connection error! Please check your connection!");
        }
        ?>
        <form action="" method="post">
            <table>
                <tr>
                    <th>
                        <label for="lblEnrollment">EnrolmentNumber : </label>
                    </th>
                    <td>
                        <input type="number" name="numEnro" id="numEnro">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblFirstName">FirstName : </label>
                    </th>
                    <td>
                        <input type="text" name="txtFirstName" id="txtFirstName">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblLastName">LastName : </label>
                    </th>
                    <td>
                        <input type="text" name="txtLastName" id="txtLastName">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblContact">Contact No : </label>
                    </th>
                    <td>
                        <input type="tel" name="numContact" id="numContact" maxlength=13 minlength=10>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblGender">Age : </label>
                    </th>
                    <td>
                        <label for="lblMale">Male&nbsp;</label>
                        <input type="radio" name="radioGender" id="radioGender" value="M">
                        <label for="lblFemale">Female&nbsp;</label>
                        <input type="radio" name="radioGender" id="radioGender" value="F">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblHobby">Hobby : </label>
                    </th>
                    <td>
                        <label for="lblMale">Dancing&nbsp;</label>
                        <input type="checkbox" name="chkHobby[]" id="chkHobby" value="Dancing">
                        <label for="lblFemale">Swimming&nbsp;</label>
                        <input type="checkbox" name="chkHobby[]" id="chkHobby" value="Swimming">
                        <label for="lblTravel">Traveling&nbsp;</label>
                        <input type="checkbox" name="chkHobby[]" id="chkHobby" value="Traveling">
                        <label for="lblOther">Others&nbsp;</label>
                        <input type="checkbox" name="chkHobby[]" id="chkHobby" value="Others">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblCity">City : </label>
                    </th>
                    <td>
                        <select name="City">
                            <option selected disabled>--SELECT--</option>
                            <option>Surat</option>
                            <option>Ahmedabad</option>
                            <option>Vadodhara</option>
                            <option>Mehsana</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblPassword">Password : </label>
                    </th>
                    <td>
                        <input type="password" name="txtPassword" id="txtPassword">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblConfirmPassword">Type again - Password : </label>
                    </th>
                    <td>
                        <input type="password" name="txtConfirmPassword" id="txtConfirmPassword">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" value="Register" name="btnRegister">
                    </td>
                </tr>
                <tr>
                    <td>
                        Have account?&nbsp;
                        <a href="login.php">Login</a>
                    </td>
                </tr>
            </table>
        </form>
        <?php
        if (isset($_POST['btnRegister'])) {
            $enrolment = $_POST['numEnro'];
            $FirstName = $_POST['txtFirstName'];
            $LastName = $_POST['txtLastName'];
            $Contact = $_POST['numContact'];
            $Gender = $_POST['radioGender'];
            $str = $_POST['chkHobby'];
            $Hobby = implode(',', $str);
            $City = $_POST['City'];
            if($_POST['txtPassword'] != $_POST['txtConfirmPassword']) {
                die("Both password doesn't match!");
            }
            $password = $_POST['txtPassword'];
            
            $query = "INSERT INTO registrationTable VALUES($enrolment, '$FirstName', '$LastName', $Contact, '$Gender', '$Hobby', '$City', '$password')";
            $res = $mysqli->query($query);
            // $query;
            
            if($res){
                echo 'Successfully registered!';
                $_SESSION['Name'] = $FirstName." ".$LastName;
                header("Location: home.php");
            } else {
                echo 'Error while registering!', error_log($message);
            }
        }
        ?>
    </body>
</html>
