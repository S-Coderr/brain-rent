<?php
$c = file_get_contents('pages/dashboard-expert.php');
$c = str_replace("color:'#fff'", "color:#fff", $c);
file_put_contents('pages/dashboard-expert.php', $c);
echo "Fixed!";
?>
