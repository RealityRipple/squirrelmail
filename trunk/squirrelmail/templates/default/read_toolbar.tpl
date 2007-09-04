<?php
/**
 * read_toolbar.tpl
 *
 * This template generates the "Options" toolbar while reading a message.
 * 
 * The following variables are available in this template:
 *      
 *      $links - array containing various elements to be displayed in the toolbar.
 *               Each element is an array representing an option that contains the
 *               following elements:
 *          $link['URL']  - URL needed to access the action
 *          $link['Text'] - Text to be displayed for the action.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
<small>
 <?php
    foreach ($links as $count=>$link) {
        # Skip empty links
        if (empty($link['Text']))
            continue;
            
        # Printer firendly link must be handled a little differently
        if ($link['Text'] == _("View Printable Version")) {
            if ($javascript_on) {
                ?>
 <script type="text/javascript">
 <!--
 function printFormat () {
     var print_win = window.open("../src/printer_friendly_main.php<?php echo $link['URL']; ?>", "Print", "width=800; height=600");
     print_win.focus();
 }
 // -->
 </script>
 <a href="javascript:printFormat()"><?php echo $link['Text']; ?></a>
                <?php
            } else {
                # everything else follows this format...
                ?>
 <a href="../src/printer_friendly_bottom.php<?php echo $link['URL']; ?>" target="_blank"><?php echo $link['Text']; ?></a>
               <?php
            }
        } else {
            ?>
 <a href="<?php echo $link['URL']; ?>"><?php echo $link['Text']; ?></a>
            <?php
        }
        
        # Spit out a divider between each element
        if ($count < count($links)-1) {
            ?>
 &nbsp;|&nbsp;
            <?php
        }
    }
 ?>
</small>
