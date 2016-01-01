<?php
/**
 * search_result_top.tpl
 *
 * This template is displayed above the search results but after the search form.
 * 
 * IMPORTANT: This template does *not* handle the displaying of the results
 *            themselves!  That is handled by the message_list.tpl template!
 * 
 * The following variables are available in this template:
 *      $query_has_error - boolean TRUE if the search query generated an error
 *      $query_error     - string containing error generated.  NULL if no error.
 *      $query           - string describing the query
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<hr />
<div class="search">
<table cellspacing="0" class="table2">
 <tr>
  <td class="header2">
   <?php echo _("Search Results"); ?>
  </td>
 </tr>
 <?php
    if ($query_has_error) {
        ?>
 <tr>
  <td class="queryError">
   <?php echo $query_error; ?>
  </td>
 </tr>
        <?php
    }
 ?>
 <tr>
  <td style="text-align: center;">
   <?php echo $query; ?>
  </td>
 </tr>
</table>
</div>