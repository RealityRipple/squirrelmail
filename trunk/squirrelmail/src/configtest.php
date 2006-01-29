<?php

/**
 * SquirrelMail configtest script
 *
 * @copyright &copy; 2003-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage config
 */

/************************************************************
 * NOTE: you do not need to change this script!             *
 * If it throws errors you need to adjust your config.      *
 ************************************************************/

// This script could really use some restructuring as it has grown quite rapidly
// but is not very 'clean'. Feel free to get some structure into this thing.

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

/* set default value in order to block remote access to script */
$allow_remote_configtest=false;

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
    <meta name="robots" content="noindex,nofollow">
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

/* Block remote use of script */
if (! $allow_remote_configtest) {
    sqGetGlobalVar('REMOTE_ADDR',$client_ip,SQ_SERVER);
    sqGetGlobalVar('SERVER_ADDR',$server_ip,SQ_SERVER);

    if ((! isset($client_ip) || $client_ip!='127.0.0.1') &&
        (! isset($client_ip) || ! isset($server_ip) || $client_ip!=$server_ip)) {
        do_err('Enable "Allow remote configtest" option in squirrelmail configuration in order to use this script.');
    }
}
/* checking PHP specs */

echo "<p><table>\n<tr><td>SquirrelMail version:</td><td><b>" . $version . "</b></td></tr>\n" .
     '<tr><td>Config file version:</td><td><b>' . $config_version . "</b></td></tr>\n" .
     '<tr><td>Config file last modified:</td><td><b>' .
         date ('d F Y H:i:s', filemtime(SM_PATH . 'config/config.php')) .
         "</b></td></tr>\n</table>\n</p>\n\n";

/* TODO: check $config_version here */

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

/* dangerous php settings */
/**
 * mbstring.func_overload allows to replace original string and regexp functions
 * with their equivalents from php mbstring extension. It causes problems when
 * scripts analyze 8bit strings byte after byte or use 8bit strings in regexp tests.
 * Setting can be controlled in php.ini (php 4.2.0), webserver config (php 4.2.0)
 * and .htaccess files (php 4.3.5).
 */
if (function_exists('mb_internal_encoding') &&
    check_php_version(4,2,0) &&
    (int)ini_get('mbstring.func_overload')!=0) {
    $mb_error='You have enabled mbstring overloading.'
        .' It can cause problems with SquirrelMail scripts that rely on single byte string functions.';
    do_err($mb_error);
}

/* checking paths */

echo "Checking paths...<br />\n";

if(!file_exists($data_dir)) {
    // data_dir is not that important in db_setups.
    if (isset($prefs_dsn) && ! empty($prefs_dsn)) {
        $data_dir_error = "Data dir ($data_dir) does not exist!\n";
        echo $IND .'<font color="red"><b>ERROR:</b></font> ' . $data_dir_error;
    } else {
        do_err("Data dir ($data_dir) does not exist!");
    }
}
// don't check if errors
if(!isset($data_dir_error) && !is_dir($data_dir)) {
    if (isset($prefs_dsn) && ! empty($prefs_dsn)) {
        $data_dir_error = "Data dir ($data_dir) is not a directory!\n";
        echo $IND . '<font color="red"><b>ERROR:</b></font> ' . $data_dir_error;
    } else {
        do_err("Data dir ($data_dir) is not a directory!");
    }
}
// datadir should be executable - but no clean way to test on that
if(!isset($data_dir_error) && !is_writable($data_dir)) {
    if (isset($prefs_dsn) && ! empty($prefs_dsn)) {
        $data_dir_error = "Data dir ($data_dir) is not writable!\n";
        echo $IND . '<font color="red"><b>ERROR:</b></font> ' . $data_dir_error;
    } else {
        do_err("Data dir ($data_dir) is not writable!");
    }
}

if (isset($data_dir_error)) {
    echo " Some plugins might need access to data directory.<br />\n";
} else {
    // todo_ornot: actually write something and read it back.
    echo $IND . "Data dir OK.<br />\n";
}

if($data_dir == $attachment_dir) {
    echo $IND . "Attachment dir is the same as data dir.<br />\n";
    if (isset($data_dir_error)) {
        do_err($data_dir_error);
    }
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

if ( $squirrelmail_default_language != 'en_US' ) {
    $loc_path = SM_PATH .'locale/'.$squirrelmail_default_language.'/LC_MESSAGES/squirrelmail.mo';
    if( ! file_exists( $loc_path ) ) {
        do_err('You have set <i>' . $squirrelmail_default_language .
            '</i> as your default language, but I cannot find this translation (should be '.
            'in <tt>' . $loc_path . '</tt>). Please note that you have to download translations '.
            'separately from the main SquirrelMail package.', FALSE);
    } elseif ( ! is_readable( $loc_path ) ) {
        do_err('You have set <i>' . $squirrelmail_default_language .
            '</i> as your default language, but I cannot read this translation (file '.
            'in <tt>' . $loc_path . '</tt> unreadable).', FALSE);
    } else {
        echo $IND . "Default language OK.<br />\n";
    }
} else {
    echo $IND . "Default language OK.<br />\n";
}

echo $IND . "Base URL detected as: <tt>" . htmlspecialchars(get_location()) . "</tt><br />\n";

/* check minimal requirements for other security options */

/* imaps or ssmtp */
if($use_smtp_tls == 1 || $use_imap_tls == 1) {
    if(!check_php_version(4,3,0)) {
        do_err('You need at least PHP 4.3.0 for SMTP/IMAP TLS!');
    }
    if(!extension_loaded('openssl')) {
        do_err('You need the openssl PHP extension to use SMTP/IMAP TLS!');
    }
}
/* starttls extensions */
if($use_smtp_tls == 2 || $use_imap_tls == 2) {
    if (! function_exists('stream_socket_enable_crypto')) {
        do_err('If you want to use STARTTLS extension, you need stream_socket_enable_crypto() function from PHP 5.1.0 and newer.');
    }
}
/* digest-md5 */
if ($smtp_auth_mech=='digest-md5' || $imap_auth_mech =='digest-md5') {
    if (!extension_loaded('xml')) {
        do_err('You need the PHP XML extension to use Digest-MD5 authentication!');
    }
}

/* check outgoing mail */

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
    $stream = fsockopen( ($use_smtp_tls==1?'tls://':'').$smtpServerAddress, $smtpPort,
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

    /* smtp starttls checks */
    if ($use_smtp_tls==2) {
        // if something breaks, script should close smtp connection on exit.

        // say helo
        fwrite($stream,"EHLO $client_ip\r\n");

        $ehlo=array();
        $ehlo_error = false;
        while ($line=fgets($stream, 1024)){
            if (preg_match("/^250(-|\s)(\S*)\s+(\S.*)/",$line,$match)||
                preg_match("/^250(-|\s)(\S*)\s+/",$line,$match)) {
                if (!isset($match[3])) {
                    // simple one word extension
                    $ehlo[strtoupper($match[2])]='';
                } else {
                    // ehlo-keyword + ehlo-param
                    $ehlo[strtoupper($match[2])]=trim($match[3]);
                }
                if ($match[1]==' ') {
                    $ret = $line;
                    break;
                }
            } else {
                // 
                $ehlo_error = true;
                $ehlo[]=$line;
                break;
            }
        }
        if ($ehlo_error) {
            do_err('SMTP EHLO failed. You need ESMTP support for SMTP STARTTLS');
        } elseif (!array_key_exists('STARTTLS',$ehlo)) {
            do_err('STARTTLS support is not declared by SMTP server.');
        }

        fwrite($stream,"STARTTLS\r\n");
        $starttls_response=fgets($stream, 1024);
        if ($starttls_response[0]!=2) {
            $starttls_cmd_err = 'SMTP STARTTLS failed. Server replied: '
                .htmlspecialchars($starttls_response);
            do_err($starttls_cmd_err);
        } elseif(! stream_socket_enable_crypto($stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            do_err('Failed to enable encryption on SMTP STARTTLS connection.');
        } else {
            echo $IND . "SMTP STARTTLS extension looks OK.<br />\n";
        }
        // According to RFC we should second ehlo call here.
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

/**
 * Check the IMAP server
 */
echo "Checking IMAP service....<br />\n";

/** Can we open a connection? */
$stream = fsockopen( ($use_imap_tls==1?'tls://':'').$imapServerAddress, $imapPort,
                       $errorNumber, $errorString);
if(!$stream) {
    do_err("Error connecting to IMAP server \"$imapServerAddress:$imapPort\".".
        "Server error: ($errorNumber) ".
    htmlspecialchars($errorString));
}

/** Is the first response 'OK'? */
$imapline = fgets($stream, 1024);
if(substr($imapline, 0,4) != '* OK') {
   do_err('Error connecting to IMAP server. Server error: '.
       htmlspecialchars($imapline));
}

echo $IND . 'IMAP server ready (<tt><small>'.
    htmlspecialchars(trim($imapline))."</small></tt>)<br />\n";

/** Check capabilities */
fputs($stream, "A001 CAPABILITY\r\n");
$capline = '';
while ($line=fgets($stream, 1024)){
  if (preg_match("/A001.*/",$line)) {
     break;
  } else {
     $capline.=$line;
  }
}

/* don't display capabilities before STARTTLS */
if ($use_imap_tls==2 && stristr($capline, 'STARTTLS') === false) {
    do_err('Your server doesn\'t support STARTTLS.');
} elseif($use_imap_tls==2) {
    /* try starting starttls */
    fwrite($stream,"A002 STARTTLS\r\n");
    $starttls_line=fgets($stream, 1024);
    if (! preg_match("/^A002 OK.*/i",$starttls_line)) {
        $imap_starttls_err = 'IMAP STARTTLS failed. Server replied: '
                .htmlspecialchars($starttls_line);
        do_err($imap_starttls_err);
    } elseif (! stream_socket_enable_crypto($stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        do_err('Failed to enable encryption on IMAP connection.');
    } else {
        echo $IND . "IMAP STARTTLS extension looks OK.<br />\n";
    }

    // get new capability line
    fwrite($stream,"A003 CAPABILITY\r\n");
    $capline='';
    while ($line=fgets($stream, 1024)){
        if (preg_match("/A003.*/",$line)) {
            break;
        } else {
            $capline.=$line;
        }
    }
}

echo $IND . 'Capabilities: <tt>'.htmlspecialchars($capline)."</tt><br />\n";

if($imap_auth_mech == 'login' && stristr($capline, 'LOGINDISABLED') !== FALSE) {
    do_err('Your server doesn\'t allow plaintext logins. '.
        'Try enabling another authentication mechanism like CRAM-MD5, DIGEST-MD5 or TLS-encryption '.
        'in the SquirrelMail configuration.', FALSE);
}

/** OK, close connection */
fputs($stream, "A004 LOGOUT\r\n");
fclose($stream);

echo "Checking internationalization (i18n) settings...<br />\n";
echo "$IND gettext - ";
if (function_exists('gettext')) {
    echo 'Gettext functions are available.'
        .' On some systems you must have appropriate system locales compiled.'
        ."<br />\n";
} else {
    echo 'Gettext functions are unavailable.'
        .' SquirrelMail will use slower internal gettext functions.'
        ."<br />\n";
}
echo "$IND mbstring - ";
if (function_exists('mb_detect_encoding')) {
    echo "Mbstring functions are available.<br />\n";
} else {
    echo 'Mbstring functions are unavailable.'
        ." Japanese translation won't work.<br />\n";
}
echo "$IND recode - ";
if (function_exists('recode')) {
    echo "Recode functions are available.<br />\n";
} elseif (isset($use_php_recode) && $use_php_recode) {
    echo "Recode functions are unavailable.<br />\n";
    do_err('Your configuration requires recode support, but recode support is missing.');
} else {
    echo "Recode functions are unavailable.<br />\n";
}
echo "$IND iconv - ";
if (function_exists('iconv')) {
    echo "Iconv functions are available.<br />\n";
} elseif (isset($use_php_iconv) && $use_php_iconv) {
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
            'pgsql' => 'pg_connect',
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
            $aDsn = explode(':', $dsn);
            $dbtype = array_shift($aDsn);
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
                do_err($dbtype.' database support not present!');
            }
        }
    } else {
        $db_error='Required PHP PEAR DB support is not available.'
            .' Is PEAR installed and is the include path set correctly to find <tt>DB.php</tt>?'
            .' The include path is now:<tt>' . ini_get('include_path') . '</tt>.';
        do_err($db_error);
    }
} else {
    echo $IND."not using database functionality.<br />\n";
}

// LDAP DB tests
echo "Checking LDAP functions...<br />\n";
if( empty($ldap_server) ) {
    echo $IND."not using LDAP functionality.<br />\n";
} else {
    if ( !function_exists('ldap_connect') ) {
        do_err('Required LDAP support is not available.');
    } else {
        echo "$IND LDAP support present.<br />\n";
        foreach ( $ldap_server as $param ) {

            $linkid = @ldap_connect($param['host'], (empty($param['port']) ? 389 : $param['port']) );

            if ( $linkid ) {
               echo "$IND LDAP connect to ".$param['host']." successful: ".$linkid."<br />\n";

                if ( !empty($param['protocol']) &&
                     !ldap_set_option($linkid, LDAP_OPT_PROTOCOL_VERSION, $param['protocol']) ) {
                    do_err('Unable to set LDAP protocol');
                }

                if ( empty($param['binddn']) ) {
                    $bind = @ldap_bind($linkid);
                } else {
                    $bind = @ldap_bind($param['binddn'], $param['bindpw']);
                }

                if ( $bind ) {
                    echo "$IND LDAP Bind Successful <br />";
                } else {
                    do_err('Unable to Bind to LDAP Server');
                }

                @ldap_close($linkid);
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