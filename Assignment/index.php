<?php
include 'connect.php';
$countries = $conn->query("SELECT * FROM countries");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Dynamic Dropdown</title>
    </head>
    <body>
        <h2 align="center">Dynamic Dropdown</h2>

        <form action="" method="POST">
            <label for="country">Select Country:</label>
            <select id="country" onchange="loadStates(this.value)" style="height: 30px; width: 200px" >
                <option value="" disabled selected>-- Select Country --</option>
                <?php while ($row = $countries->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                <?php endwhile; ?>
            </select>

            <br><br>

            <label for="state">Select State:</label>
            <select id="state" onchange="loadCities(this.value)" style="height: 30px; width: 200px">
                <option value="" disabled selected>-- Select State --</option>
            </select>

            <br><br>

            <label for="city">Select city:</label>
            <select id="city" style="height: 30px; width: 200px">
                <option value=""  disabled selected>-- Select city --</option>
            </select>

            <br><br>
            
            <input type="submit" value="Submit">
        </form>
        <br><br>

        <script>
            function loadStates(countryId) {
                if (countryId === "") {
                    document.getElementById("state").innerHTML = "<option value=''>-- Select State --</option>";
                    return;
                }

                var xhr = new XMLHttpRequest();
                xhr.open("GET", "get_states.php?country_id=" + countryId, true);

                xhr.onload = function () {
                    if (xhr.status === 200 && xhr.readyState === 4) {
                        document.getElementById("state").innerHTML = this.responseText;
                    }
                };

                xhr.send();
            }

            function loadCities(stateId) {
                if (stateId === "") {
                    document.getElementById("city").innerHTML = "<option value=''>-- Select city --</option>";
                    return;
                }

                var xhr = new XMLHttpRequest();
                xhr.open("GET", "get_cities.php?state_id=" + stateId, true);
                xhr.onload = function () {
                    if (xhr.status === 200 && xhr.readyState === 4) {
                        document.getElementById("city").innerHTML = this.responseText;
                    }
                };

                xhr.send();
            }
        </script>
    </body>
</html>