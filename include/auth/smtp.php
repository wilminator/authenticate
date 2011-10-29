<?php
function email_fread($handle)
    {
    echo "'".rawurlencode("\r\n")."'<br>";
    $retval='';
    do {
       	$retval.=fread($handle,1024);
       	$lines=explode("\r\n",$retval);
        }while(end($lines)!='' || prev($lines)===false || substr(current($lines),3,1)!==' ');
    return $retval;
    }

function email_error_trap($handle,$expected_code)
    {
    $response=email_fread($handle);
    $lines=explode($response,"\r\n");
    $error=false;
    if(substr(reset(array_slice(explode("\r\n",$response),-2,1)),0,4)!="$expected_code ")
        { fclose($handle); $error=true; }
    return array($error,$response,$lines);
    }

function email($host,$user,$pass,$from_name,$from,$to_name,$to,$subject,$message)
    {
    #Create the header
    $header=<<<EOD
Date: $date
From: $from_name <$from>
User-Agent: AUTHENTICATION mailer
MIME-Version: 1.0
To: $to_name <$to>
Subject: $subject
Content-Type: text/plain; charset=us-ascii; format=flowed
Content-Transfer-Encoding: 7bit


EOD;
    if($host)
        $handle=fsockopen($host,25,$errno,$error,30);
    else
        $handle=false;
    if($handle===false)
        {
        $result=@mail($to,$subject,$message,$headers);
        if ($result==true)
            {
            //echo "Used simple mail handler.";
            //exit;
            return false;
            }
        return "Socket $errno: $error";
        }
    #Connection MOTD
    list($error,$response,$lines)=email_error_trap($handle,'220');
    if($error) return $response;

    #EHLO
    fwrite($handle,"EHLO $_SERVER[SERVER_NAME]\r\n");
    #EHLO response and command verification
    list($error,$response,$lines)=email_error_trap($handle,'250');
    if($error) return $response;

    #AUTHENTICATE
    fwrite($handle,"AUTH PLAIN ".base64_encode("$user\0$user\0$pass")."\r\n");
    #AUTHENTICATION RESPONSE
    list($error,$response,$lines)=email_error_trap($handle,'235');
    if($error) return $response;

    #MAIL
    fwrite($handle,"MAIL FROM:<$from>\r\n");
    #MAIL RESPONSE
    list($error,$response,$lines)=email_error_trap($handle,'250');
    if($error) return $response;

    #RCPT
    fwrite($handle,"RCPT TO:<$to>\r\n");
    #RCPT RESPONSE
    list($error,$response,$lines)=email_error_trap($handle,'250');
    if($error) return $response;

    #DATA
    fwrite($handle,"DATA\r\n");
    #DATA RESPONSE
    list($error,$response,$lines)=email_error_trap($handle,'354');
    if($error) return $response;

    #ADD MESSAGE HEADER
    $date=date('r');
    $message=$header.$message;
    echo $message;
    #SEND MESSAGE
    if(substr($message,-2)==="\r\n")
        $message=substr($message,0,-2);
    foreach(explode("\n",$message) as $line)
        {
        if(substr($line,0,1)=='.')
            $line='.'.$line;
        if(substr($line,-1)=="\r")
            $line=substr($line,0,-1);
        fwrite($handle,"$line\r\n");
        }
    fwrite($handle,".\r\n");
    #FINAL DATA RESPONSE
    list($error,$response,$lines)=email_error_trap($handle,'250');
    if($error) return $response;

    #DATA
    fwrite($handle,"QUIT\r\n");
    #DATA RESPONSE
    list($error,$response,$lines)=email_error_trap($handle,'221');
    if($error) return $response;

    #Close the socket
    fclose($handle);
    #Return no errors.
    return false;
    }
?>
