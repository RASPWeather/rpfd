<?php
        $sForm = BuildMainContent();
        echo $sForm;

exit;
// -----------------------------------------------------------------------
function BuildMainContent()
{
    date_default_timezone_set('Europe/London');

    $FORM = "";

    $sRootURL = "http://mrsap.org/rasp/rpfd";
    echo "Fix the sRootFiles variable and delete this line";exit;
    $sRootFiles = "<wherei ever you put this stuff>/rpfd";
    // http://mrsap.org/rasp/rpfd/LOG/rpfd.uk12.log

    $sLogURL = $sRootURL. "/LOG";
    $sOutURL = $sRootURL. "/OUT";
    $aModels = array ( "uk12","uk12+1","uk12+2","uk12+3","uk12+4","uk12+5","uk12+6" );

    $FORM .= "<table>";
    $FORM .= "<tr>";
    $FORM .= "<th align='center'>RPFD Distance<br>Contour Map</th>";
    $FORM .= "<th align='center'>RPFD Task<br>Polygon Map</th>";
    //$FORM .= "<th>RPFD Log file</th>";
    //$FORM .= "<th>RPFD Log Error file</th></tr>";
    $FORM .= "<th align='center'>Progress Log</th>";
    $FORM .= "<th align='center'>Last Status</th>";
    $FORM .="</tr>";

    foreach ($aModels as $key=>$sModel)
    {
        $FORM .= "<tr>";

        // RPFD files
        // http://mrsap.org/rasp/rpfd/OUT/rpfd.UK12.png
        $sTarget = $sOutURL."/rpfd.".strtoupper($sModel).".png";
        $FORM .= "<td align='center'><a href='$sTarget'>$sModel PNG</a></td>";

        // Polygons
        // http://mrsap.org/rasp/rpfd/OUT/rpfd.UK12.poly.png
        $sTarget = $sOutURL."/rpfd.".strtoupper($sModel).".poly.png";
        $FORM .= "<td align='center'><a href='$sTarget'>$sModel Poly PNG</a></td>";

        // Log files
        // http://mrsap.org/rasp/rpfd/LOG/rpfd.uk12.log
        //$sTarget = $sLogURL."/rpfd.$sModel.log";
        //$FORM .= "<td><a href='$sTarget'>$sModel Log</a></td>";

        // Error Files
        // http://mrsap.org/rasp/rpfd/LOG/rpfd.uk12.log.err
        //$sTarget = $sLogURL."/rpfd.$sModel.log.err";
        //$FORM .= "<td><a href='$sTarget'>$sModel Error Log</a></td>";

        /// Progress ...
        // http://mrsap.org/rasp/rpfd/OUT/progress.uk12.txt
        $sTarget = $sOutURL."/progress.$sModel.txt";
        $FORM .= "<td align='center'><a href='$sTarget'>$sModel</a></td>";

        $sLine = TailLogFile($sRootFiles."/OUT/progress.".$sModel.".txt");
        $FORM .= "<td align='center'>$sLine</td>";

        $FORM .= "</tr>";
    }
    $FORM .= "</table>";
    $FORM .= "<br><small>Refershed at: ".date(DATE_RFC2822)."</small>";

        return $FORM;
}
// -----------------------------------------------------------------------
function TailLogFile($sFileLocation)
{
    $aLines = file($sFileLocation); // hopefully not enormous though ...
    $iBufferSize = count($aLines); // how many did we get?

    return $aLines[$iBufferSize-1]; // just the last line
}
?>
