<?php
/**
 * option_groups.tpl
 *
 * Template for rendering main option page blocks
 *
 * @copyright &copy; 2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/**
 * This function prints out an option page row.
 * FIXME: remove function from template
 */
function print_optionpages_row($leftopt, $rightopt = false) {
    global $color;

    if ($rightopt) {
        $rightopt_name = html_tag( 'td', '<a href="' . $rightopt['url'] . '">' . $rightopt['name'] . '</a>', 'left', $color[9], 'valign="top" width="49%"' );
        $rightopt_desc = html_tag( 'td', $rightopt['desc'], 'left', $color[0], 'valign="top" width="49%"' );
    } else {
        $rightopt_name = html_tag( 'td', '&nbsp;', 'left', $color[4], 'valign="top" width="49%"' );
        $rightopt_desc = html_tag( 'td', '&nbsp;', 'left', $color[4], 'valign="top" width="49%"' );
    }

    echo
    html_tag( 'table', "\n" .
        html_tag( 'tr', "\n" .
            html_tag( 'td', "\n" .
                html_tag( 'table', "\n" .
                    html_tag( 'tr', "\n" .
                        html_tag( 'td',
                            '<a href="' . $leftopt['url'] . '">' . $leftopt['name'] . '</a>' ,
                        'left', $color[9], 'valign="top" width="49%"' ) .
                        html_tag( 'td',
                            '&nbsp;' ,
                        'left', $color[4], 'valign="top" width="2%"' ) . "\n" .
                        $rightopt_name
                    ) . "\n" .
                    html_tag( 'tr', "\n" .
                        html_tag( 'td',
                            $leftopt['desc'] ,
                        'left', $color[0], 'valign="top" width="49%"' ) .
                        html_tag( 'td',
                            '&nbsp;' ,
                        'left', $color[4], 'valign="top" width="2%"' ) . "\n" .
                        $rightopt_desc
                    ) ,
                '', '', 'width="100%" cellpadding="2" cellspacing="0" border="0"' ) ,
            'left', '', 'valign="top"' )
        ) ,
    '', $color[4], 'width="100%" cellpadding="0" cellspacing="5" border="0"' );
}

/** extract variables */
extract($t);

/**
 * Display error notices and other messages
 * Maybe formating should be moved from src/options.php
 */
echo $notice;

/********************************************/
/* Now, print out each option page section. */
/********************************************/
$first_optpage = false;
echo html_tag( 'table', '', '', $color[4], 'width="100%" cellpadding="0" cellspacing="5" border="0"' ) . "\n" .
    html_tag( 'tr' ) . "\n" .
    html_tag( 'td', '', 'left', '', 'valign="top"' ) .
    html_tag( 'table', '', '', $color[4], 'width="100%" cellpadding="3" cellspacing="0" border="0"' ) . "\n" .
    html_tag( 'tr' ) . "\n" .
    html_tag( 'td', '', 'left' );

foreach ($optpage_blocks as $next_optpage) {
    if ($first_optpage == false) {
        $first_optpage = $next_optpage;
    } else {
        print_optionpages_row($first_optpage, $next_optpage);
        $first_optpage = false;
    }
}

if ($first_optpage != false) {
    print_optionpages_row($first_optpage);
}
echo "</td></tr></table></td></tr></table>\n";


?>
</td></tr>
</table>
</td></tr>
</table>
