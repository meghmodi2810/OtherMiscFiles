<?php
require 'db.php';
if (isset($_GET['val'])) {
    if ($_GET['val'] == "Patient") {
        ?>
        <form method='post' action='register_patient.php'>
            <h1 align="center">Register Patient</h1>
            Name:~<input type='text' name='name' required><br>
            Disease:~<input type='text' name='disease' required><br>
            City:~<select name='city'>
                <option value=''>--select city--</option>
                <option value='Surat'>Surat</option>
                <option value='Valsad'>Valsad</option>
                <option value='Vapi'>Vapi</option>
                <option value='Vav'>Vav</option>
                <option value='Bardoli'>Bardoli</option>
            </select><br>
            Email:~<input type="email" name='email' required><br>
            Password:~<input type='password' name='password' required><br>
            Confirm Password:~<input type='password' name='con-password' required><br>
            <input type='submit' name='register-patient' value="Register">
        </form>
        <?php
    } else if ($_GET['val'] == "Doctor") {
        ?>
        <form method='post' action="register_doctor.php">
            <h1 align='center'>Register Doctor</h1>
            Name:~<input type='text' name='name' required><br>
            Specialist:~<input type='text' name='specialist' required><br>
            Email:~<input type="email" name='email' required><br>
            Password:~<input type='password' name='password' required><br>
            Confirm Password:~<input type='password' name='con-password' required><br>
            <input type='submit' name='register-doctor' value="Register">
        </form>
        <?php
    } else {
        echo 'invalid choice';
    }
}