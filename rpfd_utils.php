<?php
// ----------------------------------------------------------------------------
// Work out final bearing based on input and whther minus or plus the input bearing
/*
	Example is if 90 is input and we want the left bearing (minus), that's 90-30 degrees (60)
	Note if the input bearing is smaller 29 or less then we go through North and also if >330
*/
function CalcSubBearing($iInputBearing)
// assumes 60 degrees apart in equilateral triangle
{
	global $bDebug;         // debugging flag

	if ($iInputBearing< 0) // bad
	{
		if ($bDebug){ LogMsg("Bad input bearing '$iInputBearing' as less than zero!\n");} // catch if bad in the ini file
		// return bad!
		exit (1);
	}

	if ( ($iInputBearing>30) && ($iInputBearing<330) ) // easy sums
	{
		$iLeft = $iInputBearing - 30;
		$iRight = $iInputBearing + 30;
		// return each component either side of the input bearing
		return array ($iLeft, $iRight);
	}
	// is it 330 to 359?
	if ( ($iInputBearing>=330) && ($iInputBearing<=360) ) 
	{
		$iLeft = $iInputBearing - 30; // no problem as still less than 360
		
		// right will be 60 degrees plus $iLeft, subtract 360
		// e.g. Input = 335, left is 305, right is 005 
		
		$iRight = (360 - ($iLeft + 60)) * -1;

		if ($iRight == 360){
			$iRight = 0; // reset to due north
		}
		
		return array ($iLeft, $iRight);
	}
	// must be 29 or less
	$iRight = $iInputBearing + 30; // no problem as going upwards
	// left will be 60 degrees minus $iRight 
	// e.g. Input = 005, right is 035, left is 335
	
	$iLeft = (360 + $iInputBearing) -30;
	if ($iLeft == 360){
		$iLeft = 0; // reset to due north
	}
	return array ($iLeft, $iRight);
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
// if we get <model>+1 or similar, turn into html chars with %2b
function CheckRegion($sRegion)
{
	$sText = str_replace("+", "%2b", $sRegion);
	return $sText;
}
// ----------------------------------------------------------------------------
/*
        lat1 = latitude of start point in degrees

        long1 = longitude of start point in degrees

        d = distance in KM

        angle = bearing in degrees
*/
function get_gps_distance($lat1,$long1,$d,$angle)
{
        # Earth Radious in KM
        $R = 6378.14;

        # Degree to Radian
        $latitude1 = $lat1 * (M_PI/180);
        $longitude1 = $long1 * (M_PI/180);
        $brng = $angle * (M_PI/180);

        $latitude2 = asin(sin($latitude1)*cos($d/$R) + cos($latitude1)*sin($d/$R)*cos($brng));
        $longitude2 = $longitude1 + atan2(sin($brng)*sin($d/$R)*cos($latitude1),cos($d/$R)-sin($latitude1)*sin($latitude2));

        # back to degrees
        $latitude2 = $latitude2 * (180/M_PI);
        $longitude2 = $longitude2 * (180/M_PI);

        # 6 decimal for Leaflet and other system compatibility
   $lat2 = round ($latitude2,6);
   $long2 = round ($longitude2,6);

   // Push in array and get back
   $tab[0] = $lat2;
   $tab[1] = $long2;

   // return two-element array
   return $tab;
 }
// ----------------------------------------------------------------------------
// load the configured parameters from the INI file

function LoadInputParameters()
{
    global $gsRegion; // array
    global $gsTaskType;
    global $bDebug;
    global $gsHomeFolder;
    global $giMAXTHERMALPCT;
    global $gsLocationsFile;
    global $gsOutputFolder;
    global $sResultsFilename;
    global $aDistanceIncrements; // array
    global $aGliderTypes; // array
    global $aVectorDirections; // array
    global $gsBoundaryFile; 
    global $sInterCSVFileName;
    global $sCgiScript;
    global $gsTaskType;
    global $gIniFileName;
    global $argc,$argv;
    global $gsProgressTextFile;
    global $gsPolyResultsFilename;
    
	// we need to get from the command line the region to use or default
	if ($argc>1) {
			foreach ($argv as $key=>$value){
					if ($key=="1"){
							$gsRegion=$value;
							LogMsg("Using supplied region: ".$gsRegion);
							break; // move on ...
					}
			}
	} else { // default to UK12
			// days/regions
			echo "No region or model given. Try uk12.\n";
			exit(1);
	}
	// ---------------------------------------------------------------------------------------------------
	// Parse INI file sections
	$ini_array = parse_ini_file($gIniFileName, true);
	if ($ini_array == FALSE)
	{
			//can't find ini file!
			echo "Please check the general configuration file exists in the installation.";
			exit(1);
	}
	// used to enable extra logging if needed in main body. Set TRUE to turn on
	if ($ini_array["debug_mode"] == "TRUE")
	{
			$bDebug = TRUE;
			LogMsg("Debugging enabled.");

	} else {
			$bDebug = FALSE;
	}

	// this root folder is used everywhere and shoud be set
	if ($ini_array["home_folder"] != "")
	{
			$gsHomeFolder = $ini_array["home_folder"];
			LogMsg("Home folder set to =".$gsHomeFolder);
			if (!file_exists($gsHomeFolder)) {
					LogMsg("Home folder '$gsHomeFolder' not found.");
					exit(1);
			}
	}  else {
			LogMsg("The home folder location is not set. Set in section 'home_folder' in the INI file and rerun.");
			exit( 1 );
	}

	if ($ini_array["thermal_percent"])
	{
			$giMAXTHERMALPCT = $ini_array["thermal_percent"];
			LogMsg("Thermalling check percent set to =".$giMAXTHERMALPCT);
	}  else {
			$giMAXTHERMALPCT = 57;
			LogMsg("Thermalling check percent default to =".$giMAXTHERMALPCT);
	}
	if ($ini_array["locations_file"] != "")
	{
			$gsLocationsFile = $gsHomeFolder."/".$ini_array["locations_file"];
			LogMsg("Locations file set to =".$gsLocationsFile);
			if (!file_exists($gsLocationsFile)) {
					LogMsg("Locations file '$gsLocationsFile' not found.");
					exit(1);
			}
	}  else {
			LogMsg("The locations file is not set. Set in section 'locations_file' in the INI file and rerun.");
			exit( 1 );
	}
	// progress file for watching where we are up to - tracks locations as the counter
	if ($ini_array["output_folder"] != "")
	{
			$gsOutputFolder = $gsHomeFolder."/".$ini_array["output_folder"];
			LogMsg("Output folder set to =".$gsOutputFolder);

			if (!file_exists($gsOutputFolder)) {
					LogMsg("Output folder '$gsOutputFolder' not found.");
					exit(1);
			}

			$gsProgressTextFile = $gsOutputFolder."/progress.".ltrim(rtrim($gsRegion)).".txt";
			// to allow for parallel output processing
			LogMsg("Progress file set to =".$gsProgressTextFile);
			// now empty it if it exists
			// note this is used by a CGI to assess progress of the script
			if (file_exists($gsProgressTextFile)) {
					unlink($gsProgressTextFile);
			}
	}  else {
			LogMsg("The progress log file is not set. Set in section 'output_folder' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["results_file_name"] != "")
	{
			$sResultsFilename = $gsRegion.".".$ini_array["results_file_name"];
			LogMsg("Results file set to =".$sResultsFilename);
			$sResultsFilename = $gsOutputFolder."/".$sResultsFilename;
			LogMsg("Full results file set to =".$sResultsFilename);
	}  else {
			LogMsg("The results file is not set. Set in section 'results_file_name' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["task_distances"] != "")
	{
			// distance increments in km to check for
			$aDistanceIncrements = explode (",",$ini_array["task_distances"]);
			LogMsg("Task distances set to =".$ini_array["task_distances"]);
	}  else {
			LogMsg("Task distances not set. Set in section 'task_distances' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["glider_polars"] != "")
	{
			$aGliderTypes = explode (",",$ini_array["glider_polars"]);
			// just one for now - matches what is in the glider string array in track average start time
			LogMsg("Glider type set to =".$ini_array["glider_polars"]);
	}  else {
			LogMsg("Glider type (polar) not set. Set in section 'glider_polars' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["vector_directions"] != "" )
	{
			// Vector direction change in degrees
			$aVectorDirections = explode (",",$ini_array["vector_directions"]);
			LogMsg("Vector directions set to =".$ini_array["vector_directions"]);
	}  else {
			LogMsg("Vector directions not set. Set in section 'vector_directions' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["boundary_coords"] != "")
	{
			$gsBoundaryFile = $gsHomeFolder."/".$ini_array["boundary_coords"];
			LogMsg("Boundary data file set to =".$gsBoundaryFile);
			if (!file_exists($gsBoundaryFile)) {
					LogMsg("Boundary coordinates file '$gsBoundaryFile' not found.");
					exit(1);
			}

	}  else {
			LogMsg("The file of boundary values is not set. Set in section 'boundary_coords' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["intermediate_csv"] != "")
	{
			$sInterCSVFileName = $gsRegion.".".$ini_array["intermediate_csv"];
			LogMsg("Intermediate CSV file set to =".$sInterCSVFileName);
			$sInterCSVFileName = $gsOutputFolder."/".$sInterCSVFileName;
			LogMsg("Full Intermediate CSV file set to =".$sInterCSVFileName);
			if (file_exists($sInterCSVFileName)) {
					unlink($sInterCSVFileName);
			}
	}  else {
			LogMsg("The intermediate CSV file is not set. Set in section 'intermediate_csv' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["cgi_script"] != "" )
	{
			// Vector direction change in degrees
			$sCgiScript = $gsHomeFolder."/".$ini_array["cgi_script"];
			LogMsg("CGI Script =".$ini_array["cgi_script"]);
	}  else {
			LogMsg("Vector directions not set. Set in section 'cgi_script' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["task_type"] != "")
	{
			$gsTaskType = $ini_array["task_type"];
			// check if correct - "free", "oar", "closed"
			if  ( ($gsTaskType!= "free") && ($gsTaskType != "oar") && ($gsTaskType != "closed") )
			{
				LogMsg("The task calculation type '$gsTaskType' is not valid. Set in section 'task_type' in the INI file and rerun.");
				exit( 1 );				
			}
			LogMsg("Task type file set to = ".$gsTaskType);
	}  else {
			LogMsg("The task calculation type is not set. Set in section 'task_type' in the INI file and rerun.");
			exit( 1 );
	}
	if ($ini_array["results_poly_file_name"] != "")
	{
			$gsPolyResultsFilename = $gsRegion.".".$ini_array["results_poly_file_name"];
			LogMsg("Results poly file set to =".$gsPolyResultsFilename );
			$gsPolyResultsFilename = $gsOutputFolder."/".$gsPolyResultsFilename ;
			LogMsg("Full results poly file set to =".$gsPolyResultsFilename );
			// remove if needed
			if (file_exists($gsPolyResultsFilename)) {
					unlink($gsPolyResultsFilename);
			}
	}  else {
			LogMsg("The results poly file is not set. Set in section 'results_poly_file_name' in the INI file and rerun.");
			exit( 1 );
	}

	
	LogMsg("Loaded INI file without errors.");

}
// ----------------------------------------------------------------------------

?>
