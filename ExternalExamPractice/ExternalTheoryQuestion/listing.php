<?php
require_once 'db_connect.php';
include_once 'base.php';

session_start();
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
}
$stmt = $conn->prepare("SELECT * FROM restaurant");
$stmt->execute();

$res = $stmt->get_result();
?>
<form action="" method="POST">
    Search box : 
    <input type="text" name="txtSearch" id="txtSearch" onkeyup="loaddata(this.value)">
</form>
<div id="txtLoad">
</div>

<script>
    function loaddata(str) {
        xhr = new XMLHttpRequest();
        xhr.open("get", "getData.php?str="+str, true);
        xhr.send();
        
        xhr.onreadystatechange = function () {
            if(xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById('txtLoad').innerHTML = xhr.response;
            }
        };
    }
    
    window.onload = function () {
        loaddata("");
    };
</script>