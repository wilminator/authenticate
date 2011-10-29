<?php

/**
 * web_objects.php
 * Programmable web objects
 * version 1.0.0
 * copyright Mike Wilmes 2005
 **/

function make_input($name,$objval,$attrs=null)
    {
    echo "<input name=\"$name\" value=\"{$objval}\"";
    if(is_array($attrs))
        foreach($attrs as $attr=>$value)
            echo " $attr=\"$value\"";
    echo ">\n";
    }

function make_textarea($name,$objval,$attrs=null)
    {
    echo "<textarea name=\"$name\"";
    if(is_array($attrs))
        foreach($attrs as $attr=>$value)
            echo " $attr=\"$value\"";
    echo ">{$objval}</textarea>";
    }

function make_select($name,$objval,&$source,$attrs=null)
    {
    echo "<select name=\"$name\"";
    if(is_array($attrs))
        foreach($attrs as $attr=>$value)
            echo " $attr=\"$value\"";
    echo ">\n";
    foreach($source as $value=>$tag)
        {
        $tag=htmlentities($tag);
        $value=htmlentities($value);
        echo "<option value=\"$value\"".($objval==$value?' selected':'').">$tag</option>\n";
        }
    echo "</select>\n";
    }
    
function make_checkbox($name,$checked,$attrs=null)
    {
    echo "<input type=\"checkbox\" name=\"$name\"";
    if($checked)
        echo " checked=\"checked\"";
    if(is_array($attrs))
        foreach($attrs as $attr=>$value)
            echo " $attr=\"$value\"";
    echo ">\n";
    }
    
function set_values_as_keys($array)
    {
    $retval=array();
    foreach($array as $value)
        $retval[$value]=$value;
    return $retval;
    }
?>
