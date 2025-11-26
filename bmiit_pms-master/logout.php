<?php
session_start();
session_unset();
session_destroy();

// ✅ Use PHP redirect only
header("Location: login.php");
exit();
?>
