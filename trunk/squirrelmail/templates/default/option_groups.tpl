<?php
/**
 * option_groups.tpl
 *
 * Template for rendering main option page blocks
 * 
 * The following variables are available to this template:
 *      $page_title - string containing the title element for this page
 *      $options    - array containing option blocks to be displayed.  Each
 *                    element in the array will contain the following fields:
 *          $el['url']       - The URL of the link to display that option page
 *          $el['name']      - The name of the option page
 *          $el['desc']      - string containing the description of that option block
 *          $el['js']        - boolean TRUE if the element requires javascript being enabled. 
 *          $el['accesskey'] - an access key, if one exists (if not, it will be "NONE")
 *
 * @copyright 2006-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** extract variables */
extract($t);
?>
<div id="optionGroups">
<table cellspacing="0">
 <tr>
  <td colspan="2" class="title">
   <?php echo $page_title; ?>
  </td>
 </tr>
 <tr>
  <?php
    foreach ($options as $index=>$option) {
        ?>
  <td class="optionElement">
   <table cellspacing="0">
    <tr>
     <td class="optionName">
      <a href=<?php echo '"'.$option['url'].'"'; if ($option['accesskey'] != 'NONE') echo ' accesskey="' . $option['accesskey'] . '"'; ?>><?php echo $option['name']; ?></a>
     </td>
    </tr>
    <tr>
     <td class="optionDesc">
      <?php echo $option['desc']; ?>
     </td>
    </tr>
   </table>
  </td>
        <?php
        if (($index+1) % 2 == 0) {
            echo " </tr>\n <tr>\n";
        }
    }
  ?>
 </tr>
</table>
</div>
