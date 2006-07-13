<?php
/**
 * options.tpl
 *
 * Template for rendering the options page
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */


/** extract variables */
extract($t);

?>
</td></tr>
</table>
</td></tr>
</table>
<?php

/**
 * $Log$
 * Revision 1.2  2006/07/13 18:49:44  tokul
 * reverting some templating changes. They broke plugin blocks.
 * moving display of option blocks to separate template
 *
 * Revision 1.1  2006/07/09 22:22:31  vanmer
 * - initial revision of a template for options output
 *
 *
**/
?>