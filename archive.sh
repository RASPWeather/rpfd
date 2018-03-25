#!/bin/bash
#---------------------------------------------------------------
# Set as cron job to run at the end of the day before any new runs the following day
#---------------------------------------------------------------
        #---------------------------------------------------------------
        HOME=put_your_root_folder_here/rpfd
        #---------------------------------------------------------------
        # The folder and file names ...
        #%1= Day, %2=Month, %3=day, %4 =HH:MM:SS %5=Zone %6=Year
        YEAR=`(set \`date +%Y\`;echo $1)`
        MONTH=`(set \`date +%m\`;echo $1)`
        DAY=`(set \`date +%d\`;echo $1)`
        NOW=`(set \`date\`;echo $4)`
        FULL=$(date)
        TODAY=$YEAR-$MONTH-$DAY-$NOW
        ARCHIVE_DIR=$HOME/archive
        #------------------------------------------------------
        printf "\nStarted at $FULL"
        #------------------------------------------------------
        # now copy the file into the store area
        #%1= Day, %2=Month, %3=day, %4 =HH:MM:SS %5=Zone %6=Year
        #  example is "Fri Aug  1 13:44:27 BST 2008"
        if  !(`test -d $ARCHIVE_DIR`;) then
                # does not exist
                mkdir $ARCHIVE_DIR
                printf "\nCreated $ARCHIVE_DIR directory\n"
        fi
        #create the year folder if required
        if  !(`test -d $ARCHIVE_DIR/$YEAR`;) then
                # does not exist
                mkdir $ARCHIVE_DIR/$YEAR
                printf "\nCreated $YEAR directory\n"
        fi
        #
        #create the month folder if required
        if  !(`test -d $ARCHIVE_DIR/$YEAR/$MONTH`;) then
                # does not exist
                mkdir $ARCHIVE_DIR/$YEAR/$MONTH
                printf "\nCreated $MONTH directory\n"
        fi
        #
        #create the day folder if required
        if  !(`test -d $ARCHIVE_DIR/$YEAR/$MONTH/$DAY`;) then
                # does not exist
                mkdir $ARCHIVE_DIR/$YEAR/$MONTH/$DAY
                printf "\nCreated $DAY directory\n"
        fi

        #------------------------------------------------------
        PLOTS_PNG=$HOME/OUT/*.png
        printf "\nUsing:\nPLOTS_PNG=$PLOTS_PNG"
        PLOTS_DAT=$HOME/OUT/*.dat
        printf "\nUsing:\nPLOTS_DAT=$PLOTS_DAT"
        PLOTS_CSV=$HOME/OUT/*.csv
        printf "\nUsing:\nPLOTS_CSV=$PLOTS_CSV"
        PLOTS_ERR=$HOME/OUT/*.err
        printf "\nUsing:\nPLOTS_ERR=$PLOTS_ERR"
        PLOTS_LOG_TXT=$HOME/OUT/*.txt
        printf "\nUsing:\nPLOTS_LOG_TXT=$PLOTS_LOG_TXT\n"

        /usr/bin/tar cvfz $ARCHIVE_DIR/$YEAR/$MONTH/$DAY/plots.png.tar.gz $PLOTS_PNG
        /usr/bin/tar cvfz $ARCHIVE_DIR/$YEAR/$MONTH/$DAY/plots.dat.tar.gz $PLOTS_DAT
        /usr/bin/tar cvfz $ARCHIVE_DIR/$YEAR/$MONTH/$DAY/plots.csv.tar.gz $PLOTS_CSV
        /usr/bin/tar cvfz $ARCHIVE_DIR/$YEAR/$MONTH/$DAY/plots.err.tar.gz $PLOTS_ERR
        /usr/bin/tar cvfz $ARCHIVE_DIR/$YEAR/$MONTH/$DAY/plots.log.txt.tar.gz $PLOTS_LOG_TXT

        #------------------------------------------------------
        FULL=$(date)
        printf "\nFinished at $FULL\n"
