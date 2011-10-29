<?php
#This controls the logging threshold.  Lower numbers record more errors.
define ('AUTH_LOG_THRESHOLD',6);

#This controls how long an account may remain inactive and logged
#in before being logged out.  If is '', then the account is never logged out.
define ('AUTH_TIMEOUT','1 hour');

#This controls the maximum times each user account
#may be concurrently logged in.
define ('AUTH_MAX_LOGIN_COUNT',3);

#This controls who long an account may not be logged into before being
#automatically deleted.  If is '', then the account is never deleted.
define ('AUTH_MAX_INACTIVE','365 days');

//New account email
#This is the subject to the email for new accounts.
define ('AUTH_NEW_ACCOUNT_SUBJECT',"Your Wilminator.com Account Confirmation");

#This is the intro to the email for new accounts.
define ('AUTH_NEW_ACCOUNT_INTRO',"
Thank you for your interest in Wilminator.com!  You are only a few moments away from playing and helping develop what we are hoping to be one of the best Online Multi-Player RPGs around!
");

#This is the ending to the email for new accounts.
define ('AUTH_NEW_ACCOUNT_OUTRO',"
Welcome aboard!
The staff of Wilminator.com
");

//Change email address email
#This is the subject to the email sent to the new email
#address for confirmation.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_NEW_SUBJECT',"Your Wilminator.com Account Confirmation");

#This is the intro to the email for changing emails.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_NEW_INTRO',"
Dear valued member,
This email is being sent to you in order to confirm your account's change to this email address.
");

#This is the ending to the email for changing emails.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_NEW_OUTRO',"
If this isn't what you want to do, you only need to log into your account without clicking on the above link.  You may also contact us with any further concerns you may have.

Thank you,
The staff of Wilminator.com
");

#This is the subject to the email sent to the old email
#address to warn about information changes.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_OLD_SUBJECT',"Change Of Your Wilminator.com Account Information");

#This is the email for changing emails sent to the old account.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_OLD',"
Dear valued member,
This email is being sent to you in because a request has been submitted to change your account email address.

If this is what you want to happen, an email has been sent to the new email address that has been specified.  Please follow the instruction in that email.

If you do not want this to happen, please log into your account and verify that the email address has not been changed.  If it has been changed or you have any further questions, please email us at help@wilminator.com.

Thank you,
The staff of Wilminator.com
");

//Cancel change email address email
#This is the subject to the email for cancelled email address changes.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_CANCEL_SUBJECT',"Canceled Wilminator.com Account Email Change");

#This is the email sent when an email change is canceled.
define ('AUTH_ACCOUNT_CHANGE_EMAIL_CANCEL',"
Dear valued member,
This email is being sent to you because a submitted change to the email address attached to your account has been canceled.  If you have any questions, feel free to contact us.

Thank you,
The staff of Wilminator.com
");

//Reset account email
#This is the subject to the email for resetting passwords.
define ('AUTH_ACCOUNT_RESET_SUBJECT',"Your Wilminator.com Account Confirmation");

#This is the intro to the email for resetting an account.
define ('AUTH_ACCOUNT_RESET_INTRO',"
Dear valued member,
This email is being sent to you in order to confirm resetting your account's password.
");

#This is the ending to the email for resetting passwords.
define ('AUTH_ACCOUNT_RESET_OUTRO',"
If this isn't what you want to do, you only need to log into your account without clicking on the above link.  You may also contact us with any further concerns you may have.

Thank you,
The staff of Wilminator.com
");

#This is the subject to the email for resetting passwords.
define ('AUTH_ACCOUNT_RESET_DONE_SUBJECT',"Your Wilminator.com Account Update");

#This is the intro to the email when an account is reset.
define ('AUTH_ACCOUNT_RESET_DONE_INTRO',"
Dear valued member,
This email is being sent to you because your account's password has been reset.
");

#This is the ending to the email for changing emails.
define ('AUTH_ACCOUNT_RESET_DONE_OUTRO',"
Please log in and change your password to something you are more comfortable with.

Thank you,
The staff of Wilminator.com
");
?>
