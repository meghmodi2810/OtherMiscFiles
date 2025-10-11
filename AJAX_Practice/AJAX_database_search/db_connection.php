<?php

$conn = new mysqli("localhost", "root", "6640", "db_selfcreation");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
