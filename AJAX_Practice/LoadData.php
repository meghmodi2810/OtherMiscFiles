<?php
    $conn = new mysqli("localhost","root","6640","");
    
    $n =  $_GET['n'];
    
    $q = "select * from Student where sname like '$n%'";
    
    $query = $conn->query($q);
    
    echo '<table>';
    while($r=$query->fetch_row())
    {
        echo '<tr>';
        echo '<td>';
            echo $r[0];
        echo '</td>';
        echo '<td>';
            echo $r[1];
        echo '</td>';
        echo '<td>';
            echo $r[2];
        echo '</td>';
        echo '<td>';
            echo "<a href='delete.php?id=$r[0]'>Delete</a>";
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
?>