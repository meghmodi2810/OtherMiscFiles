<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>fee for hostel</title>
    </head>
    <body>
        <form method="post">
            <h1>Search by Room type</h1>
            <input type="text" id="searchRoom" placeholder="Search by Room type">
            <div id="roomresult"></div>
            <br>
            <h1>Search live</h1>
            <input type="text" id="searchlive" placeholder="Search by Room type">
            <div id="liveresult"></div>
            <br>
            
            fee id:<input type="number" name="fid" placeholder="Enter your fee id" required><br>
            student id:<input type="number" name="sid" placeholder="Enter your student id" required><br>
            monthly fee:<input type="number" name="mfee" placeholder="Enter monthly fee" required><br>
            duration:<input type="text" name="duration" placeholder="Enter duration" required><br>
            <input type="submit" name="submit" value="submit">
        </form>
        <?php
        
        include 'connection.php';        
        if(isset($_POST['submit']))
        {
            $fid=$_POST['fid'];
            $sid=$_POST['sid'];
            $mfee=$_POST['mfee'];
            $duration=$_POST['duration'];
            
            $totalfee=$mfee * $duration;
            
            $insert="INSERT INTO feemaster(feeid,studentid,monthlyfee,duration,totalfees) VALUES 
                    ($fid,$sid,$mfee,'$duration',$totalfee)";
            
            $insertqueary= mysqli_query($connect, $insert);
        }
        ?>
        <script src="jquery-3.7.1.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
             $('#searchRoom').keyup(function(){
                var input =$(this).val();
                if(input !="")
                {
                    $.ajax({
                       url:"room.php",
                       method:"POST",
                       data:{input:input},
                       
                       success:function(data)
                       {
                           $('#roomresult').html(data);
                       }
                    });
                }else
                {
                    $('#roomresult').html("");
                }
             });  
            });
        </script>    
        <script type="text/javascript">
            $(document).ready(function(){
              $('#searchlive').keyup(function(){
                 var input=$(this).val();
                 if(input !="")
                 {
                     $.ajax({
                         url:"live.php",
                         method:"POST",
                         data:{input:input},
                         
                         success:function(data)
                         {
                             $('#liveresult').html(data);
                         }
                     });
                 }else
                 {
                     $('#liveresult').html("");
                 }
              });  
            });
        </script>
    </body>
</html>
