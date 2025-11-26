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
    <body>
        <?php
        session_start();
        include_once 'base.php';
        if (!isset($_SESSION['username'])) {
            header('Location: index.php');
        } else {
            echo '<h1 align="center">WELCOME, ' . $_SESSION['username'] . "</h1>";
        }
        ?>
        <form action="" method="POST">
            <label for="lblName">
                Name :
            </label>
            <input type="text" name="txtName" id="txtName" placeholder="Enter your full name.." required>
            <BR><BR>
            <label for="lblPhoneNumber">
                Phone number :
            </label>
            <input type="tel" name="txtContact" id="txtContact" placeholder="Enter your phone number.." required>
            <BR><BR>
            <label for="lblEmail">
                Email :
            </label>
            <input type="email" name="txtEmail" id="txtEmail" placeholder="Enter your email.." required>
            <BR><BR>
            <label for="lblSource">
                Source city :
            </label>
            <input type="text" name="txtSource" id="txtSource" placeholder="Enter your city here.." required>
            <BR><BR>
            <label for="lblDestination">
                Destination city :
            </label>
            <select name="txtDestination" id="txtDestination" required>
                <option value="" selected disabled>--SELECT--</option>
                <option>Surat</option>
                <option>Vapi</option>
                <option>Vadodhara</option>
                <option>Valsad</option>
                <option>Daman</option>
                <option>Rajkot</option>
                <option>Mehsana</option>
                <option>Ahmedabad</option>
                <option>Modhera</option>
            </select>
            <BR><BR>
            <label for="lblBusType">
                Bus Type :
            </label>
            <select name="txtBusType" id="txtBusType" required>
                <option value="" selected disabled>--SELECT--</option>
                <option>Sleeper</option>
                <option>AC Sleeper</option>
                <option>Volvo</option>
            </select>
            <BR><BR>
            <label for="lblNumberOfTicker">
                Number Of Ticker :
            </label>
            <input type="number" name="txtNumber" id="txtNumber" placeholder="Enter your number of tickets.." size="30" required>
            <BR><BR>
            <label for="lblFacilities">
                Facilities :
            </label>
            <input type="checkbox" name="txtFacilities[]" id="txtFacilities" value="Blanket">
            Blanket
            <input type="checkbox" name="txtFacilities[]" id="txtFacilities" value="Snacks">
            Snacks
            <input type="checkbox" name="txtFacilities[]" id="txtFacilities" value="WiFi">
            WiFi
            <input type="checkbox" name="txtFacilities[]" id="txtFacilities" value="WaterBottle">
            Water Bottle
            <BR><BR>
            <input type="submit" name="btnBook" id="btnBook" value="Book">
        </form>
        <?php
        if (isset($_POST['btnBook'])) {
            $_SESSION['Name'] = $_POST['txtName'];
            $_SESSION['PhoneNumber'] = $_POST['txtContact'];
            $_SESSION['Email'] = $_POST['txtEmail'];
            $_SESSION['Source'] = $_POST['txtSource'];
            $_SESSION['Destination'] = $_POST['txtDestination'];
            $_SESSION['BusType'] = $_POST['txtBusType'];
            $_SESSION['NumberOfTicket'] = $_POST['txtNumber'];
            $arr = $_POST['txtFacilities'];

            $_SESSION['Facilities'] = implode(",", $arr);

            $price = 0;
            foreach ($arr as $value) {
                if ($value == 'Blanket') {
                    $price += 100;
                } elseif ($value == 'Snacks') {
                    $price += 150;
                } elseif ($value == 'WiFi') {
                    $price += 50;
                } elseif ($value == 'WaterBottle') {
                    $price += 30;
                }
            }
            
            if($_SESSION['BusType'] == 'Sleeper') {
                $price += 800;
            } elseif($_SESSION['BusType'] == 'AC Sleeper') {
                $price += 1200;
            } elseif($_SESSION['BusType'] == 'Volvo') {
                $price += 2000;
            }
            
            $_SESSION['FinalPrice'] = $price * $_SESSION['NumberOfTicket'];
            
            echo "Booking confirmed!";
        }
        ?>
    </body>
</html>
