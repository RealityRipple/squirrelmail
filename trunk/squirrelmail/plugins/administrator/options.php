<?php

/**
 * Administrator Plugin
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Philippe Mingo
 *
 * $Id$
 */

chdir('..');
require_once('../src/validate.php');
require_once('../functions/page_header.php');
require_once('../functions/imap.php');
require_once('../src/load_prefs.php');
require_once('../plugins/administrator/defines.php');

displayPageHeader($color, 'None');

$cfgfile = '../config/config.php';
$cfg_defaultfile = '../config/config_default.php';
$cfg = file( $cfg_defaultfile );
$newcfg = $dfncfg = array( );
$cm = FALSE;

foreach ( $defcfg as $key => $def ) {
    $newcfg[$key] = '';
}

foreach ( $cfg as $l ) {
    // Remove inline /* */ Blocks
    $l = preg_replace( '/\/\*.*\*\//', '', $l );
    $l = preg_replace( '/#.*$/', '', $l );
    $l = preg_replace( '/\/\/.*$/', '', $l );
    $v = $s = trim( $l );
    if ( $cm ) {
        if( substr( $v, -2 ) == '*/' ) {
            $v = '';
            $cm = FALSE;
        } else if( $i = strpos( $v, '*/' ) ) {
            $v = substr( $v, $i );
            $cm = FALSE;
        } else {
            $v = '';
        }
    } else {
        if( $v{0}.$v{1} == '/*' ) {
            $v = '';
            $cm = TRUE;
        } else if ( $i = strpos( $v, '/*' ) ) {
            $v = substr( $v, 0, $i );
            $cm = TRUE;
        }
    }

    if ( $i = strpos( $v, '=' ) ) {
        $key = trim( substr( $v, 0, $i - 1 ) );
        $val = str_replace( ';', '', trim( substr( $v, $i + 1 ) ) );
        $newcfg[$key] = $val;
        $dfncfg[$key] = $val;
    }

}

$cfg = file( $cfgfile );

$cm = FALSE;
foreach ( $cfg as $l ) {
    $l = preg_replace( '/\/\*.*\*\//', '', $l );
    $l = preg_replace( '/#.*$/', '', $l );
    $l = preg_replace( '/\/\/.*$/', '', $l );
    $v = $s = trim( $l );
    if ( $cm ) {
        if( substr( $v, -2 ) == '*/' ) {
            $v = '';
            $cm = FALSE;
        } else if( $i = strpos( $v, '*/' ) ) {
            $v = substr( $v, $i );
            $cm = FALSE;
        } else {
            $v = '';
        }
    } else {
        if( $v{0}.$v{1} == '/*' ) {
            $v = '';
            $cm = TRUE;
        } else if ( $i = strpos( $v, '/*' ) ) {
            $v = substr( $v, 0, $i );
            $cm = TRUE;
        }
    }

    if ( $i = strpos( $v, '=' ) ) {
        $key = trim( substr( $v, 0, $i - 1 ) );
        $val = str_replace( ';', '', trim( substr( $v, $i + 1 ) ) );
        $newcfg[$key] = $val;
    }

}

echo "<form action=$PHP_SELF method=post>" .
    "<br><center><table width=95% bgcolor=\"$color[5]\"><tr><td>".
    "<table width=100% cellspacing=0 bgcolor=\"$color[4]\">" ,
    "<tr bgcolor=\"$color[5]\"><th colspan=2>" . _("Configuration Administrator") . "</th></tr>";
foreach ( $newcfg as $k => $v ) {
    $l = strtolower( $v );
    $type = SMOPT_TYPE_UNDEFINED;
    $n = substr( $k, 1 );
    $n = str_replace( '[', '_', $n );
    $n = str_replace( ']', '_', $n );
    $e = 'adm_' . $n;
    $name = $k;
    $size = 50;
    if ( isset( $defcfg[$k] ) ) {
        $name = $defcfg[$k]['name'];
        $type = $defcfg[$k]['type'];
        $size = $defcfg[$k]['size'];
    } else if ( $l == 'true' ) {
        $v = 'TRUE';
        $type = SMOPT_TYPE_BOOLEAN;
    } else if ( $l == 'false' ) {
        $v = 'FALSE';
        $type = SMOPT_TYPE_BOOLEAN;
    } else if ( $v{0} == "'" ) {
        $type = SMOPT_TYPE_STRING;
    } else if ( $v{0} == '"' ) {
        $type = SMOPT_TYPE_STRING;
    }

    switch ( $type ) {
    case SMOPT_TYPE_TITLE:
        echo "<tr bgcolor=\"$color[5]\"><th colspan=2>$name</th></tr>";
        break;
    case SMOPT_TYPE_COMMENT:
        $v = substr( $v, 1, strlen( $v ) - 2 );
        echo "<tr><td>$name</td><td>";
        echo "<b>$v</b>";
        $newcfg[$k] = "'$v'";
        break;
    case SMOPT_TYPE_INTEGER:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = intval( $HTTP_POST_VARS[$e] );
            $newcfg[$k] = $v;
        }
        echo "<tr><td>$name</td><td>";
        echo "<input size=10 name=\"adm_$n\" value=\"$v\">";
        break;
    case SMOPT_TYPE_STRLIST:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = '"' . $HTTP_POST_VARS[$e] . '"';
            $newcfg[$k] = $v;
        }
        echo "<tr><td>$name</td><td>";
        echo "<select name=\"adm_$n\">";
        foreach ( $defcfg[$k]['posvals'] as $kp => $vp ) {
            echo "<option value=\"$kp\"";
            if ( $kp == substr( $v, 1, strlen( $v ) - 2 ) ) {
                echo ' selected';
            }
            echo ">$vp</option>";
        }
        echo '</select>';
        break;

    case SMOPT_TYPE_STRING:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = '"' . $HTTP_POST_VARS[$e] . '"';
            $newcfg[$k] = $v;
        }
        echo "<tr><td>$name</td><td>";
        echo "<input size=\"$size\" name=\"adm_$n\" value=\"" . substr( $v, 1, strlen( $v ) - 2 ) . "\">";
        break;
    case SMOPT_TYPE_BOOLEAN:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = $HTTP_POST_VARS[$e];
            $newcfg[$k] = $v;
        }
        if ( $v == 'TRUE' ) {
            $ct = ' checked';
            $cf = '';
        } else {
            $ct = '';
            $cf = ' checked';
        }
        echo "<tr><td>$name</td><td>";
        echo "<INPUT$ct type=radio NAME=\"adm_$n\" value=\"TRUE\">" . _("Yes") .
            "<INPUT$cf type=radio NAME=\"adm_$n\" value=\"FALSE\">" . _("No");
        break;
    default:
        echo "<tr><td>$name</td><td>";
        echo "<b><i>$v</i></b>";
    }
    echo "</td></tr>\n";
}
echo "<tr bgcolor=\"$color[5]\"><th colspan=2><input value=\"" .
     _("Change Settings") . "\" type=submit></th></tr>" ,
     '</table></td></tr></table></form>';

/*
    Write the options to the file.
*/
$fp = fopen( $cfgfile, 'w' );
fwrite( $fp, "<?PHP\n".
            "/**\n".
            " * SquirrelMail Configuration File\n".
            " * Created using the Administrator Plugin\n".
            " */\n\n" );

fwrite( $fp, 'GLOBAL ' );
$not_first = FALSE;
foreach ( $newcfg as $k => $v ) {
    if ( $k{0} == '$' ) {
        if( $i = strpos( $k, '[' ) ) {
            if( strpos( $k, '[0]' ) ) {
                if( $not_first ) {
                    fwrite( $fp, ', ' );
                }
                fwrite( $fp, substr( $k, 0, $i) );
                $not_first = TRUE;
            }
        } else {
            if( $not_first ) {
                fwrite( $fp, ', ' );
            }
            fwrite( $fp, $k );
            $not_first = TRUE;
        }
    }
}
fwrite( $fp, ";\n" );
foreach ( $newcfg as $k => $v ) {
    if ( $k{0} == '$' ) {
        fwrite( $fp, "$k = $v;\n" );
    }
}
fwrite( $fp, '?>' );
fclose( $fp );
?>
