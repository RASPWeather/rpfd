#/bin/bash
#-------------------------------------------------------
# plot_rpfd.sh
# Feb 18 2018
# v0.1
#-------------------------------------------------------
NOW=$(date)
printf "******\n$0 started at $NOW\n******\n"
# Inputs
#   $1 as the input date label
#   $2 as the data input file from last stage as full path
#   $3 as the model label (e.g. UK12)
#   $4 as thermal % label
#   $5 as the glider polar label
#   $6 as the task polygon file if applicable

PARAMS_EXPECTED=7
if [ "$#" -ne $PARAMS_EXPECTED ]; then
    printf "\nNot enough parameters given. Expected $PARAMS_EXPECTED, got $#. Use something like ..." 
	printf "\nUsage: $0 20180202 /home/rasp/your-data-input-file.dat UK12 99 Std_Cirrus /home/rasp/your-data-poly-input-file.dat closed\n" 
	printf "\nCommand line given:$0 $1 $2 $3 $4 $5 $6 $7 $8 $9\n"
    exit 1
fi
# we also need to define an input date for the header and the input data file
INPUTDATE=$1
if [ -n "$INPUTDATE" ]; then
	export ENV_VALIDITY=$INPUTDATE
	printf "Using the date=$ENV_VALIDITY"
else
    printf "$0: No input date set. Use something like 20170901\n"
    exit 1
fi

NCL_INPUT_DATA_FILE=$2
if [ -e "$2" ]; then
    printf "\nUsing input data file="$NCL_INPUT_DATA_FILE
else
    printf "\n$0:Input data file $2 not found\n" >&2
    exit 1
fi

if [ -n "$3" ]; then
    ## make sure uppercase - lowercase used elsewhere for processing
    export ENV_MODEL_NAME=${3^^}
	printf "\nUsing model name=$ENV_MODEL_NAME"
else
    printf "\n$0:No model name set. Use something like UK12\n"
    exit 1
fi

if [ -n "$4" ]; then
    export ENV_THERM_PCT=$4
	printf "\nUsing thermalling percent label=$ENV_THERM_PCT"
	
else
    printf "\n$0:No thermalling percent set. Use something like 55\n"
    exit 1
fi

if [ -n "$5" ]; then
    export ENV_GLIDER_POLAR=$5
	printf "\nUsing glider type label=$ENV_GLIDER_POLAR"
else
    printf "\n$0:No glider type label set. Use something like Std_Cirrus\n"
    exit 1
fi

if [ -e "$6" ]; then
    export ENV_NCL_INPUT_POLY_DATA_FILE=$6
	printf "\nUsing input polygon file=$ENV_NCL_INPUT_POLY_DATA_FILE"
else
    printf "\n$0:input polygon file $6 not found\n" >&2
    exit 1
fi

if [ -n "$7" ]; then
    export ENV_NCL_INPUT_TASK_TYPE=$7
	printf "\nUsing input task type=$ENV_NCL_INPUT_TASK_TYPE"
else
    printf "\n$0:input task type $7 not found\n" >&2
    exit 1
fi

#-------------------------------------------------------
# set env vars 
# update this to match your environment
#where all the pre-processing is done ...
# 	Note assumes running in the same folder
HOME=$(/usr/bin/php /home/rasp3/build/maxdist.0.4/get_ini.php home_folder)
# where the root of the processing is

# NCL home
NCLHOME=/home/rasp3/build/ncl

RPFD_NCL_SCRIPT=$HOME/plot_rpfd.ncl

export RPFD_NCL_OUTPUT_NAME=rpfd.$ENV_MODEL_NAME
printf "\nUsing this file name=$RPFD_NCL_OUTPUT_NAME"

RPFD_NCL_SCRIPT_LOGFILE=$HOME/OUT/$RPFD_NCL_OUTPUT_NAME.plot.err.log
printf "\nUsing this full script log file name=$RPFD_NCL_SCRIPT_LOGFILE"

export RPFD_NCL_OUTPUT_FILE=$HOME/OUT/$RPFD_NCL_OUTPUT_NAME
printf "\nUsing this full output file name=$RPFD_NCL_OUTPUT_FILE\n\n"

# set env vars for ncl to run
# update this to match your environment
export LD_LIBRARY_PATH=/usr/lib64:$BASEDIR/lib
export NCL_COMMAND=$NCLHOME/bin/ncl
export NCARG_FONTCAPS=$NCLHOME/lib/ncarg/fontcaps
export NCARG_RANGS=/home/rasp3/lib/rangs
export NCARG_GRAPHCAPS=$NCLHOME/lib/ncarg/graphcaps
export NCARG_ROOT=$NCLHOME
export NCARG_DATABASE=$NCLHOME/lib/ncarg/database
export NCARG_LIB=$NCLHOME/lib/ncarg
export NCARG_NCARG=$NCLHOME/lib/ncarg

export OMP_NUM_THREADS=1
#-------------------------------------------------------

if [ -e "$RPFD_NCL_SCRIPT" ]; then
    printf "\nUsing this NCL SCRIPT=$RPFD_NCL_SCRIPT"
else
    printf "\n$0: NCL script not found. Expecting=$RPFD_NCL_SCRIPT\n" >&2
    exit 1
fi

# check we have the right version of NCL
NCL_VERSION="$($NCL_COMMAND -V)"
if [ $NCL_VERSION != "6.4.0" ]; then
    printf "\n$0: Needs NCL version 6.4.0. Found $NCL_VERSION\n"
    exit 1
else
    printf "\nUsing NCL version=$NCL_VERSION"
fi

#set some labels as environment variables for the plot
# this is the date/time now on the server
export ENV_EXEC_TIME=$(date)

# temporary data file 
TEMP_PLOT_DATA=/tmp/$INPUTDATE.rpfd_plot.dat

# now add the sea coordinates
rm -f $TEMP_PLOT_DATA
cat $NCL_INPUT_DATA_FILE > $TEMP_PLOT_DATA
cat $HOME/sea.full.dat >> $TEMP_PLOT_DATA
export ENV_NCL_INPUT_DATA_FILE=$TEMP_PLOT_DATA

# now run the NCL script 
#   -> this will create an output file as in the environment variable "RPFD_NCL_OUTPUT_NAME"
RUN_RES="$($NCL_COMMAND $RPFD_NCL_SCRIPT rundate=$INPUTDATE) " # > $RPFD_NCL_SCRIPT_LOGFILE)"

if [ -e "$RPFD_NCL_OUTPUT_FILE.png" ]; then
    printf "\nOutput map created ... $RPFD_NCL_OUTPUT_FILE.png\n"
else 
    # didn't get created ?
    printf "\nOutput map from NCL not created ... '$RPFD_NCL_OUTPUT_FILE.png' ... "
    printf "\n... have a look in the $RPFD_NCL_SCRIPT_LOGFILE?\n"
    exit 1
fi

#if [ $RUN_RES <> "0" ]; then
#    # problem with NCL_COMMAND
#    printf "\nError of some kind with NCL - check the log files.\n"
#    exit 1
#fi

echo "\nNCL script done ($RUN_RES)... now post processing ..."

#-------------------------------------------------------
#if you want to add a watermark ... use the following 
#-------------------------------------------------------
TOUT=$(/usr/bin/php $HOME/get_ini.php tmp_folder)
printf "\nUsing temp folder: $TOUT"

#copy the ncl output to a temporary name
printf "\nAbout to: cp -f $RPFD_NCL_OUTPUT_FILE.png $TOUT/$INPUTDATE.ds.png"
cp -f $RPFD_NCL_OUTPUT_FILE.png $TOUT/$INPUTDATE.ds.png

# create a smaller version of the watermark - you can edit the watermark image to suit
printf "\nAbout to: /usr/bin/convert $HOME / water-mark-logo.png -resize 85 $TOUT / $INPUTDATE.water-mark-logo-sm.png"
/usr/bin/convert $HOME/water-mark-logo.png -resize 85% $TOUT/$INPUTDATE.water-mark-logo-sm.png

printf "\nAbout to: /usr/bin/composite $TOUT/$INPUTDATE.water-mark-logo-sm.png $TOUT/$INPUTDATE.ds.png -gravity NorthEast $TOUT/$INPUTDATE.ds2.png"
# blend the two - and put in top right (NE) corner - tweak if you like
/usr/bin/composite $TOUT/$INPUTDATE.water-mark-logo-sm.png $TOUT/$INPUTDATE.ds.png -gravity NorthEast $TOUT/$INPUTDATE.ds2.png

# copy the blended version (force overwrite) to readable location as the final file
HTTP_DIR=$(/usr/bin/php $HOME/get_ini.php http_folder)
printf "\nCopying $RPFD_NCL_OUTPUT_NAME.png to $HTTP_DIR\n"
cp -f $TOUT/$INPUTDATE.ds2.png $HTTP_DIR/$RPFD_NCL_OUTPUT_NAME.png

printf "\nCopying -f $TOUT/$INPUTDATE.ds2.png $RPFD_NCL_OUTPUT_FILE.png\n"
cp -f $TOUT/$INPUTDATE.ds2.png $RPFD_NCL_OUTPUT_FILE.png

#tidy up
rm -f $TOUT/$INPUTDATE.ds.png $TOUT/$INPUTDATE.ds2.png $TOUT/$INPUTDATE.water-mark-logo-sm.png $TEMP_PLOT_DATA

NOW=$(date)
printf "\n******\n$0 finished at $NOW\n******\n"
