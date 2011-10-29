<?php
class AUTH_ERROR
    {
    var $error;
    var $msg;

    function AUTH_ERROR($error,$msg)
        {
        $this->error=$error;
        $this->msg=$msg;
        }

    function get_error()
        {
        return $this->error;
        }

    function get_msg()
        {
        return $this->msg;
        }
    }

function is_auth_error($value)
    {
    return (is_a($value,'AUTH_ERROR'));
    }
?>
