#!/usr/bin/perl
#
# This all could (maybe) be done in a shell script, but I suck at those.

$i = 0;
$Verbose = 0;
$Plugin = "";
$Version = "";

foreach $arg (@ARGV)
{
    if ($arg eq "-v")
    {
        $Verbose = 1;
    }
    elsif ($Plugin eq "")
    {
        $Plugin = $arg;
    }
    elsif ($Version eq "")
    {
        $Version = $arg;
    }
    else
    {
        print "Unrecognized argument:  $arg\n";
	exit(0);
    }
}

if ($Version eq "")
{
    print "Syntax:  make_archive.pl [-v] plugin_name version.number\n";
    print "-v = be verbose\n";
    exit(0);
}


print "Reformatting plugin name and version number.\n" if ($Verbose);
$Plugin =~ s/\///g;
$Version =~ s/\./_/g;

VerifyInfo($Plugin, $Version);

print "Getting file list.\n" if ($Verbose);
@Files = RecurseDir($Plugin);

$QuietString = " > /dev/null 2> /dev/null" if (! $Verbose);

print "\n\n" if ($Verbose);
print "Creating $Plugin-$Version.tar.gz\n";
system("tar cvfz $Plugin-$Version.tar.gz $Plugin" . FindTarExcludes(@Files)
    . $QuietString);
    
print "\n\n" if ($Verbose);
print "Creating $Plugin-$Version.zip\n";
system("zip -r $Plugin-$Version.zip $Plugin/" . FindZipExcludes(@Files)
    . $QuietString);



sub VerifyInfo
{
    local ($Plugin, $Version) = @_;
    
    if (! -e $Plugin && ! -d $Plugin)
    {
        print "The $Plugin directory doesn't exist, " .
	    "or else it is not a directory.\n";
        exit(0);
    }
}


sub FindTarExcludes
{
    local (@Files) = @_;
    
    $ExcludeStr = "";
    
    foreach $File (@Files)
    {
        if ($File =~ /^(.*\/CVS)\/$/)
	{
	    $ExcludeStr .= " $1";
	}
    }
    
    if ($ExcludeStr ne "")
    {
        $ExcludeStr = " --exclude" . $ExcludeStr;
    }
    
    return $ExcludeStr;
}

sub FindZipExcludes
{
    local (@Files) = @_;
    
    $ExcludeStr = "";
    
    foreach $File (@Files)
    {
        if ($File =~ /^(.*\/CVS)\/$/)
	{
	    $ExcludeStr .= " $1/ $1/*";
	}
    }
    
    if ($ExcludeStr ne "")
    {
        $ExcludeStr = " -x" . $ExcludeStr;
    }
    
    return $ExcludeStr;
}

sub RecurseDir
{
    local ($Dir) = @_;
    local (@Files, @Results);
    
    opendir(DIR, $Dir);
    @Files = readdir(DIR);
    closedir(DIR);
    
    @Results = ("$Dir/");
    
    foreach $file (@Files)
    {
        next if ($file =~ /^[\.]+/);
        if (-d "$Dir/$file")
	{
	    push (@Results, RecurseDir("$Dir/$file"));
	}
	else
	{
	    push (@Results, "$Dir/$file");
	}
    }
    
    return @Results;
}
