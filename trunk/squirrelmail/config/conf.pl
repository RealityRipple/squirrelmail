#!/usr/bin/perl
# conf.pl
# Written March 26, 2000
# Luke Ehresman (lehresma@css.tayloru.edu)
#
# A simple configure script to configure squirrelmail
############################################################				  
$WHT = "\x1B[37;1m";
$NRM = "\x1B[0m";

system "clear";
print "\n\n--------------------------------------------------------\n";
print "SquirrelMail version 0.4 -- Configure script\n";
print "by SquirrelMail Development Team\n";
print "http://squirrelmail.sourceforge.net\n";
print "--------------------------------------------------------\n";
print "\n";
 
############################################################				  
# First, lets read in the data already in there...
############################################################				  
if ( -e "config.php") {
	print "The file \"config.php\" exists.  Using it for defaults.\n\n";
	open (FILE, "config.php");
} else {
	print "No config file found.  Reading from config_defaults.php.\n\n";
	open (FILE, "config_default.php");
}

#  Reads and parses the current configuration file (either
#  config.php or config_default.php).

while ($line = <FILE>) {
	if ($line =~ /^\s+\$/) {
		$line =~ s/^\s+\$//;
		$var = $line;
		
		if ($var =~ /^([a-z]|[A-Z])/) {
			@options = split(/\s=\s/, $var);
			$options[1] =~ s/[\n|\r]//g;
			$options[1] =~ s/^"//g;
			$options[1] =~ s/;.*$//g;
			$options[1] =~ s/"$//g;

			if ($options[0] =~ /^special_folders/) {
				if ($options[0] =~ /\[.*\]$/) {
					$sub = $options[0];
					$sub =~ s/\]$//;
					$sub = substr ($sub, @sub-1, 1);

					$special_folders[$sub] = $options[1];
				}	
			} elsif ($options[0] =~ /^theme\[[0-9]+\]\["PATH"\]/) {
				$sub = $options[0];
				$sub =~ s/\]\["PATH"\]//;
				$sub = substr ($sub, @sub-1, 1);
				$theme_path[$sub] = $options[1];
			} elsif ($options[0] =~ /^theme\[[0-9]+\]\["NAME"\]/) {
				$sub = $options[0];
				$sub =~ s/\]\["NAME"\]//;
				$sub = substr ($sub, @sub-1, 1);
				$theme_name[$sub] = $options[1];
			} else {
				${$options[0]} = $options[1];
			}	
		}	
	}
}

open (FILE, ">cf.php");
print FILE "<?\n\t/** SquirrelMail configure script\n";
print FILE "	 ** Created using the configure script, config.pl.\n\t **/\n\n";


#####################################################################################

# org_name
sub command1 {
   print "We have tried to make the name SquirrelMail as transparent as\n";
   print "possible.  If you set up an organization name, most places where\n";
   print "SquirrelMail would take credit will be credited to your organization.\n";
   print "\n";
	print "What is the name of your organization [$org_name]: ";
	$new_org_name = <STDIN>;
	if ($new_org_name eq "\n") {
   	$new_org_name = $org_name;
	} else {
	   $new_org_name =~ s/[\r|\n]//g;
	}
   return $new_org_name;
}

# org_logo
sub command2 {
   print "Your organization's logo is an image that will be displayed at\n";
   print "different times throughout SquirrelMail.  This is asking for the\n";
   print "literal (/usr/local/squirrelmail/images/logo.jpg) or relative\n";
   print "(../images/logo.jpg) path to your logo.\n";
   print "\n";
   print "Where is the logo for your organization [$org_logo]: ";
   $new_org_logo = <STDIN>;
   if ($new_org_logo eq "\n") {
      $new_org_logo = $org_logo;
   } else {
      $new_org_logo =~ s/[\r|\n]//g;
   }
   return $new_org_logo;
}

# org_title
sub command3 {
   print "A title is what is displayed at the top of the browser window in\n";
   print "the titlebar.  Usually this will end up looking something like:\n";
   print "\"Netscape: $org_title\"\n";
   print "\n";
   print "The title of the web page [$org_title]: ";
   $new_org_title = <STDIN>;
   if ($new_org_title eq "\n") {
      $new_org_title = $org_title;
   } else {
      $new_org_title =~ s/[\r|\n]//g;
   }
   return $new_org_title;
}
		  
# domain 
sub command10 {
   print "The domain is what is on the right side of the \@ in the email\n";
   print "address.  If your address is somebody\@somewhere.com, then your\n";
   print "domain would be \"somewhere.com\".\n";
   print "\n";
   print "Your domain [$WHT$domain$NRM]: ".$WHT;
   $new_domain = <STDIN>;
   if ($new_domain eq "\n") {
      $new_domain = $domain;
   } else {
      $new_domain =~ s/[\r|\n]//g;
   }
   return $new_domain;
}

#####################################################################################

while (($command ne "q") && ($command ne "Q")) {
	system "clear";
	print $WHT."General Information --\n".$NRM;
	print "1   Name of organization       : $WHT$org_name$NRM\n";
	print "2   Where is your logo         : $WHT$org_logo$NRM\n";
	print "3   Title of web page          : $WHT$org_title$NRM\n";
	print "\n";
	print $WHT."Server Information --\n".$NRM;
	print "10  Domain                     : $WHT$domain$NRM\n";
	print "11  IMAP server                : \n";
	print "12  IMAP port                  : \n";
	print "13  Use Sendmail?              : \n";
	if (@vars[4] eq "true") {
		print "14     Sendmail path           : \n";
	} else {
		print "15     SMTP server             : \n";
		print "16     SMTP port               : \n";
	}
	print "\n";
	print $WHT."General Options --\n".$NRM;
	print "20  Welcome message\n";
	print "21  Auto expunge               : @vars[10]\n";
	print "22  Fodlers sub of INBOX       : @vars[11]\n";
	print "\n";
	print "x   Exit without saving\n";
	print "q   Save and exit\n";
	print "\n";
	print "Command: ";

	$command = <STDIN>;
	$command =~ s/[\n|\r]//g; 

	print "\n";

	if    ($command eq "1" ) { $org_name   = command1 (); }
	elsif ($command eq "2" ) { $org_logo   = command2 (); }
	elsif ($command eq "3" ) { $org_title  = command3 (); }

	elsif ($command eq "10") { $domain             = command10 (); }
	elsif ($command eq "11") { $imapServerAddress  = command11 (); }
	elsif ($command eq "12") { $imapPort           = command12 (); }
	elsif ($command eq "13") { $use_sendmail       = command3 (); }
	elsif ($command eq "14") { $org_title  = command3 (); }
	elsif ($command eq "15") { $org_title  = command3 (); }
}
