<?php 
$time = time(); 
$file = fopen(__DIR__."/write_time_in_txt.txt", "w");
fwrite($file, $time);
fclose($file);
echo $time; 
?>