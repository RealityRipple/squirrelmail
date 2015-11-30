#!/usr/bin/env perl
# conf.pl
#
# Copyright (c) 1999-2015 The SquirrelMail Project Team
# Licensed under the GNU GPL. For full terms see COPYING.
#
# A simple configure script to configure SquirrelMail
#
# $Id$
############################################################
$conf_pl_version = "1.5.0";

############################################################
# Check what directory we're supposed to be running in, and
# change there if necessary.  File::Basename has been in
# Perl since at least 5.003_7, and nobody sane runs anything
# before that, but just in case.
############################################################
my $dir;
if ( eval q{require "File/Basename.pm"} ) {
    $dir = File::Basename::dirname($0);
    chdir($dir);
}

############################################################
# Some people try to run this as a CGI. That's wrong!
############################################################
if ( defined( $ENV{'PATH_INFO'} )
    || defined( $ENV{'QUERY_STRING'} )
    || defined( $ENV{'REQUEST_METHOD'} ) ) {
    print "Content-Type: text/html\n\n";
    print "You must run this script from the command line.";
    exit;
    }

############################################################
# If we got here, use Cwd to get the full directory path
# (the Basename stuff above will sometimes return '.' as
# the base directory, which is not helpful here).
############################################################
use Cwd;
$dir = cwd();


############################################################
# Try to determine what the version of SquirrelMail is
############################################################
$sm_version = 'unknown';
if ( -e "../include/constants.php" && -r "../include/constants.php") {
    open( FILE, "../include/constants.php" );
    while ( $line = <FILE> ) {
        if ($line =~ m/^define\('SM_VERSION', ?'(\d+\.\d+\.\d+( ?\[\w+]|))'/) {
            $sm_version = $1;
            last;
        }
    }
    close(FILE);
}


############################################################
# First, let's read in the data already in there...
############################################################
if ( -e "config.php" ) {
    # Make sure that file is readable
    if (! -r "config.php") {
        clear_screen();
        print "WARNING:\n";
        print "The file \"config/config.php\" was found, but you don't\n";
        print "have rights to read it.\n";
        print "\n";
        print "Press enter to continue";
        $ctu = <STDIN>;
        exit;
    }
    open( FILE, "config.php" );
    while ( $line = <FILE> ) {
        $line =~ s/^\s+//;
        $line =~ s/^\$//;
        $var = $line;

        $var =~ s/=/EQUALS/;
        if ( $var =~ /^([a-z])/i ) {
            @o = split ( /\s*EQUALS\s*/, $var );
            if ( $o[0] eq "config_version" ) {
                $o[1] =~ s/[\n\r]//g;
                $o[1] =~ s/[\'\"];\s*$//;
                $o[1] =~ s/;$//;
                $o[1] =~ s/^[\'\"]//;

                $config_version = $o[1];
                close(FILE);
            }
        }
    }
    close(FILE);

    if ( $config_version ne $conf_pl_version ) {
        clear_screen();
        print "WARNING:\n";
        print "  The file \"config/config.php\" was found, but it is for\n";
        print "  an older version of SquirrelMail. It is possible to still\n";
        print "  read the defaults from this file but be warned that many\n";
        print "  preferences change between versions. It is recommended that\n";
        print "  you start with a clean config.php for each upgrade that you\n";
        print "  do. To do this, just move config/config.php out of the way.\n";
        print "\n";
        print "Continue loading with the old config.php [y/N]? ";
        $ctu = <STDIN>;

        if ( ( $ctu !~ /^y\n/i ) || ( $ctu =~ /^\n/ ) ) {
            exit;
        }

        print "\nDo you want me to stop warning you [y/N]? ";
        $ctu = <STDIN>;
        if ( $ctu =~ /^y\n/i ) {
            $print_config_version = $conf_pl_version;
        } else {
            $print_config_version = $config_version;
        }
    } else {
        $print_config_version = $config_version;
    }

    $config = 1;
    open( FILE, "config.php" );
} elsif ( -e "config_default.php" ) {
    open( FILE, "config_default.php" );
    while ( $line = <FILE> ) {
        $line =~ s/^\s+//;
        $line =~ s/^\$//;
        $var = $line;

        $var =~ s/=/EQUALS/;
        if ( $var =~ /^([a-z])/i ) {
            @o = split ( /\s*EQUALS\s*/, $var );
            if ( $o[0] eq "config_version" ) {
                $o[1] =~ s/[\n\r]//g;
                $o[1] =~ s/[\'\"];\s*$//;
                $o[1] =~ s/;$//;
                $o[1] =~ s/^[\'\"]//;

                $config_version = $o[1];
                close(FILE);
            }
        }
    }
    close(FILE);

    if ( $config_version ne $conf_pl_version ) {
        clear_screen();
        print "WARNING:\n";
        print "  You are trying to use a 'config_default.php' from an older\n";
        print "  version of SquirrelMail. This is HIGHLY unrecommended. You\n";
        print "  should get the 'config_default.php' that matches the version\n";
        print "  of SquirrelMail that you are running. You can get this from\n";
        print "  the SquirrelMail web page by going to the following URL:\n";
        print "      http://squirrelmail.org.\n";
        print "\n";
        print "Continue loading with old config_default.php (a bad idea) [y/N]? ";
        $ctu = <STDIN>;

        if ( ( $ctu !~ /^y\n/i ) || ( $ctu =~ /^\n/ ) ) {
            exit;
        }

        print "\nDo you want me to stop warning you [y/N]? ";
        $ctu = <STDIN>;
        if ( $ctu =~ /^y\n/i ) {
            $print_config_version = $conf_pl_version;
        } else {
            $print_config_version = $config_version;
        }
    } else {
        $print_config_version = $config_version;
    }
    $config = 2;
    open( FILE, "config_default.php" );
} else {
    print "No configuration file found. Please get config_default.php\n";
    print "or config.php before running this again. This program needs\n";
    print "a default config file to get default values.\n";
    exit;
}

# Read and parse the current configuration file
# (either config.php or config_default.php).
while ( $line = <FILE> ) {
    $line =~ s/^\s+//;
    $line =~ s/^\$//;
    $var = $line;

    $var =~ s/=/EQUALS/;
    if ( $var =~ /^([a-z])/i ) {
        @options = split ( /\s*EQUALS\s*/, $var );
        $options[1] =~ s/[\n\r]//g;
        $options[1] =~ s/[\'\"];\s*$//;
        $options[1] =~ s/;$//;
        $options[1] =~ s/^[\'\"]//;
        # de-escape escaped strings
        $options[1] =~ s/\\'/'/g;
        $options[1] =~ s/\\\\/\\/g;

        if ( $options[0] =~ /^user_themes\[[0-9]+\]\[['"]PATH['"]\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]\[['"]PATH['"]\]//;
            $sub =~ s/.*\[//;
            if ( -e "../css/" ) {
                $options[1] =~ s/^\.\.\/config/\.\.\/css/;
            }
            $user_theme_path[$sub] = &change_to_rel_path($options[1]);
        } elsif ( $options[0] =~ /^user_themes\[[0-9]+\]\[['"]NAME['"]\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]\[['"]NAME['"]\]//;
            $sub =~ s/.*\[//;
            $user_theme_name[$sub] = $options[1];
        } elsif ( $options[0] =~ /^icon_themes\[[0-9]+\]\[['"]PATH['"]\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]\[['"]PATH['"]\]//;
            $sub =~ s/.*\[//;
            if ( -e "../images/" ) {
                $options[1] =~ s/^\.\.\/config/\.\.\/images/;
            }
            $icon_theme_path[$sub] = &change_to_rel_path($options[1]);
        } elsif ( $options[0] =~ /^icon_themes\[[0-9]+\]\[['"]NAME['"]\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]\[['"]NAME['"]\]//;
            $sub =~ s/.*\[//;
            $icon_theme_name[$sub] = $options[1];
        } elsif ( $options[0] =~ /^aTemplateSet\[[0-9]+\]\[['"]ID['"]\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]\[['"]ID['"]\]//;
            $sub =~ s/.*\[//;
            if ( -e "../templates" ) {
                $options[1] =~ s/^\.\.\/config/\.\.\/templates/;
            }
            $templateset_id[$sub] = $options[1];
##### FIXME: This section BELOW here so old prefs files don't blow up when running conf.pl
#####        Remove after a month or two 
} elsif ( $options[0] =~ /^aTemplateSet\[[0-9]+\]\[['"]PATH['"]\]/ ) {
    $sub = $options[0];
    $sub =~ s/\]\[['"]PATH['"]\]//;
    $sub =~ s/.*\[//;
    if ( -e "../templates" ) {
        $options[1] =~ s/^\.\.\/config/\.\.\/templates/;
    }
    $templateset_id[$sub] = $options[1];
##### FIXME: This section ABOVE here so old prefs files don't blow up when running conf.pl
#####        Remove after a month or two 
        } elsif ( $options[0] =~ /^aTemplateSet\[[0-9]+\]\[['"]NAME['"]\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]\[['"]NAME['"]\]//;
            $sub =~ s/.*\[//;
            $templateset_name[$sub] = $options[1];
        } elsif ( $options[0] =~ /^plugins\[[0-9]*\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]//;
            $sub =~ s/^plugins\[//;
            if ($sub eq '') {
               push @plugins, $options[1];
            } else {
               $plugins[$sub] = $options[1];
            }
        } elsif ($options[0] =~ /^fontsets\[\'[a-z]*\'\]/) {
            # parse associative $fontsets array
            $sub = $options[0];
            $sub =~ s/\'\]//;
            $sub =~ s/^fontsets\[\'//;
            $fontsets{$sub} = $options[1];
        } elsif ( $options[0] =~ /^theme\[[0-9]+\]\[['"]PATH|NAME['"]\]/ ) {
            ##
            ## $color themes are no longer supported.  Please leave this
            ## so conf.pl doesn't barf if it encounters a $theme.
            ##
        } elsif ( $options[0] =~ /^ldap_server\[[0-9]+\]/ ) {
            $sub = $options[0];
            $sub =~ s/\]//;
            $sub =~ s/^ldap_server\[//;
            $continue = 0;
            while ( ( $tmp = <FILE> ) && ( $continue != 1 ) ) {
                if ( $tmp =~ /\);\s*$/ ) {
                    $continue = 1;
                }

                if ( $tmp =~ /^\s*[\'\"]host[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]host[\'\"]\s*=>\s*[\'\"]//i;
                    $tmp =~ s/[\'\"],?\s*$//;
                    $tmp =~ s/[\'\"]\);\s*$//;
                    $host = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]base[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]base[\'\"]\s*=>\s*[\'\"]//i;
                    $tmp =~ s/[\'\"],?\s*$//;
                    $tmp =~ s/[\'\"]\);\s*$//;
                    $base = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]charset[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]charset[\'\"]\s*=>\s*[\'\"]//i;
                    $tmp =~ s/[\'\"],?\s*$//;
                    $tmp =~ s/[\'\"]\);\s*$//;
                    $charset = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]port[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]port[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $port = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]maxrows[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]maxrows[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $maxrows = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]filter[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]filter[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $filter = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]name[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]name[\'\"]\s*=>\s*[\'\"]//i;
                    $tmp =~ s/[\'\"],?\s*$//;
                    $tmp =~ s/[\'\"]\);\s*$//;
                    $name = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]binddn[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]binddn[\'\"]\s*=>\s*[\'\"]//i;
                    $tmp =~ s/[\'\"],?\s*$//;
                    $tmp =~ s/[\'\"]\);\s*$//;
                    $binddn = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]bindpw[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]bindpw[\'\"]\s*=>\s*[\'\"]//i;
                    $tmp =~ s/[\'\"],?\s*$//;
                    $tmp =~ s/[\'\"]\);\s*$//;
                    $bindpw = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]protocol[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]protocol[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $protocol = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]limit_scope[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]limit_scope[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $limit_scope = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]listing[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]listing[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $listing = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]writeable[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]writeable[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $writeable = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]search_tree[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]search_tree[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $search_tree = $tmp;
                } elsif ( $tmp =~ /^\s*[\'\"]starttls[\'\"]/i ) {
                    $tmp =~ s/^\s*[\'\"]starttls[\'\"]\s*=>\s*[\'\"]?//i;
                    $tmp =~ s/[\'\"]?,?\s*$//;
                    $tmp =~ s/[\'\"]?\);\s*$//;
                    $starttls = $tmp;
                }
            }
            $ldap_host[$sub]    = $host;
            $ldap_base[$sub]    = $base;
            $ldap_name[$sub]    = $name;
            $ldap_port[$sub]    = $port;
            $ldap_maxrows[$sub] = $maxrows;
            $ldap_filter[$sub]  = $filter;
            $ldap_charset[$sub] = $charset;
            $ldap_binddn[$sub]  = $binddn;
            $ldap_bindpw[$sub]  = $bindpw;
            $ldap_protocol[$sub] = $protocol;
            $ldap_limit_scope[$sub] = $limit_scope;
            $ldap_listing[$sub] = $listing;
            $ldap_writeable[$sub] = $writeable;
            $ldap_search_tree[$sub] = $search_tree;
            $ldap_starttls[$sub] = $starttls;
        } elsif ( $options[0] =~ /^(data_dir|attachment_dir|org_logo|signout_page|icon_theme_def)$/ ) {
            ${ $options[0] } = &change_to_rel_path($options[1]);
        } else {
            ${ $options[0] } = $options[1];
        }
    }
}
close FILE;

# RPC template sets aren't included in user interface skin list,
# so add the one from the config file here
#
if ($rpc_templateset =~ /_rpc$/) {
    $templateset_name[$#templateset_name + 1] = $rpc_templateset;
    $templateset_id[$#templateset_id + 1] = $rpc_templateset;
}

# FIXME: unknown introduction date
$useSendmail = 'false'                  if ( lc($useSendmail) ne 'true' );
$sendmail_path = "/usr/sbin/sendmail"   if ( !$sendmail_path );
$pop_before_smtp = 'false'              if ( !$pop_before_smtp );
$pop_before_smtp_host = ''              if ( !$pop_before_smtp_host );
$default_unseen_notify = 2              if ( !$default_unseen_notify );
$default_unseen_type = 1                if ( !$default_unseen_type );
$config_use_color = 0                   if ( !$config_use_color );
$invert_time = 'false'                  if ( !$invert_time );
$force_username_lowercase = 'false'     if ( !$force_username_lowercase );
$optional_delimiter = "detect"          if ( !$optional_delimiter );
$auto_create_special = 'false'          if ( !$auto_create_special );
$default_use_priority = 'true'          if ( !$default_use_priority );
$default_use_mdn = 'true'               if ( !$default_use_mdn );
$delete_folder = 'false'                if ( !$delete_folder );
$noselect_fix_enable = 'false'          if ( !$noselect_fix_enable );
$frame_top = "_top"                     if ( !$frame_top );
$provider_uri = ''                      if ( !$provider_uri );
$provider_name = ''                     if ( !$provider_name || $provider_name eq 'SquirrelMail');
$no_list_for_subscribe = 'false'        if ( !$no_list_for_subscribe );
$allow_charset_search = 'true'          if ( !$allow_charset_search );
$allow_advanced_search = 0              if ( !$allow_advanced_search) ;
$prefs_user_field = 'user'              if ( !$prefs_user_field );
$prefs_key_field = 'prefkey'            if ( !$prefs_key_field );
$prefs_val_field = 'prefval'            if ( !$prefs_val_field );
$session_name = 'SQMSESSID'             if ( !$session_name );
$skip_SM_header = 'false'               if ( !$skip_SM_header );
$default_use_javascript_addr_book = 'false' if (! $default_use_javascript_addr_book);

# since 1.2.0
$hide_sm_attributions = 'false'         if ( !$hide_sm_attributions );
# since 1.2.5
$edit_identity = 'true'                 if ( !$edit_identity );
$edit_name = 'true'                     if ( !$edit_name );
# since 1.4.23/1.5.2
$edit_reply_to = 'true'                 if ( !$edit_reply_to );

# since 1.4.0
$use_smtp_tls= 'false'                  if ( !$use_smtp_tls);
$smtp_auth_mech = 'none'                if ( !$smtp_auth_mech );
$use_imap_tls = 'false'                 if ( !$use_imap_tls );
$imap_auth_mech = 'login'               if ( !$imap_auth_mech );

# $use_imap_tls and $use_smtp_tls are switched to integer since 1.5.1
$use_imap_tls = 0                      if ( $use_imap_tls eq 'false');
$use_imap_tls = 1                      if ( $use_imap_tls eq 'true');
$use_smtp_tls = 0                      if ( $use_smtp_tls eq 'false');
$use_smtp_tls = 1                      if ( $use_smtp_tls eq 'true');

# since 1.5.0
$show_alternative_names = 'false'       if ( !$show_alternative_names );
# $available_languages option available only in 1.5.0. removed due to $languages
# implementation changes. options are provided by limit_languages plugin
# $available_languages = 'all'            if ( !$available_languages );
$aggressive_decoding = 'false'          if ( !$aggressive_decoding );
# available only in 1.5.0 and 1.5.1
# $advanced_tree = 'false'                if ( !$advanced_tree );
$use_php_recode = 'false'               if ( !$use_php_recode );
$use_php_iconv = 'false'                if ( !$use_php_iconv );
$buffer_output = 'false'                if ( !$buffer_output );

# since 1.5.1
$use_icons = 'false'                    if ( !$use_icons );
$use_iframe = 'false'                   if ( !$use_iframe );
$lossy_encoding = 'false'               if ( !$lossy_encoding );
$allow_remote_configtest = 'false'      if ( !$allow_remote_configtest );
$secured_config = 'true'                if ( !$secured_config );
$sq_https_port = 443                    if ( !$sq_https_port );
$sq_ignore_http_x_forwarded_headers = 'true' if ( !$sq_ignore_http_x_forwarded_headers );

$sm_debug_mode = 'SM_DEBUG_MODE_MODERATE' if ( !$sm_debug_mode );
#FIXME: When this is STABLE software, remove the line above and uncomment the one below:
#$sm_debug_mode = 'SM_DEBUG_MODE_OFF'    if ( !$sm_debug_mode );
$sm_debug_mode = convert_debug_binary_integer_to_constants($sm_debug_mode);

$addrbook_global_table = 'global_abook' if ( !$addrbook_global_table );
$addrbook_global_writeable = 'false'    if ( !$addrbook_global_writeable );
$addrbook_global_listing = 'false'      if ( !$addrbook_global_listing );
$abook_global_file = ''                 if ( !$abook_global_file);
$abook_global_file_writeable = 'false'  if ( !$abook_global_file_writeable);
$abook_global_file_listing = 'true'     if ( !$abook_global_file_listing );
$encode_header_key = ''                 if ( !$encode_header_key );
$hide_auth_header = 'false'             if ( !$hide_auth_header );
$time_zone_type = '0'                   if ( !$time_zone_type );
$prefs_user_size = 128                  if ( !$prefs_user_size );
$prefs_key_size = 64                    if ( !$prefs_key_size );
$prefs_val_size = 65536                 if ( !$prefs_val_size );

# add qmail-inject test here for backwards compatibility
if ( !$sendmail_args && $sendmail_path =~ /qmail-inject/ ) {
    $sendmail_args = '';
} elsif ( !$sendmail_args ) {
    $sendmail_args = '-i -t';
}

$default_fontsize = ''                  if ( !$default_fontsize);
$default_fontset = ''                   if ( !$default_fontset);
if ( !%fontsets) {
    %fontsets = ('serif',     'serif',
                 'sans',      'helvetica,arial,sans-serif',
                 'comicsans', 'comic sans ms,sans-serif',
                 'tahoma',    'tahoma,sans-serif',
                 'verasans',  'bitstream vera sans,verdana,sans-serif');
}

# sorting options changed names and reversed values in 1.5.1
$disable_thread_sort = 'false'         if ( !$disable_thread_sort );
$disable_server_sort = 'false'         if ( !$disable_server_sort );

# since 1.5.2
$abook_file_line_length = 2048         if ( !$abook_file_line_length );
$config_location_base = ''             if ( !$config_location_base );
$smtp_sitewide_user = ''               if ( !$smtp_sitewide_user );
$smtp_sitewide_pass = ''               if ( !$smtp_sitewide_pass );
$icon_theme_def = ''                   if ( !$icon_theme_def );
$disable_plugins = 'false'             if ( !$disable_plugins );
$disable_plugins_user = ''             if ( !$disable_plugins_user );
$only_secure_cookies = 'true'          if ( !$only_secure_cookies );
$disable_security_tokens = 'false'     if ( !$disable_security_tokens );
$check_referrer = ''                   if ( !$check_referrer );
$ask_user_info = 'true'                if ( !$ask_user_info );
$use_transparent_security_image = 'true' if ( !$use_transparent_security_image );
$display_imap_login_error = 'false'    if ( !$display_imap_login_error );

if ( $ARGV[0] eq '--install-plugin' ) {
    print "Activating plugin " . $ARGV[1] . "\n";
    if ( -d "../plugins/" . $ARGV[1]) {
        push @plugins, $ARGV[1];
        save_data();
        exit(0);
    } else {
        print "No such plugin.\n";
        exit(1);
    }
} elsif ( $ARGV[0] eq '--remove-plugin' ) {
    print "Removing plugin " . $ARGV[1] . "\n";
    foreach $plugin (@plugins) {
        if ( $plugin ne $ARGV[1] ) {
            push @newplugins, $plugin;
        }
    }
    @plugins = @newplugins;
    save_data();
    exit(0);
} elsif ( $ARGV[0] eq '--update-plugins' or $ARGV[0] eq '-u') {
    build_plugin_hook_array();
    exit(0);
} elsif ( $ARGV[0] eq '--help' or $ARGV[0] eq '-h') {
    print "SquirrelMail Configuration Script\n";
    print "Usage:\n";
    print " * No arguments: initiates the configuration dialog\n";
    print " * --install-plugin <plugin> : activates the specified plugin\n";
    print " * --remove-plugin <plugin>  : deactivates the specified plugin\n";
    print " * --update-plugins , -u     : rebuilds plugin_hooks.php according\n";
    print "                               to plugins activated in config.php\n";
    print " * --help , -h               : Displays this help\n";
    print "\n";
    exit(0);
}



####################################################################################

# used in multiple places, define once
$list_supported_imap_servers = 
    "    bincimap    = Binc IMAP server\n" .
    "    courier     = Courier IMAP server\n" .
    "    cyrus       = Cyrus IMAP server\n" .
    "    dovecot     = Dovecot Secure IMAP server\n" .
    "    exchange    = Microsoft Exchange IMAP server\n" .
    "    hmailserver = hMailServer\n" .
    "    macosx      = Mac OS X Mailserver\n" .
    "    mercury32   = Mercury/32\n" .
    "    uw          = University of Washington's IMAP server\n" .
    "    gmail       = IMAP access to Google mail (Gmail) accounts\n";

#####################################################################################
if ( $config_use_color == 1 ) {
    $WHT = "\x1B[1m";
    $NRM = "\x1B[0m";
} else {
    $WHT              = "";
    $NRM              = "";
    $config_use_color = 2;
}

# lists can be printed in more than one column; default is just one
#
$columns = 1;

# try to get screen width dynamically if possible; default to 80
# (user can override with "w#" command)
#
eval { require "sys/ioctl.ph" };
if ($@
 || !defined &TIOCGWINSZ
 || !open(TTY, "+</dev/tty")
 || !ioctl(TTY, &TIOCGWINSZ, $winsize='')) {
    $screen_width = 80;
} else {
    ($row, $col, $xpixel, $ypixel) = unpack('S4', $winsize);
    $screen_width = $col;
}

while ( ( $command ne "q" ) && ( $command ne "Q" ) && ( $command ne ":q" ) ) {
    clear_screen();
    print $WHT. "SquirrelMail Configuration : " . $NRM;
    if    ( $config == 1 ) { print "Read: config.php"; }
    elsif ( $config == 2 ) { print "Read: config_default.php"; }
    print "\nConfig version $print_config_version; SquirrelMail version $sm_version\n";
    print "---------------------------------------------------------\n";

    if ( $menu == 0 ) {
        print $WHT. "Main Menu --\n" . $NRM;
        print "1.  Organization Preferences\n";
        print "2.  Server Settings\n";
        print "3.  Folder Defaults\n";
        print "4.  General Options\n";
        print "5.  User Interface\n";
        print "6.  Address Books\n";
        print "7.  Message of the Day (MOTD)\n";
        print "8.  Plugins\n";
        print "9.  Database\n";
        print "10. Language settings\n";
        print "11. Tweaks\n";
        print "\n";
        print "D.  Set pre-defined settings for specific IMAP servers\n";
        print "\n";
    } elsif ( $menu == 1 ) {
        print $WHT. "Organization Preferences\n" . $NRM;
        print "1.  Organization Name      : $WHT$org_name$NRM\n";
        print "2.  Organization Logo      : $WHT$org_logo$NRM\n";
        print "3.  Org. Logo Width/Height : $WHT($org_logo_width/$org_logo_height)$NRM\n";
        print "4.  Organization Title     : $WHT$org_title$NRM\n";
        print "5.  Signout Page           : $WHT$signout_page$NRM\n";
        print "6.  Top Frame              : $WHT$frame_top$NRM\n";
        print "7.  Provider link          : $WHT$provider_uri$NRM\n";
        print "8.  Provider link text     : $WHT$provider_name$NRM\n";

        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 2 ) {
        print $WHT. "Server Settings\n\n" . $NRM;
        print $WHT . "General" . $NRM . "\n";
        print "-------\n";
        print "1.  Domain                 : $WHT$domain$NRM\n";
        print "2.  Invert Time            : $WHT$invert_time$NRM\n";
        print "3.  Sendmail or SMTP       : $WHT";
        if ( lc($useSendmail) eq 'true' ) {
            print "Sendmail";
        } else {
            print "SMTP";
        }
        print "$NRM\n";
        print "\n";

        if ( $show_imap_settings ) {
          print $WHT . "IMAP Settings". $NRM . "\n--------------\n";
          print "4.  IMAP Server            : $WHT$imapServerAddress$NRM\n";
          print "5.  IMAP Port              : $WHT$imapPort$NRM\n";
          print "6.  Authentication type    : $WHT$imap_auth_mech$NRM\n";
          print "7.  Secure IMAP (TLS)      : $WHT" . display_use_tls($use_imap_tls) . "$NRM\n";
          print "8.  Server software        : $WHT$imap_server_type$NRM\n";
          print "9.  Delimiter              : $WHT$optional_delimiter$NRM\n";
          print "\n";
        } elsif ( $show_smtp_settings ) {
          if ( lc($useSendmail) eq 'true' ) {
            print $WHT . "Sendmail" . $NRM . "\n--------\n";
            print "4.   Sendmail Path         : $WHT$sendmail_path$NRM\n";
            print "5.   Sendmail arguments    : $WHT$sendmail_args$NRM\n";
            print "6.   Header encryption key : $WHT$encode_header_key$NRM\n";
            print "\n";
          } else {
            print $WHT . "SMTP Settings" . $NRM . "\n-------------\n";
            print "4.   SMTP Server           : $WHT$smtpServerAddress$NRM\n";
            print "5.   SMTP Port             : $WHT$smtpPort$NRM\n";
            print "6.   POP before SMTP       : $WHT$pop_before_smtp$NRM\n";
            print "7.   SMTP Authentication   : $WHT$smtp_auth_mech" . display_smtp_sitewide_userpass() ."$NRM\n";
            print "8.   Secure SMTP (TLS)     : $WHT" . display_use_tls($use_smtp_tls) . "$NRM\n";
            print "9.   Header encryption key : $WHT$encode_header_key$NRM\n";
            print "\n";
          }
        }

        if ($show_imap_settings == 0) {
          print "A.  Update IMAP Settings   : ";
          print "$WHT$imapServerAddress$NRM:";
          print "$WHT$imapPort$NRM ";
          print "($WHT$imap_server_type$NRM)\n";
        }
        if ($show_smtp_settings == 0) {
          if ( lc($useSendmail) eq 'true' ) {
            print "B.  Change Sendmail Config : $WHT$sendmail_path$NRM\n";
          } else {
            print "B.  Update SMTP Settings   : ";
            print "$WHT$smtpServerAddress$NRM:";
            print "$WHT$smtpPort$NRM\n";
          }
        }
        if ( $show_smtp_settings || $show_imap_settings )
        {
          print "H.  Hide " .
                ($show_imap_settings ? "IMAP Server" :
                  (lc($useSendmail) eq 'true') ? "Sendmail" : "SMTP") . " Settings\n";
        }

        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 3 ) {
        print $WHT. "Folder Defaults\n" . $NRM;
        print "1.  Default Folder Prefix          : $WHT$default_folder_prefix$NRM\n";
        print "2.  Show Folder Prefix Option      : $WHT$show_prefix_option$NRM\n";
        print "3.  Trash Folder                   : $WHT$trash_folder$NRM\n";
        print "4.  Sent Folder                    : $WHT$sent_folder$NRM\n";
        print "5.  Drafts Folder                  : $WHT$draft_folder$NRM\n";
        print "6.  By default, move to trash      : $WHT$default_move_to_trash$NRM\n";
        print "7.  By default, save sent messages : $WHT$default_move_to_sent$NRM\n";
        print "8.  By default, save as draft      : $WHT$default_save_as_draft$NRM\n";
        print "9.  List Special Folders First     : $WHT$list_special_folders_first$NRM\n";
        print "10. Show Special Folders Color     : $WHT$use_special_folder_color$NRM\n";
        print "11. Auto Expunge                   : $WHT$auto_expunge$NRM\n";
        print "12. Default Sub. of INBOX          : $WHT$default_sub_of_inbox$NRM\n";
        print "13. Show 'Contain Sub.' Option     : $WHT$show_contain_subfolders_option$NRM\n";
        print "14. Default Unseen Notify          : $WHT$default_unseen_notify$NRM\n";
        print "15. Default Unseen Type            : $WHT$default_unseen_type$NRM\n";
        print "16. Auto Create Special Folders    : $WHT$auto_create_special$NRM\n";
        print "17. Folder Delete Bypasses Trash   : $WHT$delete_folder$NRM\n";
        print "18. Enable /NoSelect folder fix    : $WHT$noselect_fix_enable$NRM\n";
        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 4 ) {
        print $WHT. "General Options\n" . $NRM;
        print "1.  Data Directory               : $WHT$data_dir$NRM\n";
        print "2.  Attachment Directory         : $WHT$attachment_dir$NRM\n";
        print "3.  Directory Hash Level         : $WHT$dir_hash_level$NRM\n";
        print "4.  Default Left Size            : $WHT$default_left_size$NRM\n";
        print "5.  Usernames in Lowercase       : $WHT$force_username_lowercase$NRM\n";
        print "6.  Allow use of priority        : $WHT$default_use_priority$NRM\n";
        print "7.  Hide SM attributions         : $WHT$hide_sm_attributions$NRM\n";
        print "8.  Allow use of receipts        : $WHT$default_use_mdn$NRM\n";
        print "9.  Allow editing of identity    : $WHT$edit_identity$NRM\n";
        print "    Allow editing of name        : $WHT$edit_name$NRM\n";
        print "    Allow editing of reply-to    : $WHT$edit_reply_to$NRM\n";
        print "    Remove username from header  : $WHT$hide_auth_header$NRM\n";
        print "10. Disable server thread sort   : $WHT$disable_thread_sort$NRM\n";
        print "11. Disable server-side sorting  : $WHT$disable_server_sort$NRM\n";
        print "12. Allow server charset search  : $WHT$allow_charset_search$NRM\n";
        print "13. Allow advanced search        : $WHT$allow_advanced_search$NRM\n";
        print "14. PHP session name             : $WHT$session_name$NRM\n";
        print "15. Time zone configuration      : $WHT$time_zone_type$NRM\n";
        print "16. Location base                : $WHT$config_location_base$NRM\n";
        print "17. Only secure cookies if poss. : $WHT$only_secure_cookies$NRM\n";
        print "18. Disable secure forms         : $WHT$disable_security_tokens$NRM\n";
        print "19. Page referal requirement     : $WHT$check_referrer$NRM\n";
        print "20. Security image               : $WHT" . (lc($use_transparent_security_image) eq 'true' ? 'Transparent' : 'Textual') . "$NRM\n";
        print "21. Display login error from IMAP: $WHT$display_imap_login_error$NRM\n";
        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 5 ) {
        print $WHT. "User Interface\n" . $NRM;
        print "1.  Use Icons?                   : $WHT$use_icons$NRM\n";
#        print "3.  Default Icon Set             : $WHT$icon_theme_def$NRM\n";
        print "2.  Default font size            : $WHT$default_fontsize$NRM\n";
        print "3.  Manage template sets (skins)\n";
        print "4.  Manage user themes\n";
        print "5.  Manage font sets\n";
        print "6.  Manage icon themes\n";

        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 6 ) {
        print $WHT. "Address Books\n" . $NRM;
        print "1.  Change LDAP Servers\n";
        for ( $count = 0 ; $count <= $#ldap_host ; $count++ ) {
            print "    >  $ldap_host[$count]\n";
        }
        print "2.  Use Javascript address book search          : $WHT$default_use_javascript_addr_book$NRM\n";
        print "3.  Global address book file                    : $WHT$abook_global_file$NRM\n";
        print "4.  Allow writing into global file address book : $WHT$abook_global_file_writeable$NRM\n";
        print "5.  Allow listing of global file address book   : $WHT$abook_global_file_listing$NRM\n";
        print "6.  Allowed address book line length            : $WHT$abook_file_line_length$NRM\n";
        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 7 ) {
        print $WHT. "Message of the Day (MOTD)\n" . $NRM;
        print "\n$motd\n";
        print "\n";
        print "1   Edit the MOTD\n";
        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 8 ) {
        if (lc($disable_plugins) eq 'true' && $disable_plugins_user ne '') {
            print $WHT. "Plugins (WARNING: All plugins are currently disabled\n                  for the user \"$disable_plugins_user\"!)\n" . $NRM;
        } elsif (lc($disable_plugins) eq 'true') {
            print $WHT. "Plugins (WARNING: All plugins are currently disabled!)\n" . $NRM;
        } else {
            print $WHT. "Plugins\n" . $NRM;
        }
        print "  Installed Plugins\n";
        if ($columns > 1) {
            $num = print_multi_col_list(1, $columns, $screen_width, 1, @plugins);
        } else {
            $num = 0;
            for ( $count = 0 ; $count <= $#plugins ; $count++ ) {
                $num = $count + 1;
                $english_name = get_plugin_english_name($plugins[$count]);
                if ( $english_name eq "" ) {
                    print "    $WHT$num.$NRM $plugins[$count]" . get_plugin_version($plugins[$count]) . "\n";
                } else {
                    print "    $WHT$num.$NRM $english_name ($plugins[$count])" . get_plugin_version($plugins[$count]) . "\n";
                }
            }
        }
        print "\n  Available Plugins:\n";
        opendir( DIR, "../plugins" );
        @files          = sort(readdir(DIR));
        $pos            = 0;
        @unused_plugins = ();
        for ( $i = 0 ; $i <= $#files ; $i++ ) {
            if ( -d "../plugins/" . $files[$i] && $files[$i] !~ /^\./ && $files[$i] ne ".svn" ) {
                $match = 0;
                for ( $k = 0 ; $k <= $#plugins ; $k++ ) {
                    if ( $plugins[$k] eq $files[$i] ) {
                        $match = 1;
                    }
                }
                if ( $match == 0 ) {
                    $unused_plugins[$pos] = $files[$i];
                    $pos++;
                }
            }
        }

        if ($columns > 1) {
            $num = print_multi_col_list($num + 1, $columns, $screen_width, 1, @unused_plugins);
        } else {
            for ( $i = 0 ; $i <= $#unused_plugins ; $i++ ) {
                $num = $num + 1;
                $english_name = get_plugin_english_name($unused_plugins[$i]);
                if ( $english_name eq "" ) {
                    print "    $WHT$num.$NRM $unused_plugins[$i]" . get_plugin_version($unused_plugins[$i]) . "\n";
                } else {
                    print "    $WHT$num.$NRM $english_name ($unused_plugins[$i])" . get_plugin_version($unused_plugins[$i]) . "\n";
                }
            }
        }
        closedir DIR;

        print "\n";
        if (lc($disable_plugins) eq 'true' && $disable_plugins_user ne '') {
            print "E   Enable active plugins (all plugins currently\n    disabled for the user \"$disable_plugins_user\")\n";
        } elsif (lc($disable_plugins) eq 'true') {
            print "E   Enable active plugins (all plugins currently\n    disabled)\n";
        } else {
            print "D   Disable all plugins\n";
        }
        print "U   Set the user for whom plugins can be disabled\n";
        print "R   Return to Main Menu\n";
        print "C#  List plugins in <#> number of columns\n";
        print "W#  Change screen width to <#> (currently $screen_width)\n";
    } elsif ( $menu == 9 ) {
        print $WHT. "Database\n" . $NRM;
        print "1.  DSN for Address Book   : $WHT$addrbook_dsn$NRM\n";
        print "2.  Table for Address Book : $WHT$addrbook_table$NRM\n";
        print "\n";
        print "3.  DSN for Preferences    : $WHT$prefs_dsn$NRM\n";
        print "4.  Table for Preferences  : $WHT$prefs_table$NRM\n";
        print "5.  Field for username     : $WHT$prefs_user_field$NRM ($prefs_user_size)\n";
        print "6.  Field for prefs key    : $WHT$prefs_key_field$NRM ($prefs_key_size)\n";
        print "7.  Field for prefs value  : $WHT$prefs_val_field$NRM ($prefs_val_size)\n";
        print "\n";
        print "8.  DSN for Global Address Book            : $WHT$addrbook_global_dsn$NRM\n";
        print "9.  Table for Global Address Book          : $WHT$addrbook_global_table$NRM\n";
        print "10. Allow writing into Global Address Book : $WHT$addrbook_global_writeable$NRM\n";
        print "11. Allow listing of Global Address Book   : $WHT$addrbook_global_listing$NRM\n";
        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 10 ) {
        print $WHT. "Language settings\n" . $NRM;
        print "1.  Default Language                : $WHT$squirrelmail_default_language$NRM\n";
        print "2.  Default Charset                 : $WHT$default_charset$NRM\n";
        print "3.  Show alternative language names : $WHT$show_alternative_names$NRM\n";
        print "4.  Enable aggressive decoding      : $WHT$aggressive_decoding$NRM\n";
        print "5.  Enable lossy encoding           : $WHT$lossy_encoding$NRM\n";
        print "\n";
        print "R   Return to Main Menu\n";
    } elsif ( $menu == 11 ) {
        print $WHT. "Interface tweaks\n" . $NRM;
        print "1.  Display html mails in iframe : $WHT$use_iframe$NRM\n";
        print "2.  Ask user info on first login : $WHT$ask_user_info$NRM\n";
        print "\n";
        print $WHT. "PHP tweaks\n" . $NRM;
        print "4.  Use php recode functions     : $WHT$use_php_recode$NRM\n";
        print "5.  Use php iconv functions      : $WHT$use_php_iconv$NRM\n";
        print "6.  Buffer all output            : $WHT$buffer_output$NRM\n";
        print "\n";
        print $WHT. "Configuration tweaks\n" . $NRM;
        print "7.  Allow remote configtest     : $WHT$allow_remote_configtest$NRM\n";
        print "8.  Debug mode                  : $WHT$sm_debug_mode$NRM\n";
        print "9.  Secured configuration mode  : $WHT$secured_config$NRM\n";
        print "10. HTTPS port                  : $WHT$sq_https_port$NRM\n";
        print "11. Ignore HTTP_X_FORWARDED headers: $WHT$sq_ignore_http_x_forwarded_headers$NRM\n";
        print "\n";
        print "R   Return to Main Menu\n";
    }
    if ( $config_use_color == 1 ) {
        print "C   Turn color off\n";
    } else {
        print "C   Turn color on\n";
    }
    print "S   Save data\n";
    print "Q   Quit\n";

    print "\n";
    print "Command >> " . $WHT;
    $command = <STDIN>;
    $command =~ s/[\n\r]//g;
    $command =~ tr/A-Z/a-z/;
    print "$NRM\n";

    # Read the commands they entered.
    if ( $command eq "r" ) {
        $menu = 0;
    } elsif ( $command eq "s" ) {
        save_data();
        print "Press enter to continue...";
        $tmp   = <STDIN>;
        $saved = 1;
    } elsif ( ( $command eq "q" ) && ( $saved == 0 ) ) {
        print "You have not saved your data.\n";
        print "Save?  [" . $WHT . "Y" . $NRM . "/n]: ";
        $save = <STDIN>;
        if ( ( $save =~ /^y/i ) || ( $save =~ /^\s*$/ ) ) {
            save_data();
        }
    } elsif ( $command eq "c" ) {
        if ( $config_use_color == 1 ) {
            $config_use_color = 2;
            $WHT              = "";
            $NRM              = "";
        } else {
            $config_use_color = 1;
            $WHT              = "\x1B[1m";
            $NRM              = "\x1B[0m";
        }
    } elsif ( $command =~ /^w([0-9]+)/ ) {
        $screen_width = $1;
    } elsif ( $command eq "d" && $menu == 0 ) {
        set_defaults();
    } else {
        $saved = 0;
        if ( $menu == 0 ) {
            if ( ( $command > 0 ) && ( $command < 12 ) ) {
                $menu = $command;
            }
        } elsif ( $menu == 1 ) {
            if    ( $command == 1 ) { $org_name                      = command1(); }
            elsif ( $command == 2 ) { $org_logo                      = command2(); }
            elsif ( $command == 3 ) { ($org_logo_width,$org_logo_height)  = command2a(); }
            elsif ( $command == 4 ) { $org_title                     = command3(); }
            elsif ( $command == 5 ) { $signout_page                  = command4(); }
            elsif ( $command == 6 ) { $frame_top                     = command6(); }
            elsif ( $command == 7 ) { $provider_uri                  = command7(); }
            elsif ( $command == 8 ) { $provider_name                 = command8(); }

        } elsif ( $menu == 2 ) {
            if ( $command eq "a" )    { $show_imap_settings = 1; $show_smtp_settings = 0; }
            elsif ( $command eq "b" ) { $show_imap_settings = 0; $show_smtp_settings = 1; }
            elsif ( $command eq "h" ) { $show_imap_settings = 0; $show_smtp_settings = 0; }
            elsif ( $command <= 3 ) {
              if    ( $command == 1 )  { $domain                 = command11(); }
              elsif ( $command == 2 )  { $invert_time            = command110(); }
              elsif ( $command == 3 )  { $useSendmail            = command14(); }
              $show_imap_settings = 0; $show_smtp_settings = 0;
            } elsif ( $show_imap_settings ) {
              if    ( $command == 4 )  { $imapServerAddress      = command12(); }
              elsif ( $command == 5 )  { $imapPort               = command13(); }
              elsif ( $command == 6 )  { $imap_auth_mech     = command112a(); }
              elsif ( $command == 7 )  { $use_imap_tls       = command_use_tls("IMAP",$use_imap_tls); }
              elsif ( $command == 8 )  { $imap_server_type       = command19(); }
              elsif ( $command == 9 )  { $optional_delimiter     = command111(); }
            } elsif ( $show_smtp_settings && lc($useSendmail) eq 'true' ) {
              if    ( $command == 4 )  { $sendmail_path          = command15(); }
              elsif ( $command == 5 )  { $sendmail_args          = command_sendmail_args(); }
              elsif ( $command == 6 )  { $encode_header_key      = command114(); }
            } elsif ( $show_smtp_settings ) {
              if    ( $command == 4 )  { $smtpServerAddress      = command16(); }
              elsif ( $command == 5 )  { $smtpPort               = command17(); }
              elsif ( $command == 6 )  { $pop_before_smtp        = command18a(); }
              elsif ( $command == 7 )  { $smtp_auth_mech    = command112b(); }
              elsif ( $command == 8 )  { $use_smtp_tls      = command_use_tls("SMTP",$use_smtp_tls); }
              elsif ( $command == 9 )  { $encode_header_key      = command114(); }
            }
        } elsif ( $menu == 3 ) {
            if    ( $command == 1 )  { $default_folder_prefix          = command21(); }
            elsif ( $command == 2 )  { $show_prefix_option             = command22(); }
            elsif ( $command == 3 )  { $trash_folder                   = command23a(); }
            elsif ( $command == 4 )  { $sent_folder                    = command23b(); }
            elsif ( $command == 5 )  { $draft_folder                   = command23c(); }
            elsif ( $command == 6 )  { $default_move_to_trash          = command24a(); }
            elsif ( $command == 7 )  { $default_move_to_sent           = command24b(); }
            elsif ( $command == 8 )  { $default_save_as_draft          = command24c(); }
            elsif ( $command == 9 )  { $list_special_folders_first     = command27(); }
            elsif ( $command == 10 ) { $use_special_folder_color       = command28(); }
            elsif ( $command == 11 ) { $auto_expunge                   = command29(); }
            elsif ( $command == 12 ) { $default_sub_of_inbox           = command210(); }
            elsif ( $command == 13 ) { $show_contain_subfolders_option = command211(); }
            elsif ( $command == 14 ) { $default_unseen_notify          = command212(); }
            elsif ( $command == 15 ) { $default_unseen_type            = command213(); }
            elsif ( $command == 16 ) { $auto_create_special            = command214(); }
            elsif ( $command == 17 ) { $delete_folder                  = command215(); }
            elsif ( $command == 18 ) { $noselect_fix_enable            = command216(); }
        } elsif ( $menu == 4 ) {
            if    ( $command == 1 )  { $data_dir                 = command33a(); }
            elsif ( $command == 2 )  { $attachment_dir           = command33b(); }
            elsif ( $command == 3 )  { $dir_hash_level           = command33c(); }
            elsif ( $command == 4 )  { $default_left_size        = command35(); }
            elsif ( $command == 5 )  { $force_username_lowercase = command36(); }
            elsif ( $command == 6 )  { $default_use_priority     = command37(); }
            elsif ( $command == 7 )  { $hide_sm_attributions     = command38(); }
            elsif ( $command == 8 )  { $default_use_mdn          = command39(); }
            elsif ( $command == 9 )  { $edit_identity            = command310(); }
            elsif ( $command == 10 ) { $disable_thread_sort     = command312(); }
            elsif ( $command == 11 ) { $disable_server_sort     = command313(); }
            elsif ( $command == 12 ) { $allow_charset_search     = command314(); }
            elsif ( $command == 13 ) { $allow_advanced_search    = command316(); }
            elsif ( $command == 14 ) { $session_name             = command317(); }
            elsif ( $command == 15 ) { $time_zone_type           = command318(); }
            elsif ( $command == 16 ) { $config_location_base     = command_config_location_base(); }
            elsif ( $command == 17 ) { $only_secure_cookies = command319(); }
            elsif ( $command == 18 ) { $disable_security_tokens  = command320(); }
            elsif ( $command == 19 ) { $check_referrer           = command321(); }
            elsif ( $command == 20 ) { $use_transparent_security_image = command322(); }
            elsif ( $command == 21 ) { $display_imap_login_error = command323(); }
        } elsif ( $menu == 5 ) {
            if ( $command == 1 )     { $use_icons      = commandB3(); }
#            elsif ( $command == 3 )  { $icon_theme_def = command53(); }
            elsif ( $command == 2 )  { $default_fontsize = command_default_fontsize(); }
            elsif ( $command == 3 )  { $templateset_default = command_templates(); }
            elsif ( $command == 4 )  { command_userThemes(); }
            elsif ( $command == 5 )  { command_fontsets(); }
            elsif ( $command == 6 )  { command_iconSets(); }
        } elsif ( $menu == 6 ) {
            if    ( $command == 1 ) { command61(); }
            elsif ( $command == 2 ) { command62(); }
            elsif ( $command == 3 ) { $abook_global_file=command63(); }
            elsif ( $command == 4 ) { command64(); }
            elsif ( $command == 5 ) { command65(); }
            elsif ( $command == 6 ) { command_abook_file_line_length(); }
        } elsif ( $menu == 7 ) {
            if ( $command == 1 ) { $motd = command71(); }
        } elsif ( $menu == 8 ) {
            if    ( $command =~ /^[0-9]+/ )    { @plugins              = command81(); }
            elsif ( $command eq "u" )          { $disable_plugins_user = command82(); }
            elsif ( $command eq "d" )          { $disable_plugins      = 'true'; }
            elsif ( $command eq "e" )          { $disable_plugins      = 'false'; }
            elsif ( $command =~ /^c([0-9]+)/ ) { $columns              = $1; }
        } elsif ( $menu == 9 ) {
            if    ( $command == 1 ) { $addrbook_dsn     = command91(); }
            elsif ( $command == 2 ) { $addrbook_table   = command92(); }
            elsif ( $command == 3 ) { $prefs_dsn        = command93(); }
            elsif ( $command == 4 ) { $prefs_table      = command94(); }
            elsif ( $command == 5 ) { $prefs_user_field = command95(); }
            elsif ( $command == 6 ) { $prefs_key_field  = command96(); }
            elsif ( $command == 7 ) { $prefs_val_field  = command97(); }
            elsif ( $command == 8 ) { $addrbook_global_dsn       = command98(); }
            elsif ( $command == 9 ) { $addrbook_global_table     = command99(); }
            elsif ( $command == 10 ) { $addrbook_global_writeable = command910(); }
            elsif ( $command == 11 ) { $addrbook_global_listing  = command911(); }
        } elsif ( $menu == 10 ) {
            if    ( $command == 1 ) { $squirrelmail_default_language = commandA1(); }
            elsif ( $command == 2 ) { $default_charset               = commandA2(); }
            elsif ( $command == 3 ) { $show_alternative_names        = commandA3(); }
            elsif ( $command == 4 ) { $aggressive_decoding           = commandA4(); }
            elsif ( $command == 5 ) { $lossy_encoding                = commandA5(); }
        } elsif ( $menu == 11 ) {
            if    ( $command == 1 ) { $use_iframe     = commandB2(); }
            elsif ( $command == 2 ) { $ask_user_info  = command_ask_user_info(); }
            elsif ( $command == 4 ) { $use_php_recode = commandB4(); }
            elsif ( $command == 5 ) { $use_php_iconv  = commandB5(); }
            elsif ( $command == 6 ) { $buffer_output  = commandB6(); }
            elsif ( $command == 7 ) { $allow_remote_configtest = commandB7(); }
            elsif ( $command == 8 ) { $sm_debug_mode = commandB8(); }
            elsif ( $command == 9 ) { $secured_config = commandB9(); }
            elsif ( $command == 10 ) { $sq_https_port = commandB10(); }
            elsif ( $command == 11 ) { $sq_ignore_http_x_forwarded_headers = commandB11(); }
        }
    }
}

# we exit here
print "\nExiting conf.pl.\n".
    "You might want to test your configuration by browsing to\n".
    "http://your-squirrelmail-location/src/configtest.php\n".
    "Happy SquirrelMailing!\n\n";


####################################################################################

# org_name
sub command1 {
    print "We have tried to make the name SquirrelMail as transparent as\n";
    print "possible.  If you set up an organization name, most places where\n";
    print "SquirrelMail would take credit will be credited to your organization.\n";
    print "\n";
    print "If your Organization Name includes a '\$', please precede it with a \\. \n";
    print "Other '\$' will be considered the beginning of a variable that\n";
    print "must be defined before the \$org_name is printed.\n";
    print "\n";
    print "[$WHT$org_name$NRM]: $WHT";
    $new_org_name = <STDIN>;
    if ( $new_org_name eq "\n" ) {
        $new_org_name = $org_name;
    } else {
        $new_org_name =~ s/[\r\n]//g;
    $new_org_name =~ s/\"/&quot;/g;
    }
    return $new_org_name;
}

# org_logo
sub command2 {
    print "Your organization's logo is an image that will be displayed at\n";
    print "different times throughout SquirrelMail. ";
    print "\n";
    print "Please be aware of the following: \n";
    print "  - Relative URLs are relative to the config dir\n";
    print "    to use the default logo, use ../images/sm_logo.png\n";
    print "  - To specify a logo defined outside the SquirrelMail source tree\n";
    print "    use the absolute URL the webserver would use to include the file\n";
    print "    e.g. http://example.com/images/mylogo.gif or /images/mylogo.jpg\n";
    print "\n";
    print "[$WHT$org_logo$NRM]: $WHT";
    $new_org_logo = <STDIN>;
    if ( $new_org_logo eq "\n" ) {
        $new_org_logo = $org_logo;
    } else {
        $new_org_logo =~ s/[\r\n]//g;
    }
    return $new_org_logo;
}

# org_logo_width
sub command2a {
    print "Your organization's logo is an image that will be displayed at\n";
    print "different times throughout SquirrelMail.  Width\n";
    print "and Height of your logo image.  Use '0' to disable.\n";
    print "\n";
    print "Width: [$WHT$org_logo_width$NRM]: $WHT";
    $new_org_logo_width = <STDIN>;
    $new_org_logo_width =~ tr/0-9//cd;  # only want digits!
    if ( $new_org_logo_width eq '' ) {
        $new_org_logo_width = $org_logo_width;
    }
    if ( $new_org_logo_width > 0 ) {
        print "Height: [$WHT$org_logo_height$NRM]: $WHT";
        $new_org_logo_height = <STDIN>;
        $new_org_logo_height =~ tr/0-9//cd;  # only want digits!
        if( $new_org_logo_height eq '' ) {
        $new_org_logo_height = $org_logo_height;
    }
    } else {
        $new_org_logo_height = 0;
    }
    return ($new_org_logo_width, $new_org_logo_height);
}

# org_title
sub command3 {
    print "A title is what is displayed at the top of the browser window in\n";
    print "the titlebar.  Usually this will end up looking something like:\n";
    print "\"Netscape: $org_title\"\n";
    print "\n";
    print "If your Organization Title includes a '\$', please precede it with a \\. \n";
    print "Other '\$' will be considered the beginning of a variable that\n";
    print "must be defined before the \$org_title is printed.\n";
    print "\n";
    print "[$WHT$org_title$NRM]: $WHT";
    $new_org_title = <STDIN>;
    if ( $new_org_title eq "\n" ) {
        $new_org_title = $org_title;
    } else {
        $new_org_title =~ s/[\r\n]//g;
    $new_org_title =~ s/\"/\'/g;
    }
    return $new_org_title;
}

# signout_page
sub command4 {
    print "When users click the Sign Out button they will be logged out and\n";
    print "then sent to signout_page.  If signout_page is left empty,\n";
    print "(hit space and then return) they will be taken, as normal,\n";
    print "to the default and rather sparse SquirrelMail signout page.\n";
    print "\n";
    print "[$WHT$signout_page$NRM]: $WHT";
    $new_signout_page = <STDIN>;
    if ( $new_signout_page eq "\n" ) {
        $new_signout_page = $signout_page;
    } else {
        $new_signout_page =~ s/[\r\n]//g;
        $new_signout_page =~ s/^\s+$//g;
    }
    return $new_signout_page;
}

# Default top frame
sub command6 {
    print "SquirrelMail defaults to using the whole of the browser window.\n";
    print "This allows you to keep it within a specified frame. The default\n";
    print "is '_top'\n";
    print "\n";
    print "[$WHT$frame_top$NRM]: $WHT";
    $new_frame_top = <STDIN>;
    if ( $new_frame_top eq "\n" ) {
        $new_frame_top = '_top';
    } else {
        $new_frame_top =~ s/[\r\n]//g;
        $new_frame_top =~ s/^\s+$//g;
    }
    return $new_frame_top;
}

# Default link to provider
sub command7 {
    print "Here you can set the link on the top-right of the message list.\n";
    print "If empty, it will not be displayed.\n";
    print "\n";
    print "[$WHT$provider_uri$NRM]: $WHT";
    $new_provider_uri = <STDIN>;
    if ( $new_provider_uri eq "\n" ) {
        $new_provider_uri = '';
    } else {
        $new_provider_uri =~ s/[\r\n]//g;
        $new_provider_uri =~ s/^\s+$//g;
    }
    return $new_provider_uri;
}

sub command8 {
    print "Here you can set the name of the link on the top-right of the message list.\n";
    print "The default is empty (do not display anything).'\n";
    print "\n";
    print "[$WHT$provider_name$NRM]: $WHT";
    $new_provider_name = <STDIN>;
    if ( $new_provider_name eq "\n" ) {
        $new_provider_name = '';
    } else {
        $new_provider_name =~ s/[\r\n]//g;
        $new_provider_name =~ s/^\s+$//g;
        $new_provider_name =~ s/\'/\\'/g;
    }
    return $new_provider_name;
}

####################################################################################

# domain
sub command11 {
    print "The domain name is the suffix at the end of all email addresses.  If\n";
    print "for example, your email address is jdoe\@example.com, then your domain\n";
    print "would be example.com.\n";
    print "\n";
    print "[$WHT$domain$NRM]: $WHT";
    $new_domain = <STDIN>;
    if ( $new_domain eq "\n" ) {
        $new_domain = $domain;
    } else {
        $new_domain =~ s/\s//g;
    }
    return $new_domain;
}

# imapServerAddress
sub command12 {
    print "This is the hostname where your IMAP server can be contacted.\n";
    print "[$WHT$imapServerAddress$NRM]: $WHT";
    $new_imapServerAddress = <STDIN>;
    if ( $new_imapServerAddress eq "\n" ) {
        $new_imapServerAddress = $imapServerAddress;
    } else {
        $new_imapServerAddress =~ s/[\r\n]//g;
    }
    return $new_imapServerAddress;
}

# imapPort
sub command13 {
    print "This is the port that your IMAP server is on.  Usually this is 143.\n";
    print "[$WHT$imapPort$NRM]: $WHT";
    $new_imapPort = <STDIN>;
    if ( $new_imapPort eq "\n" ) {
        $new_imapPort = $imapPort;
    } else {
        $new_imapPort =~ s/[\r\n]//g;
    }
    return $new_imapPort;
}

# useSendmail
sub command14 {
    print "You now need to choose the method that you will use for sending\n";
    print "messages in SquirrelMail.  You can either connect to an SMTP server\n";
    print "or use sendmail directly.\n";
    if ( lc($useSendmail) eq 'true' ) {
        $default_value = "1";
    } else {
        $default_value = "2";
    }
    print "\n";
    print "  1.  Sendmail\n";
    print "  2.  SMTP\n";
    print "Your choice [1/2] [$WHT$default_value$NRM]: $WHT";
    $use_sendmail = <STDIN>;
    if ( ( $use_sendmail =~ /^1\n/i )
        || ( ( $use_sendmail =~ /^\n/ ) && ( $default_value eq "1" ) ) ) {
        $useSendmail = 'true';
        } else {
        $useSendmail = 'false';
        }
    return $useSendmail;
}

# sendmail_path
sub command15 {
    print "Specify where the sendmail executable is located.  Usually /usr/sbin/sendmail\n";
    print "[$WHT$sendmail_path$NRM]: $WHT";
    $new_sendmail_path = <STDIN>;
    if ( $new_sendmail_path eq "\n" ) {
        $new_sendmail_path = $sendmail_path;
    } else {
        $new_sendmail_path =~ s/[\r\n]//g;
    }
    return $new_sendmail_path;
}

# Extra sendmail arguments
sub command_sendmail_args {
    print "Specify additional sendmail program arguments.\n";
    print "\n";
    print "Make sure that arguments are supported by your sendmail program. -f argument \n";
    print "is added automatically by SquirrelMail scripts. Variable defaults to standard\n";
    print "/usr/sbin/sendmail arguments. If you use qmail-inject, nbsmtp or any other \n";
    print "sendmail wrapper, which does not support -i and -t arguments, set variable to\n";
    print "empty string or use arguments suitable for your mailer.\n";
    print "\n";
    print "[$WHT$sendmail_args$NRM]: $WHT";
    $new_sendmail_args = <STDIN>;
    if ( $new_sendmail_args eq "\n" ) {
        $new_sendmail_args = $sendmail_args;
    } else {
        # strip linefeeds and crs.
        $new_sendmail_args =~ s/[\r\n]//g;
    }
    return trim($new_sendmail_args);
}

# smtpServerAddress
sub command16 {
    print "This is the hostname of your SMTP server.\n";
    print "[$WHT$smtpServerAddress$NRM]: $WHT";
    $new_smtpServerAddress = <STDIN>;
    if ( $new_smtpServerAddress eq "\n" ) {
        $new_smtpServerAddress = $smtpServerAddress;
    } else {
        $new_smtpServerAddress =~ s/[\r\n]//g;
    }
    return $new_smtpServerAddress;
}

# smtpPort
sub command17 {
    print "This is the port to connect to for SMTP.  Usually 25.\n";
    print "[$WHT$smtpPort$NRM]: $WHT";
    $new_smtpPort = <STDIN>;
    if ( $new_smtpPort eq "\n" ) {
        $new_smtpPort = $smtpPort;
    } else {
        $new_smtpPort =~ s/[\r\n]//g;
    }
    return $new_smtpPort;
}

# pop before SMTP
sub command18a {
    print "Do you wish to use POP3 before SMTP?  Your server must\n";
    print "support this in order for SquirrelMail to work with it.\n";

    $YesNo = 'n';
    $YesNo = 'y' if ( lc($pop_before_smtp) eq 'true' );

    print "Use POP before SMTP (y/n) [$WHT$YesNo$NRM]: $WHT";

    $new_pop_before_smtp = <STDIN>;
    $new_pop_before_smtp =~ tr/yn//cd;
    if ( $new_pop_before_smtp eq "y" ) {
        $new_pop_before_smtp = "true";
    } elsif ( $new_pop_before_smtp eq "n" ) {
        $new_pop_before_smtp = "false";
    } else {
        $new_pop_before_smtp = $pop_before_smtp;
    }

    # if using POP before SMTP, allow setting of custom POP server address
    if ($new_pop_before_smtp eq "true") {
        print "$NRM\nIf the address of the POP server is not the same as\n";
        print "your SMTP server, you may specify it here. Leave blank (to\n";
        print "clear this, enter only spaces) to use the same address as\n";
        print "your SMTP server.\n";
        print "POP before SMTP server address [$WHT$pop_before_smtp_host$NRM]: $WHT";

        $new_pop_before_smtp_host = <STDIN>;
        if ( $new_pop_before_smtp_host eq "\n" ) {
            $new_pop_before_smtp_host = $pop_before_smtp_host;
        } elsif ($new_pop_before_smtp_host =~ /^\s+$/) {
            $new_pop_before_smtp_host = '';
        } else {
            $new_pop_before_smtp_host =~ s/[\r|\n]//g;
        }
        $pop_before_smtp_host = $new_pop_before_smtp_host;
    }

    return $new_pop_before_smtp;
}

# imap_server_type
sub command19 {
    print "Each IMAP server has its own quirks.  As much as we tried to stick\n";
    print "to standards, it doesn't help much if the IMAP server doesn't follow\n";
    print "the same principles.  We have made some work-arounds for some of\n";
    print "these servers.  If you would like to use them, please select your\n";
    print "IMAP server.  If you do not wish to use these work-arounds, you can\n";
    print "set this to \"other\", and none will be used.\n";
    print $list_supported_imap_servers;
    print "\n";
    print "    other       = Not one of the above servers\n";
    print "\n";
    print "[$WHT$imap_server_type$NRM]: $WHT";
    $new_imap_server_type = <STDIN>;

    if ( $new_imap_server_type eq "\n" ) {
        $new_imap_server_type = $imap_server_type;
    } else {
        $new_imap_server_type =~ s/[\r\n]//g;
    }
    return $new_imap_server_type;
}

# invert_time
sub command110 {
    print "Sometimes the date of messages sent is messed up (off by a few hours\n";
    print "on some machines).  Typically this happens if the system doesn't support\n";
    print "tm_gmtoff.  It will happen only if your time zone is \"negative\".\n";
    print "This most often occurs on Solaris 7 machines in the United States.\n";
    print "By default, this is off.  It should be kept off unless problems surface\n";
    print "about the time that messages are sent.\n";
    print "    no  = Do NOT fix time -- almost always correct\n";
    print "    yes = Fix the time for this system\n";

    $YesNo = 'n';
    $YesNo = 'y' if ( lc($invert_time) eq 'true' );

    print "Fix the time for this system (y/n) [$WHT$YesNo$NRM]: $WHT";

    $new_invert_time = <STDIN>;
    $new_invert_time =~ tr/yn//cd;
    return 'true'  if ( $new_invert_time eq "y" );
    return 'false' if ( $new_invert_time eq "n" );
    return $invert_time;
}

sub command111 {
    print "This is the delimiter that your IMAP server uses to distinguish between\n";
    print "folders.  For example, Cyrus uses '.' as the delimiter and a complete\n";
    print "folder would look like 'INBOX.Friends.Bob', while UW uses '/' and would\n";
    print "look like 'INBOX/Friends/Bob'.  Normally this should be left at 'detect'\n";
    print "but if you are sure you know what delimiter your server uses, you can\n";
    print "specify it here.\n";
    print "\nTo have it autodetect the delimiter, set it to 'detect'.\n\n";
    print "[$WHT$optional_delimiter$NRM]: $WHT";
    $new_optional_delimiter = <STDIN>;

    if ( $new_optional_delimiter eq "\n" ) {
        $new_optional_delimiter = $optional_delimiter;
    } else {
        $new_optional_delimiter =~ s/[\r\n]//g;
    }
    return $new_optional_delimiter;
}
# IMAP authentication type
# Possible values: login, plain, cram-md5, digest-md5
# Now offers to detect supported mechs, assuming server & port are set correctly

sub command112a {
    if ($use_imap_tls ne "0") {
        # 1. Script does not handle TLS.
        # 2. Server does not have to declare all supported authentication mechs when 
        #    STARTTLS is used. Supported mechs are declared only after STARTTLS.
        print "Auto-detection of login methods is unavailable when using TLS or STARTTLS.\n";
    } else {
        print "If you have already set the hostname and port number, I can try to\n";
        print "detect the mechanisms your IMAP server supports.\n";
        print "I will try to detect CRAM-MD5 and DIGEST-MD5 support.  I can't test\n";
        print "for \"login\" or \"plain\" without knowing a username and password.\n";
        print "Auto-detecting is optional - you can safely say \"n\" here.\n";
        print "\nTry to detect supported mechanisms? [y/N]: ";
        $inval=<STDIN>;
        chomp($inval);
        if ($inval =~ /^y\b/i) {
          # Yes, let's try to detect.
          print "Trying to detect IMAP capabilities...\n";
          my $host = $imapServerAddress . ':'. $imapPort;
          print "CRAM-MD5:\t";
          my $tmp = detect_auth_support('IMAP',$host,'CRAM-MD5');
          if (defined($tmp)) {
              if ($tmp eq 'YES') {
                  print "$WHT SUPPORTED$NRM\n";
              } else {
                print "$WHT NOT SUPPORTED$NRM\n";
              }
          } else {
            print $WHT . " ERROR DETECTING$NRM\n";
          }

          print "DIGEST-MD5:\t";
          $tmp = detect_auth_support('IMAP',$host,'DIGEST-MD5');
          if (defined($tmp)) {
              if ($tmp eq 'YES') {
                print "$WHT SUPPORTED$NRM\n";
            } else {
                print "$WHT NOT SUPPORTED$NRM\n";
            }
          } else {
            print $WHT . " ERROR DETECTING$NRM\n";
          }

        }
    }
      print "\nWhat authentication mechanism do you want to use for IMAP connections?\n\n";
      print $WHT . "login" . $NRM . " - Plaintext. If you can do better, you probably should.\n";
      print $WHT . "plain" . $NRM . " - SASL PLAIN. If you need this, you already know it.\n";
      print $WHT . "cram-md5" . $NRM . " - Slightly better than plaintext methods.\n";
      print $WHT . "digest-md5" . $NRM . " - Privacy protection - better than cram-md5.\n";
      print "\n*** YOUR IMAP SERVER MUST SUPPORT THE MECHANISM YOU CHOOSE HERE ***\n";
      print "If you don't understand or are unsure, you probably want \"login\"\n\n";
      print "login, plain, cram-md5, or digest-md5 [$WHT$imap_auth_mech$NRM]: $WHT";
      $inval=<STDIN>;
      chomp($inval);
      if ( ($inval =~ /^cram-md5\b/i) || ($inval =~ /^digest-md5\b/i) || ($inval =~ /^login\b/i) || ($inval =~ /^plain\b/i)) {
        return lc($inval);
      } else {
        # user entered garbage or default value so nothing needs to be set
        return $imap_auth_mech;
      }
}


# SMTP authentication type
# Possible choices: none, login, plain, cram-md5, digest-md5
sub command112b {
    if ($use_smtp_tls ne "0") {
        print "Auto-detection of login methods is unavailable when using TLS or STARTTLS.\n";
    } elsif (eval ("use IO::Socket; 1")) {
        # try loading IO::Socket module
        print "If you have already set the hostname and port number, I can try to\n";
        print "automatically detect some of the mechanisms your SMTP server supports.\n";
        print "Auto-detection is *optional* - you can safely say \"n\" here.\n";
        print "\nTry to detect auth mechanisms? [y/N]: ";
        $inval=<STDIN>;
        chomp($inval);
        if ($inval =~ /^y\b/i) {
            # Yes, let's try to detect.
            print "Trying to detect supported methods (SMTP)...\n";

            # Special case!
            # Check none by trying to relay to junk@microsoft.com
            $host = $smtpServerAddress . ':' . $smtpPort;
            my $sock = IO::Socket::INET->new($host);
            print "Testing none:\t\t$WHT";
            if (!defined($sock)) {
                print " ERROR TESTING\n";
                close $sock;
            } else {
                $got = <$sock>;  # Discard greeting
                print $sock "HELO $domain\r\n";
                $got = <$sock>;  # Discard
                print $sock "MAIL FROM:<tester\@squirrelmail.org>\r\n";
                $got = <$sock>;  # Discard
                print $sock "RCPT TO:<junk\@microsoft.com\r\n";
                $got = <$sock>;  # This is the important line
                if ($got =~ /^250\b/) {  # SMTP will relay without auth
                    print "SUPPORTED$NRM\n";
                } else {
                  print "NOT SUPPORTED$NRM\n";
                }
                print $sock "RSET\r\n";
                print $sock "QUIT\r\n";
                close $sock;
            }

            # Try login (SquirrelMail default)
            print "Testing login:\t\t";
            $tmp=detect_auth_support('SMTP',$host,'LOGIN');
            if (defined($tmp)) {
                if ($tmp eq 'YES') {
                    print $WHT . "SUPPORTED$NRM\n";
                } else {
                    print $WHT . "NOT SUPPORTED$NRM\n";
                }
              } else {
                  print $WHT . "ERROR DETECTING$NRM\n";
              }

            # Try plain
            print "Testing plain:\t\t";
            $tmp=detect_auth_support('SMTP',$host,'PLAIN');
            if (defined($tmp)) {
                if ($tmp eq 'YES') {
                    print $WHT . "SUPPORTED$NRM\n";
                } else {
                    print $WHT . "NOT SUPPORTED$NRM\n";
                }
              } else {
                  print $WHT . "ERROR DETECTING$NRM\n";
              }

            # Try CRAM-MD5
            print "Testing CRAM-MD5:\t";
            $tmp=detect_auth_support('SMTP',$host,'CRAM-MD5');
            if (defined($tmp)) {
                if ($tmp eq 'YES') {
                    print $WHT . "SUPPORTED$NRM\n";
                } else {
                    print $WHT . "NOT SUPPORTED$NRM\n";
                }
              } else {
                  print $WHT . "ERROR DETECTING$NRM\n";
            }


            print "Testing DIGEST-MD5:\t";
            $tmp=detect_auth_support('SMTP',$host,'DIGEST-MD5');
            if (defined($tmp)) {
                if ($tmp eq 'YES') {
                    print $WHT . "SUPPORTED$NRM\n";
                } else {
                    print $WHT . "NOT SUPPORTED$NRM\n";
                }
              } else {
                  print $WHT . "ERROR DETECTING$NRM\n";
            }
        }
    }
    print "\nWhat authentication mechanism do you want to use for SMTP connections?\n";
    print $WHT . "none" . $NRM . " - Your SMTP server does not require authorization.\n";
    print $WHT . "login" . $NRM . " - Plaintext. If you can do better, you probably should.\n";
    print $WHT . "plain" . $NRM . " - SASL PLAIN. Plaintext. If you can do better, you probably should.\n";
    print $WHT . "cram-md5" . $NRM . " - Slightly better than plaintext.\n";
    print $WHT . "digest-md5" . $NRM . " - Privacy protection - better than cram-md5.\n";
    print $WHT . "\n*** YOUR SMTP SERVER MUST SUPPORT THE MECHANISM YOU CHOOSE HERE ***\n" . $NRM;
    print "If you don't understand or are unsure, you probably want \"none\"\n\n";
    print "none, login, plain, cram-md5, or digest-md5 [$WHT$smtp_auth_mech$NRM]: $WHT";
    $inval=<STDIN>;
    chomp($inval);
    if ($inval =~ /^none\b/i) {
        # remove sitewide smtp authentication information
        $smtp_sitewide_user = '';
        $smtp_sitewide_pass = '';
        # SMTP doesn't necessarily require logins
        return "none";
    } elsif ( ($inval =~ /^cram-md5\b/i) || ($inval =~ /^digest-md5\b/i) ||
              ($inval =~ /^login\b/i) || ($inval =~/^plain\b/i)) {
        command_smtp_sitewide_userpass($inval);
        return lc($inval);
    } elsif (trim($inval) eq '') {
        # user selected default value
        command_smtp_sitewide_userpass($smtp_auth_mech);
        return $smtp_auth_mech;
    } else {
        # user entered garbage 
        return $smtp_auth_mech;
    }
}

sub command_smtp_sitewide_userpass($) {
    # get first function argument
    my $auth_mech = shift(@_);
    my $default, $tmp;
    $auth_mech = lc(trim($auth_mech));
    if ($auth_mech eq 'none') {
        return;
    }
    print "SMTP authentication uses IMAP username and password by default.\n";
    print "\n";
    print "Would you like to use other login and password for all SquirrelMail \n";
    print "SMTP connections?";
    if ($smtp_sitewide_user ne '') {
        $default = 'y';
        print " [Y/n]:";
    } else {
        $default = 'n';
        print " [y/N]:";
    }
    $tmp=<STDIN>;
    $tmp = trim($tmp);
    
    if ($tmp eq '') {
        $tmp = $default;
    } else {
        $tmp = lc($tmp);
    }

    if ($tmp eq 'n') {
        $smtp_sitewide_user = '';
        $smtp_sitewide_pass = '';
    } elsif ($tmp eq 'y') {
        print "Enter username [$smtp_sitewide_user]:";
        my $new_user = <STDIN>;
        $new_user = trim($new_user);
        if ($new_user ne '') {
            $smtp_sitewide_user = $new_user;
        }
        if ($smtp_sitewide_user ne '') {
            print "If you don't enter any password, current sitewide password will be used.\n";
            print "If you enter space, password will be set to empty string.\n";
            print "Enter password:";
            my $new_pass = <STDIN>;
            if ($new_pass ne "\n") {
                $smtp_sitewide_pass = trim($new_pass);
            }
        } else {
            print "Invalid input. You must set username used for SMTP authentication.\n";
            print "Click enter to continue\n";
            $tmp = <STDIN>;
        }
    } else {
        print "Invalid input\n";
        print "Click enter to continue\n";
        $tmp = <STDIN>;
    }
}

# Sub adds information about SMTP authentication type to menu
sub display_smtp_sitewide_userpass() {
    my $ret = '';
    if ($smtp_auth_mech ne 'none') {
        if ($smtp_sitewide_user ne '') {
            $ret = ' (with custom username and password)';
        } else {
            $ret = ' (with IMAP username and password)';
        }
    }
    return $ret;
}

# TLS
# This sub is reused for IMAP and SMTP
# Args: service name, default value
sub command_use_tls {
    my($default_val,$service,$inval);
    $service=$_[0];
    $default_val=$_[1];
    print "TLS (Transport Layer Security) encrypts the traffic between server and client.\n";
    print "STARTTLS extensions allow to start encryption on existing plain text connection.\n";
    print "These options add specific PHP and IMAP server configuration requirements.\n";
    print "See SquirrelMail documentation about connection security.\n";
    print "\n";
    print "If your " . $service . " server is localhost, you can safely disable this.\n";
    print "If it is remote, you may wish to seriously consider enabling this.\n";
    $valid_input=0;
    while ($valid_input eq 0) {
        print "\nSelect connection security model:\n";
        print " 0 - Use plain text connection\n";
        print " 1 - Use TLS connection\n";
        print " 2 - Use STARTTLS extension\n";
        print "Select [$default_val]: ";
        $inval=<STDIN>;
        $inval=trim($inval);
        if ($inval =~ /^[012]$/ || $inval eq '') {
            $valid_input = 1;
        }
    }
    if ($inval ne '') {$default_val = $inval};
    return $default_val;
}

# This sub is used to display human readable text for 
# $use_imap_tls and $use_smtp_tls values in conf.pl menu
sub display_use_tls($) {
    my $val = shift(@_);
    my $ret = 'disabled';
    if ($val eq '2') {
        $ret = 'STARTTLS';
    } elsif ($val eq '1') {
        $ret = 'TLS';
    }
    return $ret;
}

# $encode_header_key
sub command114 {
    print "This encryption key allows the hiding of SquirrelMail Received:\n";
    print "headers in outbound messages.  SquirrelMail uses the encryption\n";
    print "key to encode the username, remote address, and proxied address\n";
    print "and then stores that encoded information in X-Squirrel-* headers.\n";
    print "\n";
    print "Warning: the encryption function used to accomplish this is not\n";
    print "bulletproof. When used with a static encryption key as it is here,\n";
    print "it provides only minimal security and the encoded user information\n";
    print "in the X-Squirrel-* headers can be decoded quickly by a skilled\n";
    print "attacker.\n";
    print "\n";
    print "When you need to inspect an email sent from your system with the\n";
    print "X-Squirrel-* headers, you can decode the user information therein\n";
    print "by using the decrypt_headers.php script found in the SquirrelMail\n";
    print "contrib/ directory. You'll need the encryption key that you\n";
    print "defined here when doing so.\n";
    print "\n";
    print "Enter encryption key: ";
    $new_encode_header_key = <STDIN>;
    if ( $new_encode_header_key eq "\n" ) {
        $new_encode_header_key = $encode_header_key;
    } else {
        $new_encode_header_key =~ s/[\r\n]//g;
    }
    return $new_encode_header_key;
}

# MOTD
sub command71 {
    print "\nYou can now create the welcome message that is displayed\n";
    print "every time a user logs on.  You can use HTML or just plain\n";
    print
"text.  If you do not wish to have one, just make it blank.\n\n(Type @ on a blank line to exit)\n";

    $new_motd = "";
    do {
        print "] ";
        $line = <STDIN>;
        $line =~ s/[\r\n]//g;
        if ( $line ne "@" ) {
            $line =~ s/  /\&nbsp;\&nbsp;/g;
            $line =~ s/\t/\&nbsp;\&nbsp;\&nbsp;\&nbsp;/g;
            $line =~ s/$/ /;
            $line =~ s/\"/\\\"/g;

            $new_motd = $new_motd . $line;
        }
    } while ( $line ne "@" );
    return $new_motd;
}

################# PLUGINS ###################

sub command81 {
    $command =~ s/[\s\n\r]*//g;
    if ( $command > 0 ) {
        $command = $command - 1;
        if ( $command <= $#plugins ) {
            @newplugins = ();
            $ct         = 0;
            while ( $ct <= $#plugins ) {
                if ( $ct != $command ) {
                    @newplugins = ( @newplugins, $plugins[$ct] );
                }
                $ct++;
            }
            @plugins = @newplugins;
        } elsif ( $command <= $#plugins + $#unused_plugins + 1 ) {
            $num        = $command - $#plugins - 1;
            @newplugins = @plugins;
            $ct         = 0;
            while ( $ct <= $#unused_plugins ) {
                if ( $ct == $num ) {
                    @newplugins = ( @newplugins, $unused_plugins[$ct] );
                }
                $ct++;
            }
            @plugins = @newplugins;
        }
    }
    return @plugins;
}

# disable_plugins_user
sub command82 {
    print "When all active plugins are disabled, they can be disabled only\n";
    print "for the one user named here.  If left blank, plugins will be\n";
    print "disabled for ALL users.  This setting has no effect if plugins\n";
    print "are not disabled.\n";
    print "\n";
    print "This must be the exact IMAP login name for the desired user.\n";
    print "\n";
    print "[$WHT$disable_plugins_user$NRM]: $WHT";
    $new_disable_plugins_user = <STDIN>;
    if ( $new_disable_plugins_user eq "\n" ) {
        $new_disable_plugins_user = $disable_plugins_user;
    } else {
        $new_disable_plugins_user =~ s/[\r\n]//g;
    }
    return $new_disable_plugins_user;
}

################# FOLDERS ###################

# default_folder_prefix
sub command21 {
    print "Some IMAP servers (UW, for example) store mail and folders in\n";
    print "your user space in a separate subdirectory.  This is where you\n";
    print "specify what that directory is.\n";
    print "\n";
    print "EXAMPLE:  mail/";
    print "\n";
    print "NOTE:  If you use Cyrus, or some server that would not use this\n";
    print "       option, you must set this to 'none'.\n";
    print "\n";
    print "[$WHT$default_folder_prefix$NRM]: $WHT";
    $new_default_folder_prefix = <STDIN>;

    if ( $new_default_folder_prefix eq "\n" ) {
        $new_default_folder_prefix = $default_folder_prefix;
    } else {
        $new_default_folder_prefix =~ s/[\r\n]//g;
    }
    if ( ( $new_default_folder_prefix =~ /^\s*$/ ) || ( $new_default_folder_prefix =~ m/^none$/i ) ) {
        $new_default_folder_prefix = "";
    } else {
        # add the trailing delimiter only if we know what the server is.
        if (($imap_server_type eq 'cyrus' and
                  $optional_delimiter eq 'detect') or
                 ($imap_server_type eq 'courier' and
                  $optional_delimiter eq 'detect')) {
           $new_default_folder_prefix =~ s/\.*$/\./;
        } elsif ($imap_server_type eq 'uw' and
                 $optional_delimiter eq 'detect') {
           $new_default_folder_prefix =~ s/\/*$/\//;
        }
    }
    return $new_default_folder_prefix;
}

# Show Folder Prefix
sub command22 {
    print "It is possible to set up the default folder prefix as a user\n";
    print "specific option, where each user can specify what their mail\n";
    print "folder is.  If you set this to false, they will never see the\n";
    print "option, but if it is true, this option will appear in the\n";
    print "'options' section.\n";
    print "\n";
    print "NOTE:  You set the default folder prefix in option '1' of this\n";
    print "       section.  That will be the default if the user doesn't\n";
    print "       specify anything different.\n";
    print "\n";

    if ( lc($show_prefix_option) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "\n";
    print "Show option (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $show_prefix_option = 'true';
    } else {
        $show_prefix_option = 'false';
    }
    return $show_prefix_option;
}

# Trash Folder
sub command23a {
    print "You can now specify where the default trash folder is located.\n";
    print "On servers where you do not want this, you can set it to anything\n";
    print "and set option 6 to false.\n";
    print "\n";
    print "This is relative to where the rest of your email is kept.  You do\n";
    print "not need to worry about their mail directory.  If this folder\n";
    print "would be ~/mail/trash on the filesystem, you only need to specify\n";
    print "that this is 'trash', and be sure to put 'mail/' in option 1.\n";
    print "\n";

    print "[$WHT$trash_folder$NRM]: $WHT";
    $new_trash_folder = <STDIN>;
    if ( $new_trash_folder eq "\n" ) {
        $new_trash_folder = $trash_folder;
    } else {
        if (check_imap_folder($new_trash_folder)) {
            $new_trash_folder =~ s/[\r\n]//g;
        } else {
            $new_trash_folder = $trash_folder;
        }
    }
    return $new_trash_folder;
}

# Sent Folder
sub command23b {
    print "This is where messages that are sent will be stored.  SquirrelMail\n";
    print "by default puts a copy of all outgoing messages in this folder.\n";
    print "\n";
    print "This is relative to where the rest of your email is kept.  You do\n";
    print "not need to worry about their mail directory.  If this folder\n";
    print "would be ~/mail/sent on the filesystem, you only need to specify\n";
    print "that this is 'sent', and be sure to put 'mail/' in option 1.\n";
    print "\n";

    print "[$WHT$sent_folder$NRM]: $WHT";
    $new_sent_folder = <STDIN>;
    if ( $new_sent_folder eq "\n" ) {
        $new_sent_folder = $sent_folder;
    } else {
        if (check_imap_folder($new_sent_folder)) {
            $new_sent_folder =~ s/[\r\n]//g;
        } else {
            $new_sent_folder = $sent_folder;
        }
    }
    return $new_sent_folder;
}

# Draft Folder
sub command23c {
    print "You can now specify where the default draft folder is located.\n";
    print "On servers where you do not want this, you can set it to anything\n";
    print "and set option 9 to false.\n";
    print "\n";
    print "This is relative to where the rest of your email is kept.  You do\n";
    print "not need to worry about their mail directory.  If this folder\n";
    print "would be ~/mail/drafts on the filesystem, you only need to specify\n";
    print "that this is 'drafts', and be sure to put 'mail/' in option 1.\n";
    print "\n";

    print "[$WHT$draft_folder$NRM]: $WHT";
    $new_draft_folder = <STDIN>;
    if ( $new_draft_folder eq "\n" ) {
        $new_draft_folder = $draft_folder;
    } else {
        if (check_imap_folder($new_draft_folder)) {
            $new_draft_folder =~ s/[\r\n]//g;
        } else {
            $new_draft_folder = $draft_folder;
        }
    }
    return $new_draft_folder;
}

# default move to trash
sub command24a {
    print "By default, should messages get moved to the trash folder?  You\n";
    print "can specify the default trash folder in option 3.  If this is set\n";
    print "to false, messages will get deleted immediately without moving\n";
    print "to the trash folder.\n";
    print "\n";
    print "Trash folder is currently: $trash_folder\n";
    print "\n";

    if ( lc($default_move_to_trash) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "By default, move to trash (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $default_move_to_trash = 'true';
    } else {
        $default_move_to_trash = 'false';
    }
    return $default_move_to_trash;
}

# default move to sent (save sent messages)
sub command24b {
    print "By default, should copies of outgoing messages get saved in the\n";
    print "sent folder?  You can specify the default sent folder in option 4.\n";
    print "If this is set to false, messages will get sent and no copy will\n";
    print "be made.\n";
    print "\n";
    print "Sent folder is currently: $sent_folder\n";
    print "\n";

    if ( lc($default_move_to_sent) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "By default, save sent messages (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $default_move_to_sent = 'true';
    } else {
        $default_move_to_sent = 'false';
    }
    return $default_move_to_sent;
}

# default save as draft
sub command24c {
    print "By default, should the save to draft option be shown? You can\n";
    print "specify the default drafts folder in option 5. If this is set\n";
    print "to false, users will not be shown the save to draft option.\n";
    print "\n";
    print "Drafts folder is currently: $draft_folder\n";
    print "\n";

    if ( lc($default_save_as_draft) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "By default, save as draft (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $default_save_as_draft = 'true';
    } else {
        $default_save_as_draft = 'false';
    }
    return $default_save_as_draft;
}

# List special folders first
sub command27 {
    print "SquirrelMail has what we call 'special folders' that are not\n";
    print "manipulated and viewed like normal folders.  Some examples of\n";
    print "these folders would be INBOX, Trash, Sent, etc.  This option\n";
    print "Simply asks if you want these folders listed first in the folder\n";
    print "listing.\n";
    print "\n";

    if ( lc($list_special_folders_first) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "\n";
    print "List first (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $list_special_folders_first = 'true';
    } else {
        $list_special_folders_first = 'false';
    }
    return $list_special_folders_first;
}

# Show special folders color
sub command28 {
    print "SquirrelMail has what we call 'special folders' that are not\n";
    print "manipulated and viewed like normal folders.  Some examples of\n";
    print "these folders would be INBOX, Trash, Sent, etc.  This option\n";
    print "wants to know if we should display special folders in a\n";
    print "color than the other folders.\n";
    print "\n";

    if ( lc($use_special_folder_color) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "\n";
    print "Show color (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $use_special_folder_color = 'true';
    } else {
        $use_special_folder_color = 'false';
    }
    return $use_special_folder_color;
}

# Auto expunge
sub command29 {
    print "The way that IMAP handles deleting messages is as follows.  You\n";
    print "mark the message as deleted, and then to 'really' delete it, you\n";
    print "expunge it.  This option asks if you want to just have messages\n";
    print "marked as deleted, or if you want SquirrelMail to expunge the \n";
    print "messages too.\n";
    print "\n";

    if ( lc($auto_expunge) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Auto expunge (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $auto_expunge = 'true';
    } else {
        $auto_expunge = 'false';
    }
    return $auto_expunge;
}

# Default sub of inbox
sub command210 {
    print "Some IMAP servers (Cyrus) have all folders as subfolders of INBOX.\n";
    print "This can cause some confusion in folder creation for users when\n";
    print "they try to create folders and don't put it as a subfolder of INBOX\n";
    print "and get permission errors.  This option asks if you want folders\n";
    print "to be subfolders of INBOX by default.\n";
    print "\n";

    if ( lc($default_sub_of_inbox) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Default sub of INBOX (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $default_sub_of_inbox = 'true';
    } else {
        $default_sub_of_inbox = 'false';
    }
    return $default_sub_of_inbox;
}

# Show contain subfolder option
sub command211 {
    print "Some IMAP servers (UW) make it so that there are two types of\n";
    print "folders.  Those that contain messages, and those that contain\n";
    print "subfolders.  If this is the case for your server, set this to\n";
    print "true, and it will ask the user whether the folder they are\n";
    print "creating contains subfolders or messages.\n";
    print "\n";

    if ( lc($show_contain_subfolders_option) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Show option (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $show_contain_subfolders_option = 'true';
    } else {
        $show_contain_subfolders_option = 'false';
    }
    return $show_contain_subfolders_option;
}

# Default Unseen Notify
sub command212 {
    print "This option specifies where the users will receive notification\n";
    print "about unseen messages by default.  This is of course an option that\n";
    print "can be changed on a user level.\n";
    print "  1 = No notification\n";
    print "  2 = Only on the INBOX\n";
    print "  3 = On all folders\n";
    print "\n";

    print "Which one should be default (1,2,3)? [$WHT$default_unseen_notify$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( $new_show =~ /^[123]\n/i ) {
        $default_unseen_notify = $new_show;
    }
    $default_unseen_notify =~ s/[\r\n]//g;
    return $default_unseen_notify;
}

# Default Unseen Type
sub command213 {
    print "Here you can define the default way that unseen messages will be displayed\n";
    print "to the user in the folder listing on the left side.\n";
    print "  1 = Only unseen messages   (4)\n";
    print "  2 = Unseen and Total messages  (4/27)\n";
    print "\n";

    print "Which one should be default (1,2)? [$WHT$default_unseen_type$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( $new_show =~ /^[12]\n/i ) {
        $default_unseen_type = $new_show;
    }
    $default_unseen_type =~ s/[\r\n]//g;
    return $default_unseen_type;
}

# Auto create special folders
sub command214 {
    print "Would you like the Sent, Trash, and Drafts folders to be created\n";
    print "automatically print for you when a user logs in?  If the user\n";
    print "accidentally deletes their special folders, this option will\n";
    print "automatically create it again for them.\n";
    print "\n";

    if ( lc($auto_create_special) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Auto create special folders? (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $auto_create_special = 'true';
    } else {
        $auto_create_special = 'false';
    }
    return $auto_create_special;
}

# Automatically delete folders
sub command215 {
    if ( $imap_server_type eq "uw" ) {
        print "UW IMAP servers will not allow folders containing mail to also contain folders.\n";
        print "Deleting folders will bypass the trash folder and be immediately deleted\n\n";
        print "If this is not the correct value for your server,\n";
        print "please use option D on the Main Menu to configure your server correctly.\n\n";
        print "Press enter to continue...\n";
        $new_delete = <STDIN>;
        $delete_folder = 'true';
    } else {
        if ( $imap_server_type eq "courier" ) {
            print "Courier (or Courier-IMAP) IMAP servers may not support ";
            print "subfolders of Trash. \n";
            print "Specifically, if Courier is set to always move messages to Trash, \n";
            print "Trash will be treated by Courier as a special folder that does not \n";
            print "allow subfolders. \n\n";
            print "Please verify your Courier configuration, and test folder deletion \n";
            print "when changing this setting.\n\n";
        }

        print "Are subfolders of the Trash supported by your IMAP server?\n";
        print "If so, should deleted folders be sent to Trash?\n";
        print "If not, say no (deleted folders should not be sent to Trash)\n\n";
        # reversal of logic.
        # question was: Should folders be automatically deleted instead of sent to trash..
        # we've changed the question to make it more clear,
        # and are here handling that to avoid changing the answers..
        if ( lc($delete_folder) eq 'true' ) {
            $default_value = "n";
        } else {
            $default_value = "y";
        }
        print "Send deleted folders to Trash? (y/n) [$WHT$default_value$NRM]: $WHT";
        $new_delete = <STDIN>;
        if ( ( $new_delete =~ /^y\n/i ) || ( ( $new_delete =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
            $delete_folder = 'false';
        } else {
            $delete_folder = 'true';
        }
    }
    return $delete_folder;
}

#noselect fix
sub command216 {
    print "Some IMAP servers allow subfolders to exist even if the parent\n";
    print "folders do not. This fixes some problems with the folder list\n";
    print "when this is the case, causing the /NoSelect folders to be displayed\n";
    print "\n";

    if ( lc($noselect_fix_enable) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "enable noselect fix? (y/n) [$WHT$noselect_fix_enable$NRM]: $WHT";
    $noselect_fix_enable = <STDIN>;
    if ( ( $noselect_fix_enable =~ /^y\n/i ) || ( ( $noselect_fix_enable =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $noselect_fix_enable = 'true';
    } else {
        $noselect_fix_enable = 'false';
    }
    return $noselect_fix_enable;
}
############# GENERAL OPTIONS #####################

# Data directory
sub command33a {
    print "Specify the location for your data directory.\n";
    print "You need to create this directory yourself.\n";
    print "The path name can be absolute or relative (to the config directory).\n";
    print "Here are two examples:\n";
    print "  Absolute:    /var/local/squirrelmail/data/\n";
    print "  Relative:    ../data/\n";
    print "Relative paths to directories outside of the SquirrelMail distribution\n";
    print "will be converted to their absolute path equivalents in config.php.\n\n";
    print "Note: There are potential security risks with having a writeable directory\n";
    print "under the web server's root directory (ex: /home/httpd/html).\n";
    print "For this reason, it is recommended to put the data directory\n";
    print "in an alternate location of your choice. \n";
    print "\n";

    print "[$WHT$data_dir$NRM]: $WHT";
    $new_data_dir = <STDIN>;
    if ( $new_data_dir eq "\n" ) {
        $new_data_dir = $data_dir;
    } else {
        $new_data_dir =~ s/[\r\n]//g;
    }
    if ( $new_data_dir =~ /^\s*$/ ) {
        $new_data_dir = "";
    } else {
        $new_data_dir =~ s/\/*$//g;
        $new_data_dir =~ s/$/\//g;
    }
    return $new_data_dir;
}

# Attachment directory
sub command33b {
    print "Path to directory used for storing attachments while a mail is\n";
    print "being composed. The path name can be absolute or relative (to the\n";
    print "config directory). Here are two examples:\n";
    print "  Absolute:    /var/local/squirrelmail/attach/\n";
    print "  Relative:    ../attach/\n";
    print "Relative paths to directories outside of the SquirrelMail distribution\n";
    print "will be converted to their absolute path equivalents in config.php.\n\n";
    print "Note:  There are a few security considerations regarding this\n";
    print "directory:\n";
    print "  1.  It should have the permission 733 (rwx-wx-wx) to make it\n";
    print "      impossible for a random person with access to the webserver\n";
    print "      to list files in this directory.  Confidential data might\n";
    print "      be laying around in there.\n";
    print "      Depending on your user:group assignments, 730 (rwx-wx---)\n";
    print "      may be possible, and more secure (e.g. root:apache)\n";
    print "  2.  Since the webserver is not able to list the files in the\n";
    print "      content is also impossible for the webserver to delete files\n";
    print "      lying around there for too long.\n";
    print "  3.  It should probably be another directory than the data\n";
    print "      directory specified in option 3.\n";
    print "\n";

    print "[$WHT$attachment_dir$NRM]: $WHT";
    $new_attachment_dir = <STDIN>;
    if ( $new_attachment_dir eq "\n" ) {
        $new_attachment_dir = $attachment_dir;
    } else {
        $new_attachment_dir =~ s/[\r\n]//g;
    }
    if ( $new_attachment_dir =~ /^\s*$/ ) {
        $new_attachment_dir = "";
    } else {
        $new_attachment_dir =~ s/\/*$//g;
        $new_attachment_dir =~ s/$/\//g;
    }
    return $new_attachment_dir;
}

sub command33c {
    print "The directory hash level setting allows you to configure the level\n";
    print "of hashing that SquirrelMail employs in your data and attachment\n";
    print "directories. This value must be an integer ranging from 0 to 4.\n";
    print "When this value is set to 0, SquirrelMail will simply store all\n";
    print "files as normal in the data and attachment directories. However,\n";
    print "when set to a value from 1 to 4, a simple hashing scheme will be\n";
    print "used to organize the files in this directory. In short, the crc32\n";
    print "value for a username will be computed. Then, up to the first 4\n";
    print "digits of the hash, as set by this configuration value, will be\n";
    print "used to directory hash the files for that user in the data and\n";
    print "attachment directory. This allows for better performance on\n";
    print "servers with larger numbers of users.\n";
    print "\n";

    print "[$WHT$dir_hash_level$NRM]: $WHT";
    $new_dir_hash_level = <STDIN>;
    if ( $new_dir_hash_level eq "\n" ) {
        $new_dir_hash_level = $dir_hash_level;
    } else {
        $new_dir_hash_level =~ s/[\r\n]//g;
    }
    if ( ( int($new_dir_hash_level) < 0 )
        || ( int($new_dir_hash_level) > 4 )
        || !( int($new_dir_hash_level) eq $new_dir_hash_level ) ) {
        print "Invalid Directory Hash Level.\n";
        print "Value must be an integer ranging from 0 to 4\n";
        print "Hit enter to continue.\n";
        $enter_key = <STDIN>;

        $new_dir_hash_level = $dir_hash_level;
        }

    return $new_dir_hash_level;
}

sub command35 {
    print "This is the default size (in pixels) of the left folder list.\n";
    print "Default is 200, but you can set it to whatever you wish.  This\n";
    print "is a user preference, so this will only show up as their default.\n";
    print "\n";
    print "[$WHT$default_left_size$NRM]: $WHT";
    $new_default_left_size = <STDIN>;
    if ( $new_default_left_size eq "\n" ) {
        $new_default_left_size = $default_left_size;
    } else {
        $new_default_left_size =~ s/[\r\n]//g;
    }
    return $new_default_left_size;
}

sub command36 {
    print "Some IMAP servers only have lowercase letters in the usernames\n";
    print "but they still allow people with uppercase to log in.  This\n";
    print "causes a problem with the user's preference files.  This option\n";
    print "transparently changes all usernames to lowercase.";
    print "\n";

    if ( lc($force_username_lowercase) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Convert usernames to lowercase (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        return 'true';
    }
    return 'false';
}

sub command37 {
    print "";
    print "\n";

    if ( lc($default_use_priority) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }

    print "Allow users to specify priority of outgoing mail (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        return 'true';
    }
    return 'false';
}

sub command38 {
    print "";
    print "\n";

    if ( lc($hide_sm_attributions) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }

    print "Hide SM attributions (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        return 'true';
    }
    return 'false';
}

sub command39 {
    print "";
    print "\n";

    if ( lc($default_use_mdn) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }

    print "Enable support for read/delivery receipt support (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        return 'true';
    }
    return 'false';
}


sub command310 {
    print "  In loosely managed environments, you may want to allow users
  to edit their full name and email address. In strictly managed
  environments, you may want to force users to use the name
  and email address assigned to them.

  'y' - allow a user to edit their full name and email address,
  'n' - users must use the assigned values.

  ";

    if ( lc($edit_identity) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow editing of user's identity? (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_edit = <STDIN>;
    if ( ( $new_edit =~ /^y\n/i ) || ( ( $new_edit =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $edit_identity = 'true';
        $edit_name = 'true';
        $edit_reply_to = 'true';
        $hide_auth_header = command311c();
    } else {
        $edit_identity = 'false';
        $edit_name = command311();
        $edit_reply_to = command311b();
        $hide_auth_header = command311c();
    }
    return $edit_identity;
}

sub command311 {
    print "$NRM";
    print "\n  Given that users are not allowed to modify their
  email address, can they edit their full name?

  ";

    if ( lc($edit_name) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow the user to edit their full name? (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_edit = <STDIN>;
    if ( ( $new_edit =~ /^y\n/i ) || ( ( $new_edit =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $edit_name = 'true';
    } else {
        $edit_name = 'false';
    }
    return $edit_name;
}

sub command311b {
    print "$NRM";
    print "\n  Given that users are not allowed to modify their
  email address, can they edit their reply-to address?

  ";

    if ( lc($edit_reply_to) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow the user to edit their reply-to address? (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_edit = <STDIN>;
    if ( ( $new_edit =~ /^y\n/i ) || ( ( $new_edit =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $edit_reply_to = 'true';
    } else {
        $edit_reply_to = 'false';
    }
    return $edit_reply_to;
}

sub command311c {
    print "$NRM";
    print "\n  SquirrelMail adds username information to every outgoing email in
  order to prevent possible sender forging by users that are allowed
  to change their email and/or full name.

  You can remove user information from this header (y) if you think
  that it violates privacy or security.

  Note: If users are allowed to change their email addresses, this
  setting will make it difficult to determine who sent what where.
  Use at your own risk.

  Note: If you have defined a header encryption key in your SMTP or
  Sendmail settings (see the \"Server Settings\" option page), this
  setting is ignored because all user information in outgoing messages
  is encoded.

  ";

    if ( lc($hide_auth_header) eq "true" ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Remove username from email headers? (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_header = <STDIN>;
    if ( ( $new_header =~ /^y\n/i ) || ( ( $new_header =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $hide_auth_header = "true";
    } else {
        $hide_auth_header = "false";
    }
    return $hide_auth_header;
}

sub command312 {
    print "This option allows you to disable server side thread sorting if your server \n";
    print "declares THREAD support, but you don't want to provide threading options \n";
    print "to end users or THREAD extension is broken or extension does not work with \n";
    print "options used by SquirrelMail. Option is not used, if THREAD extension is \n";
    print "not declared in IMAP CAPABILITY.\n";
    print "\n";

    if ( lc($disable_thread_sort) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Disable server side thread sorting? (y/n) [$WHT$default_value$NRM]: $WHT";
    $disable_thread_sort = <STDIN>;
    if ( ( $disable_thread_sort =~ /^y\n/i ) || ( ( $disable_thread_sort =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $disable_thread_sort = 'true';
    } else {
        $disable_thread_sort = 'false';
    }
    return $disable_thread_sort;
}

sub command313 {
    print "This option allows you to disable server side sorting if your server declares \n";
    print "SORT support, but SORT extension is broken or does not work with options \n";
    print "used by SquirrelMail. Option is not used, if SORT extension is not declared \n";
    print "in IMAP CAPABILITY.\n";
    print "\n";
    print "It is strongly recommended to keep server side sorting enabled, if your ";
    print "IMAP server supports it.";
    print "\n";

    if ( lc($disable_server_sort) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Disable server-side sorting? (y/n) [$WHT$default_value$NRM]: $WHT";
    $disable_server_sort = <STDIN>;
    if ( ( $disable_server_sort =~ /^y\n/i ) || ( ( $disable_server_sort =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $disable_server_sort = 'true';
    } else {
        $disable_server_sort = 'false';
    }
    return $disable_server_sort;
}

sub command314 {
    print "This option allows you to choose if SM uses charset search\n";
    print "Your IMAP server must support the SEARCH CHARSET command for this to work\n";
    print "\n";

    if ( lc($allow_charset_search) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow charset searching? (y/n) [$WHT$default_value$NRM]: $WHT";
    $allow_charset_search = <STDIN>;
    if ( ( $allow_charset_search =~ /^y\n/i ) || ( ( $allow_charset_search =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $allow_charset_search = 'true';
    } else {
        $allow_charset_search = 'false';
    }
    return $allow_charset_search;
}

# command315 (UID support) obsoleted.

# advanced search option
sub command316 {
    print "This option allows you to control the use of advanced search form.\n";
    print "  0 = enable basic search only\n";
    print "  1 = enable advanced search only\n";
    print "  2 = enable both\n";
    print "\n";

    print "Allowed search (0,1,2)? [$WHT$allow_advanced_search$NRM]: $WHT";
    $new_allow_advanced_search = <STDIN>;
    if ( $new_allow_advanced_search =~ /^[012]\n/i ) {
        $allow_advanced_search = $new_allow_advanced_search;
    }
    $allow_advanced_search =~ s/[\r\n]//g;
    return $allow_advanced_search;
}


sub command317 {
    print "This option allows you to change the name of the PHP session used\n";
    print "by SquirrelMail.  Unless you know what you are doing, you probably\n";
    print "don't need or want to change this from the default of SQMSESSID.\n";
    print "[$WHT$session_name$NRM]: $WHT";
    $new_session_name = <STDIN>;
    chomp($new_session_name);
    if ( $new_session_name eq "" ) {
        $new_session_name = $session_name;
    }
    return $new_session_name;
}

# time zone config (since 1.5.1)
sub command318 {
    print "This option allows you to control the use of time zones.\n";
    print "  0 = (default) standard, GNU C time zone names\n";
    print "  1 = strict, generic time zone codes with offsets\n";
    print "  2 = custom, GNU C time zones loaded from config/timezones.php\n";
    print "  3 = custom strict, generic time zone codes with offsets loaded \n";
    print "      from config/timezones.php\n";
    print "See SquirrelMail documentation about format of config/timezones.php file.\n";
    print "\n";

    print "Desired time zone configuration (0,1,2,3)? [$WHT$time_zone_type$NRM]: $WHT";
    $new_time_zone_type = <STDIN>;
    if ( $new_time_zone_type =~ /^[0123]\n/i ) {
        $time_zone_type = $new_time_zone_type;
    } else {
        print "\nInvalid configuration value.\n";
        print "\nPress enter to continue...";
        $tmp = <STDIN>;
    }
    $time_zone_type =~ s/[\r\n]//g;
    return $time_zone_type;
}

# set the location base for redirects (since 1.5.2)
sub command_config_location_base {
    print "Here you can set the base part of the SquirrelMail URL.\n";
    print "It is normally autodetected but if that fails, use this\n";
    print "option to override.\n";
    print "It should contain only the protocol and hostname/port parts\n";
    print "of the URL; the full path will be appended automatically.\n\n";
    print "Examples:\nhttp://webmail.example.org\nhttp://webmail.example.com:8080\nhttps://webmail.example.com:6691\n\n";
    print "Do not add any path elements.\n";

    print "URL base? [" .$WHT."autodetect$NRM]: $WHT";
    $new_config_location_base = <STDIN>;
    chomp($new_config_location_base);
    $config_location_base = $new_config_location_base;
    
    return $config_location_base;
}

# only_secure_cookies (since 1.5.2)
sub command319 {
    print "This option allows you to specify that if a user session is initiated\n";
    print "under a secure (HTTPS, SSL-encrypted) connection, the cookies given to\n";
    print "the browser will ONLY be transmitted via a secure connection henceforth.\n\n";
    print "Generally this is a Good Thing, and should NOT be disabled.  However,\n";
    print "if you intend to use the Secure Login or Show SSL Link plugins to\n";
    print "encrypt the user login, but not the rest of the SquirrelMail session,\n";
    print "this can be turned off.  Think twice before doing so.\n";
    print "\n";

    if ( lc($only_secure_cookies) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Transmit cookies only on secure connection when available? (y/n) [$WHT$default_value$NRM]: $WHT";
    $only_secure_cookies = <STDIN>;
    if ( ( $only_secure_cookies =~ /^y\n/i ) || ( ( $only_secure_cookies =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $only_secure_cookies = 'true';
    } else {
        $only_secure_cookies = 'false';
    }
    return $only_secure_cookies;
}


# disable_security_tokens (since 1.5.2)
sub command320 {
    print "This option allows you to turn off the security checks in the forms\n";
    print "that SquirrelMail generates.  It is NOT RECOMMENDED that you disable\n";
    print "this feature - otherwise, your users may be exposed to phishing and\n";
    print "other attacks.\n";
    print "Unless you know what you are doing, you should leave this set to \"NO\".\n";
    print "\n";

    if ( lc($disable_security_tokens) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Disable secure forms? (y/n) [$WHT$default_value$NRM]: $WHT";
    $disable_security_tokens = <STDIN>;
    if ( ( $disable_security_tokens =~ /^y\n/i ) || ( ( $disable_security_tokens =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $disable_security_tokens = 'true';
    } else {
        $disable_security_tokens = 'false';
    }
    return $disable_security_tokens;
}



# check_referrer (since 1.5.2)
sub command321 {
    print "This option allows you to enable referal checks for all page requests\n";
    print "made to SquirrelMail.  This can help ensure that page requests came\n";
    print "from the same server and not from an attacker's site (usually the\n";
    print "result of a XSS or phishing attack).  To enable referal checking,\n";
    print "this setting can be set to the domain where your SquirrelMail is\n";
    print "being hosted (usually the same as the Domain setting under Server\n";
    print "Settings).  For example, it could be \"example.com\", or if you\n";
    print "use a plugin (such as Login Manager) to host SquirrelMail on more\n";
    print "than one domain, you can set this to \"###DOMAIN###\" to tell it\n";
    print "to use the current domain.\n";
    print "\n";
    print "However, in some cases (where proxy servers are in use, etc.), the\n";
    print "domain might be different.\n";
    print "\n";
    print "NOTE that referal checks are not foolproof - they can be spoofed by\n";
    print "browsers, and some browsers intentionally don't send referal\n";
    print "information (in which case, the check is silently bypassed)\n";
    print "\n";

    print "Referal requirement? [$WHT$check_referrer$NRM]: $WHT";
    $new_check_referrer = <STDIN>;
    chomp($new_check_referrer);
    $check_referrer = $new_check_referrer;

    return $check_referrer;
}



# use_transparent_security_image (since 1.5.2)
sub command322 {
    print "When HTML messages are being displayed, SquirrelMail's default behavior\n";
    print "is to remove all remote images and replace them with a local one.\n";
    print "\n";
    print "This option allows you to specify whether the local image should contain\n";
    print "text that indicates to the user that \"this image has been removed for\n";
    print "security reasons\" (translated into most languages), or if it should be\n";
    print "transparent.\n";
    print "\n";

    if ( lc($use_transparent_security_image) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Use transparent security image? (y/n) [$WHT$default_value$NRM]: $WHT";
    $use_transparent_security_image = <STDIN>;
    if ( ( $use_transparent_security_image =~ /^y\n/i ) || ( ( $use_transparent_security_image =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $use_transparent_security_image = 'true';
    } else {
        $use_transparent_security_image = 'false';
    }
    return $use_transparent_security_image;
}



# display_imap_login_error (since 1.5.2)
sub command323 {
    print "Some IMAP servers return detailed information about why a login is\n";
    print "being refused (the username or password could be invalid or there\n";
    print "might be an administrative lock on the account).\n";
    print "\n";
    print "Enabling this option will cause SquirrelMail to display login failure\n";
    print "messages directly from the IMAP server.  When it is disabled, login\n";
    print "failures are always reported to the user with the traditional \"Unknown\n";
    print "user or password incorrect.\"\n";
    print "\n";

    if ( lc($display_imap_login_error) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Display login error messages directly from the IMAP server? (y/n) [$WHT$default_value$NRM]: $WHT";
    $display_imap_login_error = <STDIN>;
    if ( ( $display_imap_login_error =~ /^y\n/i ) || ( ( $display_imap_login_error =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $display_imap_login_error = 'true';
    } else {
        $display_imap_login_error = 'false';
    }
    return $display_imap_login_error;
}



sub command_userThemes {
    print "\nDefine the user themes that you wish to use.  If you have added\n";
    print "a theme of your own, just follow the instructions (?) about\n";
    print "how to add them.  You can also change the default theme.\n\n";
    
    print "Available user themes:\n";
    $count = 0;
    while ( $count <= $#user_theme_name ) {
        if ( $count == $user_theme_default ) {
            print " *";
        } else {
            print "  ";
        }
        if ( $count < 10 ) {
            print " ";
        }
        $name       = $user_theme_name[$count];
        $num_spaces = 35 - length($name);
        for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
            $name = $name . " ";
        }

        print " $count.  $name";
        print "($user_theme_path[$count])\n";

        $count++;
    }
    
    print "\n";
    print ".------------------------------------.\n";
    print "| t             (detect user themes) |\n";
    print "| +                 (add user theme) |\n";
    print "| - N            (remove user theme) |\n";
    print "| m N      (mark default user theme) |\n";
    print "| l               (list user themes) |\n";
    print "| d                           (done) |\n";
    print "`------------------------------------'\n";
    
    print "\n[user_themes] command (?=help) > ";
    $input = <STDIN>;
    $input =~ s/[\r\n]//g;
    while ( $input ne "d" ) {
        if ( $input =~ /^\s*l\s*/i ) {
            $count = 0;
            while ( $count <= $#user_theme_name ) {
                if ( $count == $user_theme_default ) {
                    print " *";
                } else {
                    print "  ";
                }
                if ( $count < 10 ) {
                    print " ";
                }
                $name       = $user_theme_name[$count];
                $num_spaces = 35 - length($name);
                for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
                    $name = $name . " ";
                }

                print " $count.  $name";
                print "($user_theme_path[$count])\n";

                $count++;
            }
        } elsif ( $input =~ /^\s*m\s*[0-9]+/i ) {
            $old_def       = $user_theme_default;
            $user_theme_default = $input;
            $user_theme_default =~ s/^\s*m\s*//;
            if ( ( $user_theme_default > $#user_theme_name ) || ( $user_theme_default < 0 ) ) {
                print "Cannot set default theme to $user_theme_default.  That theme does not exist.\n";
                $user_theme_default = $old_def;
            }
        } elsif ( $input =~ /^\s*\+/ ) {
            print "What is the name of this theme? ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $user_theme_name[ $#user_theme_name + 1 ] = $name;
            print "Be sure to put ../css/ before the filename.\n";
            print "What file is this stored in (ex: ../css/my_theme/): ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $user_theme_path[ $#user_theme_path + 1 ] = $name;
        } elsif ( $input =~ /^\s*-\s*[0-9]?/ ) {
            if ( $input =~ /[0-9]+\s*$/ ) {
                $rem_num = $input;
                $rem_num =~ s/^\s*-\s*//g;
                $rem_num =~ s/\s*$//;
            } else {
                $rem_num = $#user_theme_name;
            }
            if ( $rem_num == $user_theme_default ) {
                print "You cannot remove the default theme!\n";
            } else {
                $count          = 0;
                @new_theme_name = ();
                @new_theme_path = ();
                while ( $count <= $#user_theme_name ) {
                    if ( $count != $rem_num ) {
                        @new_theme_name = ( @new_theme_name, $user_theme_name[$count] );
                        @new_theme_path = ( @new_theme_path, $user_theme_path[$count] );
                    }
                    $count++;
                }
                @user_theme_name = @new_theme_name;
                @user_theme_path = @new_theme_path;
                if ( $user_theme_default > $rem_num ) {
                    $user_theme_default--;
                }
            }
        } elsif ( $input =~ /^\s*t\s*/i ) {
            print "\nStarting detection...\n\n";

            opendir( DIR, "../css" );
            @files = sort(readdir(DIR));
            $cnt = 0;
            while ( $cnt <= $#files ) {
                $filename = "../css/" . $files[$cnt] .'/';
                if ( $files[$cnt] !~ /^\./ && $filename ne "../css/rtl.css" && -e $filename . "default.css" ) {
                    $found = 0;
                    for ( $x = 0 ; $x <= $#user_theme_path ; $x++ ) {
                        if ( $user_theme_path[$x] eq $filename ) {
                            $found = 1;
                        }
                    }
                    if ( $found != 1 ) {
                        print "** Found user theme: $filename\n";
                        $def = $files[$cnt];
                        $def =~ s/_/ /g;
                        $def = lc($def);
                        #$def =~ s/(^\w+)/ucfirst $1/eg;
                        #$def =~ s/(\s+)(\w+)/$1 . ucfirst $2/eg;
                        $def =~ s/(^\w+)|(\s+)(\w+)/ucfirst $1 . $2 . ucfirst $3/eg;
                        print "   What is its name? [$def]: ";
                        $nm = <STDIN>;
                        $nm =~ s/^\s+|\s+$|[\n\r]//g;
                        if ( $nm eq '' ) { $nm = $def; }
                        $user_theme_name[ $#user_theme_name + 1 ] = $nm;
                        $user_theme_path[ $#user_theme_path + 1 ] = $filename;
                    }
                }
                $cnt++;
            }
            print "\n";
            for ( $cnt = 0 ; $cnt <= $#user_theme_path ; $cnt++ ) {
                $filename = $user_theme_path[$cnt];
                if ( $filename != 'none' && !( -e $filename ."/default.css" ) ) {
                    print "  Removing $filename (file not found)\n";
                    $offset         = 0;
                    @new_user_theme_name = ();
                    @new_user_theme_path = ();
                    for ( $x = 0 ; $x < $#user_theme_path ; $x++ ) {
                        if ( $user_theme_path[$x] eq $filename ) {
                            $offset = 1;
                        }
                        if ( $offset == 1 ) {
                            $new_user_theme_name[$x] = $user_theme_name[ $x + 1 ];
                            $new_user_theme_path[$x] = $user_theme_path[ $x + 1 ];
                        } else {
                            $new_user_theme_name[$x] = $user_theme_name[$x];
                            $new_user_theme_path[$x] = $user_theme_path[$x];
                        }
                    }
                    @user_theme_name = @new_user_theme_name;
                    @user_theme_path = @new_user_theme_path;
                }
            }
            print "\nDetection complete!\n\n";

            closedir DIR;
        } elsif ( $input =~ /^\s*\?\s*/ ) {
            print ".------------------------------------.\n";
            print "| t             (detect user themes) |\n";
            print "| +                 (add user theme) |\n";
            print "| - N            (remove user theme) |\n";
            print "| m N      (mark default user theme) |\n";
            print "| l               (list user themes) |\n";
            print "| d                           (done) |\n";
            print "`------------------------------------'\n";
        }
        print "[user_themes] command (?=help) > ";
        $input = <STDIN>;
        $input =~ s/[\r\n]//g;
    }
}

sub command_iconSets {
    print "\nDefine the icon themes that you wish to use.  If you have added\n";
    print "a theme of your own, just follow the instructions (?) about\n";
    print "how to add them.  You can also change the default and fallback\n";
    print "themes.  The default theme will be used when no icon theme is\n";
    print "set by the user.  The fallback theme will be used if an icon\n";
    print "cannot be found in the currently selected icon theme.\n\n";
    
    print "Available icon themes:\n\n";

    $count = 0;
    while ( $count <= $#icon_theme_name ) {
        if ( $count == $icon_theme_def ) {
            print " d";
        } else {
            print "  ";
        }
        if ( $count eq $icon_theme_fallback ) {
            print "f ";
        } else {
            print "  ";
        }
        if ( $count < 10 ) {
            print " ";
        }
        $name       = $icon_theme_name[$count];
        $num_spaces = 35 - length($name);
        for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
            $name = $name . " ";
        }

        print " $count.  $name";
        print "($icon_theme_path[$count])\n";

        $count++;
    }
    
    print "\n d = Default icon theme\n";
    print " f = Fallback icon theme\n";
    print "\n";
    print ".------------------------------------.\n";
    print "| t             (detect icon themes) |\n";
    print "| +                 (add icon theme) |\n";
    print "| - N            (remove icon theme) |\n";
    print "| m N      (mark default icon theme) |\n";
    print "| f N        (set fallback icon set) |\n";
    print "| l               (list icon themes) |\n";
    print "| d                           (done) |\n";
    print "`------------------------------------'\n";
    
    print "\n[icon_themes] command (?=help) > ";
    $input = <STDIN>;
    $input =~ s/[\r\n]//g;
    while ( $input ne "d" ) {
        if ( $input =~ /^\s*l\s*/i ) {
            $count = 0;
            print "\n";
            while ( $count <= $#icon_theme_name ) {
		        if ( $count == $icon_theme_def ) {
		            print " d";
		        } else {
		            print "  ";
		        }
		        if ( $count eq $icon_theme_fallback ) {
		            print "f ";
		        } else {
		            print "  ";
		        }
                $name       = $icon_theme_name[$count];
                $num_spaces = 35 - length($name);
                for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
                    $name = $name . " ";
                }

                print " $count.  $name";
                print "($icon_theme_path[$count])\n";

                $count++;
            }
		    print "\n d = Default icon theme\n";
		    print " f = Fallback icon theme\n\n";
        } elsif ( $input =~ /^\s*m\s*[0-9]+/i ) {
            $old_def       = $icon_theme_def;
            $icon_theme_def = $input;
            $icon_theme_def =~ s/^\s*m\s*//;
            if ( ( $icon_theme_default > $#icon_theme_name ) || ( $icon_theme_default < 0 ) ) {
                print "Cannot set default icon theme to $icon_theme_default.  That theme does not exist.\n";
                $icon_theme_def = $old_def;
            }
        } elsif ( $input =~ /^\s*f\s*[0-9]+/i ) {
            $old_fb       = $icon_theme_fallback;
            $icon_theme_fallback = $input;
            $icon_theme_fallback =~ s/^\s*f\s*//;
            if ( ( $icon_theme_fallback > $#icon_theme_name ) || ( $icon_theme_fallback < 0 ) ) {
                print "Cannot set fallback icon theme to $icon_theme_fallback.  That theme does not exist.\n";
                $icon_theme_fallback = $old_fb;
            }
        } elsif ( $input =~ /^\s*\+/ ) {
            print "What is the name of this icon theme? ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $icon_theme_name[ $#icon_theme_name + 1 ] = $name;
            print "Be sure to put ../images/themes/ before the filename.\n";
            print "What directory is this icon theme stored in (ex: ../images/themes/my_theme/)? ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $icon_theme_path[ $#icon_theme_path + 1 ] = $name;
        } elsif ( $input =~ /^\s*-\s*[0-9]?/ ) {
            if ( $input =~ /[0-9]+\s*$/ ) {
                $rem_num = $input;
                $rem_num =~ s/^\s*-\s*//g;
                $rem_num =~ s/\s*$//;
            } else {
                $rem_num = $#icon_theme_name;
            }
            if ( $rem_num == $icon_theme_def ) {
                print "You cannot remove the default icon theme!\n";
            } elsif ( $rem_num == $icon_theme_fallback ) {
                print "You cannot remove the fallback icon theme!\n";
            } else {
                $count          = 0;
                @new_theme_name = ();
                @new_theme_path = ();
                while ( $count <= $#icon_theme_name ) {
                    if ( $count != $rem_num ) {
                        @new_theme_name = ( @new_theme_name, $icon_theme_name[$count] );
                        @new_theme_path = ( @new_theme_path, $icon_theme_path[$count] );
                    }
                    $count++;
                }
                @icon_theme_name = @new_theme_name;
                @icon_theme_path = @new_theme_path;
                if ( $icon_theme_def > $rem_num ) {
                    $icon_theme_def--;
                }
            }
        } elsif ( $input =~ /^\s*t\s*/i ) {
            print "\nStarting detection...\n\n";

            opendir( DIR, "../images/themes/" );
            @files = sort(readdir(DIR));
            $cnt = 0;
            while ( $cnt <= $#files ) {
                $filename = "../images/themes/" . $files[$cnt] .'/';
                if ( -d "../images/themes/" . $files[$cnt] && $files[$cnt] !~ /^\./ && $files[$cnt] ne ".svn" ) {
                    $found = 0;
                    for ( $x = 0 ; $x <= $#icon_theme_path ; $x++ ) {
                        if ( $icon_theme_path[$x] eq $filename ) {
                            $found = 1;
                        }
                    }
                    if ( $found != 1 ) {
                        print "** Found icon theme: $filename\n";
                        $def = $files[$cnt];
                        $def =~ s/_/ /g;
                        $def = lc($def);
                        #$def =~ s/(^\w+)/ucfirst $1/eg;
                        #$def =~ s/(\s+)(\w+)/$1 . ucfirst $2/eg;
                        $def =~ s/(^\w+)|(\s+)(\w+)/ucfirst $1 . $2 . ucfirst $3/eg;
                        print "   What is its name? [$def]: ";
                        $nm = <STDIN>;
                        $nm =~ s/^\s+|\s+$|[\n\r]//g;
                        if ( $nm eq '' ) { $nm = $def; }
                        $icon_theme_name[ $#icon_theme_name + 1 ] = $nm;
                        $icon_theme_path[ $#icon_theme_path + 1 ] = $filename;
                    }
                }
                $cnt++;
            }
            print "\n";
            for ( $cnt = 0 ; $cnt <= $#icon_theme_path ; $cnt++ ) {
                $filename = $icon_theme_path[$cnt];
                if ( $filename ne "none" && $filename ne "template" && ! -d $filename ) {
                    print "  Removing $filename (file not found)\n";
                    $offset         = 0;
                    @new_icon_theme_name = ();
                    @new_icon_theme_path = ();
                    for ( $x = 0 ; $x < $#icon_theme_path ; $x++ ) {
                        if ( $icon_theme_path[$x] eq $filename ) {
                            $offset = 1;
                        }
                        if ( $offset == 1 ) {
                            $new_icon_theme_name[$x] = $icon_theme_name[ $x + 1 ];
                            $new_icon_theme_path[$x] = $icon_theme_path[ $x + 1 ];
                        } else {
                            $new_icon_theme_name[$x] = $icon_theme_name[$x];
                            $new_icon_theme_path[$x] = $icon_theme_path[$x];
                        }
                    }
                    @icon_theme_name = @new_icon_theme_name;
                    @icon_theme_path = @new_icon_theme_path;
                }
            }
            print "\nDetection complete!\n\n";

            closedir DIR;
        } elsif ( $input =~ /^\s*\?\s*/ ) {
            print ".------------------------------------.\n";
            print "| t             (detect icon themes) |\n";
            print "| +                 (add icon theme) |\n";
            print "| - N            (remove icon theme) |\n";
            print "| m N      (mark default icon theme) |\n";
            print "| f N        (set fallback icon set) |\n";
            print "| l               (list icon themes) |\n";
            print "| d                           (done) |\n";
            print "`------------------------------------'\n";
        }
        print "[icon_themes] command (?=help) > ";
        $input = <STDIN>;
        $input =~ s/[\r\n]//g;
    }
}

sub command_templates {
    print "\nDefine the template sets (skins) that you wish to use.  If you have added\n";
    print "a template set of your own, just follow the instructions (?) about\n";
    print "how to add them.  You can also change the default template.\n";

    print "\n  Available Templates:\n";

    $count = 0;
    while ( $count <= $#templateset_name ) {
        if ( $templateset_id[$count] eq $templateset_default ) {
            print " d";
        } else {
            print "  ";
        }
        if ( $templateset_id[$count] eq $templateset_fallback ) {
            print "f";
        } else {
            print " ";
        }
        if ( $templateset_id[$count] eq $rpc_templateset ) {
            print "r ";
        } else {
            print "  ";
        }
        if ( $count < 10 ) {
            print " ";
        }
        if ( $count < 100 ) {
            print " ";
        }
        $name       = $templateset_name[$count];

        # present RPC template sets differently
        #
        if ( $templateset_id[$count] =~ /_rpc$/ ) {
            $name = $name . " (not shown in user interface; used for RPC interface only)";
        } else {

            $num_spaces = 35 - length($name);
            for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
                $name = $name . " ";
            }
            $name = $name . "($templateset_id[$count])";

        }

        print " $count.  $name\n";

        $count++;
    }
    print "\n  d = default template set\n"
       . "  f = fallback template set\n"
       . "  r = RPC template set\n\n";

    $menu_text = ".-------------------------------------.\n"
               . "| t             (detect template set) |\n"
               . "| +                (add template set) |\n"
               . "| - N           (remove template set) |\n"
               . "| m N     (mark default template set) |\n"
               . "| f N     (set fallback template set) |\n"
               . "| r N          (set RPC template set) |\n"
               . "| l        (list template sets/skins) |\n"
               . "| d                            (done) |\n"
               . "|-------------------------------------|\n"
               . "| where N is a template set number    |\n"
               . "`-------------------------------------'\n";
    print "\n";
    print $menu_text;
    print "\n[template set] command (?=help) > ";

    $input = <STDIN>;
    $input =~ s/[\r\n]//g;
    while ( $input ne "d" ) {

        # list template sets
        #
        if ( $input =~ /^\s*l\s*/i ) {
            $count = 0;
            while ( $count <= $#templateset_name ) {
                if ( $templateset_id[$count] eq $templateset_default ) {
                    print " d";
                } else {
                    print "  ";
                }
                if ( $templateset_id[$count] eq $templateset_fallback ) {
                    print "f";
                } else {
                    print " ";
                }
                if ( $templateset_id[$count] eq $rpc_templateset ) {
                    print "r ";
                } else {
                    print "  ";
                }
                if ( $count < 10 ) {
                    print " ";
                }
                if ( $count < 100 ) {
                    print " ";
                }
                $name       = $templateset_name[$count];

                # present RPC template sets differently
                #
                if ( $templateset_id[$count] =~ /_rpc$/ ) {
                    $name = $name . " (not shown in user interface; used for RPC interface only)";
                } else {

                    $num_spaces = 35 - length($name);
                    for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
                        $name = $name . " ";
                    }
                    $name = $name . "($templateset_id[$count])";

                }

                print " $count.  $name\n";

                $count++;
            }
            print "\n  d = default template set\n"
                . "  f = fallback template set\n"
                . "  r = RPC template set\n\n";

        # mark default template set
        #
        } elsif ( $input =~ /^\s*m\s*[0-9]+/i ) {
            $old_def       = $templateset_default;
            $input =~ s/^\s*m\s*//;
            $templateset_default = $templateset_id[$input];
            if ( $templateset_default =~ /^\s*$/ ) {
                print "Cannot set default template set to $input.  That template set does not exist.\n";
                $templateset_default = $old_def;
            }
            if ( $templateset_default =~ /_rpc$/ ) {
                print "Cannot set default template set to $input.  That template set is intended for the RPC interface only.\n";
                $templateset_default = $old_def;
            }

        # set fallback template set
        #
        } elsif ( $input =~ /^\s*f\s*[0-9]+/i ) {
            $old_def       = $templateset_fallback;
            $input =~ s/^\s*f\s*//;
            $templateset_fallback = $templateset_id[$input];
            if ( $templateset_fallback =~ /^\s*$/ ) {
                print "Cannot set fallback template set to $input.  That template set does not exist.\n";
                $templateset_fallback = $old_def;
            }
            if ( $templateset_fallback =~ /_rpc$/ ) {
                print "Cannot set fallback template set to $input.  That template set is intended for the RPC interface only.\n";
                $templateset_fallback = $old_def;
            }

        # set RPC template set
        #
        } elsif ( $input =~ /^\s*r\s*[0-9]+/i ) {
            $old_def       = $rpc_templateset;
            $input =~ s/^\s*r\s*//;
            $rpc_templateset = $templateset_id[$input];
            if ( $rpc_templateset =~ /^\s*$/ ) {
                print "Cannot set RPC template set to $input.  That template set does not exist.\n";
                $rpc_templateset = $old_def;
            }
            if ( $rpc_templateset !~ /_rpc$/ ) {
                print "Cannot set fallback template set to $input.  That template set is not intended for the RPC interface.\n";
                $rpc_templateset = $old_def;
            }

        # add template set
        #
        } elsif ( $input =~ /^\s*\+/ ) {
            print "\nWhat is the name of this template (as shown to your users): ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $templateset_name[ $#templateset_name + 1 ] = $name;
            print "\n\nThe directory name should not contain any path information\n"
                . "or slashes, and should be the name of the directory that the\n"
                . "template set is found in within the SquirrelMail templates\n"
                . "directory.\n\n";
            print "What directory is this stored in (ex: default_advanced): ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $templateset_id[ $#templateset_id + 1 ] = $name;

        # detect template sets
        #
        } elsif ( $input =~ /^\s*t\s*/i ) {
            print "\nStarting detection...\n\n";
            opendir( DIR, "../templates" );
            @files = sort(readdir(DIR));
            $cnt = 0;
            while ( $cnt <= $#files ) {
                if ( -d "../templates/" . $files[$cnt] && $files[$cnt] !~ /^\./ && $files[$cnt] ne ".svn" ) {
                    $filename = $files[$cnt];
                    $found = 0;
                    for ( $x = 0 ; $x <= $#templateset_id ; $x++ ) {
                        if ( $templateset_id[$x] eq $filename ) {
                            $found = 1;
                            last;
                        }
                    }
                    if ( $found != 1) {
                        print "** Found template set: $filename\n";
                        $def = $files[$cnt];

                        # no user-friendly names needed for RPC template sets
                        #
                        if ( $def =~ /_rpc$/ ) {
                            $nm = $def;
                        } else {
                            $def = lc($def);
                            $def =~ s/_/ /g;
                            #$def =~ s/(^\w+)/ucfirst $1/eg;
                            #$def =~ s/(\s+)(\w+)/$1 . ucfirst $2/eg;
                            $def =~ s/(^\w+)|(\s+)(\w+)/ucfirst $1 . $2 . ucfirst $3/eg;
                            print "   What is it's name (as shown to your users)? [$def]: ";
                            $nm = <STDIN>;
                            $nm =~ s/^\s+|\s+$|[\n\r]//g;
                            if ( $nm eq '' ) { $nm = $def; }
                        }
                        $templateset_id[ $#templateset_id + 1 ] = $filename;
                        $templateset_name[ $#templateset_name + 1 ] = $nm;
                    }
                }
                $cnt++;
            }
            print "\n";
            for ( $cnt= 0 ; $cnt <= $#templateset_id ; ) {
                $filename = $templateset_id[$cnt];
                if ( !(-d  change_to_rel_path('SM_PATH . \'templates/' . $filename)) ) {
                    print "  Removing \"$filename\" (template set directory not found)\n";
                    if ( $templateset_default eq $filename ) { $templateset_default = 'default'; }
                    if ( $templateset_fallback eq $filename ) { $templateset_fallback = 'default'; }
                    if ( $rpc_templateset eq $filename ) { $rpc_templateset = 'default_rpc'; }
                    $offset         = 0;
                    @new_templateset_name = ();
                    @new_templateset_id = ();
                    for ( $x = 0 ; $x < $#templateset_id ; $x++ ) {
                        if ( $templateset_id[$x] eq $filename ) {
                            $offset = 1;
                        }
                        if ( $offset == 1 ) {
                            $new_templateset_name[$x] = $templateset_name[ $x + 1 ];
                            $new_templateset_id[$x] = $templateset_id[ $x + 1 ];
                        } else {
                            $new_templateset_name[$x] = $templateset_name[$x];
                            $new_templateset_id[$x] = $templateset_id[$x];
                        }
                    }
                    @templateset_name = @new_templateset_name;
                    @templateset_id = @new_templateset_id;
                } else { $cnt++; }
            }
            print "\nDetection complete!\n\n";

            closedir DIR;

        # remove template set
        #
        # undocumented functionality of removing last template set isn't that great
        #} elsif ( $input =~ /^\s*-\s*[0-9]?/ ) {
        } elsif ( $input =~ /^\s*-\s*[0-9]+/ ) {
            if ( $input =~ /[0-9]+\s*$/ ) {
                $rem_num = $input;
                $rem_num =~ s/^\s*-\s*//g;
                $rem_num =~ s/\s*$//;
            } else {
                $rem_num = $#templateset_name;
            }
            if ( $templateset_id[$rem_num] eq $templateset_default ) {
                print "You cannot remove the default template set!\n";
            } elsif ( $templateset_id[$rem_num] eq $templateset_fallback ) {
                print "You cannot remove the fallback template set!\n";
            } elsif ( $templateset_id[$rem_num] eq $rpc_templateset ) {
                print "You cannot remove the RPC template set!\n";
            } else {
                $count          = 0;
                @new_templateset_name = ();
                @new_templateset_id = ();
                while ( $count <= $#templateset_name ) {
                    if ( $count != $rem_num ) {
                        @new_templateset_name = ( @new_templateset_name, $templateset_name[$count] );
                        @new_templateset_id = ( @new_templateset_id, $templateset_id[$count] );
                    }
                    $count++;
                }
                @templateset_name = @new_templateset_name;
                @templateset_id = @new_templateset_id;
            }

        # help
        #
        } elsif ( $input =~ /^\s*\?\s*/ ) {
            print $menu_text;

        # command not understood
        #
        } else {
            print "Command not understood\n";
        }

        print "[template set] command (?=help) > ";
        $input = <STDIN>;
        $input =~ s/[\r\n]//g;
    }
    return $templateset_default;
}


# sets default font size option
sub command_default_fontsize {
    print "Enter default font size [$WHT$$default_fontsize$NRM]: $WHT";
    $new_size = <STDIN>;
    if ( $new_size eq "\n" ) {
        $new_size = $size;
    } else {
        $new_size =~ s/[\r\n]//g;
    }
    return $new_size;
}

# controls available fontsets
sub command_fontsets {
    # Greeting
    print "You can control fontsets available to end users here.\n";
    # set initial $input value
    $input = 'l';
    while ( $input ne "x" ) {
        if ( $input =~ /^\s*a\s*/i ) {
            # add new fontset
            print "\nFontset name: ";
            $name = <STDIN>;
            if (! $fontsets{trim($name)}) {
                print "Fontset string: ";
                $value = <STDIN>;
                $fontsets{trim($name)} = trim($value);
            } else {
                print "\nERROR: Such fontset already exists.\n";
            }
        } elsif ( $input =~ /^\s*e\s*/i ) {
            # edit existing fontset
            print "\nFontset name: ";
            $name = <STDIN>;
            if (! $fontsets{trim($name)}) {
                print "\nERROR: No such fontset.\n";
            } else {
                print "Fontset string [$fontsets{trim($name)}]: ";
                $value = <STDIN>;
                $fontsets{trim($name)} = trim($value);
            }
        } elsif ( $input =~ /^\s*d\s*/ ) {
            # delete existing fontset
            print "\nFontset name: ";
            $name = <STDIN>;
            if (! $fontsets{trim($name)}) {
                print "\nERROR: No such fontset.\n";
            } else {
                delete $fontsets{trim($name)};
            }
        } elsif ( $input =~ /^\s*l\s*/ ) {
            # list fontsets
            print "\nConfigured fontsets:\n";
            while (($fontset_name, $fontset_string) = each(%fontsets)) {
                print "  $fontset_name = $fontset_string\n";
            }
            print "Default fontset: $default_fontset\n";
        } elsif ( $input =~ /^\s*m\s*/ ) {
            # set default fontset
            print "\nSet default fontset [$default_fontset]: ";
            $name = <STDIN>;
            if (trim($name) ne '' and ! $fontsets{trim($name)}) {
                print "\nERROR: No such fontset.\n";
            } else {
                $default_fontset = trim($name);
            }
        } else {
            # print available commands on any other input
            print "\nAvailable commands:\n";
            print "  a - Adds new fontset.\n";
            print "  d - Deletes existing fontset.\n";
            print "  e - Edits existing fontset.\n";
            print "  h or ? - Shows this help screen.\n";
            print "  l - Lists available fontsets.\n";
            print "  m - Sets default fontset.\n";
            print "  x - Exits fontset editor mode.\n";
        }
        print "\nCommand [fontsets] (a,d,e,h,?=help,l,m,x)> ";
        $input = <STDIN>;
        $input =~ s/[\r\n]//g;
    }
}

sub command61 {
    print "You can now define different LDAP servers.\n";
    print "Please ensure proper permissions for config.php when including\n";
    print "sensitive passwords.\n\n";
    print "[ldap] command (?=help) > ";
    $input = <STDIN>;
    $input =~ s/[\r\n]//g;
    while ( $input ne "d" ) {
        if ( $input =~ /^\s*l\s*/i ) {
            $count = 0;
            while ( $count <= $#ldap_host ) {
                print "$count. $ldap_host[$count]\n";
                print "        base: $ldap_base[$count]\n";
                if ( $ldap_charset[$count] ) {
                    print "     charset: $ldap_charset[$count]\n";
                }
                if ( $ldap_port[$count] ) {
                    print "        port: $ldap_port[$count]\n";
                }
                if ( $ldap_name[$count] ) {
                    print "        name: $ldap_name[$count]\n";
                }
                if ( $ldap_maxrows[$count] ) {
                    print "     maxrows: $ldap_maxrows[$count]\n";
                }
                if ( $ldap_filter[$count] ) {
                    print "      filter: $ldap_filter[$count]\n";
                }
                if ( $ldap_binddn[$count] ) {
                    print "      binddn: $ldap_binddn[$count]\n";
                    if ( $ldap_bindpw[$count] ) {
                        print "      bindpw: $ldap_bindpw[$count]\n";
                    }
                }
                if ( $ldap_protocol[$count] ) {
                    print "    protocol: $ldap_protocol[$count]\n";
                }
                if ( $ldap_limit_scope[$count] ) {
                    print " limit_scope: $ldap_limit_scope[$count]\n";
                }
                if ( $ldap_listing[$count] ) {
                    print "     listing: $ldap_listing[$count]\n";
                }
                if ( $ldap_writeable[$count] ) {
                    print "   writeable: $ldap_writeable[$count]\n";
                }
                if ( $ldap_search_tree[$count] ) {
                    print " search_tree: $ldap_search_tree[$count]\n";
                }
                if ( $ldap_starttls[$count] ) {
                    print "    starttls: $ldap_starttls[$count]\n";
                }

                print "\n";
                $count++;
            }
        } elsif ( $input =~ /^\s*\+/ ) {
            $sub = $#ldap_host + 1;

            print "First, we need to have the hostname or the IP address where\n";
            print "this LDAP server resides. Example: ldap.bigfoot.com\n";
            print "\n";
            print "You can use any URI compatible with your LDAP library. Please\n";
            print "note that StartTLS option is not compatible with ldaps and\n";
            print "ldapi URIs.\n";
            print "hostname: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_host[$sub] = $name;

            print "\n";

            print "Next, we need the server root (base dn).  For this, an empty\n";
            print "string is allowed.\n";
            print "Example: ou=member_directory,o=netcenter.com\n";
            print "base: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_base[$sub] = $name;

            print "\n";

            print "This is the TCP/IP port number for the LDAP server.  Default\n";
            print "port is 389.  This is optional.  Press ENTER for default.\n";
            print "port: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_port[$sub] = $name;

            print "\n";

            print "This is the charset for the server.  Default is utf-8.  This\n";
            print "is also optional.  Press ENTER for default.\n";
            print "charset: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_charset[$sub] = $name;

            print "\n";

            print "This is the name for the server, used to tag the results of\n";
            print "the search.  Default it \"LDAP: hostname\".  Press ENTER for default\n";
            print "name: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_name[$sub] = $name;

            print "\n";

            print "You can specify the maximum number of rows in the search result.\n";
            print "Default value is equal to 250 rows.  Press ENTER for default.\n";
            print "maxrows: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_maxrows[$sub] = $name;


            print "\n";

            print "If your LDAP server does not like anonymous logins, you can specify bind DN.\n";
            print "Default is none, anonymous bind.  Press ENTER for default.\n";
            print "binddn: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_binddn[$sub] = $name;

            print "\n";

            if ( $ldap_binddn[$sub] ne '' ) {

                print "Now, please specify password for that DN.\n";
                print "bindpw: ";
                $name = <STDIN>;
                $name =~ s/[\r\n]//g;
                $ldap_bindpw[$sub] = $name;

                print "\n";
            }

            print "You can specify bind protocol version here.\n";
            print "Default protocol version depends on your php ldap settings.\n";
            print "Press ENTER for default.\n";
            print "protocol: ";
            $name = <STDIN>;
            $name =~ s/[\r\n]//g;
            $ldap_protocol[$sub] = $name;

            print "\n";

            print "This configuration section allows to set some rarely used\n";
            print "options and options specific to some LDAP implementations.\n";
            print "\n";
            print "Do you want to set advanced LDAP directory settings? (y/N):";
            $ldap_advanced_settings = <STDIN>;
            if ( $ldap_advanced_settings =~ /^y\n/i ) {
                $ldap_advanced_settings = 'true';
            } else {
                $ldap_advanced_settings = 'false';
            }

            if ($ldap_advanced_settings eq 'true') {
              print "\n";

              print "You can control LDAP directory listing here. This option can\n";
              print "be useful if you run small LDAP server and want to provide listing\n";
              print "of all addresses stored in LDAP to users of webmail interface.\n";
              print "Number of displayed entries is limited by maxrows setting.\n";
              print "\n";
              print "Don't enable this option for public LDAP directories.\n";
              print "\n";
              print "Allow listing of LDAP directory? (y/N):";
              $name = <STDIN>;
              if ( $name =~ /^y\n/i ) {
                $name = 'true';
              } else {
                $name = 'false';
              }
              $ldap_listing[$sub] = $name;

              print "\n";

              print "You can control write access to LDAP address book here. This option can\n";
              print "be useful if you run small LDAP server and want to provide writable\n";
              print "shared address book stored in LDAP to users of webmail interface.\n";
              print "\n";
              print "Don't enable this option for public LDAP directories.\n";
              print "\n";
              print "Allow writing to LDAP directory? (y/N):";
              $name = <STDIN>;
              if ( $name =~ /^y\n/i ) {
                $name = 'true';
              } else {
                $name = 'false';
              }
              $ldap_writeable[$sub] = $name;

              print "\n";

              print "You can specify an additional search filter.\n";
              print "This could be something like \"(objectclass=posixAccount)\".\n";
              print "No filtering is performed by default. Press ENTER for default.\n";
              print "filter: ";
              $name = <STDIN>;
              $name =~ s/[\r|\n]//g;
              $ldap_filter[$sub] = $name;

              print "\n";

              print "You can control search scope here.\n";
              print "This option is specific to Microsoft ADS implementation.\n";
              print "It requires use of v3 or newer LDAP protocol.\n";
              print "Don't enable it, if you use other LDAP server.\n";
              print "\n";
              print "Limit ldap scope? (y/N):";
              $name = <STDIN>;
              if ( $name =~ /^y\n/i ) {
                $name = 'true';
              } else {
                $name = 'false';
              }
              $ldap_limit_scope[$sub] = $name;

              print "\n";

              print "You can control ldap search type here.\n";
              print "Addresses can be searched in entire LDAP subtree (default)\n";
              print "or only first level entries are returned.\n";
              print "\n";
              print "Search entire LDAP subtree? (Y/n):";
              $name = <STDIN>;
              if ( $name =~ /^n\n/i ) {
                $name = 'false';
              } else {
                $name = 'true';
              }
              $ldap_search_tree[$sub] = $name;

              print "\n";

              print "You can control use of StartTLS on LDAP connection here.\n";
              print "This option requires use of v3 or newer LDAP protocol and php 4.2+.\n";
              print "\n";
              print "Use StartTLS? (y/N):";
              $name = <STDIN>;
              if ( $name =~ /^y\n/i ) {
                $name = 'true';
              } else {
                $name = 'false';
              }
              $ldap_starttls[$sub] = $name;
            }
            print "\n";

        } elsif ( $input =~ /^\s*-\s*[0-9]?/ ) {
            if ( $input =~ /[0-9]+\s*$/ ) {
                $rem_num = $input;
                $rem_num =~ s/^\s*-\s*//g;
                $rem_num =~ s/\s*$//;
            } else {
                $rem_num = $#ldap_host;
            }
            $count            = 0;
            @new_ldap_host    = ();
            @new_ldap_base    = ();
            @new_ldap_port    = ();
            @new_ldap_name    = ();
            @new_ldap_charset = ();
            @new_ldap_maxrows = ();
            @new_ldap_filter  = ();
            @new_ldap_bindpw  = ();
            @new_ldap_binddn  = ();
            @new_ldap_protocol = ();
            @new_ldap_limit_scope = ();
            @new_ldap_listing = ();
            @new_ldap_writeable = ();
            @new_ldap_search_tree = ();
            @new_ldap_starttls = ();

            while ( $count <= $#ldap_host ) {
                if ( $count != $rem_num ) {
                    @new_ldap_host    = ( @new_ldap_host,    $ldap_host[$count] );
                    @new_ldap_base    = ( @new_ldap_base,    $ldap_base[$count] );
                    @new_ldap_port    = ( @new_ldap_port,    $ldap_port[$count] );
                    @new_ldap_name    = ( @new_ldap_name,    $ldap_name[$count] );
                    @new_ldap_charset = ( @new_ldap_charset, $ldap_charset[$count] );
                    @new_ldap_maxrows = ( @new_ldap_maxrows, $ldap_maxrows[$count] );
                    @new_ldap_filter  = ( @new_ldap_filter,  $ldap_filter[$count] );
                    @new_ldap_binddn  = ( @new_ldap_binddn,  $ldap_binddn[$count] );
                    @new_ldap_bindpw  = ( @new_ldap_bindpw,  $ldap_bindpw[$count] );
                    @new_ldap_protocol  = ( @new_ldap_protocol,  $ldap_protocol[$count] );
                    @new_ldap_limit_scope = ( @new_ldap_limit_scope,  $ldap_limit_scope[$count] );
                    @new_ldap_listing = ( @new_ldap_listing, $ldap_listing[$count] );
                    @new_ldap_writeable = ( @new_ldap_writeable, $ldap_writeable[$count] );
                    @new_ldap_search_tree = ( @new_ldap_search_tree, $ldap_search_tree[$count] );
                    @new_ldap_starttls = ( @new_ldap_starttls, $ldap_starttls[$count] );
                }
                $count++;
            }
            @ldap_host    = @new_ldap_host;
            @ldap_base    = @new_ldap_base;
            @ldap_port    = @new_ldap_port;
            @ldap_name    = @new_ldap_name;
            @ldap_charset = @new_ldap_charset;
            @ldap_maxrows = @new_ldap_maxrows;
            @ldap_filter  = @new_ldap_filter;
            @ldap_binddn  = @new_ldap_binddn;
            @ldap_bindpw  = @new_ldap_bindpw;
            @ldap_protocol = @new_ldap_protocol;
            @ldap_limit_scope = @new_ldap_limit_scope;
            @ldap_listing = @new_ldap_listing;
            @ldap_writeable = @new_ldap_writeable;
            @ldap_search_tree = @new_ldap_search_tree;
            @ldap_starttls = @new_ldap_starttls;

        } elsif ( $input =~ /^\s*\?\s*/ ) {
            print ".-------------------------.\n";
            print "| +            (add host) |\n";
            print "| - N       (remove host) |\n";
            print "| l          (list hosts) |\n";
            print "| d                (done) |\n";
            print "`-------------------------'\n";
        }
        print "[ldap] command (?=help) > ";
        $input = <STDIN>;
        $input =~ s/[\r\n]//g;
    }
}

sub command62 {
    print "Some of our developers have come up with very good javascript interface\n";
    print "for searching through address books, however, our original goals said\n";
    print "that we would be 100% HTML.  In order to make it possible to use their\n";
    print "interface, and yet stick with our goals, we have also written a plain\n";
    print "HTML version of the search.  Here, you can choose which version to use.\n";
    print "\n";
    print "This is just the default value.  It is also a user option that each\n";
    print "user can configure individually\n";
    print "\n";

    if ( lc($default_use_javascript_addr_book) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_use_javascript_addr_book = 'false';
        $default_value                    = "n";
    }
    print "Use javascript version by default (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $default_use_javascript_addr_book = 'true';
    } else {
        $default_use_javascript_addr_book = 'false';
    }
    return $default_use_javascript_addr_book;
}

# global filebased address book
sub command63 {
    print "If you want to use global file address book, then you\n";
    print "must set this option to a valid value. If option does\n";
    print "not have path elements, system assumes that file is\n";
    print "stored in data directory. If relative path is set, it is\n";
    print "relative to main SquirrelMail directory. If value is empty,\n";
    print "address book is not enabled.\n";
    print "\n";

    print "[$WHT$abook_global_file$NRM]: $WHT";
    $new_abook_global_file = <STDIN>;
    if ( $new_abook_global_file eq "\n" ) {
        $new_abook_global_file = $abook_global_file;
    } else {
        $new_abook_global_file =~ s/[\r\n]//g;
    }
    return $new_abook_global_file;
}

# writing into global filebased abook control
sub command64 {
    print "This setting controls writing into global file address\n";
    print "book options. Address book file must be writeable by\n";
    print "webserver's user, if you want to enable this option.\n";
    print "\n";

    if ( lc($abook_global_file_writeable) eq 'true' ) {
        $default_value = "y";
    } else {
        $abook_global_file_writeable = 'false';
        $default_value               = "n";
    }
    print "Allow writing into global file address book (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $abook_global_file_writeable = 'true';
    } else {
        $abook_global_file_writeable = 'false';
    }
    return $abook_global_file_writeable;
}

# listing of global filebased abook control
sub command65 {
    print "This setting controls listing of global file address\n";
    print "book in addresses page.\n";
    print "\n";

    if ( lc($abook_global_file_listing) eq 'true' ) {
        $default_value = "y";
    } else {
        $abook_global_file_listing = 'false';
        $default_value               = "n";
    }
    print "Allow listing of global file address book (y/n) [$WHT$default_value$NRM]: $WHT";
    $new_show = <STDIN>;
    if ( ( $new_show =~ /^y\n/i ) || ( ( $new_show =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $abook_global_file_listing = 'true';
    } else {
        $abook_global_file_listing = 'false';
    }
    return $abook_global_file_listing;
}

# controls $abook_file_line_length setting
sub command_abook_file_line_length {
    print "This setting controls space allocated to file based address book records.\n";
    print "End users will be unable to save address book entry, if total entry size \n";
    print "(quoted address book fields + 4 delimiters + linefeed) exceeds allowed \n";
    print "address book length size.\n";
    print "\n";
    print "Same setting is applied to personal and global file based address books.\n";
    print "\n";
    print "It is strongly recommended to keep default setting value. Change it only\n";
    print "if you really want to store address book entries that are bigger than two\n";
    print "kilobytes (2048).\n";
    print "\n";

    print "Enter allowed address book line length [$abook_file_line_length]: ";
    my $tmp = <STDIN>;
    $tmp = trim($tmp);
    # value is not modified, if user hits Enter or enters space
    if ($tmp ne '') {
        # make sure that input is numeric
        if ($tmp =~ /^\d+$/) {
            $abook_file_line_length = $tmp;
        } else {
            print "If you want to change this setting, you must enter number.\n";
            print "If you want to keep original setting - enter space.\n\n";
            print "Press Enter to continue...";
            $tmp = <STDIN>;
        }
    }
}

sub command91 {
    print "If you want to store your users address book details in a database then\n";
    print "you need to set this DSN to a valid value. The format for this is:\n";
    print "mysql://user:pass\@hostname/dbname\n";
    print "Where mysql can be one of the databases PHP supports, the most common\n";
    print "of these are mysql, msql and pgsql.\n";
    print "Please ensure proper permissions for config.php when including\n";
    print "sensitive passwords.\n\n";
    print "If the DSN is left empty (hit space and then return) the database\n";
    print "related code for address books will not be used.\n";
    print "\n";

    if ( $addrbook_dsn eq "" ) {
        $default_value = "Disabled";
    } else {
        $default_value = $addrbook_dsn;
    }
    print "[$WHT$addrbook_dsn$NRM]: $WHT";
    $new_dsn = <STDIN>;
    if ( $new_dsn eq "\n" ) {
        $new_dsn = $addrbook_dsn;
    } else {
        $new_dsn =~ s/[\r\n]//g;
        $new_dsn =~ s/^\s+$//g;
    }
    return $new_dsn;
}

sub command92 {
    print "This is the name of the table you want to store the address book\n";
    print "data in, it defaults to 'address'\n";
    print "\n";
    print "[$WHT$addrbook_table$NRM]: $WHT";
    $new_table = <STDIN>;
    if ( $new_table eq "\n" ) {
        $new_table = $addrbook_table;
    } else {
        $new_table =~ s/[\r\n]//g;
    }
    return $new_table;
}

sub command93 {
    print "If you want to store your users preferences in a database then\n";
    print "you need to set this DSN to a valid value. The format for this is:\n";
    print "mysql://user:pass\@hostname/dbname\n";
    print "Where mysql can be one of the databases PHP supports, the most common\n";
    print "of these are mysql, msql and pgsql.\n";
    print "Please ensure proper permissions for config.php when including\n";
    print "sensitive passwords.\n\n";
    print "If the DSN is left empty (hit space and then return) the database\n";
    print "related code for address books will not be used.\n";
    print "\n";

    if ( $prefs_dsn eq "" ) {
        $default_value = "Disabled";
    } else {
        $default_value = $prefs_dsn;
    }
    print "[$WHT$prefs_dsn$NRM]: $WHT";
    $new_dsn = <STDIN>;
    if ( $new_dsn eq "\n" ) {
        $new_dsn = $prefs_dsn;
    } else {
        $new_dsn =~ s/[\r\n]//g;
        $new_dsn =~ s/^\s+$//g;
    }
    return $new_dsn;
}

sub command94 {
    print "This is the name of the table you want to store the preferences\n";
    print "data in, it defaults to 'userprefs'\n";
    print "\n";
    print "[$WHT$prefs_table$NRM]: $WHT";
    $new_table = <STDIN>;
    if ( $new_table eq "\n" ) {
        $new_table = $prefs_table;
    } else {
        $new_table =~ s/[\r\n]//g;
    }
    return $new_table;
}

sub command95 {
    print "This is the name of the field in which you want to store the\n";
    print "username of the person the prefs are for. It defaults to 'user'\n";
    print "\n";
    print "[$WHT$prefs_user_field$NRM]: $WHT";
    $new_field = <STDIN>;
    if ( $new_field eq "\n" ) {
        $new_field = $prefs_user_field;
    } else {
        $new_field =~ s/[\r\n]//g;
    }
    $prefs_user_size = db_pref_size($prefs_user_size);
    return $new_field;
}

sub command96 {
    print "This is the name of the field in which you want to store the\n";
    print "preferences keyword. It defaults to 'prefkey'\n";
    print "\n";
    print "[$WHT$prefs_key_field$NRM]: $WHT";
    $new_field = <STDIN>;
    if ( $new_field eq "\n" ) {
        $new_field = $prefs_key_field;
    } else {
        $new_field =~ s/[\r\n]//g;
    }
    $prefs_key_size = db_pref_size($prefs_key_size);
    return $new_field;
}

sub command97 {
    print "This is the name of the field in which you want to store the\n";
    print "preferences value. It defaults to 'prefval'\n";
    print "\n";
    print "[$WHT$prefs_val_field$NRM]: $WHT";
    $new_field = <STDIN>;
    if ( $new_field eq "\n" ) {
        $new_field = $prefs_val_field;
    } else {
        $new_field =~ s/[\r\n]//g;
    }
    $prefs_val_size = db_pref_size($prefs_val_size);
    return $new_field;
}

# routine is used to set database field limits
# it needs one argument
sub db_pref_size() {
    my ($size) = $_[0];
    print "\nDatabase fields have size limits.\n";
    print "\n";
    print "What limit is set for this field? [$WHT$size$NRM]: $WHT";
    $new_size = <STDIN>;
    if ( $new_size eq "\n" ) {
        $new_size = $size;
    } else {
        $new_size =~ s/[\r\n]//g;
    }
    return $new_size;
}

sub command98 {
    print "If you want to store your global address book in a database then\n";
    print "you need to set this DSN to a valid value. The format for this is:\n";
    print "mysql://user:pass\@hostname/dbname\n";
    print "Where mysql can be one of the databases PHP supports, the most common\n";
    print "of these are mysql, msql and pgsql.\n";
    print "Please ensure proper permissions for config.php when including\n";
    print "sensitive passwords.\n\n";
    print "If the DSN is left empty (hit space and then return) the database\n";
    print "related code for global SQL address book will not be used.\n";
    print "\n";

    if ( $addrbook_global_dsn eq "" ) {
        $default_value = "Disabled";
    } else {
        $default_value = $addrbook_global_dsn;
    }
    print "[$WHT$addrbook_global_dsn$NRM]: $WHT";
    $new_dsn = <STDIN>;
    if ( $new_dsn eq "\n" ) {
        $new_dsn = $addrbook_global_dsn;
    } else {
        $new_dsn =~ s/[\r\n]//g;
        $new_dsn =~ s/^\s+$//g;
    }
    return $new_dsn;
}

sub command99 {
    print "This is the name of the table you want to store the global address book\n";
    print "data in. Default table name is 'global_abook'. Address book uses same\n";
    print "database format as personal address book.\n";
    print "\n";
    print "[$WHT$addrbook_global_table$NRM]: $WHT";
    $new_table = <STDIN>;
    if ( $new_table eq "\n" ) {
        $new_table = $addrbook_global_table;
    } else {
        $new_table =~ s/[\r\n]//g;
    }
    return $new_table;
}

sub command910 {
    print "This option controls users\' ability to add or modify records stored \n";
    print "in global address book\n";

    if ( lc($addrbook_global_writeable) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow writing into global address book? (y/n) [$WHT$default_value$NRM]: $WHT";
    $addrbook_global_writeable = <STDIN>;
    if ( ( $addrbook_global_writeable =~ /^y\n/i ) || ( ( $addrbook_global_writeable =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $addrbook_global_writeable = 'true';
    } else {
        $addrbook_global_writeable = 'false';
    }
    return $addrbook_global_writeable;
}

sub command911 {
    print "Enable this option if you want to see listing of addresses stored \n";
    print "in global address book\n";

    if ( lc($addrbook_global_listing) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow listing of global address book? (y/n) [$WHT$default_value$NRM]: $WHT";
    $addrbook_global_listing = <STDIN>;
    if ( ( $addrbook_global_listing =~ /^y\n/i ) || ( ( $addrbook_global_listing =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $addrbook_global_listing = 'true';
    } else {
        $addrbook_global_listing = 'false';
    }
    return $addrbook_global_listing;
}


# Default language
sub commandA1 {
    print "SquirrelMail attempts to set the language in many ways.  If it\n";
    print "can not figure it out in another way, it will default to this\n";
    print "language.  Please use the code for the desired language.\n";
    print "\n";
    print "[$WHT$squirrelmail_default_language$NRM]: $WHT";
    $new_squirrelmail_default_language = <STDIN>;
    if ( $new_squirrelmail_default_language eq "\n" ) {
        $new_squirrelmail_default_language = $squirrelmail_default_language;
    } else {
        $new_squirrelmail_default_language =~ s/[\r\n]//g;
        $new_squirrelmail_default_language =~ s/^\s+$//g;
    }
    return $new_squirrelmail_default_language;
}
# Default Charset
sub commandA2 {
    print "This option controls what character set is used when sending\n";
    print "mail and when sending HTML to the browser. Option works only\n";
    print "with US English (en_US) translation. Other translations use\n";
    print "charsets that are set in translation settings.\n";
    print "\n";

    print "[$WHT$default_charset$NRM]: $WHT";
    $new_default_charset = <STDIN>;
    if ( $new_default_charset eq "\n" ) {
        $new_default_charset = $default_charset;
    } else {
        $new_default_charset =~ s/[\r\n]//g;
    }
    return $new_default_charset;
}
# Alternative language names
sub commandA3 {
    print "Enable this option if you want to see localized language names in\n";
    print "language selection box. Note, that this option can trigger\n";
    print "installation of foreign language support modules in some browsers.\n";
    print "\n";

    if ( lc($show_alternative_names) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Show alternative language names? (y/n) [$WHT$default_value$NRM]: $WHT";
    $show_alternative_names = <STDIN>;
    if ( ( $show_alternative_names =~ /^y\n/i ) || ( ( $show_alternative_names =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $show_alternative_names = 'true';
    } else {
        $show_alternative_names = 'false';
    }
    return $show_alternative_names;
}

# Aggressive decoding
sub commandA4 {
    print "Enable this option if you want to use CPU and memory intensive decoding\n";
    print "functions. This option allows reading multibyte charset, that are used\n";
    print "in Eastern Asia. SquirrelMail will try to use recode functions here,\n";
    print "even when you have disabled use of recode in Tweaks section.\n";
    print "\n";

    if ( lc($aggressive_decoding) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Enable aggressive decoding? (y/n) [$WHT$default_value$NRM]: $WHT";
    $aggressive_decoding = <STDIN>;
    if ( ( $aggressive_decoding =~ /^y\n/i ) || ( ( $aggressive_decoding =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $aggressive_decoding = 'true';
    } else {
        $aggressive_decoding = 'false';
    }
    return $aggressive_decoding;
}

# Lossy encoding
sub commandA5 {
    print "Enable this option if you want to allow lossy charset encoding in message\n";
    print "composition pages. This option allows charset conversions when output\n";
    print "charset does not support all symbols used in original charset. Symbols\n";
    print "unsupported by output charset will be replaced with question marks.\n";
    print "\n";

    if ( lc($lossy_encoding) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Enable lossy encoding? (y/n) [$WHT$default_value$NRM]: $WHT";
    $lossy_encoding = <STDIN>;
    if ( ( $lossy_encoding =~ /^y\n/i ) || ( ( $lossy_encoding =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $lossy_encoding = 'true';
    } else {
        $lossy_encoding = 'false';
    }
    return $lossy_encoding;
}

# display html emails in iframe
sub commandB2 {
    print "This option can enable html email rendering inside iframe.\n";
    print "Inline frames are used in order to provide sandbox environment";
    print "for html code included in html formated emails.";
    print "Option is experimental and might have glitches in some parts of code.";
    print "\n";

    if ( lc($use_iframe) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Display html emails in iframe? (y/n) [$WHT$default_value$NRM]: $WHT";
    $use_iframe = <STDIN>;
    if ( ( $use_iframe =~ /^y\n/i ) || ( ( $use_iframe =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $use_iframe = 'true';
    } else {
        $use_iframe = 'false';
    }
    return $use_iframe;
}

# ask user info
sub command_ask_user_info {
    print "New users need to supply their real name and email address to\n";
    print "send out proper mails. When this option is enabled, a user that\n";
    print "logs in for the first time will be redirected to the Personal\n";
    print "Options screen and asked to supply their personal data.\n";
    print "\n";

    if ( lc($ask_user_info) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Ask user info? (y/n) [$WHT$default_value$NRM]: $WHT";
    $ask_user_info = <STDIN>;
    if ( ( $ask_user_info =~ /^y\n/i ) || ( ( $ask_user_info =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $ask_user_info = 'true';
    } else {
        $ask_user_info = 'false';
    }
    return $ask_user_info;
}

# use icons
sub commandB3 {
    print "Enabling this option will cause icons to be used instead of text\n";
    print "markers next to each message in mailbox lists that represent\n";
    print "new, read, flagged, and deleted messages, as well as those that\n";
    print "have been replied to and forwarded. Icons are also used next to\n";
    print "(un)expanded folders in the folder list (Oldway = false).  These\n";
    print "icons are quite small, but will obviously be more of a resource\n";
    print "drain than text markers.\n";
    print "\n";

    if ( lc($use_icons) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Use icons? (y/n) [$WHT$default_value$NRM]: $WHT";
    $use_icons = <STDIN>;
    if ( ( $use_icons =~ /^y\n/i ) || ( ( $use_icons =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $use_icons = 'true';
    } else {
        $use_icons = 'false';
    }
    return $use_icons;
}
# php recode
sub commandB4 {
    print "Enable this option if you want to use php recode functions to read\n";
    print "emails written in charset that differs from the one that is set in\n";
    print "translation selected by user. Code is experimental, it might cause\n";
    print "errors, if email contains charset unsupported by recode or if your\n";
    print "php does not have recode support.\n";
    print "\n";

    if ( lc($use_php_recode) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Use php recode functions? (y/n) [$WHT$default_value$NRM]: $WHT";
    $use_php_recode = <STDIN>;
    if ( ( $use_php_recode =~ /^y\n/i ) || ( ( $use_php_recode =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $use_php_recode = 'true';
    } else {
        $use_php_recode = 'false';
    }
    return $use_php_recode;
}
# php iconv
sub commandB5 {
    print "Enable this option if you want to use php iconv functions to read\n";
    print "emails written in charset that differs from the one that is set in\n";
    print "translation selected by user. Code is experimental, it works only\n";
    print "with translations that use utf-8 charset. Code might cause errors,\n";
    print "if email contains charset unsupported by iconv or if your php does\n";
    print "not have iconv support.\n";
    print "\n";

    if ( lc($use_php_iconv) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Use php iconv functions? (y/n) [$WHT$default_value$NRM]: $WHT";
    $use_php_iconv = <STDIN>;
    if ( ( $use_php_iconv =~ /^y\n/i ) || ( ( $use_php_iconv =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $use_php_iconv = 'true';
    } else {
        $use_php_iconv = 'false';
    }
    return $use_php_iconv;
}

# buffer output
sub commandB6 {
    print "In some cases, buffering all output (holding it on the server until\n";
    print "the full page is ready to send to the browser) allows more complex\n";
    print "functionality, especially for plugins that want to add headers on hooks\n";
    print "that are beyond the point of output having been sent to the browser\n";
    print "otherwise.  Most plugins that need this functionality will enable it\n";
    print "automatically on their own, but you can turn it on manually here.  You'd\n";
    print "usually want to do this if you want to specify a custom output handler\n";
    print "for parsing the output - you can do that by specifying a value for\n";
    print "\$buffered_output_handler in config_local.php.  Don't forget to define\n";
    print "a function of the same name as what \$buffered_output_handler is set to.\n";
    print "\n";

    if ( lc($buffer_output) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Buffer all output? (y/n) [$WHT$default_value$NRM]: $WHT";
    $buffer_output = <STDIN>;
    if ( ( $buffer_output =~ /^y\n/i ) || ( ( $buffer_output =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $buffer_output = 'true';
    } else {
        $buffer_output = 'false';
    }
    return $buffer_output;
}

# configtest block
sub commandB7 {
    print "Enable this option if you want to check SquirrelMail configuration\n";
    print "remotely with configtest.php script.\n";
    print "\n";

    if ( lc($allow_remote_configtest) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Allow remote configuration tests? (y/n) [$WHT$default_value$NRM]: $WHT";
    $allow_remote_configtest = <STDIN>;
    if ( ( $allow_remote_configtest =~ /^y\n/i ) || ( ( $allow_remote_configtest =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $allow_remote_configtest = 'true';
    } else {
        $allow_remote_configtest = 'false';
    }
    return $allow_remote_configtest;
}

# Default Icon theme
sub command53 {
    print "You may change the path to the default icon theme to be used, if icons\n";
    print "have been enabled.  This theme will be used when an icon cannot be\n";
    print "found in the current theme, or when no icon theme is specified.  If\n";
    print "left blank, and icons are enabled, the default theme will be used\n";
    print "from images/themes/default/.\n";
    print "\n";
    print "To clear out an existing value, just type a space for the input.\n";
    print "\n";
    print "Please be aware of the following: \n";
    print "  - Relative URLs are relative to the config dir\n";
    print "    to use the icon themes directory, use ../images/themes/newtheme/\n";
    print "  - The icon theme may be outside the SquirrelMail directory, but\n";
    print "    it must be web accessible.\n";
    print "[$WHT$icon_theme_def$NRM]: $WHT";
    $new_icon_theme_def = <STDIN>;

    if ( $new_icon_theme_def eq "\n" ) {
        $new_icon_theme_def = $icon_theme_def;
    } else {
        $new_icon_theme_def =~ s/[\r\n]//g;
    }
    $new_icon_theme_def =~ s/^\s*//;
    return $new_icon_theme_def;
}

# SquirrelMail debug mode (since 1.5.2)
sub commandB8 {
    print "When debugging or developing SquirrelMail, you may want to increase\n";
    print "the verbosity of certain kinds of errors, notices, and/or diagnostics.\n";
    print "You may enable one or more of the debugging modes here.  Please make\n";
    print "sure that you have turned off debugging if you are using SquirrelMail\n";
    print "in a production environment.\n\n";

    $input = "";
    while ( $input ne "d\n" ) {
        $sm_debug_mode = convert_debug_constants_to_binary_integer($sm_debug_mode);

        # per include/constants.php, here are the debug mode values:
        #
        # 0          SM_DEBUG_MODE_OFF
        # 1          SM_DEBUG_MODE_SIMPLE
        # 512        SM_DEBUG_MODE_MODERATE
        # 524288     SM_DEBUG_MODE_ADVANCED
        # 536870912  SM_DEBUG_MODE_STRICT
        #
        print "\n#  Enabled?  Description\n";
        print "---------------------------------------------------------------------\n";
        print "0     " . ($sm_debug_mode == 0 ? "y" : " ")
            . "      No debugging (recommended in production environments)\n";
        print "1     " . ($sm_debug_mode & 1 ? "y" : " ")
            . "      Simple debugging (PHP E_ERROR)\n";
        print "2     " . ($sm_debug_mode & 512 ? "y" : " ")
            . "      Moderate debugging (PHP E_ALL without E_STRICT)\n";
        print "3     " . ($sm_debug_mode & 524288 ? "y" : " ")
            . "      Advanced debugging (PHP E_ALL (without E_STRICT) plus\n";
        print "             log errors intentionally suppressed)\n";
        print "4     " . ($sm_debug_mode & 536870912 ? "y" : " ")
            . "      Strict debugging (PHP E_ALL and E_STRICT)\n";
        print "\n";
    
        print "SquirrelMail debug mode (0,1,2,3,4) or d when done? : $WHT";
        $input = <STDIN>;
        if ( $input eq "d\n" ) {
            # nothing
        } elsif ($input !~ /^[0-9]+\n$/) {
            print "\nInvalid configuration value.\n";
            print "\nPress enter to continue...";
            $tmp = <STDIN>;
        } elsif ( $input == "0\n" ) {
            $sm_debug_mode = 0;
        } elsif ( $input == "1\n" ) {
            if ($sm_debug_mode & 1) {
                $sm_debug_mode ^= 1;
            } else {
                $sm_debug_mode |= 1;
            }
        } elsif ( $input == "2\n" ) {
            if ($sm_debug_mode & 512) {
                $sm_debug_mode ^= 512;
            } else {
                $sm_debug_mode |= 512;
            }
        } elsif ( $input == "3\n" ) {
            if ($sm_debug_mode & 524288) {
                $sm_debug_mode ^= 524288;
            } else {
                $sm_debug_mode |= 524288;
            }
        } elsif ( $input == "4\n" ) {
            if ($sm_debug_mode & 536870912) {
                $sm_debug_mode ^= 536870912;
            } else {
                $sm_debug_mode |= 536870912;
            }
        } else {
            print "\nInvalid configuration value.\n";
            print "\nPress enter to continue...";
            $tmp = <STDIN>;
        }
        print "\n";
    }
    $sm_debug_mode = convert_debug_binary_integer_to_constants($sm_debug_mode);
    return $sm_debug_mode;
}

# Secured configuration mode (since 1.5.2)
sub commandB9 {
    print "This option allows you to enable \"Secured Configuration\" mode,\n";
    print "which will guarantee that certain settings made herein will be\n";
    print "made immutable and will not be subject to override by either friendly\n";
    print "or unfriendly code/plugins.  Only a small number of settings herein\n";
    print "will be used in this manner - just those that are deemed to be a\n";
    print "potential security threat when rouge plugin or other code may be\n";
    print "executed inside SquirrelMail.\n";
    print "\n";

    if ( lc($secured_config) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }
    print "Enable secured configuration mode? (y/n) [$WHT$default_value$NRM]: $WHT";
    $secured_config = <STDIN>;
    if ( ( $secured_config =~ /^y\n/i ) || ( ( $secured_config =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $secured_config = 'true';
    } else {
        $secured_config = 'false';
    }
    return $secured_config;
}

# Set a (non-standard) HTTPS port
sub commandB10 {
    print "If you run HTTPS (SSL-secured HTTP) on a non-standard port, you should\n";
    print "indicate that port here.  Even if you do not, SquirrelMail may still\n";
    print "auto-detect secure connections, but it is safer and also very useful\n";
    print "for third party plugins if you specify the port number here.\n";
    print "\n";
    print "Most SquirrelMail administrators will not need to use this setting\n";
    print "because most all web servers use port 443 for HTTPS connections, and\n";
    print "SquirrelMail assumes 443 unless something else is given here.\n";
    print "\n";

    print "Enter your HTTPS port [$sq_https_port]: ";
    my $tmp = <STDIN>;
    $tmp = trim($tmp);
    # value is not modified, if user hits Enter or enters space
    if ($tmp ne '') {
        # make sure that input is numeric
        if ($tmp =~ /^\d+$/) {
            $sq_https_port = $tmp;
        } else {
            print "\n";
            print "--- INPUT ERROR ---\n";
            print "\n";
            print "If you want to change this setting, you must enter a number.\n";
            print "If you want to keep the original value, just press Enter.\n\n";
            print "Press Enter to continue...";
            $tmp = <STDIN>;
        }
    }
    return $sq_https_port;
}

# Ignore HTTP_X_FORWARDED_* headers?
sub commandB11 {

    if ( lc($sq_ignore_http_x_forwarded_headers) eq 'true' ) {
        $default_value = "y";
    } else {
        $default_value = "n";
    }

    print "Because HTTP_X_FORWARDED_* headers can be sent by the client and\n";
    print "therefore possibly exploited by an outsider, SquirrelMail ignores\n";
    print "them by default. If a proxy server or other machine sits between\n";
    print "clients and your SquirrelMail server, you can turn this off to\n";
    print "tell SquirrelMail to use such headers.\n";
    print "\n";

    print "Ignore HTTP_X_FORWARDED headers? (y/n) [$WHT$default_value$NRM]: $WHT";
    $sq_ignore_http_x_forwarded_headers = <STDIN>;
    if ( ( $sq_ignore_http_x_forwarded_headers =~ /^y\n/i ) || ( ( $sq_ignore_http_x_forwarded_headers =~ /^\n/ ) && ( $default_value eq "y" ) ) ) {
        $sq_ignore_http_x_forwarded_headers = 'true';
    } else {
        $sq_ignore_http_x_forwarded_headers = 'false';
    }
    return $sq_ignore_http_x_forwarded_headers;
}

sub save_data {
    $tab = "    ";
    if ( open( CF, ">config.php" ) ) {
        print CF "<?php\n";
        print CF "\n";

        print CF "/**\n";
        print CF " * SquirrelMail Configuration File\n";
        print CF " * Created using the configure script, conf.pl\n";
        print CF " */\n";
        print CF "\n";

        if ($print_config_version) {
            print CF "\$config_version = '$print_config_version';\n";
        }
        # integer
        print CF "\$config_use_color = $config_use_color;\n";
        print CF "\n";

        # string
        print CF "\$org_name      = \"$org_name\";\n";
        # string
        print CF "\$org_logo      = " . &change_to_SM_path($org_logo) . ";\n";
        $org_logo_width |= 0;
        $org_logo_height |= 0;
        # string
        print CF "\$org_logo_width  = '$org_logo_width';\n";
        # string
        print CF "\$org_logo_height = '$org_logo_height';\n";
        # string that can contain variables.
        print CF "\$org_title     = \"$org_title\";\n";
        # string
        print CF "\$signout_page  = " . &change_to_SM_path($signout_page) . ";\n";
        # string
        print CF "\$frame_top     = '$frame_top';\n";
        print CF "\n";

        print CF "\$provider_uri     = '$provider_uri';\n";
        print CF "\n";

        print CF "\$provider_name     = '$provider_name';\n";
        print CF "\n";

        # string that can contain variables
        print CF "\$motd = \"$motd\";\n";
        print CF "\n";

        # string
        print CF "\$squirrelmail_default_language = '$squirrelmail_default_language';\n";
        # string
        print CF "\$default_charset          = '$default_charset';\n";
        # boolean
        print CF "\$show_alternative_names   = $show_alternative_names;\n";
        # boolean
        print CF "\$aggressive_decoding   = $aggressive_decoding;\n";
        # boolean
        print CF "\$lossy_encoding        = $lossy_encoding;\n";
        print CF "\n";

        # string
        print CF "\$domain                 = '$domain';\n";
        # string
        print CF "\$imapServerAddress      = '$imapServerAddress';\n";
        # integer
        print CF "\$imapPort               = $imapPort;\n";
        # boolean
        print CF "\$useSendmail            = $useSendmail;\n";
        # string
        print CF "\$smtpServerAddress      = '$smtpServerAddress';\n";
        # integer
        print CF "\$smtpPort               = $smtpPort;\n";
        # string
        print CF "\$sendmail_path          = '$sendmail_path';\n";
        # string
        print CF "\$sendmail_args          = '$sendmail_args';\n";
        # boolean
#        print CF "\$use_authenticated_smtp = $use_authenticated_smtp;\n";
        # boolean
        print CF "\$pop_before_smtp        = $pop_before_smtp;\n";
        # string
        print CF "\$pop_before_smtp_host   = '$pop_before_smtp_host';\n";
        # string
        print CF "\$imap_server_type       = '$imap_server_type';\n";
        # boolean
        print CF "\$invert_time            = $invert_time;\n";
        # string
        print CF "\$optional_delimiter     = '$optional_delimiter';\n";
        # string
        print CF "\$encode_header_key      = '$encode_header_key';\n";
        print CF "\n";

        # string
        print CF "\$default_folder_prefix          = '$default_folder_prefix';\n";
        # string
        print CF "\$trash_folder                   = '$trash_folder';\n";
        # string
        print CF "\$sent_folder                    = '$sent_folder';\n";
        # string
        print CF "\$draft_folder                   = '$draft_folder';\n";
        # boolean
        print CF "\$default_move_to_trash          = $default_move_to_trash;\n";
        # boolean
        print CF "\$default_move_to_sent           = $default_move_to_sent;\n";
        # boolean
        print CF "\$default_save_as_draft          = $default_save_as_draft;\n";
        # boolean
        print CF "\$show_prefix_option             = $show_prefix_option;\n";
        # boolean
        print CF "\$list_special_folders_first     = $list_special_folders_first;\n";
        # boolean
        print CF "\$use_special_folder_color       = $use_special_folder_color;\n";
        # boolean
        print CF "\$auto_expunge                   = $auto_expunge;\n";
        # boolean
        print CF "\$default_sub_of_inbox           = $default_sub_of_inbox;\n";
        # boolean
        print CF "\$show_contain_subfolders_option = $show_contain_subfolders_option;\n";
        # integer
        print CF "\$default_unseen_notify          = $default_unseen_notify;\n";
        # integer
        print CF "\$default_unseen_type            = $default_unseen_type;\n";
        # boolean
        print CF "\$auto_create_special            = $auto_create_special;\n";
        # boolean
        print CF "\$delete_folder                  = $delete_folder;\n";
        # boolean
        print CF "\$noselect_fix_enable            = $noselect_fix_enable;\n";

        print CF "\n";

        # string
        print CF "\$data_dir                 = " . &change_to_SM_path($data_dir) . ";\n";
        # string that can contain a variable
        print CF "\$attachment_dir           = " . &change_to_SM_path($attachment_dir) . ";\n";
        # integer
        print CF "\$dir_hash_level           = $dir_hash_level;\n";
        # string
        print CF "\$default_left_size        = '$default_left_size';\n";
        # boolean
        print CF "\$force_username_lowercase = $force_username_lowercase;\n";
        # boolean
        print CF "\$default_use_priority     = $default_use_priority;\n";
        # boolean
        print CF "\$hide_sm_attributions     = $hide_sm_attributions;\n";
        # boolean
        print CF "\$default_use_mdn          = $default_use_mdn;\n";
        # boolean
        print CF "\$edit_identity            = $edit_identity;\n";
        # boolean
        print CF "\$edit_name                = $edit_name;\n";
        # boolean
        print CF "\$edit_reply_to            = $edit_reply_to;\n";
        # boolean
        print CF "\$hide_auth_header         = $hide_auth_header;\n";
        # boolean
        print CF "\$disable_thread_sort      = $disable_thread_sort;\n";
        # boolean
        print CF "\$disable_server_sort      = $disable_server_sort;\n";
        # boolean
        print CF "\$allow_charset_search     = $allow_charset_search;\n";
        # integer
        print CF "\$allow_advanced_search    = $allow_advanced_search;\n";
        print CF "\n";
        # integer
        print CF "\$time_zone_type           = $time_zone_type;\n";
        print CF "\n";
        # string
        print CF "\$config_location_base     = '$config_location_base';\n";
        print CF "\n";
        # boolean
        print CF "\$disable_plugins          = $disable_plugins;\n";
        # string
        print CF "\$disable_plugins_user     = '$disable_plugins_user';\n";
        print CF "\n";

        # all plugins are strings
        for ( $ct = 0 ; $ct <= $#plugins ; $ct++ ) {
            print CF "\$plugins[] = '$plugins[$ct]';\n";
        }
        print CF "\n";

        # strings
        if ( $user_theme_default eq '' ) { $user_theme_default = '0'; }
        print CF "\$user_theme_default = $user_theme_default;\n";

        for ( $count = 0 ; $count <= $#user_theme_name ; $count++ ) {
            if ($user_theme_path[$count] eq 'none') {
                $path = '\'none\'';
            } else {
                $path = &change_to_SM_path($user_theme_path[$count]);
            }
            print CF "\$user_themes[$count]['PATH'] = " . $path . ";\n";
            # escape theme name so it can contain single quotes.
            $esc_name =  $user_theme_name[$count];
            $esc_name =~ s/\\/\\\\/g;
            $esc_name =~ s/'/\\'/g;
            print CF "\$user_themes[$count]['NAME'] = '$esc_name';\n";
        }
        print CF "\n";

        if ( $icon_theme_def eq '' ) { $icon_theme_def = '0'; }
        print CF "\$icon_theme_def = $icon_theme_def;\n";
        if ( $icon_theme_fallback eq '' ) { $icon_theme_fallback = '0'; }
	    print CF "\$icon_theme_fallback = $icon_theme_fallback;\n";
	    
        for ( $count = 0 ; $count <= $#icon_theme_name ; $count++ ) {
            $path = $icon_theme_path[$count];
            if ($path eq 'none' || $path eq 'template') {
                $path = "'".$path."'";
            } else {
                $path = &change_to_SM_path($icon_theme_path[$count]);
            }
            print CF "\$icon_themes[$count]['PATH'] = " . $path . ";\n";
            # escape theme name so it can contain single quotes.
            $esc_name =  $icon_theme_name[$count];
            $esc_name =~ s/\\/\\\\/g;
            $esc_name =~ s/'/\\'/g;
            print CF "\$icon_themes[$count]['NAME'] = '$esc_name';\n";
        }
        print CF "\n";

        if ( $templateset_default eq '' ) { $templateset_default = 'default'; }
        print CF "\$templateset_default = '$templateset_default';\n";

        if ( $templateset_fallback eq '' ) { $templateset_fallback = 'default'; }
        print CF "\$templateset_fallback = '$templateset_fallback';\n";

        if ( $rpc_templateset eq '' ) { $rpc_templateset = 'default_rpc'; }
        print CF "\$rpc_templateset = '$rpc_templateset';\n";

        for ( $count = 0 ; $count <= $#templateset_name ; $count++ ) {

            # don't include RPC template sets
            #
            if ( $templateset_id[$count] =~ /_rpc$/ ) { next; }

            print CF "\$aTemplateSet[$count]['ID'] = '" . $templateset_id[$count] . "';\n";
            # escape theme name so it can contain single quotes.
            $esc_name =  $templateset_name[$count];
            $esc_name =~ s/\\/\\\\/g;
            $esc_name =~ s/'/\\'/g;
            print CF "\$aTemplateSet[$count]['NAME'] = '$esc_name';\n";
        }
        print CF "\n";


        # integer
        print CF "\$default_fontsize = '$default_fontsize';\n";
        # string
        print CF "\$default_fontset = '$default_fontset';\n";
        print CF "\n";
        # assoc. array (maybe initial value should be set somewhere else)
        print CF '$fontsets = array();'."\n";
        while (($fontset_name, $fontset_value) = each(%fontsets)) {
            print CF "\$fontsets\['$fontset_name'\] = '$fontset_value';\n";
        }
        print CF "\n";

        ## Address books
        # boolean
        print CF "\$default_use_javascript_addr_book = $default_use_javascript_addr_book;\n";
        for ( $count = 0 ; $count <= $#ldap_host ; $count++ ) {
            print CF "\$ldap_server[$count] = array(\n";
            # string
            print CF "    'host' => '$ldap_host[$count]',\n";
            # string
            print CF "    'base' => '$ldap_base[$count]'";
            if ( $ldap_name[$count] ) {
                print CF ",\n";
                # string
                print CF "    'name' => '$ldap_name[$count]'";
            }
            if ( $ldap_port[$count] ) {
                print CF ",\n";
                # integer
                print CF "    'port' => $ldap_port[$count]";
            }
            if ( $ldap_charset[$count] ) {
                print CF ",\n";
                # string
                print CF "    'charset' => '$ldap_charset[$count]'";
            }
            if ( $ldap_maxrows[$count] ) {
                print CF ",\n";
                # integer
                print CF "    'maxrows' => $ldap_maxrows[$count]";
            }
            # string
            if ( $ldap_filter[$count] ) {
                print CF ",\n";
                print CF "    'filter' => '$ldap_filter[$count]'";
            }
            if ( $ldap_binddn[$count] ) {
                print CF ",\n";
                # string
                print CF "    'binddn' => '$ldap_binddn[$count]'";
                if ( $ldap_bindpw[$count] ) {
                    print CF ",\n";
                    # string
                    print CF "    'bindpw' => '$ldap_bindpw[$count]'";
                }
            }
            if ( $ldap_protocol[$count] ) {
                print CF ",\n";
                # integer
                print CF "    'protocol' => $ldap_protocol[$count]";
            }
            if ( $ldap_limit_scope[$count] ) {
                print CF ",\n";
                # boolean
                print CF "    'limit_scope' => $ldap_limit_scope[$count]";
            }
            if ( $ldap_listing[$count] ) {
                print CF ",\n";
                # boolean
                print CF "    'listing' => $ldap_listing[$count]";
            }
           if ( $ldap_writeable[$count] ) {
                print CF ",\n";
                # boolean
                print CF "    'writeable' => $ldap_writeable[$count]";
            }
            if ( $ldap_search_tree[$count] ) {
                print CF ",\n";
                # integer
                print CF "    'search_tree' => $ldap_search_tree[$count]";
            }
            if ( $ldap_starttls[$count] ) {
                print CF ",\n";
                # boolean
                print CF "    'starttls' => $ldap_starttls[$count]";
            }
            print CF "\n";
            print CF ");\n";
            print CF "\n";
        }

        # string
        print CF "\$addrbook_dsn = '$addrbook_dsn';\n";
        # string
        print CF "\$addrbook_table = '$addrbook_table';\n\n";
        # string
        print CF "\$prefs_dsn = '$prefs_dsn';\n";
        # string
        print CF "\$prefs_table = '$prefs_table';\n";
        # string
        print CF "\$prefs_user_field = '$prefs_user_field';\n";
        # integer
        print CF "\$prefs_user_size = $prefs_user_size;\n";
        # string
        print CF "\$prefs_key_field = '$prefs_key_field';\n";
        # integer
        print CF "\$prefs_key_size = $prefs_key_size;\n";
        # string
        print CF "\$prefs_val_field = '$prefs_val_field';\n";
        # integer
        print CF "\$prefs_val_size = $prefs_val_size;\n\n";
        # string
        print CF "\$addrbook_global_dsn = '$addrbook_global_dsn';\n";
        # string
        print CF "\$addrbook_global_table = '$addrbook_global_table';\n";
        # boolean
        print CF "\$addrbook_global_writeable = $addrbook_global_writeable;\n";
        # boolean
        print CF "\$addrbook_global_listing = $addrbook_global_listing;\n\n";
        # string
        print CF "\$abook_global_file = '$abook_global_file';\n";
        # boolean
        print CF "\$abook_global_file_writeable = $abook_global_file_writeable;\n\n";
        # boolean
        print CF "\$abook_global_file_listing = $abook_global_file_listing;\n\n";
        # integer
        print CF "\$abook_file_line_length = $abook_file_line_length;\n\n";
        # boolean
        print CF "\$no_list_for_subscribe = $no_list_for_subscribe;\n";

        # string
        print CF "\$smtp_auth_mech        = '$smtp_auth_mech';\n";
        print CF "\$smtp_sitewide_user    = '". quote_single($smtp_sitewide_user) ."';\n";
        print CF "\$smtp_sitewide_pass    = '". quote_single($smtp_sitewide_pass) ."';\n";
        # string
        print CF "\$imap_auth_mech        = '$imap_auth_mech';\n";
        # boolean
        print CF "\$use_imap_tls          = $use_imap_tls;\n";
        # boolean
        print CF "\$use_smtp_tls          = $use_smtp_tls;\n";
        # boolean
        print CF "\$display_imap_login_error = $display_imap_login_error;\n";
        # string
        print CF "\$session_name          = '$session_name';\n";
        # boolean
        print CF "\$only_secure_cookies     = $only_secure_cookies;\n";
        print CF "\$disable_security_tokens = $disable_security_tokens;\n";

        # string
        print CF "\$check_referrer          = '$check_referrer';\n";

        # boolean
        print CF "\$use_transparent_security_image = $use_transparent_security_image;\n";

        print CF "\n";

        # boolean
        print CF "\$use_iframe = $use_iframe;\n";
        # boolean
        print CF "\$ask_user_info = $ask_user_info;\n";
        # boolean
        print CF "\$use_icons = $use_icons;\n";
        print CF "\n";
        # boolean
        print CF "\$use_php_recode = $use_php_recode;\n";
        # boolean
        print CF "\$use_php_iconv = $use_php_iconv;\n";
        print CF "\n";
        # boolean
        print CF "\$buffer_output = $buffer_output;\n";
        print CF "\n";
        # boolean
        print CF "\$allow_remote_configtest = $allow_remote_configtest;\n";
        print CF "\$secured_config = $secured_config;\n";
        # integer
        print CF "\$sq_https_port = $sq_https_port;\n";
        # boolean
        print CF "\$sq_ignore_http_x_forwarded_headers = $sq_ignore_http_x_forwarded_headers;\n";
        # (binary) integer or constant - convert integer 
        # values to constants before output
        $sm_debug_mode = convert_debug_binary_integer_to_constants($sm_debug_mode);
        print CF "\$sm_debug_mode = $sm_debug_mode;\n";
        print CF "\n";

        close CF;

        print "Data saved in config.php\n";

        build_plugin_hook_array();

    } else {
        print "Error saving config.php: $!\n";
    }
}

sub set_defaults {
    clear_screen();
    print $WHT. "SquirrelMail Configuration : " . $NRM;
    if    ( $config == 1 ) { print "Read: config.php"; }
    elsif ( $config == 2 ) { print "Read: config_default.php"; }
    print "\n";
    print "---------------------------------------------------------\n";

    print "While we have been building SquirrelMail, we have discovered some\n";
    print "preferences that work better with some servers that don't work so\n";
    print "well with others.  If you select your IMAP server, this option will\n";
    print "set some pre-defined settings for that server.\n";
    print "\n";
    print "Please note that you will still need to go through and make sure\n";
    print "everything is correct.  This does not change everything.  There are\n";
    print "only a few settings that this will change.\n";
    print "\n";

    $continue = 0;
    while ( $continue != 1 ) {
        print "Please select your IMAP server:\n";
        print $list_supported_imap_servers;
        print "\n";
        print "    quit        = Do not change anything\n";
        print "\n";
        print "Command >> ";
        $server = <STDIN>;
        $server =~ s/[\r\n]//g;

        # variable is used to display additional messages.
        $message = "";

        print "\n";
        if ( $server eq "cyrus" ) {
            $imap_server_type               = "cyrus";
            $default_folder_prefix          = "";
            $trash_folder                   = "INBOX.Trash";
            $sent_folder                    = "INBOX.Sent";
            $draft_folder                   = "INBOX.Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = true;
            $show_contain_subfolders_option = false;
            $optional_delimiter             = ".";
            $disp_default_folder_prefix     = "<none>";
            $force_username_lowercase       = false;

            # Delimiter might differ if unixhierarchysep is set to yes.
            $message = "\nIf you have enabled unixhierarchysep, you must change delimiter and special folder names.\n";

            $continue = 1;
        } elsif ( $server eq "uw" ) {
            $imap_server_type               = "uw";
            $default_folder_prefix          = "mail/";
            $trash_folder                   = "Trash";
            $sent_folder                    = "Sent";
            $draft_folder                   = "Drafts";
            $show_prefix_option             = true;
            $default_sub_of_inbox           = false;
            $show_contain_subfolders_option = true;
            $optional_delimiter             = "/";
            $disp_default_folder_prefix     = $default_folder_prefix;
            $delete_folder                  = true;
            $force_username_lowercase       = true;

            $continue = 1;
        } elsif ( $server eq "exchange" ) {
            $imap_server_type               = "exchange";
            $default_folder_prefix          = "";
            $default_sub_of_inbox           = true;
            $trash_folder                   = "INBOX/Deleted Items";
            $sent_folder                    = "INBOX/Sent Items";
            $drafts_folder                  = "INBOX/Drafts";
            $show_prefix_option             = false;
            $show_contain_subfolders_option = false;
            $optional_delimiter             = "detect";
            $disp_default_folder_prefix     = "<none>";
            $force_username_lowercase       = true;

            $continue = 1;
        } elsif ( $server eq "courier" ) {
            $imap_server_type               = "courier";
            $default_folder_prefix          = "INBOX.";
            $trash_folder                   = "Trash";
            $sent_folder                    = "Sent";
            $draft_folder                   = "Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = false;
            $show_contain_subfolders_option = false;
            $optional_delimiter             = ".";
            $disp_default_folder_prefix     = $default_folder_prefix;
            $delete_folder                  = true;
            $force_username_lowercase       = false;

            $continue = 1;
        } elsif ( $server eq "macosx" ) {
            $imap_server_type               = "macosx";
            $default_folder_prefix          = "INBOX/";
            $trash_folder                   = "Trash";
            $sent_folder                    = "Sent";
            $draft_folder                   = "Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = true;
            $show_contain_subfolders_option = false;
            $optional_delimiter             = "detect";
            $allow_charset_search           = false;
            $disp_default_folder_prefix     = $default_folder_prefix;

            $continue = 1;
        } elsif ( $server eq "hmailserver" ) {
            $imap_server_type               = "hmailserver";
            $default_folder_prefix          = "";
            $trash_folder                   = "INBOX.Trash";
            $sent_folder                    = "INBOX.Sent";
            $draft_folder                   = "INBOX.Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = true;
            $show_contain_subfolders_option = false;
            $optional_delimiter             = "detect";
            $allow_charset_search           = false;
            $disp_default_folder_prefix     = $default_folder_prefix;
            $delete_folder                  = false;
            $force_username_lowercase       = false;

            $continue = 1;
        } elsif ( $server eq "mercury32" ) {
            $imap_server_type               = "mercury32";
            $default_folder_prefix          = "";
            $trash_folder                   = "Trash";
            $sent_folder                    = "Sent";
            $draft_folder                   = "Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = true;
            $show_contain_subfolders_option = true;
            $optional_delimiter             = "detect";
            $delete_folder                  = true;
            $force_username_lowercase       = true;

            $continue = 1;
        } elsif ( $server eq "dovecot" ) {
            $imap_server_type               = "dovecot";
            $default_folder_prefix          = "";
            $trash_folder                   = "Trash";
            $sent_folder                    = "Sent";
            $draft_folder                   = "Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = false;
            $show_contain_subfolders_option = false;
            $delete_folder                  = false;
            $force_username_lowercase       = true;
            $optional_delimiter             = "detect";
            $disp_default_folder_prefix     = "<none>";

            $continue = 1;
        } elsif ( $server eq "bincimap" ) {
            $imap_server_type               = "bincimap";
            $default_folder_prefix          = "INBOX/";
            $trash_folder                   = "Trash";
            $sent_folder                    = "Sent";
            $draft_folder                   = "Drafts";
            $show_prefix_option             = false;
            $default_sub_of_inbox           = false;
            $show_contain_subfolders_option = false;
            $delete_folder                  = true;
            $force_username_lowercase       = false;
            $optional_delimiter             = "detect";
            $disp_default_folder_prefix     = $default_folder_prefix;

            # Default folder prefix depends on used depot.
            $message = "\nIf you use IMAPdir depot, you must set default folder prefix to empty string.\n";

            $continue = 1;
        } elsif ( $server eq "gmail" ) {
            $imap_server_type               = "gmail";
            $default_folder_prefix          = "";
            $trash_folder                   = "[Gmail]/Trash";
            $default_move_to_trash          = true;
            $sent_folder                    = "[Gmail]/Sent Mail";
            $draft_folder                   = "[Gmail]/Drafts";
            $auto_create_special            = false;
            $show_prefix_option             = false;
            $default_sub_of_inbox           = false;
            $show_contain_subfolders_option = false;
            $delete_folder                  = true;
            $force_username_lowercase       = false;
            $optional_delimiter             = "/";
            $disp_default_folder_prefix     = "<none>";
            $domain                         = "gmail.com";
            $imapServerAddress              = "imap.gmail.com";
            $imapPort                       = 993;
            $use_imap_tls                   = true;
            $imap_auth_mech                 = "login";
            $smtpServerAddress              = "smtp.gmail.com";
            $smtpPort                       = 465;
            $pop_before_smtp                = false;
            $useSendmail                    = false;
            $use_smtp_tls                   = true;
            $smtp_auth_mech                 = "login";
            $continue = 1;

            # Gmail changes system folder names (Drafts, Sent, Trash) out
            # from under you when the user changes language settings
            $message = "\nNOTE!  When a user changes languages in Gmail's interface, the\n" 
                     . "Drafts, Sent and Trash folder names are changed to localized\n"
                     . "versions thereof.  To see those folders correctly in SquirrelMail,\n"
                     . "the user should change the SquirrelMail language to match.\n"
                     . "Moreover, SquirrelMail then needs to be told what folders to use\n"
                     . "for Drafts, Sent and Trash in Options --> Folder Preferences.\n"
                     . "These default settings will only correctly find the Sent, Trash\n"
                     . "and Drafts folders if both Gmail and SquirrelMail languages are\n"
                     . "set to English.\n\n"
                     . "Also note that in some regions (Europe?), the default folder\n"
                     . "names (see main menu selection 3. Folder Defaults) are different\n"
                     . "(they may need to have the prefix \"[Google Mail]\" instead of\n"
                     . "\"[Gmail]\") and \"Trash\" may be called \"Bin\" instead.\n";

        } elsif ( $server eq "quit" ) {
            $continue = 1;
        } else {
            $disp_default_folder_prefix = $default_folder_prefix;
            print "Unrecognized server: $server\n";
            print "\n";
        }

        print "              imap_server_type = $imap_server_type\n";
        print "         default_folder_prefix = $disp_default_folder_prefix\n";
        print "                  trash_folder = $trash_folder\n";
        print "                   sent_folder = $sent_folder\n";
        print "                  draft_folder = $draft_folder\n";
        print "            show_prefix_option = $show_prefix_option\n";
        print "          default_sub_of_inbox = $default_sub_of_inbox\n";
        print "show_contain_subfolders_option = $show_contain_subfolders_option\n";
        print "            optional_delimiter = $optional_delimiter\n";
        print "                 delete_folder = $delete_folder\n";
        print "      force_username_lowercase = $force_username_lowercase\n";

        print "$message";
    }
    print "\nPress enter to continue...";
    $tmp = <STDIN>;
}

# This subroutine corrects relative paths to ensure they
# will work within the SM space. If the path falls within
# the SM directory tree, the SM_PATH variable will be
# prepended to the path, if not, then the path will be
# converted to an absolute path, e.g.
#   '../images/logo.gif'        --> SM_PATH . 'images/logo.gif'
#   '../../someplace/data'      --> '/absolute/path/someplace/data'
#   'images/logo.gif'           --> SM_PATH . 'config/images/logo.gif'
#   '/absolute/path/logo.gif'   --> '/absolute/path/logo.gif'
#   'C:\absolute\path\logo.gif' --> 'C:\absolute\path\logo.gif'
#   'http://whatever/'          --> 'http://whatever'
#   $some_var/path              --> "$some_var/path"
sub change_to_SM_path() {
    my ($old_path) = @_;
    my $new_path = '';
    my @rel_path;
    my @abs_path;
    my $subdir;

    # If the path is absolute, don't bother.
    return "\'" . $old_path . "\'"  if ( $old_path eq '');
    return "\'" . $old_path . "\'"  if ( $old_path =~ /^(\/|http)/ );
    return "\'" . $old_path . "\'"  if ( $old_path =~ /^\w:(\\|\/)/ );
    return $old_path                if ( $old_path =~ /^\'(\/|http)/ );
    return $old_path                if ( $old_path =~ /^\'\w:\// );
    return $old_path                if ( $old_path =~ /^SM_PATH/);

    if ( $old_path =~ /^\$/ ) {
        # check if it's a single var, or a $var/path combination
        # if it's $var/path, enclose in ""
        if ( $old_path =~ /\// ) {
            return '"'.$old_path.'"';
        }
        return $old_path;
    }

    # Remove remaining '
    $old_path =~ s/\'//g;

    # For relative paths, split on '../'
    @rel_path = split(/\.\.\//, $old_path);

    if ( $#rel_path > 1 ) {
        # more than two levels away. Make it absolute.
        @abs_path = split(/\//, $dir);

        # Lop off the relative pieces of the absolute path..
        for ( $i = 0; $i <= $#rel_path; $i++ ) {
            pop @abs_path;
            shift @rel_path;
        }
        push @abs_path, @rel_path;
        $new_path = "\'" . join('/', @abs_path) . "\'";
    } elsif ( $#rel_path > 0 ) {
        # it's within the SM tree, prepend SM_PATH
        $new_path = $old_path;
        $new_path =~ s/^\.\.\//SM_PATH . \'/;
        $new_path .= "\'";
    } else {
        # Last, it's a relative path without any leading '.'
    # Prepend SM_PATH and config, since the paths are
    # relative to the config directory
        $new_path = "SM_PATH . \'config/" . $old_path . "\'";
    }
  return $new_path;
}


# Change SM_PATH to admin-friendly version, e.g.:
#  SM_PATH . 'images/logo.gif' --> '../images/logo.gif'
#  SM_PATH . 'config/some.php' --> 'some.php'
#  '/absolute/path/logo.gif'   --> '/absolute/path/logo.gif'
#  'http://whatever/'          --> 'http://whatever'
sub change_to_rel_path() {
    my ($old_path) = @_;
    my $new_path = $old_path;

    if ( $old_path =~ /^SM_PATH/ ) {
        # FIXME: the following replacement loses the opening quote mark!
        $new_path =~ s/^SM_PATH . \'/\.\.\//;
        $new_path =~ s/\.\.\/config\///;
    }

    return $new_path;
}

# Attempts to auto-detect if a specific auth mechanism is supported.
# Called by 'command112a' and 'command112b'
# ARGS: service-name (IMAP or SMTP), host:port, mech-name (ie. CRAM-MD5)
sub detect_auth_support {
    # Try loading IO::Socket
    unless (eval("use IO::Socket; 1")) {
        print "Perl IO::Socket module is not available.";
        return undef;
    }
    # Misc setup
    my $service = shift;
    my $host = shift;
    my $mech = shift;
    # Sanity checks
    if ((!defined($service)) or (!defined($host)) or (!defined($mech))) {
      # Error - wrong # of args
      print "BAD ARGS!\n";
      return undef;
    }

    if ($service eq 'SMTP') {
        $cmd = "AUTH $mech\r\n";
        $logout = "QUIT\r\n";
    } elsif ($service eq 'IMAP') {
        $cmd = "A01 AUTHENTICATE $mech\n";
        $logout = "C01 LOGOUT\n";
    } else {
        # unknown service - whoops.
        return undef;
    }

    # Get this show on the road
    my $sock=IO::Socket::INET->new($host);
    if (!defined($sock)) {
        # Connect failed
        return undef;
    }
    my $discard = <$sock>; # Server greeting/banner - who cares..

    if ($service eq 'SMTP') {
        # Say hello first..
        print $sock "HELO $domain\r\n";
        $discard = <$sock>; # Yeah yeah, you're happy to see me..
    }
    print $sock $cmd;

    my $response = <$sock>;
    chomp($response);
    if (!defined($response)) {
        return undef;
    }

    # So at this point, we have a response, and it is (hopefully) valid.
    if ($service eq 'SMTP') {
        if (!($response =~ /^334/)) {
            # Not supported
            print $sock $logout;
            close $sock;
            return 'NO';
        }
    } elsif ($service eq 'IMAP') {
        if ($response =~ /^A01/) {
            # Not supported
            print $sock $logout;
            close $sock;
            return 'NO';
        }
    } else {
        # Unknown service - this shouldn't be able to happen.
        close $sock;
        return undef;
    }

    # If it gets here, the mech is supported
    print $sock "*\n";  # Attempt to cancel authentication
    print $sock $logout; # Try to log out, but we don't really care if this fails
    close $sock;
    return 'YES';
}

# trims whitespace
# Example code from O'Reilly Perl Cookbook
sub trim {
    my @out = @_;
    for (@out) {
        s/^\s+//;
        s/\s+$//;
    }
    return wantarray ? @out : $out[0];
}

sub clear_screen() {
    if ( $^O =~ /^mswin/i) {
        system "cls";
    } else {
        system "clear";
    }
}

# checks IMAP mailbox name. Refuses to accept 8bit folders
# returns 0 (folder name is not correct) or 1 (folder name is correct)
sub check_imap_folder($) {
    my $folder_name = shift(@_);

    if ($folder_name =~ /[\x80-\xFFFF]/) {
        print "Folder name contains 8bit characters. Configuration utility requires\n";
        print "UTF7-IMAP encoded folder names.\n";
        print "Press enter to continue...";
        my $tmp = <STDIN>;
        return 0;
    } elsif ($folder_name =~ /[&\*\%]/) {
        # check for ampersand and list-wildcards
        print "Folder name contains special UTF7-IMAP characters.\n";
        print "Are you sure that folder name is correct? (y/N): ";
        my $tmp = <STDIN>;
        $tmp = lc(trim($tmp));
        if ($tmp =~ /^y$/) {
            return 1;
        } else {
            return 0;
        }
    } else {
        return 1;
    }
}

# quotes string written in single quotes
sub quote_single($) {
    my $string = shift(@_);
    $string =~ s/\'/\\'/g;
    return $string;
}

# determine a plugin's version number
#
# parses the setup.php file, looking for the
# version string in the <plugin>_info() or the
# <plugin>_version functions.
#
sub get_plugin_version() {

    my $plugin_name = shift(@_);

    $setup_file = '../plugins/' . $plugin_name . '/setup.php';
    if ( -e "$setup_file" ) {
        # Make sure that file is readable
        if (! -r "$setup_file") {
            print "\n";
            print "WARNING:\n";
            print "The file \"$setup_file\" was found, but you don't\n";
            print "have rights to read it.  The plugin \"";
            print $plugin_name . "\" may not work correctly until you fix this.\n";
            print "\nPress enter to continue";
            $ctu = <STDIN>;
            print "\n";
            next;
        }

        $version = ' ';
# FIXME: grep the file instead of reading it into memory?
        $whole_file = '';
        open( FILE, "$setup_file" );
        while ( $line = <FILE> ) {
            $whole_file .= $line;
        }
        close(FILE);

        # ideally, there is a version in the <plugin>_info function...
        #
        if ($whole_file =~ /('version'\s*=>\s*['"](.*?)['"])/) {
            $version .= $2;

        # this assumes there is only one function that returns 
        # a static string in the setup file
        #
        } elsif ($whole_file =~ /(return\s*['"](.*?)['"])/) {
            $version .= $2;
        }

        return $version;

        } else {
            print "\n";
            print "WARNING:\n";
            print "The file \"$setup_file\" was not found.\n";
            print "The plugin \"" . $plugin_name;
            print "\" may not work correctly until you fix this.\n";
            print "\nPress enter to continue";
            $ctu = <STDIN>;
            print "\n";
            next;
        }

}

# determine a plugin's English name
#
# parses the setup.php file, looking for the
# English name in the <plugin>_info() function.
#
sub get_plugin_english_name() {

    my $plugin_name = shift(@_);

    $setup_file = '../plugins/' . $plugin_name . '/setup.php';
    if ( -e "$setup_file" ) {
        # Make sure that file is readable
        if (! -r "$setup_file") {
            print "\n";
            print "WARNING:\n";
            print "The file \"$setup_file\" was found, but you don't\n";
            print "have rights to read it.  The plugin \"";
            print $plugin_name . "\" may not work correctly until you fix this.\n";
            print "\nPress enter to continue";
            $ctu = <STDIN>;
            print "\n";
            next;
        }

        $english_name = '';
# FIXME: grep the file instead of reading it into memory?
        $whole_file = '';
        open( FILE, "$setup_file" );
        while ( $line = <FILE> ) {
            $whole_file .= $line;
        }
        close(FILE);

        # the English name is in the <plugin>_info function or nothing...
        #
        if ($whole_file =~ /('english_name'\s*=>\s*['"](.*?)['"])/) {
            $english_name .= $2;
        }

        return $english_name;

        } else {
            print "\n";
            print "WARNING:\n";
            print "The file \"$setup_file\" was not found.\n";
            print "The plugin \"" . $plugin_name;
            print "\" may not work correctly until you fix this.\n";
            print "\nPress enter to continue";
            $ctu = <STDIN>;
            print "\n";
            next;
        }

}

# parses the setup.php files for all activated plugins and
# builds static plugin hooks array so we don't have to load
# ALL plugins are runtime and build the hook array on every
# page request
#
# hook array is saved in config/plugin_hooks.php
#
# Note the $verbose variable at the top of this routine
# can be set to zero to quiet it down.
#
# NOTE/FIXME: we aren't necessarily interested in writing
#             a full-blown PHP parsing engine, so plenty
#             of assumptions are included herein about the
#             coding of the plugin setup files, and things
#             like commented out curly braces or other 
#             such oddities can break this in a bad way.
#
sub build_plugin_hook_array() {

    $verbose = 1;

    if ($verbose) {
        print "\n\n";
    }

    if ( open( HOOKFILE, ">plugin_hooks.php" ) ) {
        print HOOKFILE "<?php\n";
        print HOOKFILE "\n";

        print HOOKFILE "/**\n";
        print HOOKFILE " * SquirrelMail Plugin Hook Registration File\n";
        print HOOKFILE " * Auto-generated using the configure script, conf.pl\n";
        print HOOKFILE " */\n";
        print HOOKFILE "\n";
        print HOOKFILE "global \$squirrelmail_plugin_hooks;\n";
        print HOOKFILE "\n";

PLUGIN: for ( $ct = 0 ; $ct <= $#plugins ; $ct++ ) {

        if ($verbose) {
            print "Activating plugin \"" . $plugins[$ct] . "\"...\n";
        }

        $setup_file = '../plugins/' . $plugins[$ct] . '/setup.php';
        if ( -e "$setup_file" ) {
            # Make sure that file is readable
            if (! -r "$setup_file") {
                print "\n";
                print "WARNING:\n";
                print "The file \"$setup_file\" was found, but you don't\n";
                print "have rights to read it.  The plugin \"";
                print $plugins[$ct] . "\" will not be activated until you fix this.\n";
                print "\nPress enter to continue";
                $ctu = <STDIN>;
                print "\n";
                next;
            }
            open( FILE, "$setup_file" );
            $inside_init_fxn = 0;
            $brace_count = 0;
            while ( $line = <FILE> ) {

                # throw away lines until we get to target function
                #
                if (!$inside_init_fxn 
                 && $line !~ /^\s*function\s*squirrelmail_plugin_init_/i) {
                    next;
                } 
                $inside_init_fxn = 1;


                # count open braces
                #
                if ($line =~ /{/) {
                    $brace_count++;
                } 


                # count close braces
                #
                if ($line =~ /}/) {
                    $brace_count--;

                    # leaving <plugin>_init() function...
                    if ($brace_count == 0) {
                        close(FILE);
                        next PLUGIN;
                    }

                } 


                # throw away lines that are not exactly one "brace set" deep
                #
                if ($brace_count > 1) { 
                    next;
                } 


                # also not interested in lines that are not
                # hook registration points
                #
                if ($line !~ /^\s*\$squirrelmail_plugin_hooks/i) {
                    next;
                } 


                # if $line does not have an ending semicolon,
                # we need to recursively read in subsequent 
                # lines until we find one
                while ( $line !~ /;\s*$/ ) {
                    $line =~ s/[\n\r]\s*$//;
                    $line .= <FILE>;
                }


                $line =~ s/^\s+//;
                $line =~ s/^\$//;
                $var = $line;

                $var =~ s/=/EQUALS/;
                if ( $var =~ /^([a-z])/i ) {
                    @options = split ( /\s*EQUALS\s*/, $var );
                    $options[1] =~ s/[\n\r]//g;
                    $options[1] =~ s/[\'\"];\s*$//;
                    $options[1] =~ s/;$//;
                    $options[1] =~ s/^[\'\"]//;
                    # de-escape escaped strings
                    $options[1] =~ s/\\'/'/g;
                    $options[1] =~ s/\\\\/\\/g;

                    if ( $options[0] =~ /^squirrelmail_plugin_hooks\s*\[\s*['"]([a-z0-9 \/._*-]+)['"]\s*\]\s*\[\s*['"]([0-9a-z._-]+)['"]\s*\]/i ) {
                        $hook_name = $1;
                        $hooked_plugin_name = $2;
                        # Note: if we wanted to stop plugins from registering
                        #       a *different* plugin on a hook, we could catch
                        #       it here, however this has actually proven to be
                        #       a useful *feature*
                        #if ($hooked_plugin_name ne $plugins[$ct]) {
                        #    print "...plugin is tring to hook in under different name...\n";
                        #}

#FIXME: do we want to count the number of hook registrations for each plugin and warn if a plugin doesn't have any?
                        # hook registration has been found!
                        if ($verbose) {
                            if ($hooked_plugin_name ne $plugins[$ct]) {
                                print "   registering on hook \"" . $hook_name . "\" (as \"$hooked_plugin_name\" plugin)\n";
                            } else {
                                print "   registering on hook \"" . $hook_name . "\"\n";
                            }
                        }
                        $line =~ s/ {2,}/ /g;
                        $line =~ s/=/\n    =/;
                        print HOOKFILE "\$$line";

                    }

                }

            }
            close(FILE);

        } else {
            print "\n";
            print "WARNING:\n";
            print "The file \"$setup_file\" was not found.\n";
            print "The plugin \"" . $plugins[$ct];
            print "\" will not be activated until you fix this.\n";
            print "\nPress enter to continue";
            $ctu = <STDIN>;
            print "\n";
            next;
        }

    }

    print HOOKFILE "\n\n";
    close(HOOKFILE);
#    if ($verbose) {
        print "\nDone activating plugins; registration data saved in plugin_hooks.php\n\n";
#    }

    } else {

        print "\n";
        print "WARNING:\n";
        print "The file \"plugin_hooks.php\" was not able to be written to.\n";
        print "No plugins will be activated until you fix this.\n";
        print "\nPress enter to continue";
        $ctu = <STDIN>;
        print "\n";

    }

}

# converts (binary) integer values that correspond 
# to the SquirrelMail debug mode constants (see 
# include/constants.php) into those constant strings 
# (bitwise or'd if more than one is enabled)
#
# if the value passed in is not an integer, it is 
# returned unmolested
#
sub convert_debug_binary_integer_to_constants() {

    my ($debug_mode) = @_;
    if ($debug_mode =~ /^[^0-9]/) {
        return $debug_mode;
    }
    $debug_mode = int($debug_mode);
    $new_debug_mode = '';

    # per include/constants.php, here are their values:
    #
    # 0          SM_DEBUG_MODE_OFF
    # 1          SM_DEBUG_MODE_SIMPLE
    # 512        SM_DEBUG_MODE_MODERATE
    # 524288     SM_DEBUG_MODE_ADVANCED
    # 536870912  SM_DEBUG_MODE_STRICT
    #
    if ($debug_mode & 1) {
        $new_debug_mode .= ' | SM_DEBUG_MODE_SIMPLE';
    }
    if ($debug_mode & 512) {
        $new_debug_mode .= ' | SM_DEBUG_MODE_MODERATE';
    }
    if ($debug_mode & 524288) {
        $new_debug_mode .= ' | SM_DEBUG_MODE_ADVANCED';
    }
    if ($debug_mode & 536870912) {
        $new_debug_mode .= ' | SM_DEBUG_MODE_STRICT';
    }

    $new_debug_mode =~ s/^ \| //;
    if (!$new_debug_mode) {
        $new_debug_mode = 'SM_DEBUG_MODE_OFF';
    }

    return $new_debug_mode;
}

# converts SquirrelMail debug mode constants (see
# include/constants.php) into their corresponding
# (binary) integer values
#
# if the value passed in is an integer already, it
# is returned unmolested
#
sub convert_debug_constants_to_binary_integer() {

    my ($debug_mode) = @_;
    if ($debug_mode =~ /^[0-9]/) {
        return $debug_mode;
    }
    $new_debug_mode = 0;

    # per include/constants.php, here are their values:
    #
    # 0          SM_DEBUG_MODE_OFF
    # 1          SM_DEBUG_MODE_SIMPLE
    # 512        SM_DEBUG_MODE_MODERATE
    # 524288     SM_DEBUG_MODE_ADVANCED
    # 536870912  SM_DEBUG_MODE_STRICT
    #
    if ($debug_mode =~ /\bSM_DEBUG_MODE_OFF\b/) {
        $new_debug_mode = 0;
    }
    if ($debug_mode =~ /\bSM_DEBUG_MODE_SIMPLE\b/) {
        $new_debug_mode |= 1;
    }
    if ($debug_mode =~ /\bSM_DEBUG_MODE_MODERATE\b/) {
        $new_debug_mode |= 512;
    }
    if ($debug_mode =~ /\bSM_DEBUG_MODE_ADVANCED\b/) {
        $new_debug_mode |= 524288;
    }
    if ($debug_mode =~ /\bSM_DEBUG_MODE_STRICT\b/) {
        $new_debug_mode |= 536870912;
    }

    return $new_debug_mode;
}

# Function to print n column numbered lists
#
# WARNING: the names in the list will be truncated
# to fit in their respective columns based on the
# screen width and number of columns.
#
# Expected arguments (in this order):
#
#    * The start number to use for the list
#    * The number of columns to use
#    * The screen width
#    * Boolean (zero/one), indicating
#      whether or not to show item numbers
#    * The list of strings to be shown
#
# Returns: The number printed on screen of the last item in the list
#
sub print_multi_col_list {
    my ($num, $cols, $screen_width, $show_numbering, @list) = @_;
    my $x;
    my $col_cnt = 0;
    my $row_cnt = 0;
    my $rows;
    my $col_width;
    my $total = 0;
    my @layout = ();
    my @numbers = ();

    $rows = int(@list / $cols);
    if (@list % $cols) { $rows++; }
    if ($show_numbering) { $col_width = int(($screen_width - 2) / $cols) - 5; }
    else                 { $col_width = int(($screen_width - 2) / $cols) - 2; }

    # build the layout array so numbers run down each column
    #
    for ( $x = 0; $x < @list; $x++ ) {

        $layout[$row_cnt][$col_cnt] = $list[$x];
        $numbers[$row_cnt][$col_cnt] = $num++;

        # move to next column
        #
        if ($row_cnt == $rows - 1) {
            $row_cnt = 0;
            $col_cnt++;
        }
        else { $row_cnt++; }

    }

    # if we filled up fewer rows than needed, recalc column width
    #
    if ($rows * $col_cnt == @list) { $col_cnt--; } # loop above ended right after increment
    if ($col_cnt + 1 < $cols) {
        if ($show_numbering) { $col_width = int(($screen_width - 2) / ($col_cnt + 1)) - 5; }
        else                 { $col_width = int(($screen_width - 2) / ($col_cnt + 1)) - 2; }
    }

    # print it
    # iterate rows
    #
    for ( $row_cnt = 0; $row_cnt <= $rows; $row_cnt++ ) {

        # indent the row
        #
        print " ";

        # iterate columns for this row
        #
        for ( $col_cnt = 0; $col_cnt <= $cols; $col_cnt++ ) {
            if ($layout[$row_cnt][$col_cnt]) {
                print " ";
                if ($show_numbering) { printf "$WHT% 2u.$NRM", $numbers[$row_cnt][$col_cnt]; }
                printf " %-$col_width." . $col_width . "s", $layout[$row_cnt][$col_cnt];
            }
        }
        print "\n";
    }


    return $num - 1;
}

