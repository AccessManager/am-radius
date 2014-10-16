<?php

$date = new DateTime(date('Y-m-d H:i:s'));


$date->modify('+1 Months');
echo $date->format('d M Y');
