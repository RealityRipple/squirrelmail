<?php
   /**
    **  options_personal.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all options relating to personal information
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');
   require_once('../functions/imap.php');
   require_once('../functions/array.php');
   require_once('../functions/plugin.php');
   require_once('../functions/options.php');
   
   displayPageHeader($color, 'None');

   $full_name = getPref($data_dir, $username, 'full_name');
   $reply_to = getPref($data_dir, $username, 'reply_to');
   $email_address  = getPref($data_dir, $username, 'email_address'); 

?>
   <br>
<table width=95% align=center border=0 cellpadding=2 cellspacing=0>
<tr><td align="center" bgcolor="<?php echo $color[0] ?>">

      <b><?php echo _("Options") . " - " . _("Personal Information"); ?></b>

    <table width="100%" border="0" cellpadding="1" cellspacing="1">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

   <form name=f action="options.php" method=post><br>
      <table width=100% cellpadding=2 cellspacing=0 border=0>
<?php

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/
    define('SMOPT_GRP_CONTACT', 0);
    define('SMOPT_GRP_SIGNATURE', 1);

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_CONTACT] = _("Name and Address Options");
    $optvals[SMOPT_GRP_CONTACT] = array();

    /* Build a simple array into which we will build options. */
    $optvals = array();

    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'full_name',
        'caption' => _("Full Name"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_HUGE
    );

    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'email_address',
        'caption' => _("Email Address"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_HUGE
    );

    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'reply_to',
        'caption' => _("Reply To"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_HUGE
    );

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_REPLY] = _("Reply and Signature Options");
    $optvals[SMOPT_GRP_REPLY] = array();

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'reply_citation_style',
        'caption' => _("Reply Citation Style"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array(SMPREF_NONE    => _("No Citation"),
                           'author_said'  => _("AUTHOR Said"),
                           'quote_who'    => _("Quote Who XML"),
                           'user-defined' => _("User-Defined"))
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'reply_citation_start',
        'caption' => _("User-Defined Citation Start"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_MEDIUM
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'reply_citation_end',
        'caption' => _("User-Defined Citation End"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_MEDIUM
    );

    $identities_link_value = '<A HREF="options_identities.php">'
                           . _("Edit Advanced Identities")
                           . '</A> '
                           . _("(discards changes made on this form so far)");
    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'identities_link',
        'caption' => _("Multiple Identities"),
        'type'    => SMOPT_TYPE_COMMENT,
        'refresh' => SMOPT_REFRESH_NONE,
        'comment' =>  $identities_link_value
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'use_signature',
        'caption' => _("Use Signature"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'prefix_sig',
        'caption' => _("Prefix Signature with '-- ' Line"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'signature_abs',
        'caption' => _("Signature"),
        'type'    => SMOPT_TYPE_TEXTAREA,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_MEDIUM
    );

    /* Build and output the option groups. */
    $option_groups = createOptionGroups($optgrps, $optvals);
    printOptionGroups($option_groups);

    do_hook('options_personal_inside');
    OptionSubmit( 'submit_personal' );

?>
      </table>   
</form>

   <?php do_hook('options_personal_bottom'); ?>

    </td></tr>
    </table>

</td></tr>
</table>
</body></html>
