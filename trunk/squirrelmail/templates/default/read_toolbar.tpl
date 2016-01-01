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
 *          $link['Target'] - Optional link target
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
<small>
 <?php
    foreach ($links as $count=>$link) {
        # Skip empty links
        if (empty($link['Text']))
            continue;
            
            ?>
 <a href="<?php echo $link['URL']; ?>"<?php echo (empty($link['Target'])?'':' target="' . $link['Target'] . '"')?>><?php echo $link['Text']; ?></a>
            <?php
        
        # Spit out a divider between each element
        if ($count < count($links)-1) {
            ?>
 &nbsp;|&nbsp;
            <?php
        }
    }
 ?>
</small>
