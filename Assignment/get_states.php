<?php
include 'connect.php';

if (isset($_GET['country_id'])) {
    $country_id = intval($_GET['country_id']);
    $query = $conn->prepare("SELECT * FROM states WHERE country_id = ?");
    $query->bind_param("i", $country_id);
    $query->execute();
    $result = $query->get_result();

    echo "<option value=''>-- Select State --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
    }
}
