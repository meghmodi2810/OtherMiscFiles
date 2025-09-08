<form method="post">
    <input type='Submit' name='btnLogout' value='Logout'>
</form>

<?php
    session_start();
    if(!isset($_SESSION['username'])) {
        header('Location: index.php');
    }
    echo 'WELCOME TO HOMEPAGE! DEAR, ', $_SESSION['username'];
    if(isset($_POST['btnLogout'])) {
        session_destroy();
        header('Location: index.php');
    }
?>