<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Apply for Loan</title>
</head>
<body>
    <form method="post">
        <h1>Search by Branch type</h1>
        <input type="text" name="type" id="stype" placeholder="Search by Branch type...">
        <div id="typesearch"></div>
        <br>
        <h1>live search</h1>
        <input type="text" name="type" id="live_search" placeholder="Search by...">
        <div id="livesearch"></div>
        <br>
        lid: <input type="number" name="lid" placeholder="Enter loan id" required><br>
        accno: <input type="number" name="accno" placeholder="Enter account number" required><br>
        loan amount: <input type="number" name="loanamount" placeholder="Enter loan amount" required><br>
        no-of-inst: <input type="number" name="no" placeholder="Enter no-of-inst" required><br>
        loan type: <input type="text" name="loantype" placeholder="Enter loan type" required><br>
        <input type="submit" name="submit" value="Submit">
    </form>

    <?php
    include 'connection.php'; // your connection file

    if(isset($_POST['submit']))
    {
        $lid = $_POST['lid'];
        $accno = $_POST['accno'];
        $loanamt = $_POST['loanamount'];
        $no = $_POST['no'];
        $loantype = $_POST['loantype'];

        $netamount = $loanamt * $no;

        // Insert into database
        $insert = "INSERT INTO loanmaster(lid, accno, loanamount, no_of_inst, loantype, netamount)
                   VALUES ($lid, $accno, $loanamt, $no, '$loantype', $netamount)";
        $insertquery = mysqli_query($connect, $insert);

        if(!$insertquery)
        {
            echo "Data not inserted: " . mysqli_error($connect);
        }
        else
        {
            echo "Loan added successfully!";
        } 
    }
    ?>
    <script src="jquery-3.7.1.min.js"></script>
    <script type="text/javascript">
       $(document).ready(function(){
           $('#stype').keyup(function(){
              var input=$(this).val();
              
              if(input !="")
              {
                  $.ajax({
                     url:"searchbybranch.php",
                     method:"POST",
                     data:{input:input},
                     
                     success:function(data)
                     {
                         $('#typesearch').html(data);
                     }
                  });
              }else
              {
                  $('#typesearch').html("");
              }
           });
       }); 
    </script>    
    
    <script type="text/javascript">
        $(document).ready(function(){
           $('#live_search').keyup(function(){
               var input =$(this).val();
               
               if(input !="")
               {
                   $.ajax({
                      url:"searchbylive.php",
                      method:"POST",
                      data:{input:input},
                      
                      success:function(data)
                      {
                          $('#livesearch').html(data);
                      }
                   });
               }else
               {
                   $('#livesearch').html("");
               }
           });
        });
    </script>    
</body>
</html>
