<?php
if(isset($_GET['return_page']))
    $page=$_GET['return_page'];
else
    $page='index.php';
if(!isset($_GET['error']))
    {
    header ("Location: $page");
    exit;
    }
$error=$_GET['error'];
?>
<html>
<head>
<title>ERROR</title>
</head>
<body>
<p style="color:red"><?=$error?></p>
<p><a href="<?=$page?>">Return to the last page.</a></p>
</body>
</html>
