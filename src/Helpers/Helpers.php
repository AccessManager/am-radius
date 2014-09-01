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
	$result = [];
	foreach(explode(' ', $output) as $keyvalue) {
		if(strpos($keyvalue,'=') ) {
			list($k,$v) = explode('=',$keyvalue);
			$result[trim($k)] = trim($v,'"');
		}
	}
	return $result;
}