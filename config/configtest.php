<?php
/*
 * SquirrelMail configtest script
 *
 * Copyright (c) 2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id $
 */

/************************************************************
 * NOTE: you do not need to change this script!             *
 * If it throws errors you need to adjust your config.      *
 ************************************************************/

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>SquirrelMail configtest</title>
</head>
<body>
<h1>SquirrelMail configtest</h1>

<p>This script will try to check some aspects of your SquirrelMail configuration
and point you to errors whereever it can find them. You need to go run <tt>conf.pl</tt>
in this directory first before you run this script.</p>

<?php

function do_err($str, $exit = TRUE) {
    echo '<p><font color="red"><b>ERROR:</b></font> ' .$str. "</p>\n";
    if($exit) {
         echo '</body></html>';
         exit;
    }
}

$IND = str_repeat('&nbsp;',4);

ob_implicit_flush();
define('SM_PATH', '../');

include(SM_PATH . 'config/config.php');
include(SM_PATH . 'functions/strings.php');

$included = array_map('basename', get_included_files() );
if(!in_array('config.php', $included)) {
    if(!file_exists(SM_PATH . 'config/config.php')) {
        do_err('Config file '.SM_PATH . 'config/config.php does not exist!<br />'.
               'You need to run <tt>conf.pl</tt> first.');
    }
    do_err('Could not read '.SM_PATH.'config/config.php! Check file permissions.');
}
if(!in_array('strings.php', $included)) {
    do_err('Could not include '.SM_PATH.'functions/strings.php!<br />'.
           'Check permissions on that file.');
}

/* checking PHP specs */

echo '<p>SquirrelMail version: '.$version.'<br />'.
     'Config file version: '.$config_version . '<br />'.
     'Config file last modified: '.date ('d F Y H:i:s', filemtime(SM_PATH . 'config/config.php')).'</p>';

echo "Checking PHP configuration...<br />\n";

if(!check_php_version(4,0,6)) {
    do_err('Insufficient PHP version: '. PHP_VERSION . '! Minimum required: 4.0.6');
}

echo $IND . 'PHP version '.PHP_VERSION.' OK.<br />';

$php_exts = array('session','pcre');
$diff = array_diff($php_exts, get_loaded_extensions());
if(count($diff)) {
    do_err('Required PHP extensions missing: '.implode(', ',$diff) );
}

echo $IND . 'PHP extensions OK.<br />';


/* checking paths */

echo "Checking paths...<br />\n";

if(!file_exists($data_dir)) {
    do_err("Data dir ($data_dir) does not exist!");
} 
if(!is_dir($data_dir)) {
    do_err("Data dir ($data_dir) is not a directory!");
} 
if(!is_readable($data_dir)) {
    do_err("I cannot read from data dir ($data_dir)!");
} 
if(!is_writable($data_dir)) {
    do_err("I cannot write to data dir ($data_dir)!");
}

// todo_ornot: actually write something and read it back.
echo $IND . "Data dir OK.<br />\n";


if($data_dir == $attachment_dir) {
    echo $IND . "Attachment dir is the same as data dir.<br />\n";
} else {
    if(!file_exists($attachment_dir)) {
        do_err("Attachment dir ($attachment_dir) does not exist!");
    } 
    if (!is_dir($attachment_dir)) {
        do_err("Attachment dir ($attachment_dir) is not a directory!");
    } 
    if (!is_readable($attachment_dir)) {
        do_err("I cannot read from attachment dir ($attachment_dir)!");
    } 
    if (!is_writable($attachment_dir)) {
        do_err("I cannot write to attachment dir ($attachment_dir)!");
    }
    echo $IND . "Attachment dir OK.<br />\n";
}


/* check plugins and themes */

foreach($plugins as $plugin) {
    if(!file_exists(SM_PATH .'plugins/'.$plugin)) {
        do_err('You have enabled the <i>'.$plugin.'</i> plugin but I cannot find it.', FALSE);
    } elseif (!is_readable(SM_PATH .'plugins/'.$plugin.'/setup.php')) {
        do_err('You have enabled the <i>'.$plugin.'</i> plugin but I cannot read its setup.php file.', FALSE);
    }
}

echo $IND . "Plugins OK.<br />\n";

foreach($theme as $thm) {
    if(!file_exists($thm['PATH'])) {
        do_err('You have enabled the <i>'.$thm['NAME'].'</i> theme but I cannot find it ('.$thm['PATH'].').', FALSE);
    } elseif(!is_readable($thm['PATH'])) {
        do_err('You have enabled the <i>'.$thm['NAME'].'</i> theme but I cannot read it ('.$thm['PATH'].').', FALSE);
    }
}

echo $IND . "Themes OK.<br />\n";


/* check outgoing mail */

if($use_smtp_tls || $use_imap_tls) {
    if(!check_php_version(4,3,0)) {
        do_err('You need at least PHP 4.3.0 for SMTP/IMAP TLS!');
    }
    if(!extension_loaded('openssl')) {
        do_err('You need the openssl PHP extension to use SMTP/IMAP TLS!');
    }
}

echo "Checking outgoing mail service....<br />\n";

if($useSendmail) {
    // is_executable also checks for existance, but we want to be as precise as possible with the errors
    if(!file_exists($sendmail_path)) {
        do_err("Location of sendmail program incorrect ($sendmail_path)!");
    } 
    if(!is_executable($sendmail_path)) {
        do_err("I cannot execute the sendmail program ($sendmail_path)!");
    }

    echo $IND . "sendmail OK<br />\n";
} else {
    $stream = fsockopen( ($use_smtp_tls?'tls://':'').$smtpServerAddress, $smtpPort,
                        $errorNumber, $errorString);
    if(!$stream) {
        do_err("Error connecting to SMTP server \"$smtpServerAddress:$smtpPort\".".
            "Server error: ($errorNumber) $errorString");
    }

    // check for SMTP code; should be 2xx to allow us access
    $smtpline = fgets($stream, 1024);
    if(((int) $smtpline{0}) > 3) {
        do_err("Error connecting to SMTP server. Server error: ".$smtpline);
    }

    fputs($stream, 'QUIT');
    fclose($stream);
    echo $IND . 'SMTP server OK (<tt><small>'.trim($smtpline)."</small></tt>)<br />\n";

    /* POP before SMTP */
    if($pop_before_smtp) {
        $stream = fsockopen($smtpServerAddress, 110, $err_no, $err_str);
        if (!$stream) {
            do_err("Error connecting to POP Server ($smtpServerAddress:110)"
                  . " $err_no : $err_str");
        }

        $tmp = fgets($stream, 1024);
        if (substr($tmp, 0, 3) != '+OK') {
            do_err("Error connecting to POP Server ($smtpServerAddress:110)"
                  . ' '.$tmp);
        }
        fputs($stream, 'QUIT');
        fclose($stream);
        echo $IND . "POP-before-SMTP OK.<br />\n";
    }
}

echo "Checking IMAP service....<br />\n";

$stream = fsockopen( ($use_imap_tls?'tls://':'').$imapServerAddress, $imapPort,
                       $errorNumber, $errorString);
if(!$stream) {
    do_err("Error connecting to SMTP server \"$smtpServerAddress:$smtpPort\".".
        "Server error: ($errorNumber) $errorString");
}

$imapline = fgets($stream, 1024);
if(substr($imapline, 0,4) != '* OK') {
   do_err('Error connecting to IMAP server. Server error: '.$imapline);
}

fputs($stream, '001 LOGOUT');
fclose($stream);

echo $IND . 'IMAP server OK (<tt><small>'.trim($imapline)."</small></tt>)<br />\n";

// other possible checks:
// ? prefs/abook DSN
// ? locale/gettext
// ? actually start a session to see if it works
// ? ...
?>

<p>Congratulations, your SquirrelMail setup looks fine to me!</p>

<p><a href="<?php echo SM_PATH; ?>src/login.php">Login now</a></p>

</body>
</html>
