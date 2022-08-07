<?php
	# rpfd5.php
	date_default_timezone_set('Europe/London');

	// set various things to zero or empty
	$gsRegion= "";
    $gsTaskType = "";
    $bDebug = "";
    $gsHomeFolder = "";
    $giMAXTHERMALPCT = "";
    $gsLocationsFile = "";
    $gsOutputFolder = "";
    $sResultsFilename = "";
    $aDistanceIncrements[] = array(); 
    $aGliderTypes[] = array(); 
    $aVectorDirections[] = array(); 
    $gsBoundaryFile = "";
    $sInterCSVFileName = "";
    $sCgiScript = "";
    $gsTaskType = "";
    $gsProgressTextFile = "";
    $gsPolyResultsFilename = "";
    $gbNCLPlotMarkers = TRUE;

	// pull in the utilities ... don't use any though until we have parsed the INI file
	require_once "rpfd_utils.php";
    
    // the filename the configurable settings come from
	$gIniFileName = "rpfd.ini";
	// read in things from the INI file ...
	LoadInputParameters();

	LogMsg("Started processing ...");
	LogProgress( "Started ...");

	#---------------------------------------------------------------------------------------------
	# Important: This script is designed to be run from the command line, not as a php script from a web server
	#---------------------------------------------------------------------------------------------

	
	$iAppStartTime = time();      // we use this to calculate the processing time so far and ETA
	
	$iAverageLocationTime = 0; // used to compute ETA
	$iTimeSoFar = 0;
    $aResultsXml = array ();        // XML entries used for the entries that were successful - used later on for output
	$aCSVResults = array();

    $iLocationsCount = 0;
	$iCalcsSoFar = 0;
	$iTimeLeft = "a quite few ";
	$iTimeLeftMins = "some";
	$sFutureTime = "";
    
    // ---------------------------------------------------------------------------------------------------
	if ($bDebug) { $sValues = ""; foreach ($aDistanceIncrements as $key=>$value) { $sValues .= $value." "; } LogMsg("Debug: Increments=$sValues"); }

	// just be careful how many you use, as each one will take a second or two to process. Lots of directions takes a lot longer!
	if ($bDebug) { $sValues = ""; foreach ($aVectorDirections as $key=>$value) { $sValues .= $value." "; } LogMsg("Debug: Directions=$sValues"); }
	$iDirectionsCount = count ($aVectorDirections);

	// Glider Types
	if ($bDebug) { $sValues = ""; foreach ($aGliderTypes as $key=>$value) { $sValues .= $value." "; } LogMsg("Debug: Glider Types=$sValues"); }

	// masterfile of locations to process .. note is in same location as this script
	if ($bDebug) { LogMsg("Debug: Using locations stored here=$gsLocationsFile"); }
	$aLocationsFile = file($gsLocationsFile);
	// ---------------------------------------------------------------------------------------------------
	// A good script name is (from an http request)
	// get_xmlraspstarttime.cgi?region=uk12&latlons=52.169733,0.8752166,52.10645,0.7914,52.169733,0.8752166&day=0&time=1200%2b&polar=StdCirrus&wgt=1&tsink=1.0&tmult=1&grid=d2&task=RAT.LVN.RAT

	// when running from the shell command line:
	// ./get_xmlraspstarttime.cgi region=uk12 latlons=52.169733,0.8752166,52.10645,0.7914,52.169733,0.8752166 day=0 time=1200%2b polar=StdCirrus wgt=1 tsink=1.0 tmult=1 grid=d2 task=RAT.LVN.RAT
	if (!is_array($aLocationsFile)){
		LogMsg("The load of file: $aLocationsFile did not return as an array. Check the file.");
		exit(-1);
	}
	if (!is_array($aDistanceIncrements)){
		LogMsg("The directions aDistanceIncrements did not return as an array. Check the INI file.");
		exit(-1);
	}
	if (!is_array($aVectorDirections)){
		LogMsg("The vector directions $aVectorDirections did not return as an array. Check the INI file.");
		exit(-1);
	}
	$aRegions = explode($gsRegion,","); 

	$iTotalCalcs = count($aLocationsFile) * count($gsRegion) * count($aDistanceIncrements) * count($aVectorDirections);
	LogMsg("Total possible calculations: ".$iTotalCalcs);
	LogMsg("Total number of locations:   ".count($aLocationsFile));
	LogMsg("Total number of directions:  ".$iDirectionsCount);

	foreach ($aLocationsFile as $sLocation)
	{
		if ($sLocation == ""){
            break; // just in case an empty line in the input file
		}
		$iLocationsCount += 1;

		// we get a coordinate pairing
		$aLocation = explode("|", $sLocation);
		$sName = $aLocation[0]."_".$aLocation[1]; // note 0 is the trigraph used
		$fInputLat = chop($aLocation[2]);
		$fInputLon = chop($aLocation[3]);
		$iPercent = sprintf("%d",($iLocationsCount/count($aLocationsFile)*100));
		
        $sLogMessage = "At ".$sName." [".$fInputLat.",".$fInputLon."] ".$iLocationsCount." of ".count($aLocationsFile). " (".$iPercent."%) ETA ";
        if ($iTimeLeft <60) {
            if ($iTimeLeft <0) { // have gone negative - this can happen if later calculations are above average
                LogProgress( "$sLogMessage shortly - slightly overrunning ($iTimeLeftMins mins) $sFutureTime" );
            } else {
                LogProgress( "$sLogMessage in ".$iTimeLeft." secs ($iTimeLeftMins mins) $sFutureTime" );
            }
        } else { 
            LogProgress( "$sLogMessage in ".$iTimeLeft." secs ($iTimeLeftMins mins) $sFutureTime" );
        }
		
		
		$iStartTime = time();

		// for each glider type
		foreach ($aGliderTypes as $sGlider){
				// for each distance
				foreach($aDistanceIncrements as $fDistance){
					// for each bearing
					$iVectorSuccessCount = 0; // if this stays 0 for all bearings, we have stopped going further so break ...
					// at the distance try and see if we can get success on one vector ... if yes go to next distance ... if not stop
					foreach($aVectorDirections as $fBearing){
						$iCalcsSoFar +=1;
						//list($fTargetLat, $fTargetLon) = CalculateTargetLocation($fInputLat, $fInputLon, $fDistance, $fBearing);
						$sCmd = "$sCgiScript ";
						$sFinalRegion = CheckRegion($gsRegion);
						$sCmd .= "region=$sFinalRegion ";
						// work out the lat/lons ...
						switch ($gsTaskType) {
							case "free":
								// simply start, bearing and finish
								list($fTargetLat, $fTargetLon) = get_gps_distance($fInputLat, $fInputLon, $fDistance, $fBearing);
								$fTargetLat = chop($fTargetLat);
								$fTargetLon = chop($fTargetLon);								$sCmd .= "region=$sFinalRegion ";
								$sLL = "$fInputLat,$fInputLon";
								$sLL = rtrim($sLL);
								$sCmd .= "latlons=$sLL,$fTargetLat,$fTargetLon ";
								break;
								
							case "oar":
								// simply start, bearing and furthest point at half distance and return to start
								$fHalfDistance = $fDistance / 2;
								list($fTargetLat, $fTargetLon) = get_gps_distance($fInputLat, $fInputLon, $fHalfDistance, $fBearing);
								$fTargetLat = chop($fTargetLat);
								$fTargetLon = chop($fTargetLon);
								$sLL = "$fInputLat,$fInputLon"; // start lat/lon
								$sLL = rtrim($sLL);
								$sCmd .= "latlons=$sLL,$fTargetLat,$fTargetLon,$sLL "; // tack on half way and start as the finish
								break;
								
							case "closed": // this is a closed equilateral task - each turn is 60 degrees, assume clockwise
								// work out first "real" bearing which is 30 degrees minus the main bearing
								$fThirdDistance = $fDistance / 3;
								// get left/right bearings ...
								list($iLeftBearing,$iRightBearing) = CalcSubBearing($fBearing);
								// to turn point 1
								list($fTargetLat1, $fTargetLon1) = get_gps_distance($fInputLat, $fInputLon, $fThirdDistance, $iLeftBearing);
								// to turn point 2
								list($fTargetLat2, $fTargetLon2) = get_gps_distance($fInputLat, $fInputLon, $fThirdDistance, $iRightBearing);
								$fTargetLat1 = chop($fTargetLat1);
								$fTargetLon1 = chop($fTargetLon1);
								$fTargetLat2 = chop($fTargetLat2);
								$fTargetLon2 = chop($fTargetLon2);
								$sLL = "$fInputLat,$fInputLon"; // start lat/lon
								$sLL = rtrim($sLL);
								// start to tp1 to tp2 and back to start
								$sCmd .= "latlons=$sLL,$fTargetLat1,$fTargetLon1,$fTargetLat2,$fTargetLon2,$sLL ";
								
								break;
								
							default: // free
								list($fTargetLat, $fTargetLon) = get_gps_distance($fInputLat, $fInputLon, $fDistance, $fBearing);
								$fTargetLat = chop($fTargetLat);
								$fTargetLon = chop($fTargetLon);
								$sLL = "$fInputLat,$fInputLon";
								$sLL = rtrim($sLL);
								$sCmd .= "latlons=$sLL,$fTargetLat,$fTargetLon ";
						}						

						$sCmd .= "day=0 time=1200%2b ";
						$sCmd .= "polar=$sGlider ";
						$sCmd .= "wgt=1 tsink=1.0 tmult=1 grid=d2 ";
						$sCmd .= "task=".$aLocation[0]."-".$fDistance."-".$fBearing."-".$iLocationsCount."of".count($aLocationsFile)."-".$sName." ";
						// now we should try and run it ...
						 if ($bDebug) { LogMsg("Debug: Running...\"$sCmd\" "); }
						LogMsg("Trying [$gsTaskType]... $sName at $fDistance Km and bearing $fBearing degrees ($iLocationsCount of ".count($aLocationsFile).")");
						$sResults = shell_exec($sCmd);

						// need to check the results to see if any worked to permit to go to next distance
						// this will return 0 if all fails and 1 if success. a 1 will mean we carry on
						$iVectorSuccessCount += ProcessVectorXMLResults($sResults);

						StoreIntermediateResults($sResults,$sInterCSVFileName,$fBearing);

						// if we get NO successes, break and go onto the next bearing

					} // end of all the vectors for this distance

					if ( $iVectorSuccessCount == 0 ) { // all vectors failed at this distance, so don;t bother trying further out
						LogMsg("Failed on any bearing for $sName at $fDistance Km. Moving on ...");
						// now add on an empty element for zero if no tasks were successful
						break; // out of the foreach DistanceIncrement
					}
				} // end of all the distance increments
				
		} // end of each glider type
		$iEndTime = time(); // end time for the location
		$iTimeTaken = $iEndTime - $iStartTime; // that is the taken for the current location
		$iTimeSoFar = $iEndTime - $iAppStartTime;
		$iAverageLocationTime = round($iTimeSoFar / $iLocationsCount);
		$iExpectedTotalTime = round(count($aLocationsFile) * $iAverageLocationTime);
		$iTimeLeft = round($iExpectedTotalTime - $iTimeSoFar);
		$iLastETA = $iTimeLeftMins;
		$iTimeLeftMins = round($iTimeLeft /60);
		
		// estimate when that might be 
		$iFutureTime = time() + $iTimeLeft; // unix seconds since 1970
		$sFutureTime = date("H:i:s", $iFutureTime );
		
		$sLogMsg = "Average Location Processing time so far: $iAverageLocationTime secs. With the last location: $iTimeTaken secs.";
		if ($iTimeLeft <60) {
            if ($iTimeLeft <0){
                $sLogMsg = $sLogMsg." ETA should be in a minute or two (some have gone above average) at $sFutureTime";
            } else {
                $sLogMsg = $sLogMsg." ETA in less than a minute at $sFutureTime";
            }
        } else { 
            $sLogMsg = $sLogMsg." ETA in: $iTimeLeft secs ($iTimeLeftMins min or $sFutureTime)";
        }
		
		LogMsg ($sLogMsg);
		if ($iTimeLeftMins > $iLastETA )
		{
            LogMsg ("ETA is going upwards from $iLastETA to $iTimeLeftMins by ".$iTimeLeftMins-$iLastETA ." secs");
            
		}
		LogMsg ("---------------------------- End of Location ----------------------------");
	}

    $iAverageLocationTime = $iTimeSoFar / $iLocationsCount;
	LogMsg ("Average Location Processing time was ".round($iAverageLocationTime)."s");
	LogMsg ("Finished getting results.");

	file_put_contents($sResultsFilename,$sResults);

	// now parse the results that pass
	OutputNCLData();

	LogMsg ("Finished all processing normally.");
    LogProgress( "Finished.");
    
// end of main body
// ----------------------------------------------------------------------------
function ProcessVectorXMLResults($sResults)
{
        global $bDebug; // debugging flag
        global $aResultsXml;
        global $giMAXTHERMALPCT;

        $aTemp = array ();

        if ($bDebug) { LogMsg ("Debug: function ProcessVectorXMLResults(sResults)"); }

        // break out XML from the $sResults passed in from the CGI / perl script
        $aXml = simplexml_load_string($sResults);

        // get the footer details fro mthe XML file which tells us what happened
        $iSuccess = $aXml->footer->successes;
        $iFail = $aXml->footer->fails;

        // if success is 1 or more, return 1
        if ($iSuccess > 0 ) {
                if ($bDebug) { LogMsg("Debug: Found $iSuccess successful tasks"); }
                $aTemp = SiftForSuccessfulTasks($aXml, $giMAXTHERMALPCT);
                // only add on if we got a task that we deem successful
                if (sizeof($aTemp)>0) {
                        $aResultsXml[] = $aTemp;
                        return 1;
                }

                return 0;
        }
        // else return 0 as all fails

        return 0;
}
// ----------------------------------------------------------------------------
// This function outputs the entries that worked used
// assumes a valid file name and something in the array
function OutputResults($aResultsXml, $sFilename)
{
        global $bDebug; // debugging flag

        if ($bDebug) { LogMsg("Debug: function OutputResults(aResultsXml, sFilename)"); }
        if ($bDebug) { LogMsg("Debug: Sending results to: $sFilename"); }

        $fp = fopen($sFilename, 'w');
        if ($fp)
        {
                if (count($aResultsXml > 0))
                {
                        $sResults = print_r($aResultsXml,true);
                        if ($bDebug) { LogMsg("Debug: Writing Results XML to ".$sFilename." with ".count($aResultsXml)." entries"); }
                        fwrite($fp, $sResult, TRUE);
                } else {
                        LogMsg("Results XML is empty");
                        fwrite($fp, "", TRUE);
                }
        } else {
                LogMsg("Unable to write results to: $sFilename");
        }

        fclose($fp);
}
// ----------------------------------------------------------------------------
function SiftForSuccessfulTasks($aTasks,$iMAXTHERMALPCT)
{
        global $bDebug; // debugging flag

        if ($bDebug){LogMsg("Debug: function SiftForSuccessfulTasks(aTasks,iMAXTHERMALPCT)");}

        if ($bDebug){
                echo "\n ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++";
                echo "\n function SiftForSuccessfulTasks\n";
                echo "\n ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++";
                echo "\n MaxThermalPercent=$iMAXTHERMALPCT";
                echo "\n Tasks ...";
        }

        $aMaxTask = "";

        $totaltasks = 0;
        $iTempThermPct = 100;

        // read each XML task entry and when you get a field that has <result>SUCCESS</result>
        foreach ($aTasks as $aTask) {

        if ($aTask->header != "")
                {
                        if ( ($aTask->result == "SUCCESS") && ($aTask->totalthermalpct <= $iMAXTHERMALPCT) )
                        {
                                if ($bDebug){   echo "\n SUCCESS and below thermal percent ... task->result=".$aTask->result. " and task->totalthermalpct=".$aTask->totalthermalpct ;
                                                                echo "\n aTask->header->taskname=".$aTask->header->taskname;
                                                                echo "\n aTask->result=".$aTask->result. " and aTask->totalthermalpct=".$aTask->totalthermalpct ;}

                                // if this one is better than the last one (lower thermalling percent) save it as the best
                                if ($aTask->totalthermalpct < $iTempThermPct )
                                {
                                        $iTempThermPct = $aTask->totalthermalpct; // this one is better
                                        if ($bDebug){   echo "\n SUCCESS and is best (or better than the last one) ... task->result=".$aTask->result. " and task->totalthermalpct=".$aTask->totalthermalpct ; }
                                        $aMaxTask = $aTask; // reset it to this better one
                                        if ($bDebug){ print_r($aMaxTask); }
                                }
                        }
                        $totaltasks +=1; /// end of individual task check
                }
        }

        if ($bDebug){
                echo "\n ------------------------------------------------------------";
                echo "\n Total tasks processed in the sift=$totaltasks";
                echo "\n Number found in aMaxTask Array = ".sizeof($aMaxTask)."\n ";
                print_r($aMaxTask);
                echo "\n ------------------------------------------------------------";
        }

        if (sizeof($aMaxTask) == 0 ){
                return 0;
        } else {
                return $aMaxTask;
        }
}
// ----------------------------------------------------------------------------
function StoreIntermediateResults($sResults,$sInterCSVFileName,$fBearing)
{
        global $bDebug;         // debugging flag
        global $aCSVResults;
        global $giMAXTHERMALPCT;
        $sDelim =",";

        if ($bDebug) { LogMsg ("Debug: function StoreIntermediateResults(sResults,sInterCSVFileName)"); }

        // break out XML from the $sResults passed in from the CGI / perl script
        $aXml = simplexml_load_string($sResults);

        if ($bDebug) { LogMsg ("Debug: ".$aXml->footer->successes." Footer successes"); }
        if ($bDebug) { LogMsg ("Debug: ".$aXml->footer->fails." Footer fails"); }

        // read each XML task entry and output
        foreach ($aXml as $aTask) {

        if ( ($aTask->header != "") &&($aTask->result == "SUCCESS") )
                {
                        $sThermPct = sprintf("%s", $aTask->totalthermalpct);
                        if ($sThermPct < $giMAXTHERMALPCT)
                        {
                            // oputput if it is good enough and not spending much time thermalling
                                $sKey = sprintf("%s",$aTask->header->taskname);
                                // in the format "lat,long|lat,lon"
                                $sLatLon = sprintf("%s", $aTask->header->headerlatlong);
                                // now remove the comma for a space
                                $sLatLon = str_replace(",", " ",  $sLatLon ); 
                                
                                $sDist = sprintf("%s", $aTask->header->totalkm);
                                $sCSV = $sDelim.$sLatLon;
								$sCSV .= $sDelim.$sDist;
								$sCSV .= $sDelim.$fBearing;
								
								// these are calculated ...
                                $sCSV .= $sDelim.$sThermPct;
								$sCSV .= $sDelim.$aTask->clocktime;
                                $sCSV .= $sDelim.$aTask->totalmin;
                                $sCSV .= $sDelim.$aTask->totalavggroundspeedkt;
                                $sCSV .= $sDelim.$aTask->totalavggroundspeedkph;
                                $sCSV .= $sDelim.$aTask->totaloptairspeedavgkt;
                                $sCSV .= $sDelim.$aTask->percentblocked;
                                $sCSV .= $sDelim.$aTask->totalspatialavgtailwindkt;
                                $sCSV .= $sDelim.$aTask->totalspatialavgclimbratekt;
                                $sCSV .= $sDelim.$aTask->totaltailwindavgkt;
                                $sCSV .= $sDelim.$aTask->totalclimbrateavgkt;
                                

                                // The res of these are fairly static
                                $sCSV .= $sDelim.$aTask->result;
                                $sCSV .= $sDelim.$aTask->header->forecastdate;
                                $sCSV .= $sDelim.$aTask->header->processtime;
                                $sCSV .= $sDelim.$aTask->header->region;
                                $sCSV .= $sDelim.$aTask->header->headervalid;
                                $sCSV .= $sDelim.$aTask->header->headergrid;
                                $sCSV .= $sDelim.$aTask->header->headerpolar;
                                $sCSV .= $sDelim.$aTask->header->headerballast;
                                $sCSV .= $sDelim.$aTask->header->headerballast;
                                $sCSV .= $sDelim.$aTask->header->headersinkratemetrepersec;

                                if ($bDebug){ LogMsg("Debug: CSV output=".$sKey.$sDelim.$sCSV); }

                                file_put_contents($sInterCSVFileName, $sKey.$sDelim.$sCSV."\n", FILE_APPEND);

                                // now put into $aCSVResults
                                $aCSVResults[] = array ("task"=>$sKey, "thermalpct"=>$sThermPct, "latlon"=>$sLatLon, "dist"=>$sDist, "bearing"=>$fBearing );
                                /*
							Array
							(
								[0] => Array
									(
										[task] => MTF-50-225-9of11-MTF_Mottisfont_Station
										[thermalpct] => 56
										[latlon] => 51.034,-1.547|50.715,-2.049
										[dist] => 51
									)

								[1] => Array
									(
										[task] => MTF-50-225-9of11-MTF_Mottisfont_Station
										[thermalpct] => 55
										[latlon] => 51.034,-1.547|50.715,-2.049
										[dist] => 51
									)

							)
                                */
                        }
                }
        }
}
// ----------------------------------------------------------------------------
function OutputNCLData()
{
    global $bDebug;         // debugging flag
	global $gsLocationsFile;
	global $aCSVResults;
	global $sResultsFilename;
	global $gsPolyResultsFilename;
	global $gsBoundaryFile;
    global $giMAXTHERMALPCT;
	
	$aFinal = array ();

	if ($bDebug){ LogMsg("Started OutputNCLData() ************************************");}
	
    if ($bDebug){ LogMsg("Number of elements found from processed results (CSVResults)=".count($aCSVResults));}
	
	// read in the airfields again
	$aLocationsFile = file($gsLocationsFile);

	// for each location in the locations file ...
	foreach ($aLocationsFile as $sLocation)
	{
		if ($bDebug){ LogMsg("sLocation=".ltrim(rtrim($sLocation)) );} //  sLocation=LLD|Llandovery|51.995783|-3.802000|Turn Point

		$aLocation = explode("|", ltrim(rtrim($sLocation)));
		
		$sLat = rtrim( ltrim($aLocation[2]) );
		$sLon = rtrim( ltrim($aLocation[3]) );
		$sJoinedTaskName = $aLocation[0]."_".$aLocation[1]; // reglue together to match in results
		
		if ($bDebug){ LogMsg("aLocation[0] sLat sLon '".$aLocation[0]."' '".$aLocation[1]."' '".$aLocation[2]."' '".$aLocation[3]."' '".$sLat."' '".$sLon."'");}
		if ($bDebug){ LogMsg("Joined Task Name =".$sJoinedTaskName);}

		$iDist = 0;
		$bFound = FALSE;
		$aFinal[$sJoinedTaskName] = array ( $sJoinedTaskName, $sLat, $sLon, 0, 0, 0, "None" ); // set to zero
		
		// now look for one like the current location in the results that have a distance >0 and a thermal percent low enough
		foreach ($aCSVResults as $aTask) {
				//[task] => MTF-50-225-9of11-MTF_Mottisfont_Station
			//            0  1  2   3    4
			//[thermalpct] => 55
			//[latlon] => 51.034,-1.547|50.715,-2.049
			//[dist] => 51

			$aTaskDetail = explode("-",$aTask["task"]);
			
			if ($bDebug){ 
                LogMsg("TaskDetail[4] sJoinedTaskName='".$aTaskDetail[4]."' '".$sJoinedTaskName."'");
                LogMsg("aTask[thermalpct]=".$aTask["thermalpct"]);
                LogMsg("aTask[latlon]=".$aTask["latlon"]);
                LogMsg("aTask[dist]=".$aTask["dist"]);
            }

			if ($aTaskDetail[4] == $sJoinedTaskName) // we have found
			{
				if ($bDebug){ LogMsg("Found ...".$sJoinedTaskName);}
				// the second parameter is the distance achieved
				$aDist = $aTaskDetail[1];

				if ($aTask["dist"] > $iDist) // found a better one i.e. is longer
				{
					$bFound = TRUE;
					// overwrite
					$aFinal[$sJoinedTaskName] = array ( $sJoinedTaskName, $sLat, $sLon, $aTask["dist"], $aTask["thermalpct"], $aTask["bearing"], $aTask["latlon"] );
					if ($bDebug){ LogMsg("Found a better one");}
					$aThermPct = $aTask["thermalpct"];
					$iDist = $aTask["dist"];
				} else {
					if ($bDebug){ LogMsg("Found, but not a better one ".$aTask["dist"]." v $iDist");}
				}
			}
		}
		if ($bFound == FALSE){
			if ($bDebug){ LogMsg("No task found and less than ".$giMAXTHERMALPCT."%, so setting to ".$sJoinedTaskName." ".$sLat." ".$sLon." 0 0 0 NoCoords");}
			$aFinal[$sJoinedTaskName] = array ( $sJoinedTaskName, $sLat, $sLon, 0, 0, 0, "NoCoords" );
		}
	}

	// now print the ncl output
	//sResultsFilename
	file_put_contents($sResultsFilename, "SiteName StartLatitude StartLongitude Distance Direction TaskCoords\n");

	$bPolygonsWritten = FALSE;
	foreach( $aFinal as $key=>$aRes )
	{
        $sDelim = ",";
		$sLine = $aRes[0].$sDelim.$aRes[1].$sDelim.$aRes[2].$sDelim.$aRes[3].$sDelim.$aRes[5].$sDelim.$aRes[6];
		file_put_contents($sResultsFilename, $sLine."\n", FILE_APPEND);
		if ($bDebug){ LogMsg("sLine =".$sLine);}
		
		// now any polygons but only if the distance is grater than zero
		//     aRes[3] is the distance and we only write polygons when they are positive
		if ($aRes[3] > 0)
		{
            $sLine = str_replace("|", ",",$aRes[6]); // remove vertical bar
            $sLine = str_replace(" ", ",",$sLine); // remove spaces
            // now should be just lat/long pairs as CSV
            file_put_contents($gsPolyResultsFilename, $sLine."\n", FILE_APPEND);
            $bPolygonsWritten = TRUE;
            if ($bDebug){ LogMsg("sLine Polygons =".$sLine);}
		}
	}
	
	if ($bPolygonsWritten == FALSE){ // no poloygons written - create empty file
        file_put_contents($gsPolyResultsFilename, "");
        if ($bDebug){ LogMsg("NoPolygons created - made empty file");}
	}
	
	// read in the boundary file
	$aBoundaryFile = file($gsBoundaryFile);
	if ($bDebug){ LogMsg("Adding boundary file =".$gsBoundaryFile);}
	foreach( $aBoundaryFile as $sLine )
	{
		file_put_contents($sResultsFilename, $sLine, FILE_APPEND);
	}
	if ($bDebug){ LogMsg("Finished OutputNCLData() ************************************");}
}
// ----------------------------------------------------------------------------
?>
