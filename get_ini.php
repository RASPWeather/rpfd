<?php
	// Ahelper application to provide contents of INI file to the command line for shell scripts
	// ---------------------------------------------------------------------------------------------------
	// Parse INI file sections
	$ini_array = parse_ini_file("rpfd.ini", true);
	if ($ini_array == FALSE)
	{
			//can't find ini file!
			echo "Please check the general configuration file exists in the installation.\n";
			exit(1);
	}
	// ---------------------------------------------------------------------------------------------------

	// parse the inputs -> we need just one
    $sParam = "";
    
    // we need to get from the command line 
	if ($argc == 1) {
			//nothing specified
			echo "Run again with the INI entry you want, e.g. glider_polars\n";
			exit(1);
	} else { 
        foreach ($argv as $key=>$value){
            if ($key=="1"){
                    $sParam = $value;
            } 
        }
	}
	// ---------------------------------------------------------------------------------------------------
	if (array_key_exists($sParam, $ini_array) )
	{
        // found 
        if ( $ini_array[$sParam] != "" ){
            echo $ini_array[$sParam]; // no CRLF
        } else {
            echo $ini_array[$sParam]."is empty\n";
            exit(1);
        }
	} else {
        echo "INI entry '$sParam' not found.\n";
        exit(1);
	}
	// ---------------------------------------------------------------------------------------------------
?>

