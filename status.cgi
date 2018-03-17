#!/bin/bash
	HOME_DIR=/home/rasp3
	RUN_LOCATION=$HOME_DIR/build/maxdist.0.4

    # CGI header ...
    printf "Content-type: text/html\n\n\n\n"

    # Now html ...
    printf "<html>\n<head> <title>UK RASP PFD Status Report</title>"
    printf "</head>\n<body>"
    printf "\n<h2>RASP PFD Build Status Report at `date`</h2>"

        # get the number of airfields we will process for the percent figure and how many in total
        INPUT_LOCATION_FILE=$(/usr/bin/php $RUN_LOCATION/get_ini.php locations_file)
        LOCS_TO_DO=`wc -l $INPUT_LOCATION_FILE | cut -d' ' -f1`
        printf "\nUsing locations stored in $INPUT_LOCATION_FILE"
    
    printf "\n<table>"    
	for MODEL in uk12 uk12+1 uk12+2 uk12+3 uk12+4 uk12+5
	do
        printf "\n<tr><td>"
        printf "<hr><h3>${MODEL^^}</h3>"
        printf "</td><td></td></tr>"
        TARGET_PNG_NAME=rpfd.${MODEL^^}.png
        
        TARGET_PNG_URL=http://192.168.1.5/rpfd/$TARGET_PNG_NAME

        PROGRESS_FILE=progress.$MODEL.txt
        SOFAR=`wc -l $RUN_LOCATION/OUT/$PROGRESS_FILE | cut -d' ' -f1`
        SOFAR_INT=$(echo "scale=3;($SOFAR - 2" | bc | cut -d'.' -f1)

        TARGET_PNG_1_DATE=`ls -al --full-time $RUN_LOCATION/OUT/$TARGET_PNG_NAME | cut -d' ' -f6`
        TARGET_PNG_1_DATE_TIME=`ls -al --full-time $RUN_LOCATION/OUT/$TARGET_PNG_NAME | cut -d' ' -f7 | cut -d'.' -f1`

        SO_FAR_PCT=$(echo "scale=3;($SOFAR/$LOCS_TO_DO)*100" | bc | cut -d'.' -f1)

        STARTED_AT=`head -n2 $RUN_LOCATION/OUT/$PROGRESS_FILE | cut -d'#' -f2`
        FINISHED_AT=`tail -n1 $RUN_LOCATION/OUT/$PROGRESS_FILE | cut -d'#' -f2`


        printf "\n<tr><td>Model locations to process:</td><td>"
        printf "%0.0d"$LOCS_TO_DO
        printf "</td></tr>"

        printf "\n<tr><td>Model locations processed so far:</td><td>"
        printf "%0.0d"$SOFAR_INT
        printf " ("
        printf "%0.0d"$SO_FAR_PCT
        printf " %%)"
        printf "</td></tr>"

        printf "\n<tr>"
        printf "<td>Date/Time of last chart:<br>(<a href='"
        printf "$s"$TARGET_PNG_URL
        printf "'>"
        printf "%s"$TARGET_PNG_NAME
        printf "</a>)</td><td>"
        printf "%s"$TARGET_PNG_1_DATE
        printf " "
        printf "%s"$TARGET_PNG_1_DATE_TIME
        printf "</td></tr>"

        printf "\n<tr><td>First log entry:</td><td>"
        echo $STARTED_AT
        printf "</td></tr>"

        printf "\n<tr><td>Last log entry at:</td><td>"
        echo $FINISHED_AT
        printf "</td></tr>"
        
    done
    
        printf "\n</table>"
        #
        printf "\n</body></html>"
