#!/usr/bin/php
<?php
$l=array();
while($f = fgets(STDIN)){  $l[]=trim($f);}


//print_r($l);

$b=array_count_values($l);
arsort($b);

//print_r($b);

$c=1;
foreach ($b as $k => $x ) {

echo "$k : $x - ";

if($c >= 5 ) break;

$c++;
}


?>
