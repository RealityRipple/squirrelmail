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
    $mode = '';
    $l = count( $cfg );
    $modifier = FALSE;

    for ($i=0;$i<$l;$i++) {
        $line = trim( $cfg[$i] );
        $s = strlen( $line );
        for ($j=0;$j<$s;$j++) {
            switch ( $mode ) {
            case '=':
                if ( $line{$j} == '=' ) {
                    // Ok, we've got a right value, lets detect what type
                    $mode = 'D';
                } else if ( $line{$j} == ';' ) {
                    // hu! end of command
                    $key = $mode = '';
                }
                break;
            case 'K':
                // Key detect
                if( $line{$j} == ' ' ) {
                    $mode = '=';
                } else {
                    $key .= $line{$j};
                }
                break;
            case ';':
                // Skip until next ;
                if ( $line{$j} == ';' ) {
                    $mode = '';
                }
                break;
            case 'S':
                if ( $line{$j} == '\\' ) {
                    $value .= $line{$j};
                    $modifier = TRUE;
                } else if ( $line{$j} == $delimiter && $modifier === FALSE ) {
                    // End of string;
                    $newcfg[$key] = $value . $delimiter;
                    $key = $value = '';
                    $mode = ';';
                } else {
                    $value .= $line{$j};
                    $modifier = FALSE;
                }
                break;
            case 'N':
                if ( $line{$j} == ';' ) {
                    $newcfg{$key} = $value;
                    $key = $mode = '';
                } else {
                    $value .= $line{$j};
                }
                break;
            case 'C':
                // Comments
                if ( $line{$j}.$line{$j+1} == '*/' ) {
                    $mode = '';
                    $j++;
                }
                break;
            case 'D':
                // Delimiter detect
                switch ( $line{$j} ) {
                case '"':
                case "'":
                    // Double quote string
                    $delimiter = $value = $line{$j};
                    $mode = 'S';
                    break;
                case ' ':
                    // Nothing yet
                    break;
                default:
                    if ( strtoupper( substr( $line, $j, 4 ) ) == 'TRUE'  ) {
                        // Boolean TRUE
                        $newcfg{$key} = 'TRUE';
                        $key = '';
                        $mode = ';';
                    } else if ( strtoupper( substr( $line, $j, 5 ) ) == 'FALSE'  ) {
                        $newcfg{$key} = 'FALSE';
                        $key = '';
                        $mode = ';';
                    } else {
                        // Number or function call
                        $mode = 'N';
                        $value = $line{$j};
                    }
                }
                break;
            default:
                if ( strtoupper( substr( $line, $j, 7 ) ) == 'GLOBAL ' ) {
                    // Skip untill next ;
                    $mode = ';';
                    $j += 6;
                } else if ( $line{$j}.$line{$j+1} == '/*' ) {
                    $mode = 'C';
                    $j++;
                } else if ( $line{$j} == '#' || $line{$j}.$line{$j+1} == '//' ) {
                    // Delete till the end of the line
                    $j = $s;
                } else if ( $line{$j} == '$' ) {
                    // We must detect $key name
                    $mode = 'K';
                    $key = '$';
                }
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
            }
        } else {
            if( $not_first ) {
                fwrite( $fp, ', ' );
            }        
            fwrite( $fp, $k );
        }
        $not_first = TRUE;
    }
}
fwrite( $fp, ";\n" );
foreach ( $newcfg as $k => $v ) {
    if ( $k{0} == '$' ) {
        if ( substr( $k, 1, 11 ) == 'ldap_server' ) {
            $v = substr( $v, 0, strlen( $v ) - 1 ) . "\n)";
            $v = str_replace( 'array(', "array(\n\t", $v );
            $v = str_replace( "',", "',\n\t", $v );
        }
        fwrite( $fp, "$k = $v;\n" );
    }
}
fwrite( $fp, '?>' );
fclose( $fp );
?>