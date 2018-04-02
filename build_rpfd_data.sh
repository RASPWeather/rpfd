#!/bin/bash

	# Empty the /tmp folder of any intermediate files, just in case
    echo "***********************************************"
	echo "Emptying /tmp of temporary calculation files"
	rm -f /tmp/raspstarttime.out.*
    echo "***********************************************"

    PHP_EXE=/usr/bin/php

    # Set the first parameter to lowercase
    INPUT_1=${1,,}

    #where all the pre-processing is done ...
    # 	Note assumes running in the same folder
    HOME=$(/usr/bin/php /home/rasp3/build/rpfd/get_ini.php home_folder)

    #where the results will be put (no trailing slash)
    EOUT=$(/usr/bin/php $HOME/get_ini.php output_folder)
    TARGET_DIR=$HOME/$EOUT

    #---------------------------------------------------------------
    # set the date number and region name

    # we will use this to allow us to produce follow on days
    # we're using UK12 as that is quicker to process and we're looking for 
    #   trends, not absolute values. You can amend to higher resolution
    #   if you like, but it will be slower
    # expects
    #   uk12 / uk12+1 / uk12+2 / uk12+3 / uk12+4 / uk12+5 / uk12+6
    #       else defaults to uk12

    REGION=$INPUT_1

    case $INPUT_1 in
    uk12)
            DAYNAME=Today
            DAYNAME_SHORT=`(set \`date +%a\`;echo $1)`
            DAY=`(set \`date +%d\`;echo $1)`
            YEAR=`(set \`date +%Y\`;echo $1)`
            MONTH=`(set \`date +%m\`;echo $1)` # use %m for numeric
        ;;
    uk12+1)
            DAYNAME=Tomorrow
            DAYNAME_SHORT=`(set \`date -d 'tomorrow' +%a\`;echo $1)`
            DAY=`(set \`date -d 'tomorrow' +%d\`;echo $1)`
            YEAR=`(set \`date  -d 'tomorrow' +%Y\`;echo $1)`
            MONTH=`(set \`date  -d 'tomorrow' +%m\`;echo $1)`
        ;;
    uk12+2)
            DAYNAME=+2
            DAYNAME_SHORT=`(set \`date -d '+2 days' +%a\`;echo $1)`
            DAY=`(set \`date -d '+2 days' +%d\`;echo $1)`
            YEAR=`(set \`date  -d '+2 days' +%Y\`;echo $1)`
            MONTH=`(set \`date  -d '+2 days' +%m\`;echo $1)`
        ;;
    uk12+3)
            DAYNAME=+3
            DAYNAME_SHORT=`(set \`date -d '+3 days' +%a\`;echo $1)`
            DAY=`(set \`date -d '+3 days' +%d\`;echo $1)`
            YEAR=`(set \`date  -d '+3 days' +%Y\`;echo $1)`
            MONTH=`(set \`date  -d '+3 days' +%m\`;echo $1)`
        ;;
    uk12+4)
            DAYNAME=+4
            DAYNAME_SHORT=`(set \`date -d '+4 days' +%a\`;echo $1)`
            DAY=`(set \`date -d '+4 days' +%d\`;echo $1)`
            YEAR=`(set \`date  -d '+4 days' +%Y\`;echo $1)`
            MONTH=`(set \`date  -d '+4 days' +%m\`;echo $1)`
        ;;
    uk12+5)
            DAYNAME=+5
            DAYNAME_SHORT=`(set \`date -d '+5 days' +%a\`;echo $1)`
            DAY=`(set \`date -d '+5 days' +%d\`;echo $1)`
            YEAR=`(set \`date  -d '+5 days' +%Y\`;echo $1)`
            MONTH=`(set \`date  -d '+5 days' +%m\`;echo $1)`
        ;;
    uk12+6)
            DAYNAME=+6
            DAYNAME_SHORT=`(set \`date -d '+6 days' +%a\`;echo $1)`
            DAY=`(set \`date -d '+6 days' +%d\`;echo $1)`
            YEAR=`(set \`date  -d '+6 days' +%Y\`;echo $1)`
            MONTH=`(set \`date  -d '+6 days' +%m\`;echo $1)`
        ;;
    *)
        # just default to today
            DAYNAME=Today
            DAYNAME_SHORT=`(set \`date +%a\`;echo $1)`
            DAY=`(set \`date +%d\`;echo $1)`
            YEAR=`(set \`date +%Y\`;echo $1)`
            MONTH=`(set \`date +%m\`;echo $1)`
            REGION=uk12
            INPUT_1=$REGION
            echo "Defaulted to using region $REGION"
        ;;
    esac

#---------------------------------------------------------------
    # these are for working out today's date for filename labelling
    MONTHNAME=`(set \`date +%b\`;echo $1)`
    NOW=`(set \`date\`;echo $4)`
    FULL=$(date)
    TODAY=$YEAR-$MONTH-$DAY-$NOW
    TODAYSUFFIX=$DAYNAME_SHORT-$DAY-$MONTH-$YEAR

    # this is the filename only - not full path
    OUTPUT_PREFIX=rpfd
    OUTPUTNAME=$OUTPUT_PREFIX.$INPUT_1.log
    
	ELOG=$(/usr/bin/php $HOME/get_ini.php log_folder)
	# lets check it exists and if not create it
	echo "Looking for output folder ...$HOME/$ELOG"
	if [ -e $HOME/$ELOG ]; then
        echo "Found output folder ...$HOME/$ELOG"
	else
		echo "Creating the log file folder ..."
		mkdir $HOME/$ELOG
	fi
	EOUT=$(/usr/bin/php $HOME/get_ini.php output_folder)
	echo "Looking for output folder ...$HOME/$EOUT"
	# lets check it exists and if not create it
	if [ -e $HOME/$EOUT ]; then
        echo "Found output folder ...$HOME/$EOUT"
	else
		echo "Creating the output folder ..."
		mkdir $HOME/$EOUT
	fi
	
    # these have full paths ...
    OUTPUTLOGFILE=$HOME/$ELOG/$OUTPUTNAME
    OUTPUTERRFILE=$HOME/$ELOG/$OUTPUTNAME.err
    
    OUTPUT_RESULTS_FILE=$HOME/$EOUT/$INPUT_1.$OUTPUT_PREFIX.dat
	
	ELOCS=$(/usr/bin/php $HOME/get_ini.php locations_file)
    SOURCE_LOCATIONS_FILE=$HOME/$ELOCS
    
    # these come from the PHP INI file ...
    INPUT_GLIDER_POLAR=$(/usr/bin/php $HOME/get_ini.php glider_polars)
    INPUT_GLIDER_THERM_PCT=$(/usr/bin/php $HOME/get_ini.php thermal_percent)
    
	#$SOURCE_BOUNDARY
	ECOORDS_PCT=$(/usr/bin/php $HOME/get_ini.php boundary_coords)
	SOURCE_BOUNDARY=$HOME/$ECOORDS_PCT

    #polygon data file name
	EPOLY=$(/usr/bin/php $HOME/get_ini.php results_poly_file_name)
	SOURCE_POLY=$HOME/$EOUT/$INPUT_1.$EPOLY

    #polygon task typefile name
	EPOLYTYPE=$(/usr/bin/php $HOME/get_ini.php task_type)

    #polygon task typefile name
	EPLOTMARKERS=$(/usr/bin/php $HOME/get_ini.php ncl_plot_location_makers)
	EPLOTPOLYMARKERS=$(/usr/bin/php $HOME/get_ini.php ncl_plot_poly_location_makers)

    SITES_FILE=$(/usr/bin/php $HOME/get_ini.php locations_file)
    TOTAL_SITES=$(/usr/bin/wc $SITES_FILE | cut -d' ' -f2)
	
    #---------------------------------------------------------------
    echo "***********************************************"
	echo "Using region:                $REGION"
	echo "Using day:                   $DAY"
	echo "Valid day string:            $TODAYSUFFIX"	
	echo "Using general filename of:   $OUTPUTNAME"
	echo "Using log filename:          $OUTPUTLOGFILE"
	echo "Using results file:          $OUTPUT_RESULTS_FILE"
	echo "Using source locations:      $SOURCE_LOCATIONS_FILE"
	echo "Using output error file:     $OUTPUTERRFILE"
	echo "Using glider therm %:        $INPUT_GLIDER_THERM_PCT"
 	echo "Using glider POLAR:          $INPUT_GLIDER_POLAR"
 	echo "Using boundary data:         $SOURCE_BOUNDARY"
 	echo "Target directory:            $TARGET_DIR"
 	echo "Output polygon file:         $SOURCE_POLY"
 	echo "Task type:                   $EPOLYTYPE"
 	echo "PHP Executable used:         $PHP_EXE"
 	echo "NCL contour plot markers:    $EPLOTMARKERS"
 	echo "NCL plot polygon markers:    $EPLOTPOLYMARKERS"
 	echo "Total sites to be processed: $TOTAL_SITES
	echo "***********************************************"
#---------------------------------------------------------------

	# save last run output files
	if [ -f $OUTPUTLOGFILE ]
	then
		mv -f $OUTPUTLOGFILE $OUTPUTLOGFILE.old
	fi
	if [ -f $OUTPUTERRFILE ]
	then
		mv -f $OUTPUTERRFILE $OUTPUTERRFILE.old
	fi
	
	echo "About to run /usr/bin/php $HOME/rpfd5.php $REGION > $OUTPUTLOGFILE 2> $OUTPUTERRFILE"
	
	# delete any progress file ...
	rm -f $HOME/progress.txt
	# now run it ...
	$PHP_EXE $HOME/rpfd5.php $REGION > $OUTPUTLOGFILE 2> $OUTPUTERRFILE
	
	# copy to final location	
	if [ -f $OUTPUTFILE ]
	then
        cp -f $OUTPUTFILE    $TARGET_DIR
    fi
	if [ -f $OUTPUTERRFILE  ]
	then
        cp -f $OUTPUTERRFILE $TARGET_DIR
    fi

	echo "About to run: $HOME/plot_rpfd.sh $TODAYSUFFIX $OUTPUT_RESULTS_FILE $REGION $INPUT_GLIDER_THERM_PCT $INPUT_GLIDER_POLAR $SOURCE_POLY"
	$HOME/plot_rpfd.sh $TODAYSUFFIX $OUTPUT_RESULTS_FILE $REGION $INPUT_GLIDER_THERM_PCT $INPUT_GLIDER_POLAR $SOURCE_POLY $EPOLYTYPE

	echo "About to run: $HOME/plot_rpfd_poly.sh $TODAYSUFFIX $OUTPUT_RESULTS_FILE $REGION $INPUT_GLIDER_THERM_PCT $INPUT_GLIDER_POLAR $SOURCE_POLY $EPOLYTYPE"
	$HOME/plot_rpfd_poly.sh $TODAYSUFFIX $OUTPUT_RESULTS_FILE $REGION $INPUT_GLIDER_THERM_PCT $INPUT_GLIDER_POLAR $SOURCE_POLY $EPOLYTYPE

    echo "About to run: $PHP_EXE $HOME/rpfd_summary.php $REGION"
	$PHP_EXE $HOME/rpfd_summary.php $REGION

	
#---------------------------------------------------------------
