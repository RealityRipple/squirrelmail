#!/usr/bin/perl

# flat2sql.pl v1.0
# 
# Copyright (c) 2002,  Michael Blandford and Tal Yardeni
#
# This script is licensed under GPL.
##### Conf Section #####

$data_dir = "/home/www/mail.topolis.inet/www/squirrelmail-devel-20050911/data";
$db = "squirrelmail";
$abook_table = "address";
$pref_table = "userprefs";

##### ##### #####

use Getopt::Long;

&GetOptions( \%opts, qw( abook data_dir:s delete h help pref sig user:s ) );

&Usage if ( defined $opts{h} or defined $opts{help} );

unless ( defined $opts{abook} or defined $opts{pref} or
         defined $opts{sig}) {
	$opts{abook}=TRUE;
	$opts{pref}=TRUE;
        $opts{sig}=TRUE;
}

# Override the data directory if passed as an argument
$data_dir = $opts{data_dir} if ( defined $opts{data_dir} );

# Are we looking for specific users or all users?
# There has to be a better way to do this - Below
 @user_list = split ( /,/, $opts{user} ) if defined $opts{user};

# Here we go
# If no arguments are passed, and we cant open the dir, we should
# get a usage.
opendir(DIR, $data_dir) or 
  die "DIRECTORY READ ERROR: Could not open $data_dir!!\n";

while ( $filename = readdir DIR ) {
  next if ( $filename eq "." or $filename eq ".." );
  $filename =~ /(.*)\.(.*)/;
  $username = $1;

  # Deal with the people
  # There has to be a better way to do this - Above
  next if ( defined $opts{user} and grep(!/$username/, @user_list));

  # Deal with the extension files
  $ext = $2;
  next unless $ext;
  &abook if ( $ext eq "abook" and defined $opts{abook} );
  &pref  if ( $ext eq "pref"  and defined $opts{pref}  ); 
  &sig if ( $ext =~ /si([g\d])$/ and defined $opts{sig});
}
closedir ( DIR );

# All done.  Below are functions

# Process a user address file

sub abook {
  print "DELETE FROM $db.$abook_table WHERE owner = '$username;\n"
    if ( defined $opts{delete} );

  open(ABOOK, ">$data_dir/$filename") or 
    die "FILE READ ERROR: Could not open $filename!!\n";

  while (my $line = <ABOOK> ) {

    chomp $line;
    my ( $nickname,$firstname,$lastname,$email,$label ) = split(/\|/, $line);

    print "INSERT INTO $db.$abook_table "
        . "(owner,nickname,firstname,lastname,email,label) "
        . "VALUES ('$username','$nickname','$firstname','$lastname',"
        . "'$email','$label');\n"; 
  }

  close(ABOOK);
}

# Process a user prefernce file

sub pref {
  print "DELETE FROM $db.$pref_table "
    . "WHERE user = '$username' and prefkey not like '___sig\%___';\n"
    if ( defined $opts{delete} );

  open(PREFS, "<$data_dir/$filename") or 
    die "FILE READ ERROR: Could not open $filename!!\n";

  while (my $line = <PREFS> ) {

    chomp $line;
    my ( $prefkey, $prefval ) = split(/=/, $line);

    print "INSERT INTO $db.$pref_table "
        . "(user,prefkey,prefval) "
        . "VALUES ('$username','$prefkey','$prefval');\n"; 

  }

  close(PREFS);
}

# Process a user sig file

sub sig {

  $del_ext = $1;  
  $del_ext = "nature" if ( $del_ext eq "g" );
  print "DELETE FROM $db.$pref_table "
    . "WHERE user = '$username' and prefkey like '___sig" . $del_ext . "___';\n"
      if ( defined $opts{delete} );

  open(SIG, "<$data_dir/$filename") or 
    die "FILE READ ERROR: Could not open $filename!!\n";

  my  @lines = <SIG>;
  close(SIG);

  $filename =~ /.*\.si([g,\d]$)/;
  $prefkey = "___sig";
  if ( $1 eq "g" ) {
    $prefkey .= "nature___";
  } else {
    $prefkey .= "$1___";
  }

  print "INSERT INTO $db.$sig_table (user,prefkey,prefval) "
    . "VALUES ('$username','$prefkey','".join("", @lines)."');\n";
}

# Print out the usage screen

sub Usage {

$0 =~ /.*\/(.*)/;
$prog = $1;
  print <<EOL;
This program generates SQL statements to aid importing squirrelmail 
user config into a database.

Usage: $prog [--delete] [--abook] [--pref] [--sig] [--data_dir=<>] [--user=<username0[,username1[,username2]...]]

Prefs --abook, --pref, and --sig are assumed if none of them as passed

--delete removes all previous values for users ( --users=<> ) already in 
the database.  This is useful to reimport users.  
It respects --abook, --pref, and --sig.

If --user is not specified, it will try to do all users.

EOL
  exit 1;
}
