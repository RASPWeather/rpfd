#! /usr/bin/perl -w -T

### CALC RASP TRACK AVG TASK TIMES
### Modified from get_raspTrackAvg.cgi
### PAULS: 10/01/2010

### ala http://www.drjack.info/cgi-bin/get_rasptrackavg.cgi?region=GREATBRITAIN&grid=d2&day=0&time=1500&polar=LS-3&wgt=1&tsink=1&tmult=1&latons=51.,0.,52.,-1.,52.,1.,51.,0.
#rasp- ### CAN INPUT EITHER LAT,LONS OR IMAGE INFORMATION

################################################################################

	### MODIFIED FROM get_bliptrackavg.pl

	### NOTE - if input parameter argument changes may need to change its detainting

	### TO UNTAINT PATH
	$ENV{'PATH'} = '/bin:/usr/bin:/home/rasp/cgi-bin/localtask';

	### DEFINE BASEDIR - HARDWIRE FOR APACHE SERVER
	### not used - DH 2018-1-21 { $BASEDIR = '/home/rasp' ;  }

	### SET EXTERNAL SCRIPT WHICH OUTPUTS RESULT IN TEXT FORMAT
	$EXTRACTSCRIPT = "/home/rasp3/build/rpfd/maxdistraspstarttimexml.PL";

################################################################################

	use CGI::Carp qw(fatalsToBrowser);

	# for start/finish timings ...
	use POSIX qw(strftime);
	#use DateTime;
	#use Date::Calc qw(:all);

	# DH added to be able t otrack time taken
	$start_string = strftime "%a %b %e %H:%M:%S %Y", localtime;
	# start counting
	$start_t = time();

	my $PROGRAM = 'get_maxdistxmlrasptrackavg.cgi' ;

    ### PARSE CGI INPUT
    use CGI qw(:standard);
    $query = new CGI;
    $region = $query->param('region');
    $grid = $query->param('grid');
    $day = $query->param('day');
    $validtime = $query->param('time');
    $polar = $query->param('polar');
    $wgt = $query->param('wgt');
    $tsink = $query->param('tsink');
    $tmult = $query->param('tmult');
    $latlons = $query->param('latlons');
    $xylist = $query->param('xylist');
    $imagewidth = $query->param('width');
    $imageheight = $query->param('height');
    $taskname = $query->param('task');

	# added to allow for HTTP call and the CGI to output to the browser (off by default)
	# DH 20170827
	$cgi_fn = ""; # set to empty so we assume NOT a CGI
	$cgi_fn = $query->param('fn');

    ### UNTAINT INPUT PARAMS - do not allow leading "-" except with numeric value
    #tainttest: use Scalar::Util qw(tainted);
    #tainttest: print "avar " . (tainted($avar)?"IS ":"is not ") . "tainted\n" ;
    #4test=insecure:    if ( defined $region && $region =~ m|^(.*)$| ) { $region = $1 ; }
    #bad    if ( defined $region && $region =~ m|^(\w{1}[\w\-\.]*)$| ) { $region = $1 ; }
    if ( defined $region && $region =~ m|^([A-Za-z0-9][A-Za-z0-9_.+-]*)$| ) { $region = $1 ; }
    if ( defined $grid && $grid =~ m|^([dw][0-9])$| ) { $grid = $1 ; }
    if ( defined $day && $day =~ m|^([0-9-]*)$| ) { $day = $1 ; }
    #PAULS if ( defined $validtime && $validtime =~ m|^([0-9a-zA-Z]*)$| ) { $validtime = $1 ; }
    if ( defined $validtime && $validtime =~ m|^([0-9a-zA-Z+]*)$| ) { $validtime = $1 ; }
    if ( defined $polar && $polar =~ m|^([A-Za-z0-9+-][A-Za-z0-9,_+.-]*)$| ) { $polar = $1 ; }
    if ( defined $wgt && $wgt =~ m|^([0-9.-]*)$| ) { $wgt = $1 ; }
    if ( defined $tsink && $tsink =~ m|^([0-9.mkts]*)$| ) { $tsink = $1 ; }
    if ( defined $tmult && $tmult =~ m|^([0-9.]*)$| ) { $tmult = $1 ; }
    ### once seemed to get error using following but then not !?
    if ( defined $latlons && $latlons =~ m|^([0-9,.-]*)$| ) { $latlons = $1 ; }
    if ( defined $xylist && $xylist   =~ m|^([0-9,.-]*)$| ) { $xylist = $1 ; }
    if ( defined $imagewidth && $imagewidth =~ m|^([0-9]*)$| ) { $imagewidth = $1 ; }
    if ( defined $imageheight && $imageheight =~ m|^([0-9]*)$| ) { $imageheight = $1 ; }
    if ( defined $taskname && $taskname =~ m|^([A-Za-z0-9+-][A-Za-z0-9,_+.-]*)$| ) { $taskname = $1 ; }

	#### ALLOW DEFAULTS FOR CERTAIN PARAMETERS
	if ( ! defined $day || $day eq '' ) { $day = 0 ; }			# set day to today
	if ( ! defined $wgt || $wgt eq '' ) { $wgt = '1' ; }			# set to 1.0
	if ( ! defined $tsink || $tsink eq '' ) { $tsink = '1.0' ; }	# set sink to 1.0m/s
	if ( ! defined $tmult || $tmult eq '' ) { $tmult = '1' ; }	# set multiplier to 1 (none)

	#### TEST FOR MISSING ARGUMENTS
	if ( ! defined $region || $region eq '' ) { die "${PROGRAM} ERROR EXIT: missing region argument"; }
	if ( ! defined $grid || $grid eq '' ) { die "${PROGRAM} ERROR EXIT: missing grid argument"; }
	if ( ! defined $day || $day eq '' ) { die "${PROGRAM} ERROR EXIT: missing day argument"; }
	if ( ! defined $validtime || $validtime eq '' ) { die "${PROGRAM} ERROR EXIT: missing time argument"; }
	if ( ! defined $polar || $polar eq '' ) { die "${PROGRAM} ERROR EXIT: missing polar argument"; }
  
	### TEST FOR EITHER LATLONS or XYLIST INPUT ALTERNATIVE
	if ( ! defined $xylist || $xylist eq '' )
	{
		if ( ! defined $latlons || $latlons eq '' ) 
		{ 
			die "${PROGRAM} ERROR EXIT: missing latlons or xylist argument"; 
		} 
	}
	else
	{
			if ( ! defined $imagewidth || $imagewidth eq '' ) 
			{ 
				die "${PROGRAM} ERROR EXIT: missing width argument"; 
			}
			if ( ! defined $imageheight || $imageheight eq '' ) 
			{ 
				die "${PROGRAM} ERROR EXIT: missing height argument"; 
			}
	}

	### INITIALIZATION
	### SET TMP FILE IDENTIFIER
	$tmpid = int( rand 999998 ) +1;


	### GET OUTPUT TEXT FROM EXTERNAL SCRIPT
	#print "$EXTRACTSCRIPT $region $grid $day $validtime $polar $wgt $tsink $tmult $latlons $taskname $tmpid FALSE";

	$calcout = `${EXTRACTSCRIPT} $region $grid $day $validtime $polar $wgt $tsink $tmult $latlons $taskname $tmpid FALSE`;

	#PAULS - CLEAN UP THE DETRITUS
    { `/bin/rm -f /tmp/raspstarttime.out.${tmpid}`; }

	
	# DH added to give XML output which is easier to use and port about than text
	if ( defined $cgi_fn ){
		print "Content-Type: text/xml\n\n";
		print "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	}

	print "\n<taskday>\n";

	### PRINT HTML TEXT = HEADER + SCRIPT OUTPUT + FOOTER in XML
	{ print "${calcout}\n"; }

	# stop the clock
	$end_t = time();
	$end_string = strftime "%a %b %e %H:%M:%S %Y", localtime;
	# calc time to process
	$dur = $end_t - $start_t;

	print "\t<starttime>".$start_string."</starttime>";
	print "\n\t<finishtime>".$end_string."</finishtime>";
	print "\n\t<elapsedsecs>".$dur."</elapsedsecs>";
	print "\n</taskday>\n";
