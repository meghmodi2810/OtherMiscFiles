<?php

session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
}
include_once 'base.php';

if (isset($_SESSION['FinalPrice'])) {
    echo "Name : " . $_SESSION['Name'] . "<BR>";

    echo "Bus Type : " . $_SESSION['BusType'] . "<BR>";

    echo "Number of tickets : " . $_SESSION['NumberOfTicket'] . "<BR>";

    echo "Facilities claimed : " . $_SESSION['Facilities'] . "<BR>";

    echo "Final Price : " . $_SESSION['FinalPrice'] . "<BR>";
} else {
    echo "No booking done!";
}