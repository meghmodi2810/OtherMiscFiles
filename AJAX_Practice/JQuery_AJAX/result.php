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
        include 'config.php';
        echo "<br>";
        
        if(isset($_POST['input']))
        {
            $input=$_POST['input'];
            
            $query="Select * from live_search where id like '%{$input}%'
                                              or name like '%{$input}%'
                                              or email like '%{$input}%'
                                              or age like '%{$input}%'
                                              or occupation like '%{$input}%'";
            
            $re=mysqli_query($connect,$query);
            
            if(mysqli_num_rows($re)>0)
            {
              ?>
              <table align="center" border="2">
                  <thead>
                      <th>ID</th>
                      <th>NAME</th>    
                      <th>EMAIL</th>
                      <th>AGE</th>
                      <th>OCCUPATION</th>
                  </thead>
                   <tbody>
                       <?php
                        while($row= mysqli_fetch_assoc($re)){
                                $id=$row['id'];
                                $name=$row['name'];
                                $email=$row['email'];
                                $age=$row['age'];
                                $occupation=$row['occupation'];
                        ?>
                       <tr>
                           <td><?php echo $id; ?></td>
                           <td><?php echo $name; ?></td>
                            <td><?php echo $email; ?></td>
                             <td><?php echo $age; ?></td>
                              <td><?php echo $occupation; ?></td>
                           

                       </tr>
                       <?php
                        }
                       ?>
                   </tbody>
              </table>
              <?php
            } else {
                echo "no data found";
            }
        }
        // put your code here
        ?>
    </body>
</html>
