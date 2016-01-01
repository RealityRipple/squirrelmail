<?php
/**
 * view_header.tpl
 *
 * Template for displaying the full header of a message
 * 
 * The following variables are available in this template:
 *      $view_message_href - URL to navigate back to the full message
 *      $headers - Array containing all headers from the message.  Each element
 *                 represents a separate header and contains the following fields:
 * 
 *          $el['Header'] - The name of the header
 *          $el['Value']  - The value of the header.
 * 
 *                 All headers have been scrubbed by Squirrelmail already.
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
<div class="viewHeader">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header2">
   <?php echo _("Viewing Full Header"); ?> -
   <small><a href="<?php echo $view_message_href; ?>"><?php echo _("View message"); ?></a></small>
  </td>
 </tr>
 <tr>
  <td class="headers">
   <?php
    foreach ($headers as $header) {
        ?>
   <span class="headerName"><?php echo $header['Header']; ?></span> <span class="headerValue"><?php echo $header['Value']; ?></span>
        <?php
    }
   ?>
  </td>
 </tr>
</table>
</div>