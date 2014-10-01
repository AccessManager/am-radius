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


    function mikrotikRateLimit($object, $prefix = NULL)
    {
        $v = (array) $object;
        
        return         "{$v[$prefix.'max_up']}{$v[$prefix.'max_up_unit'][0]}/".
                       "{$v[$prefix.'max_down']}{$v[$prefix.'max_down_unit'][0]} ".
                       "{$v[$prefix.'max_up']}{$v[$prefix.'max_up_unit'][0]}/".
                       "{$v[$prefix.'max_down']}{$v[$prefix.'max_down_unit'][0]} ".
                       "{$v[$prefix.'max_up']}{$v[$prefix.'max_up_unit'][0]}/".
                       "{$v[$prefix.'max_down']}{$v[$prefix.'max_down_unit'][0]} ".
                       "1/1 1 ".
                       "{$v[$prefix.'min_up']}{$v[$prefix.'min_up_unit'][0]}/".
                       "{$v[$prefix.'min_down']}{$v[$prefix.'min_down_unit'][0]}";
    }

//end of file Helpers.php