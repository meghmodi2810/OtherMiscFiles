<?php
include 'connect.php';

if(isset($_GET['state_id'])) {
    $state_id = intval($_GET['state_id']);
    $query = $conn->prepare("SELECT * FROM cities WHERE state_id = ?");
    $query->bind_param("i", $state_id);
    $query->execute();
    $result = $query->get_result();

    echo "<option value=''>-- Select city --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
    }
}
