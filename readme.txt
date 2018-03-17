This bundle contains the source files to create a potential flight distance map
using RASP's task tracking capability.

Perquisites
-----------
1. NCL 6.4.0 - 6.4.0 needs is for the spatial plotting command
2. Working perl environment for the perl script and CGI
3. Working php - 5 may work, but this was developed under PHP 7.0.25

The scripts are not meant to be used as from a web server, but do need 
to be run from the command line - preferably as a cron job.

The scripts and code have comments to help, but you should be capable of 
debugging these if your environment differs a lot.

How It Works
------------
(0) The application utilises RASP's trackaverage utility to determine 
if a given task is on. The application then reruns this multiple times
to see what the maximum distance is from a fixed point. This version
simply uses a list of compass bearings, and determines if the task
is on in that direction. If so, it expands to the next distance and 
repeats to eventually a fail and then it moves on to the next location.
Simple, but slow.

(1) The file rpfd.ini contains all the important settings for it to work.
There are comments on what does what and should be self explanatory. If 
you need to change anything it is probably here.

(2) The file build_rpfd_data.sh is the main script that does a single
pass on one model for one day. A quick read of the file shows that it will
read in the rpfd.ini parameters, check files, check the input model (e.g. 
UK12) and then run the rpfd5.php file that will then runs the cgi and perl
script to create a data file (ending .dat) in the OUT folder.

(3) The LOG folder contains a log file as things progress. Results are
written into the OUT folder. There is also a "progress" file so it is 
possible to watch how far the script has got.

(4) The plot will work better if you provide a "zeroed" boundary and this
is in the file "sea.full.dat". Edit this to suit your region. 

(5) There is a water-mark png file you can use to add to the plot.

(6) A cgi script to provide the status of processing is included. Edit and 
put somewhere where it can see the output PNG files and also the LOG and OUT
directory.

(7) Locations to be used is in the airfields.txt file, referred to in the 
rpfd.ini file, but you can add or edit your own. The format is in the file.

What needs to be configured?
----------------------------
(a) The cgi file needs to be edited for your rasp data file locations, example below:

	### SET EXTERNAL SCRIPT WHICH OUTPUTS RESULT IN TEXT FORMAT
	$EXTRACTSCRIPT = "/home/rasp3/build/maxdist.0.2/maxdistraspstarttimexml.PL";

(b) Edit the perl script and update this to your location for the RASP output
data files:

  ### directory containing current forecasts
    $DATADIR = "/home/rasp3/htmlroot/content/${REGION}/FCST"; #OK, but no archives

Lastly, it is not very quick. As a benchmark, one model for one day
can take about 45 minutes.

What happens if things do not work?
-----------------------------------
Check the log file in the LOG folder and see if anything obvious.

If really stuck set the debug flag in the rpfd.ini file to true and then recheck
the log file.

By all means reach out to darren@btinternet.com if needed and good luck!

Darren
