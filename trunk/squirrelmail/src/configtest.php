<?php

/**
 * SquirrelMail configtest script
 *
 * Copyright (c) 2003-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage config
 */

/************************************************************
 * NOTE: you do not need to change this script!             *
 * If it throws errors you need to adjust your config.      *
 ************************************************************/

function do_err($str, $exit = TRUE) {
    global $IND;
    echo '<p>'.$IND.'<font color="red"><b>ERROR:</b></font> ' .$str. "</p>\n";
    if($exit) {
         echo '</body></html>';
         exit;
    }
}

$IND = str_repeat('&nbsp;',4);

ob_implicit_flush();
/** @ignore */
define('SM_PATH', '../');

/*
 * Load config before output begins. functions/strings.php depends on 
 * functions/globals.php. functions/global.php needs to be run before
 * any html output starts. If config.php is missing, error will be displayed 
 * later.
 */
if (file_exists(SM_PATH . 'config/config.php')) {
    include(SM_PATH . 'config/config.php');
    include(SM_PATH . 'functions/strings.php');
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>SquirrelMail configtest</title>
</head>
<body>
<h1>SquirrelMail configtest</h1>

<p>This script will try to check some aspects of your SquirrelMail configuration
and point you to errors whereever it can find them. You need to go run <tt>conf.pl</tt>
in the <tt>config/</tt> directory first before you run this script.</p>

<?php


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

echo "<p><table>\n<tr><td>SquirrelMail version:</td><td><b>" . $version . "</b></td></tr>\n" .
     '<tr><td>Config file version:</td><td><b>' . $config_version . "</b></td></tr>\n" .
     '<tr><td>Config file last modified:</td><td><b>' . 
         date ('d F Y H:i:s', filemtime(SM_PATH . 'config/config.php')) .
         "</b></td></tr>\n</table>\n</p>\n\n";

echo "Checking PHP configuration...<br />\n";

if(!check_php_version(4,1,0)) {
    do_err('Insufficient PHP version: '. PHP_VERSION . '! Minimum required: 4.1.0');
}

echo $IND . 'PHP version ' . PHP_VERSION . " OK.<br />\n";

$php_exts = array('session','pcre');
$diff = array_diff($php_exts, get_loaded_extensions());
if(count($diff)) {
    do_err('Required PHP extensions missing: '.implode(', ',$diff) );
}

echo $IND . "PHP extensions OK.<br />\n";


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
    if (!is_writable($attachment_dir)) {
        do_err("I cannot write to attachment dir ($attachment_dir)!");
    }
    echo $IND . "Attachment dir OK.<br />\n";
}


/* check plugins and themes */
if (isset($plugins[0])) {
    foreach($plugins as $plugin) {
        if(!file_exists(SM_PATH .'plugins/'.$plugin)) {
            do_err('You have enabled the <i>'.$plugin.'</i> plugin but I cannot find it.', FALSE);
        } elseif (!is_readable(SM_PATH .'plugins/'.$plugin.'/setup.php')) {
            do_err('You have enabled the <i>'.$plugin.'</i> plugin but I cannot read its setup.php file.', FALSE);
        }
    }
    echo $IND . "Plugins OK.<br />\n";
} else {
    echo $IND . "Plugins are not enabled in config.<br />\n";
}
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
            "Server error: ($errorNumber) ".htmlspecialchars($errorString));
    }

    // check for SMTP code; should be 2xx to allow us access
    $smtpline = fgets($stream, 1024);
    if(((int) $smtpline{0}) > 3) {
        do_err("Error connecting to SMTP server. Server error: ".
        htmlspecialchars($smtpline));
    }

    fputs($stream, 'QUIT');
    fclose($stream);
    echo $IND . 'SMTP server OK (<tt><small>'.
        trim(htmlspecialchars($smtpline))."</small></tt>)<br />\n";

    /* POP before SMTP */
    if($pop_before_smtp) {
        $stream = fsockopen($smtpServerAddress, 110, $err_no, $err_str);
        if (!$stream) {
            do_err("Error connecting to POP Server ($smtpServerAddress:110) "
                  . $err_no . ' : ' . htmlspecialchars($err_str));
        }

        $tmp = fgets($stream, 1024);
        if (substr($tmp, 0, 3) != '+OK') {
            do_err("Error connecting to POP Server ($smtpServerAddress:110)"
                  . ' '.htmlspecialchars($tmp));
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
    do_err("Error connecting to IMAP server \"$imapServerAddress:$imapPort\".".
        "Server error: ($errorNumber) ".
    htmlspecialchars($errorString));
}

$imapline = fgets($stream, 1024);
if(substr($imapline, 0,4) != '* OK') {
   do_err('Error connecting to IMAP server. Server error: '.
       htmlspecialchars($imapline));
}

fputs($stream, '001 LOGOUT');
fclose($stream);

echo $IND . 'IMAP server OK (<tt><small>'.
    htmlspecialchars(trim($imapline))."</small></tt>)<br />\n";

echo "Checking internationalization (i18n) settings...<br />\n";
echo "$IND gettext - ";
if (function_exists('gettext')) {
    echo "Gettext functions are available. You must have appropriate system locales compiled.<br />\n";
} else {
    echo "Gettext functions are unavailable. SquirrelMail will use slower internal gettext functions.<br />\n";
}
echo "$IND mbstring - ";
if (function_exists('mb_detect_encoding')) {
    echo "Mbstring functions are available.<br />\n";
} else {
    echo "Mbstring functions are unavailable. Japanese translation won't work.<br />\n";
}
echo "$IND recode - ";
if (function_exists('recode')) {
    echo "Recode functions are available.<br />\n";
} elseif ($use_php_recode) {
    echo "Recode functions are unavailable.<br />\n";
    do_err('Your configuration requires recode support, but recode support is missing.');
} else {
    echo "Recode functions are unavailable.<br />\n";
}
echo "$IND iconv - ";
if (function_exists('iconv')) {
    echo "Iconv functions are available.<br />\n";
} elseif ($use_php_iconv) {
    echo "Iconv functions are unavailable.<br />\n";
    do_err('Your configuration requires iconv support, but iconv support is missing.');
} else {
    echo "Iconv functions are unavailable.<br />\n";
}
// same test as in include/validate.php
echo "$IND timezone - ";
if ( (!ini_get('safe_mode')) ||
    !strcmp(ini_get('safe_mode_allowed_env_vars'),'') ||
    preg_match('/^([\w_]+,)*TZ/', ini_get('safe_mode_allowed_env_vars')) ) {
        echo "Webmail users can change their time zone settings.<br />\n";
} else {
    echo "Webmail users can't change their time zone settings.<br />\n";
}


// Pear DB tests
echo "Checking database functions...<br />\n";
if($addrbook_dsn || $prefs_dsn || $addrbook_global_dsn) {
    @include_once('DB.php');
    if (class_exists('DB')) {
        echo "$IND PHP Pear DB support is present.<br />\n";
        $db_functions=array(
            'dbase' => 'dbase_open', 
            'fbsql' => 'fbsql_connect', 
            'interbase' => 'ibase_connect', 
            'informix' => 'ifx_connect',
            'msql' => 'msql_connect',
            'mssql' => 'mssql_connect',
            'mysql' => 'mysql_connect',
            'mysqli' => 'mysqli_connect',
            'oci8' => 'ocilogon',
            'odbc' => 'odbc_connect',
            'pgsql' => 'pgsql_connect',
            'sqlite' => 'sqlite_open',
            'sybase' => 'sybase_connect'
            );

        $dsns = array();
        if($prefs_dsn) {
            $dsns['preferences'] = $prefs_dsn;
        }
        if($addrbook_dsn) {
            $dsns['addressbook'] = $addrbook_dsn;
        }
        if($addrbook_global_dsn) {
            $dsns['global addressbook'] = $addrbook_global_dsn;
        }

        foreach($dsns as $type => $dsn) {
            $dbtype = array_shift(explode(':', $dsn));
            if(isset($db_functions[$dbtype]) && function_exists($db_functions[$dbtype])) {
                echo "$IND$dbtype database support present.<br />\n";

                // now, test this interface:

                $dbh = DB::connect($dsn, true);
                if (DB::isError($dbh)) {
                    do_err('Database error: '. htmlspecialchars(DB::errorMessage($dbh)) .
                        ' in ' .$type .' DSN.');
                }
                $dbh->disconnect();
                echo "$IND$type database connect successful.<br />\n";

            } else {
                do_err($db.' database support not present!');
            }
        }
    } else {
        do_err('Required PHP PEAR DB support is not available. Is PEAR installed and is the
            include path set correctly to find <tt>DB.php</tt>? The include path is now:
            "<tt>' . ini_get('include_path') . '</tt>".');
    }
} else {
    echo $IND."not using database functionality.<br />\n";
}

// LDAP DB tests
echo "Checking LDAP functions...<br />\n";
if( empty($ldap_server) ) {
    echo $IND."not using LDAP functionality.<br />\n";
} else {
    if ( !function_exists(ldap_connect) ) {
        do_err('Required LDAP support is not available.');
    } else {
        echo "$IND LDAP support present.<br />\n";
        foreach ( $ldap_server as $param ) {

            $linkid = ldap_connect($param['host'], (empty($param['port']) ? 389 : $param['port']) );

            if ( $linkid ) {
               echo "$IND LDAP connect to ".$param['host']." successful: ".$linkid."<br />\n";
                
                if ( !empty($param['protocol']) &&
                     !ldap_set_option($linkid, LDAP_OPT_PROTOCOL_VERSION, $param['protocol']) ) {
                    do_err('Unable to set LDAP protocol');
                }   

                if ( empty($param['binddn']) ) {
                    $bind = ldap_bind($linkid);    
                } else {
                    $bind = ldap_bind($param['binddn'], $param['bindpw']);
                }

                if ( $bind ) {
                    echo "$IND LDAP Bind Successful <br />";
                } else {
                    do_err('Unable to Bind to LDAP Server');
                }
                
                ldap_close($linkid);
            } else {
                do_err('Connection to LDAP failed');
            }
        }
    }
}
?>

<p>Congratulations, your SquirrelMail setup looks fine to me!</p>

<p><a href="login.php">Login now</a></p>

</body>
</html>
<?php
// vim: et ts=4
?>