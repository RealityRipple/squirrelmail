<?php

/**
 * Administrator Plugin
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
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
                if ( $s > $j + 1  &&
                     $line{$j}.$line{$j+1} == '*/' ) {
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
                if ( $line{$j} == '$' ) {
                    // We must detect $key name
                    $mode = 'K';
                    $key = '$';
                } else if ( $s < $j + 2 ) {
                } else if ( strtoupper( substr( $line, $j, 7 ) ) == 'GLOBAL ' ) {
                    // Skip untill next ;
                    $mode = ';';
                    $j += 6;
                } else if ( $line{$j}.$line{$j+1} == '/*' ) {
                    $mode = 'C';
                    $j++;
                } else if ( $line{$j} == '#' || $line{$j}.$line{$j+1} == '//' ) {
                    // Delete till the end of the line
                    $j = $s;
                }
            }
        }
    }
}

/* Change paths containing SM_PATH to admin-friendly paths
   relative to the config dir, i.e.:
     ''                          --> <empty string>
     SM_PATH . 'images/logo.gif' --> ../images/logo.gif
     '/absolute/path/logo.gif'   --> /absolute/path/logo.gif
     'http://whatever/'          --> http://whatever
   Note removal of quotes in returned value
*/
function change_to_rel_path($old_path) {
    $new_path = str_replace("SM_PATH . '", "../", $old_path); 
    $new_path = str_replace("../config/","", $new_path);
    $new_path = str_replace("'","", $new_path);
    return $new_path;
}

/* Change relative path (relative to config dir) to 
   internal SM_PATH, i.e.:
     empty_string            --> ''
     ../images/logo.gif      --> SM_PATH . 'images/logo.gif'
     images/logo.gif         --> SM_PATH . 'config/images/logo.gif'
     /absolute/path/logo.gif --> '/absolute/path/logo.gif'
     http://whatever/        --> 'http://whatever'
*/     
function change_to_sm_path($old_path) {
   if ( $old_path === '' || $old_path == "''" ) {
     return "''";
   } elseif ( preg_match("/^(\/|http)/", $old_path) ) {
     return "'" . $old_path . "'";
   } elseif ( preg_match("/^(\$|SM_PATH)/", $old_path) ) {
     return $old_path;
   }
   
   $new_path = '';
   $rel_path = explode("../", $old_path);
   if ( count($rel_path) > 2 ) {
     // Since we're relative to the config dir, 
     // more than 1 ../ puts us OUTSIDE the SM tree.
     // get full path to config.php, then pop the filename
     $abs_path = explode('/', realpath (SM_PATH . 'config/config.php'));
     array_pop ($abs_path); 
     foreach ( $rel_path as $subdir ) {
       if ( $subdir === '' ) {
         array_pop ($abs_path);
       } else {
         array_push($abs_path, $subdir);
       }
     }
     foreach ($abs_path as $subdir) {
       $new_path .= $subdir . '/';
     }
     $new_path = "'$new_path'";
   } elseif ( count($rel_path) > 1 ) {
     // we're within the SM tree, prepend SM_PATH
     $new_path = str_replace('../',"SM_PATH . '", $old_path . "'");
   } else {
     // Last, if it's a relative path without a .. prefix, 
     // we're somewhere within the config dir, so prepend
     //  SM_PATH . 'config/  
     $new_path = "SM_PATH . 'config/" . $old_path . "'";
   }
   return $new_path;
}


/* ---------------------- main -------------------------- */

define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'plugins/administrator/defines.php');
require_once(SM_PATH . 'plugins/administrator/auth.php');

GLOBAL $data_dir, $username;

if ( !adm_check_user() ) {
    header('Location: ' . SM_PATH . 'src/options.php') ;
    exit;
}

displayPageHeader($color, 'None');

$newcfg = array( );

foreach ( $defcfg as $key => $def ) {
    $newcfg[$key] = '';
}

$cfgfile = SM_PATH . 'config/config.php';
parseConfig( SM_PATH . 'config/config_default.php' );
parseConfig( $cfgfile );

$colapse = array( 'Titles' => 'off',
                  'Group1' => getPref($data_dir, $username, 'adm_Group1', 'off' ),
                  'Group2' => getPref($data_dir, $username, 'adm_Group2', 'on' ),
                  'Group3' => getPref($data_dir, $username, 'adm_Group3', 'on' ),
                  'Group4' => getPref($data_dir, $username, 'adm_Group4', 'on' ),
                  'Group5' => getPref($data_dir, $username, 'adm_Group5', 'on' ),
                  'Group6' => getPref($data_dir, $username, 'adm_Group6', 'on' ),
                  'Group7' => getPref($data_dir, $username, 'adm_Group7', 'on' ),
                  'Group8' => getPref($data_dir, $username, 'adm_Group8', 'on' ) );

if ( isset( $_GET['switch'] ) ) {
    $switch = $_GET['switch'];
    if ( $colapse[$switch] == 'on' ) {
       $colapse[$switch] = 'off';
    } else {
       $colapse[$switch] = 'on';
    }
    setPref($data_dir, $username, "adm_$switch", $colapse[$switch] );
}

echo "<form action=options.php method=post name=options>" .
    "<center><table width=95% bgcolor=\"$color[5]\"><tr><td>".
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
        if ( isset( $defcfg[$k]['size'] ) ) {
            $size = $defcfg[$k]['size'];
        } else {
            $size = 40;
        }
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

    if( $type == SMOPT_TYPE_TITLE || $colapse[$act_grp] == 'off' ) {

        switch ( $type ) {
        case SMOPT_TYPE_LDAP:
        case SMOPT_TYPE_PLUGINS:
        case SMOPT_TYPE_THEME:
        case SMOPT_TYPE_HIDDEN:
            break;
        case SMOPT_TYPE_EXTERNAL:
            echo "<tr><td>$name</td><td><b>" .
                 $defcfg[$k]['value'] .
                 "</b></td></tr>";
            break;
        case SMOPT_TYPE_TITLE:
            if ( $colapse[$k] == 'on' ) {
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
            if ( $v == '""' && isset( $defcfg[$k]['default'] ) ) {
                $v = "'" . $defcfg[$k]['default'] . "'";
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
	case SMOPT_TYPE_PATH:
	    if ( isset( $HTTP_POST_VARS[$e] ) ) {
               $v = change_to_sm_path($HTTP_POST_VARS[$e]);
               $newcfg[$k] = $v;
            }
            if ( $v == "''" && isset( $defcfg[$k]['default'] ) ) {
               $v = change_to_sm_path($defcfg[$k]['default']);
               $newcfg[$k] = $v;
            }
	    echo "<tr><td>$name</td><td>".
                 "<input size=\"$size\" name=\"adm_$n\" value=\"" . change_to_rel_path($v) . "\">";
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

/* Special Themes Block */
if ( $colapse['Group7'] == 'off' ) {
    $i = 0;
    echo '<tr><th>' . _("Theme Name") .
         '</th><th>' . _("Theme Path") .
         '</th></tr>';
    while ( isset( $newcfg["\$theme[$i]['NAME']"] ) ) {
        $k1 = "\$theme[$i]['NAME']";
        $e1 = "theme_name_$i";
        if ( isset( $HTTP_POST_VARS[$e1] ) ) {
            $v1 = '"' . str_replace( '\"', '"', $HTTP_POST_VARS[$e1] ) . '"';
            $v1 = '"' . str_replace( '"', '\"', $v1 ) . '"';
            $newcfg[$k1] = $v1;
        } else {
            $v1 = $newcfg[$k1];
        }
        $k2 = "\$theme[$i]['PATH']";
        $e2 = "theme_path_$i";
        if ( isset( $HTTP_POST_VARS[$e2] ) ) {
            $v2 = change_to_sm_path($HTTP_POST_VARS[$e2]);
	    $newcfg[$k2] = $v2;
        } else {
            $v2 = $newcfg[$k2];
        }
        $name = substr( $v1, 1, strlen( $v1 ) - 2 );
        $path = change_to_rel_path($v2);
        echo '<tr>'.
             "<td align=right>$i. <input name=\"$e1\" value=\"$name\" size=30></td>".
             "<td><input name=\"$e2\" value=\"$path\" size=40></td>".
             "</tr>\n";
        $i++;

    }
}

/* Special Plugins Block */
if ( $colapse['Group8'] == 'on' ) {
    $sw = '(+)';
} else {
    $sw = '(-)';
}
echo "<tr bgcolor=\"$color[0]\"><th colspan=2>" .
     "<a href=options.php?switch=Group8 STYLE=\"text-decoration:none\"><b>$sw</b> </a>" .
     _("Plugins") . '</th></tr>';

if( $colapse['Group8'] == 'off' ) {

    $fd = opendir( '../plugins/' );
    $op_plugin = array();
    $p_count = 0;
    while (false!==($file = readdir($fd))) {
        if ($file != '.' && $file != '..' && $file != 'CVS' ) {
            if ( filetype( $file ) == 'dir' ) {
                $op_plugin[] = $file;
                $p_count++;
            }
        }
    }
    closedir($fd);
    asort( $op_plugin );

    /* Lets get the plugins that are active */
    $plugins = array();
    if ( isset( $HTTP_POST_VARS['plg'] ) ) {
        foreach ( $op_plugin as $plg ) {
            if ( isset( $HTTP_POST_VARS["plgs_$plg"] ) &&
                 $HTTP_POST_VARS["plgs_$plg"] == 'on' ) {
                $plugins[] = $plg;
            }
        }
        $i = 0;
        foreach ( $plugins as $plg ) {
            $k = "\$plugins[$i]";
            $newcfg[$k] = "'$plg'";
            $i++;
        }
        while ( isset( $newcfg["\$plugins[$i]"] ) ) {
            $k = "\$plugins[$i]";
            $newcfg[$k] = '';
            $i++;
        }
    } else {
        $i = 0;
        while ( isset( $newcfg["\$plugins[$i]"] ) ) {
            $k = "\$plugins[$i]";
            $v = $newcfg[$k];
            $plugins[] = substr( $v, 1, strlen( $v ) - 2 );
            $i++;
        }
    }
    echo "<tr><td colspan=2><input type=hidden name=plg value=on><center><table><tr><td>";
    foreach ( $op_plugin as $plg ) {
        if ( in_array( $plg, $plugins ) ) {
            $sw = ' checked';
        } else {
            $sw = '';
        }
        echo '<tr>' .
             "<td>$plg</td><td><input$sw type=checkbox name=plgs_$plg></td>".
             "</tr>\n";
    }
    echo '</td></tr></table>';

}
echo "<tr bgcolor=\"$color[5]\"><th colspan=2><input value=\"" .
     _("Change Settings") . "\" type=submit></th></tr>" ,
     '</table></td></tr></table></form>';

/*
    Write the options to the file.
*/

if( $fp = @fopen( $cfgfile, 'w' ) ) {
    fwrite( $fp, "<?PHP\n".
    "/**\n".
    " * SquirrelMail Configuration File\n".
    " * Created using the Administrator Plugin\n".
    " */\n" );

    foreach ( $newcfg as $k => $v ) {
        if ( $k{0} == '$' && $v <> '' ) {
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
} else {
    echo '<font size=+1><br>'.
         _("Config file can't be opened. Please check config.php.").
         '</font>';
}

?>
