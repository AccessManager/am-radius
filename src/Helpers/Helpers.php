<?php
// namespace AccessManager\Radius;

function reject($string)
{
    	echo "Reply-Message := \"$string\"";
    	exit(1);
}

function parseAttributes($z)
{
	$output = preg_replace("/\s+[=]\s+/",'=', $z);
	$output = preg_replace("/\s+/",' ',$output);
	$output = explode(' ',$output);
	$result = [];
    foreach($output as $pair) {
            if(strpos($pair,'=') ){
                    list($k,$v) = explode('=',$pair);
                    $result[$k] = trim($v,'"');
            }
    }
    return $result;
}

//end of file Helpers.php