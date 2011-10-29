<?php
//AUTHENTICATION utility functions
function generate_traceback($skip_depth=2)
    {
    //Get stack trace.
    $error_data=debug_backtrace();
    //eliminate skip_depth functions off the traceback.
    for($count=0;$count<$skip_depth;$count++)
        array_shift($error_data);

    //Create error dump.
    $error_dump='';
    //Create args list.
    foreach($error_data as $line)
        {
        $args=array();
        if (isset($line['args'])) foreach($line['args'] as $arg)
            {
            $type=gettype($arg);
            switch($type)
                {
                case 'boolean':
                    if($arg===true)
                        $args[]='boolean (TRUE)';
                    else
                        $args[]='boolean (FALSE)';
                    break;
                case 'integer':
                case 'double':
                    $args[]="$type ($arg)";
                    break;
                case 'string':
                    $args[]="$type \"$arg\"";
                    break;
                case 'resource':
                case 'array':
                    $args[]="$type";
                    break;
                case 'object':
                    $args[]="$type (".get_class($arg).")";
                    break;
                case 'NULL':
                    $args[]='NULL';
                    break;
                }
            }
        $args=implode(',',$args);
        $error_dump.="$line[file]:$line[line] $line[function]($args)\r\n";
        }
    $error_dump.="\r\n";
    return $error_dump;
    }
?>
