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
            include 'connector.php';
            include 'base.html';
        ?>
        <br><br>
        <fieldset style="height: 50%; width: 50%; margin: 0 auto;">
            <legend>Insert Vehicles Entries</legend>
        <form action="" method="post">
            <table border="1" align="center" cellpadding="10px" style="font-size: 22px;">
                <tr>
                    <td>
                        <label for="lblOwnerName">Owner Name</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="text" name="txtOwnername" id="txtOwnername" pattern="[A-Za-z]+" style="height: 30px; width: 200px;" title="Enter alphabets only!" placeholder="Enter your owner name here...">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblVehicleNumberPlate">Vehicle Number Plate</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="text" name="txtVehicleNumberPlate" id="txtVehicleNumberPlate" style="height: 30px; width: 200px;" placeholder="Enter your Vehicle Number here...">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblVehicleModel">Vehicle Model</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="text" name="txtVehicleModel" id="txtVehicleModel" style="height: 30px; width: 200px;" placeholder="Enter your Vehicle Model here...">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblVehicleType">Vehicle Type</label>
                    </td>
                    <td>:</td>
                    <td>
                        <select name="txtVehicleType" style="height: 30px; width: 200px;">
                            <option value="" disabled selected>--SELECT--</option>
                            <option value="Sedan">Sedan</option>
                            <option value="Coupe">Coupe</option>
                            <option value="SUV">SUV</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="MiniVan">Mini Van</option>
                            <option value="SportsCar">Sports Car</option>
                            <option value="Limousine">Limousine</option>
                            <option value="Other">Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="center">
                        <input type="submit" name="btnInsert" value="Insert">
                    </td>
                </tr>
            </table>
        </form>
        </fieldset>
        <?php
            if(isset($_POST['btnInsert'])) {
                
            }
        ?>
    </body>
</html>
