#!/bin/sh
# set this to the directory it runs in
HOME_DIR=/home/rasp3/build/rpfd

# now set them all off in parallel
for MODEL in uk12 uk12+1 uk12+2 uk12+3 uk12+4 uk12+5 uk12+6
do
	$HOME_DIR/build_rpfd_data.sh $MODEL 2> $HOME_DIR/LOG/build_rpfd_data.sh.$MODEL.err &
done
