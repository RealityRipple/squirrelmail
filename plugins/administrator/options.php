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

GLOBAL $data_dir, $username;

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

$colapse = array( 'Titles' => FALSE,
                  'Group1' => getPref($data_dir, $username, 'adm_Group1', FALSE ),
                  'Group2' => getPref($data_dir, $username, 'adm_Group2', TRUE ),
                  'Group3' => getPref($data_dir, $username, 'adm_Group3', TRUE ),
                  'Group4' => getPref($data_dir, $username, 'adm_Group4', TRUE ),
                  'Group5' => getPref($data_dir, $username, 'adm_Group5', TRUE ),
                  'Group6' => getPref($data_dir, $username, 'adm_Group6', TRUE ),
                  'Group7' => getPref($data_dir, $username, 'adm_Group7', TRUE ) );

if ( isset( $switch ) ) {
    $colapse[$switch] = !$colapse[$switch];
    setPref($data_dir, $username, "adm_$switch", $colapse[$switch] );
}

echo "<form action=$PHP_SELF method=post>" .
    "<br><center><table width=95% bgcolor=\"$color[5]\"><tr><td>".
    "<table width=100% cellspacing=0 bgcolor=\"$color[4]\">" ,
    "<tr bgcolor=\"$color[5]\"><th colspan=2>" . _("Configuration Administrator") . "</th></tr>";

$act_grp = 'Titles';  /* Active group */
    
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

    if ( substr( $k, 0, 7 ) == '$theme[' ) {
        $type = SMOPT_TYPE_THEME;
    } else if ( substr( $k, 0, 9 ) == '$plugins[' ) {
        $type = SMOPT_TYPE_PLUGINS;
    } else if ( substr( $k, 0, 13 ) == '$ldap_server[' ) {
        $type = SMOPT_TYPE_LDAP;
    }

    if( $type == SMOPT_TYPE_TITLE || !$colapse[$act_grp] ) {

        switch ( $type ) {
        case SMOPT_TYPE_LDAP:
        case SMOPT_TYPE_PLUGINS:
        case SMOPT_TYPE_THEME:
        case SMOPT_TYPE_HIDDEN:
            break;
        case SMOPT_TYPE_TITLE:
            if ( $colapse[$k] ) {
                $sw = '(+)';
            } else {
                $sw = '(-)';
            }
            echo "<tr bgcolor=\"$color[0]\"><th colspan=2>" .
                 "<a href=options.php?switch=$k STYLE=\"text-decoration:none\"><b>$sw</b> </a>" .
                 "$name</th></tr>";
            $act_grp = $k;
            break;
        case SMOPT_TYPE_COMMENT:
            $v = substr( $v, 1, strlen( $v ) - 2 );
            echo "<tr><td>$name</td><td>".
                 "<b>$v</b>";
            $newcfg[$k] = "'$v'";
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
            break;
        case SMOPT_TYPE_INTEGER:
            if ( isset( $HTTP_POST_VARS[$e] ) ) {
                $v = intval( $HTTP_POST_VARS[$e] );
                $newcfg[$k] = $v;
            }
            echo "<tr><td>$name</td><td>".
                 "<input size=10 name=\"adm_$n\" value=\"$v\">";
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
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
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
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
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
            break;
    
        case SMOPT_TYPE_TEXTAREA:
            if ( isset( $HTTP_POST_VARS[$e] ) ) {
                $v = '"' . $HTTP_POST_VARS[$e] . '"';
                $newcfg[$k] = str_replace( "\n", '', $v );
            }
            echo "<tr><td valign=top>$name</td><td>".
                 "<textarea cols=\"$size\" name=\"adm_$n\">" . substr( $v, 1, strlen( $v ) - 2 ) . "</textarea>";
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
            break;
        case SMOPT_TYPE_STRING:
            if ( isset( $HTTP_POST_VARS[$e] ) ) {
                $v = '"' . $HTTP_POST_VARS[$e] . '"';
                $newcfg[$k] = $v;
            }
            echo "<tr><td>$name</td><td>".
                 "<input size=\"$size\" name=\"adm_$n\" value=\"" . substr( $v, 1, strlen( $v ) - 2 ) . "\">";
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
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
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
            break;
        default:
            echo "<tr><td>$name</td><td>" .
                 "<b><i>$v</i></b>";
            if ( isset( $defcfg[$k]['comment'] ) ) {
                echo ' &nbsp; ' . $defcfg[$k]['comment'];
            }
            echo "</td></tr>\n";
        }
    }

}

if ( !($colapse['Group6']) ) {
    $i = 0;
    echo '<tr><th>' . _("Theme Name") .
         '</th><th>' . _("Theme Path") .
         '</th></tr>';
    while ( isset( $newcfg["\$theme[$i]['NAME']"] ) ) {
        $k1 = "\$theme[$i]['NAME']";
        $e1 = "theme_name_$i";
        if ( isset( $HTTP_POST_VARS[$e1] ) ) {
            $v1 = '"' . $HTTP_POST_VARS[$e1] . '"';
            $newcfg[$k1] = $v1;
        } else {
            $v1 = $newcfg[$k1];
        }
        $k2 = "\$theme[$i]['PATH']";
        $e2 = "theme_path_$i";
        if ( isset( $HTTP_POST_VARS[$e2] ) ) {
            $v2 = '"' . $HTTP_POST_VARS[$e2] . '"';
            $newcfg[$k2] = $v2;
        } else {
            $v2 = $newcfg[$k2];
        }
        $name = substr( $v1, 1, strlen( $v1 ) - 2 );
        $path = substr( $v2, 1, strlen( $v2 ) - 2 );
        echo '<tr>'.
             "<td align=right>$i. <input name=\"$e1\" value=\"$name\" size=30></td>".
             "<td><input name=\"$e2\" value=\"$path\" size=40></td>".
             "</tr>\n";
        $i++;
    
    }
}

if ( $colapse['Group7'] ) {
    $sw = '(+)';
} else {
    $sw = '(-)';
}
echo "<tr bgcolor=\"$color[0]\"><th colspan=2>" .
     "<a href=options.php?switch=Group7 STYLE=\"text-decoration:none\"><b>$sw</b> </a>" .
     _("Plugins") . '</th></tr>';

if( !$colapse['Group7'] ) {

    $fd = opendir( '../plugins/' );
    $op_plugin = array();
    while (false!==($file = readdir($fd))) {
        if ($file != '.' && $file != '..' && $file != 'CVS' ) {
            if ( filetype( $file ) == 'dir' ) {
                $op_plugin[] = $file;
            }
        }
    }
    closedir($fd);
    asort( $op_plugin );
    
    $i = 0;
    while ( isset( $newcfg["\$plugins[$i]"] ) ) {
        $k = "\$plugins[$i]";
        $e = "plugin_$i";
        if ( isset( $HTTP_POST_VARS[$e] ) ) {
            $v = '"' . $HTTP_POST_VARS[$e] . '"';
            $newcfg[$k] = $v;
        } else {
            $v = $newcfg[$k];
        }
        $name = substr( $v, 1, strlen( $v ) - 2 );
        echo '<tr>'.
             "<td align=right>$i.</td>".
             "<td><select name=\"$e\">";
        foreach ( $op_plugin as $op ) {
            if ( $op == $name ) {
                $cs = ' selected';
            } else {
                $cs = '';
            }
            echo "<option$cs>$op</option>";
        }
        echo "</select></td>".
             '</tr>';
        $i++;
    
    }
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
            " */\n" );

/*
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
*/
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