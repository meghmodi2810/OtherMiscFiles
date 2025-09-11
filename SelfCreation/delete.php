<?php
    include 'connector.php';
    include 'base.php';
    $stmt = $conn->prepare("DELETE FROM parking_sessions WHERE session_id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    
    if ($stmt->execute()) {
        echo "Record deleted successfully!";
        header("Location: ParkingSession.php"); // redirect back
        exit;
    } else {
        echo "Error deleting record: " . $stmt->error;
    }
