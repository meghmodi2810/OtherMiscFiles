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
             
             $searchall="select
              f.feeid,
              f.studentid,
              h.studentname,
              h.roomtype,
              f.monthlyfee,
              f.duration,
              f.totalfees
              from feemaster f
              join hostelmaster h
              on f.studentid = h.studentid
              where f.feeid like '%{$input}%'
              or f.studentid like '%{$input}%'
              or h.studentname like '%{$input}%'
              or h.roomtype like '%{$input}%'
              or f.monthlyfee like '%{$input}%'
              or f.duration like '%{$input}%'
              or f.totalfees like '%{$input}%'";
             
             $searchbyall= mysqli_query($connect, $searchall);
             if(mysqli_num_rows($searchbyall)>0)
             {?>
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
                    <?php 
                    while($l= mysqli_fetch_assoc($searchbyall))
                    {?>
                        <tr>
                            <td><?php echo $l['feeid'] ?></td>
                            <td><?php echo $l['studentid'] ?></td>
                            <td><?php echo $l['studentname'] ?></td>
                            <td><?php echo $l['roomtype'] ?></td>
                            <td><?php echo $l['monthlyfee'] ?></td>
                            <td><?php echo $l['duration'] ?></td>
                            <td><?php echo $l['totalfees'] ?></td>
                        </tr>   
                    <?php } ?>
                    </tbody>
               </table>    
            <?php } ?>
        <?php } ?>
    </body>
</html>
