<?php
/**
 * read_html_iframe.tpl
 *
 * Tempalte for displaying HTML messages within an iframe.
 * 
 * The following variables are available in this template:
 *      $iframe_url - URL to use for the src of the iframe.
 *      $html_body  - HTML to spit out in case the brwoser does nto support iframes
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
<div class="htmlIframe">
<?php echo _("Viewing HTML formatted email"); ?>
<iframe name="message_frame" src="<?php echo $iframe_url; ?>" frameborder="1" marginwidth="0" marginheight="0" scrolling="auto" height="<?php echo $iframe_height; ?>">
<?php echo $html_body; ?>
</iframe>
</div>