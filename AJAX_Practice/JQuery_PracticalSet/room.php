<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        include 'connection.php';
        
        if(isset($_POST['input']))
        {
            $input=$_POST['input'];
            
            $searchbyroom="select
              f.feeid,
              f.studentid,
              h.studentname,
              h.roomtype,
              f.monthlyfee,
              f.duration,
              f.totalfees
              from feemaster f 
              join hostelmaster h 
              on f.studentid=h.studentid 
              where h.roomtype like '%{$input}%'";
            
            $roomquery= mysqli_query($connect,$searchbyroom);
            if(mysqli_num_rows($roomquery)>0)
            { ?>
                <table border="1">
                    <thead>
                        <th>feeid</th>
                        <th>studentid</th>
                        <th>studentname</th>
                        <th>roomtype</th>
                        <th>monthlyfee</th>
                        <th>duration</th>
                        <th>totalfees</th>
                    </thead>    
                    <tbody>
                       <?php while($r=mysqli_fetch_assoc($roomquery))
                       {?>
                           <tr>
                                <td><?php echo $r['feeid'] ?></td>
                                <td><?php echo $r['studentid'] ?></td>
                                <td><?php echo $r['studentname'] ?></td>
                                <td><?php echo $r['roomtype'] ?></td>
                                <td><?php echo $r['monthlyfee'] ?></td>
                                <td><?php echo $r['duration'] ?></td>
                                <td><?php echo $r['totalfees'] ?></td>
                                
                           </tr>    
                       <?php } ?>
                    </tbody>    
                </table>
         <?php   } ?>
            
           
       <?php } ?>
    </body>
</html>
