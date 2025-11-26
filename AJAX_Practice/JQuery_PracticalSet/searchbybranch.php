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
            
            $searchbybranch=$query = "
        SELECT 
            l.lid, 
            l.accno, 
            a.customername, 
            a.branchtype,
            l.loanamount, 
            l.no_of_inst, 
            l.loantype, 
            l.netamount
        FROM loanmaster l
        JOIN accountmaster a ON l.accno = a.accno
        WHERE a.branchtype LIKE '%{$input}%'
    ";
                                                      
            $searchby= mysqli_query($connect, $searchbybranch);
            if(mysqli_num_rows($searchby)>0)
            { ?>
                <table border="1">
                      <thead>
                         <th>LID</th>
                        <th>ACCNO</th>
                        <th>customername</th>
                        <th>branchtype</th>
                        <th>LOAN AMOUNT</th>
                        <th>NO OF INST</th>
                        <th>LOAN TYPE</th>
                        <th>NET AMOUNT</th>
                      </thead>
                      <tbody>
                        <?php while($b= mysqli_fetch_assoc($searchby))
                        {?>
                            <tr>
                             <td><?php echo$b['lid']; ?></td>
                             <td><?php echo$b['accno']; ?></td>
                             <td><?php echo$b['customername']; ?></td>
                             <td><?php echo$b['branchtype']; ?></td>
                             <td><?php echo$b['loanamount']; ?></td>
                             <td><?php echo$b['no_of_inst']; ?></td>
                             <td><?php echo$b['loantype']; ?></td>
                             <td><?php echo$b['netamount']; ?></td>
                            </tr>   
                    <?php }?>

                          
                      </tbody>    
                </table>    
         <?php   } ?>
       <?php } ?>
        
    </body>
</html>
