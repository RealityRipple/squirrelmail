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

function parseConfig( $cfg_file ) {

    global $newcfg;

    $cfg = file( $cfg_file );
    $cm = FALSE;
    $j = count( $cfg );

    for ( $i=0; $i < $j; $i++ ) {
        $l = '';
        $first_char = $cfg[$i]{0};
        do {
            // Remove comments
            $c = trim( $cfg[$i] );
            // This is not correct. We should extract strings before removing comments.
            $c = preg_replace( '/\/\*.*\*\//', '', $c );
            $c = preg_replace( '/#.*$/', '', $c );
            $c = preg_replace( '/\/\/.*$/', '', $c );
            $c = trim( $c );
            $l .= $c;
            $i++;
        } while( $first_char == '$' && substr( $c, -1 ) <> ';' && $i < $j );
        $i--;
        if ( $l <> '' ) {
            if ( $cm ) {
                if( substr( $l, -2 ) == '*/' ) {
                    $l = '';
                    $cm = FALSE;
                } else if( $k = strpos( $l, '*/' ) ) {
                    $l = substr( $l, $k );
                    $cm = FALSE;
                } else {
                    $l = '';
                }
            } else {
                if( $l{0}.$l{1} == '/*' ) {
                    $l = '';
                    $cm = TRUE;
                } else if ( $k = strpos( $l, '/*' ) ) {
                    $l = substr( $l, 0, $k );
                    $cm = TRUE;
                }
            }
    
            if ( $k = strpos( $l, '=' ) ) {
                $key = trim( substr( $l, 0, $k - 1 ) );
                $val = str_replace( ';', '', trim( substr( $l, $k + 1 ) ) );
                $newcfg[$key] = $val;
            }
        }

    }

}
/* ---------------------- main -------------------------- */
chdir('..');
require_once('../src/validate.php');
require_once('../functions/page_header.php');
require_once('../functions/imap.php');
require_once('../src/load_prefs.php');
require_once('../plugins/administrator/defines.php');

$auth = FALSE;
if ( $adm_id = fileowner('../config/config.php') ) {
    $adm = posix_getpwuid( $adm_id );
    if ( $username == $adm['name'] ) {
        $auth = TRUE;
    }
}

if ( !auth ) {
    header("Location: ../../src/options.php") ;
    exit;
}

displayPageHeader($color, 'None');

$newcfg = array( );

foreach ( $defcfg as $key => $def ) {
    $newcfg[$key] = '';
}

$cfgfile = '../config/config.php';
parseConfig( '../config/config_default.php' );
parseConfig( $cfgfile );

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
        echo "<tr bgcolor=\"$color[0]\"><th colspan=2>$name</th></tr>";
        break;
    case SMOPT_TYPE_COMMENT:
        $v = substr( $v, 1, strlen( $v ) - 2 );
        echo "<tr><td>$name</td><td>".
             "<b>$v</b>";
        $newcfg[$k] = "'$v'";
        break;
    case SMOPT_TYPE_INTEGER:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = intval( $HTTP_POST_VARS[$e] );
            $newcfg[$k] = $v;
        }
        echo "<tr><td>$name</td><td>".
             "<input size=10 name=\"adm_$n\" value=\"$v\">";
        break;
    case SMOPT_TYPE_NUMLIST:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = $HTTP_POST_VARS[$e];
            $newcfg[$k] = $v;
        }
        echo "<tr><td>$name</td><td>";
        echo "<select name=\"adm_$n\">";
        foreach ( $defcfg[$k]['posvals'] as $kp => $vp ) {
            echo "<option value=\"$kp\"";
            if ( $kp == $v ) {
                echo ' selected';
            }
            echo ">$vp</option>";
        }
        echo '</select>';
        break;
    case SMOPT_TYPE_STRLIST:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = '"' . $HTTP_POST_VARS[$e] . '"';
            $newcfg[$k] = $v;
        }
        echo "<tr><td>$name</td><td>".
             "<select name=\"adm_$n\">";
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
        echo "<tr><td>$name</td><td>".
             "<input size=\"$size\" name=\"adm_$n\" value=\"" . substr( $v, 1, strlen( $v ) - 2 ) . "\">";
        break;
    case SMOPT_TYPE_BOOLEAN:
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = $HTTP_POST_VARS[$e];
            $newcfg[$k] = $v;
        } else {
            $v = strtoupper( $v );
        }
        if ( $v == 'TRUE' ) {
            $ct = ' checked';
            $cf = '';
        } else {
            $ct = '';
            $cf = ' checked';
        }
        echo "<tr><td>$name</td><td>" .
             "<INPUT$ct type=radio NAME=\"adm_$n\" value=\"TRUE\">" . _("Yes") .
             "<INPUT$cf type=radio NAME=\"adm_$n\" value=\"FALSE\">" . _("No");
        break;
    default:
        echo "<tr><td>$name</td><td>" .
             "<b><i>$v</i></b>";
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