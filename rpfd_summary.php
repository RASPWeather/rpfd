<?php
// ----------------------------------------------------------------------------
// Reads logs and results to create an XML summary file for that run
//
// Accepts the model as the input e.g. uk12 or UK12
// ----------------------------------------------------------------------------

    // globals
    $bDebug = TRUE;
    $gHomeDirectory = "/home/rasp3/build/rpfd";
    $gsRegion = "";
    $gsProgressTextFile = "";
    $gsOutputFile = "";
    $gsInputLogFile = "";
    $gsInputCSVFile = "";
    
    // ------------------------------------------------------------------------
    // Check the input parameter
    // we need to get from the command line the region to use or default
	if ($argc>1) {
        foreach ($argv as $key=>$value){
            if ($key=="1"){
                    $gsRegion=$value;
                    LogMsg("Using supplied region: ".$gsRegion);
                    break; // move on ...
            }
        }
	} else { 
        // day/region
        LogMsg("No region or model given. Try uk12.\n");
        exit(1);
	}
	// set to lowercase
	$gsRegion = strtolower($gsRegion);
	LogMsg("Set supplied region to: ".$gsRegion);
	
    // ------------------------------------------------------------------------
    $gsProgressTextFile = $gHomeDirectory."/LOG/".$gsRegion.".run.summary.log";
    if ($bDebug) { LogMsg("Set supplied output log file to: ".$gsProgressTextFile );}

    $gsOutputFile = $gHomeDirectory."/OUT/".$gsRegion.".run.summary.xml";
    if ($bDebug) { LogMsg("Set output XML file to: ".$gsOutputFile  );}
    
    $gsInputLogFile = $gHomeDirectory."/LOG/rpfd.".$gsRegion.".log";
    if ($bDebug) { LogMsg("Set input run log file to: ".$gsInputLogFile );}
    
    $gsInputCSVFile  = $gHomeDirectory."/OUT/".$gsRegion.".rpfd.csv";
    if ($bDebug) { LogMsg("Set input run CSV file to: ".$gsInputCSVFile  );}
    // ------------------------------------------------------------------------
    $iResult = CreateSummaryXML($gsRegion);
    
    if ($iResult == 0) {
        LogMsg("Finished.\n");
    } else {
        LogMsg("Finished with errors.\n");
    }

// end of main body
// ----------------------------------------------------------------------------
function CreateSummaryXML($sRegion)
{
    global $gsInputCSVFile, $gsInputLogFile, $gsOutputFile, $bDebug;
    $aLogFile = array();

    // ------------------------------------------------------------------------
    // Read the .LOG file
    if (file_exists($gsInputLogFile) == FALSE)
    {
        LogMsg("Failed to open in the log file: ".$gsInputLogFile);
        return -1;
    }

    $aLogFile = file($gsInputLogFile);
    if (count($aLogFile)< 2)
    {
        LogMsg("Failed to read in the log file. Size was: ".count($aLogFile));
        return -1;
    }
    
    // look for "2018-03-27 18:33:13: Using supplied region: uk12"
    
    // get start time
    foreach ($aLogFile as $key=>$value)
    {
        $aLine = explode(" ",$value);
        if (count($aLine) > 2) // found it
        {
            $aStartDate = $aLine[0]; 
            $aStartTime = $aLine[1];
            if ($bDebug) { LogMsg("Found start date of '$aStartDate' and start time of '$aStartTime'");}
            
            $aDate = explode("-",$aStartDate ); // assume succeeds
            $iYear = $aDate[0];
            $iMonth = $aDate[1];
            $iDay = $aDate[2];

            $aTime = explode(":",$aStartTime ); // assume succeeds
            $iHour = $aTime[0];
            $iMin = $aTime[1];
            $iSec = $aTime[2];
            
            $sStartString = "$iYear/$iMonth/$iDay $iHour:$iMin:$iSec";

            // compute UNIX equivalents as an integer
            $iUnixDTMStart = mktime($iHour, $iMin, $iSec, $iMonth ,$iDay, $iYear);
            if ($bDebug) { LogMsg("Unix start date and time of:'$iUnixDTMStart'");}
            break;
        }
        
    }
    
    // get finish time
    // we expect the last line to be used
    $sLine = $aLogFile[count($aLogFile)-1];
    $aLine = explode(" ",$sLine);
    if (count($aLine) > 2) // found it
    {
        $aFinishDate = $aLine[0];
        $aFinishTime = $aLine[1];
        if ($bDebug) { LogMsg("Found finish date of '$aFinishDate' and finish time of '$aFinishTime' using: '".$sLine."'");}
        $aDate = explode("-",$aFinishDate ); // assume succeeds
        $iYear = $aDate[0];
        $iMonth = $aDate[1];
        $iDay = $aDate[2];

        $aTime = explode(":",$aFinishTime ); // assume succeeds
        $iHour = $aTime[0];
        $iMin = $aTime[1];
        $iSec = $aTime[2];
        // rebuild
        $sFinishString = "$iYear/$iMonth/$iDay $iHour:$iMin:$iSec";

        // compute UNIX equivalents as an integer
        $iUnixDTMFinish = mktime($iHour, $iMin, $iSec, $iMonth ,$iDay, $iYear);
        if ($bDebug) { LogMsg("Unix finish date and time of:'$iUnixDTMFinish'");}

    } else {
        LogMsg("Failed to find the finish time. Line was: ".$sLine );
        return -1;
    }
    
    
    $iSecsTaken = $iUnixDTMFinish - $iUnixDTMStart;
    if ($bDebug) { LogMsg("Run time taken (s):$iSecsTaken");}
        
    // ------------------------------------------------------------------------
    // Read the .CSV file
    if (file_exists($gsInputCSVFile) == FALSE)
    {
        LogMsg("Failed to open in the CSV file: ".$gsInputCSVFile);
        return -1;
    }

    $aCSVFile = file($gsInputCSVFile);
    if (count($aCSVFile )< 2)
    {
        LogMsg("CSV file empty - possibly an error or no data for that day. Rows found: ".count($aCSVFile ));
        return -1;
    }
    // Ouptut row looks like:
        // BRT-100-45-2of6-BRT_Burnford_Common,,50.588 -4.162|50.877 -4.039|50.665 -3.705|50.588 -4.162,101,45,54,1300-1505,125,26,48,63,0,1,1.6,-2,1.6,SUCCESS,Tue Mar 27,Tue Mar 27 18:33:18 2018,UK12,Valid: CurrentDay  StartTime: 0700lst,d2 = 12000m,StdCirrus (L/D=36),WeightRatio: 1 DryWeight: 337 kg,WeightRatio: 1 DryWeight: 337 kg,1.0
        
    // assumed for now to be for only be for one glider, model
    $aEntry = explode(",",$aCSVFile[0]);
        
    // we want column 17 for the day forecasted
    $sValidDay = $aEntry[17];
    
    // we want column 22 for the Polar used
    $sGliderPolar = $aEntry[22];
    
    // ------------------------------------------------------------------------
    // Now create the XML file
    $sXML = "";
    $sXML .= "<RPFDSummary>";
    $sXML .= "\n    <RunModel>$sRegion</RunModel>";
    $sXML .= "\n    <ValidDay>$sValidDay</ValidDay>";
    $sXML .= "\n    <Polar>$sGliderPolar </Polar>";
    
    $sXML .= "\n    <RunStarted>$sStartString</RunStarted>";
    $sXML .= "\n    <RunFinished>$sFinishString</RunFinished>";
    $sXML .= "\n    <RunStartedUNIX>$iUnixDTMStart</RunStartedUNIX>";
    $sXML .= "\n    <RunFinishedUNIX>$iUnixDTMFinish</RunFinishedUNIX>";
    $sXML .= "\n    <RunTimeTaken>$iSecsTaken</RunTimeTaken>";
    $sXML .= "\n    <SummaryCreated>".date("Y-m-d H:i:s")."</SummaryCreated>";
    $sXML .= "\n    <SummaryHost>".gethostname()."</SummaryHost>";
    
    $sXML .= "\n</RPFDSummary>";
    
    if ($bDebug) { echo "\n\n$sXML\n\n";}
    
    // now wite the summary out
    // Write the contents back to the file
    $iResult = file_put_contents($gsOutputFile, $sXML);
    if ($iResult !== FALSE)
    {
        // good nothing broke
        return 0;
    } else {
        LogMsg("Failed to write output to: ".$gsOutputFile);
        return -1;
    }
}
// ----------------------------------------------------------------------------
// Simply echo to the conaole wit ha date/time stamp
function LogMsg($sMsg)
{
        echo "\n".date("Y-m-d H:i:s").": $sMsg";
}
// ----------------------------------------------------------------------------
// This will give output so CGIs can track how far the php script has got through the locations
function LogProgress( $sMsg )
{
    global $bDebug;
    global $gsProgressTextFile;

    $sProgress = "\n".date("Y-m-d H:i:s").": $sMsg";
    // append to the progress file ...
    file_put_contents($gsProgressTextFile, $sProgress, FILE_APPEND);
}
// ----------------------------------------------------------------------------

?>
