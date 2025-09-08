<html>
    <head>
        <meta charset="UTF-8">
        <title>Login Page</title>
    </head>
    <body>
        <?php
        session_start();
        if (!isset($_SESSION['username'])) {
            header("Location: login.php");
        }
        //echo 'WELCOME DEAR, ', $_SESSION['username'];

        //session_start();
        if(isset($_SESSION['salary'])) {
            $name = $_SESSION['name'];
            $DA = $_SESSION['salary'] * 125 / 100;
            $MA = $_SESSION['salary'] * 10 / 100;
            $PF = $_SESSION['salary'] * 13 / 100;
            $Tax = $_SESSION['salary'] * 10 / 100;
            $GrossSalary = $_SESSION['salary'] + 0 + $DA + $MA;
            $NetSalary = $GrossSalary - $PF - $Tax;
        } else {
            echo "we've detected you've came here without submitting the response on last page. So all values will be Zero";
            $DA = 0;
            $MA = 0;
            $PF = 0;
            $Tax = 0;
            $NetSalary = 0;
            $salary = 0;
            $name = 'null';
        }
        ?>
        <form method="post" action="">
            <table cellpadding='20px' border='1px'>
                <tr>
                    <th>
                        Name : 
                    </th>
                    <td>
                        <?php echo $name; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        DA : 
                    </th>
                    <td>
                        <?php echo $DA; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        MA : 
                    </th>
                    <td>
                        <?php echo $MA; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        PF : 
                    </th>
                    <td>
                        <?php echo $PF; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        Tax : 
                    </th>
                    <td>
                        <?php echo $Tax; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        Net Salary : 
                    </th>
                    <td>
                        <?php echo $NetSalary; ?>
                    </td>
                </tr>
            </table>
        </form>
        <a href='EmployeeInfo.php'>Click here to go to EmployeeInfo.php</a>
    </body>
</html>

