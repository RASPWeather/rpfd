;----------------------------------------------------------------------
begin
    ;
    ; Data is stored in four columns:  location  lat  lon  distance

    ; Read in each line as a string, and use "str_get_field" to 
    ; read in the fields of interest.
    ;

    print ("NCL:Normal start.")
;----------------------------------------------------------------------
    env_source_data_file  = getenv("ENV_NCL_INPUT_DATA_FILE")
    
    if(ismissing(env_source_data_file  )) then
        ; the file is not defined
        print ("NCL ERR:Source file environment variable not defined.")
        exit
    end if
    
    fname = env_source_data_file

    if (fileexists(fname)) then
        print ("NCL:Data source source file '" + fname + "' found.")
    else
        print ("NCL ERR:Data source source file '" + fname + "' not found.")
        exit
    end if
;----------------------------------------------------------------------
        
    ; now read all the parsed data in  
    lines = asciiread(fname,-1,"string")
    ;
    ; Use "str_get_field" to indicate which fields to read in.
    ; Each field is separated by an arbitrary number of spaces.
    ; start at line 1 (which is the second)

;----------------------------------------------------------------------
    env_exec_time  = getenv("ENV_EXEC_TIME")
    env_model_name = getenv("ENV_MODEL_NAME")
    env_validity   = getenv("ENV_VALIDITY")
    env_glider     = getenv("ENV_GLIDER_POLAR")
    env_therm_pct  = getenv("ENV_THERM_PCT")
    env_plot_output_file = getenv("RPFD_NCL_OUTPUT_FILE")
    env_plot_task_type = getenv("ENV_NCL_INPUT_TASK_TYPE")

    if(ismissing(env_plot_output_file )) then
        ; the file is not defined
        print ("NCL ERR:Output png file not defined ...")
        status_exit(1)
    end if

    if(ismissing(env_exec_time)) then
            env_exec_time = ""
    else 
        env_exec_time = "Created: "+env_exec_time
    end if
    print ("NCL:env_exec_time set to:"+env_exec_time)
    
    if(ismissing(env_model_name)) then
            env_model_name = ""
    else
        env_model_name =  "Model: "+env_model_name
    end if
    print ("NCL:env_model_name set to:"+env_model_name)
    
    if(ismissing(env_validity)) then
            env_validity = ""
    else
        env_validity = "Valid: " + env_validity
    end if
    print ("NCL:env_validity set to:"+env_validity )
    
    if(ismissing(env_glider)) then
            env_glider = ""
    else
        env_glider = "Polar: "+env_glider
    end if
    
    print ("NCL:env_glider set to:"+env_glider)
    if(ismissing(env_therm_pct)) then
            env_therm_pct = ""
    else
        env_therm_pct = "Thermalling %: "+env_therm_pct+"%"
    end if
    print ("NCL:env_therm_pct set to:"+env_therm_pct)

    if(ismissing(env_plot_task_type)) then
            env_plot_task_type = ""
    else
        env_plot_task_type  = "Task type: "+env_plot_task_type
    end if
    print ("NCL:Task algorithm env_plot_task_type set to:"+env_plot_task_type)
    
    print ("NCL:Found or set environment vars and files ...")
    
;----------------------------------------------------------------------
  pwv            = tofloat(str_get_field(lines(1:),4,","))  ; this is the distance variable
  pwv@lat1d      = tofloat(str_get_field(lines(1:),2,","))  ; Attach as lat1d, lon1d
  pwv@lon1d      = tofloat(str_get_field(lines(1:),3,","))  ; for plotting later
  pwv@location1d = str_get_field(lines(1:),1,",")           ; for plotting later
  pwv@taskcoords = str_get_field(lines(1:),6,",")           ; this is the task coordinates 
  pwv@bearing    = tofloat(str_get_field(lines(1:),5,","))  ; this is the bearing variable
  
  print ("NCL:Read all the data in ...")

  ; output PNG and call the file station.png
  wks = gsn_open_wks("png",env_plot_output_file)       ; send graphics to PNG file

  print ("NCL:Set output file ...")

  cmap = read_colormap_file("WhViBlGrYeOrRe") ; read color map

  res                             = True
  res@gsnMaximize                 = True
  res@gsnFrame                    = False   ; Want to draw markers later.
  res@gsnDraw                     = False
  res@gsnAddCyclic                = False

  res@sfXArray             =  pwv@lon1d
  res@sfYArray             =  pwv@lat1d
  ;res@sfZArray             =  pwv@taskcoords

  res@gsnMaximize           = True
  res@mpFillOn              = False
  res@mpLimitMode           = "LatLon"
  res@mpProjection          = "Mercator"        ; choose projection

  res@pmTickMarkDisplayMode = "Always"
  ; turn the lat/lon grid on or off
  res@mpGridAndLimbOn       = True
  ; set the Lat grid spacing in degrees 
  res@mpGridLatSpacingF     = 0.5 
  ; set the Lon grid spacing in degrees 
  res@mpGridLonSpacingF     = 0.5
  ; use high res data for the map regions
  res@mpDataBaseVersion     = "HighRes"

  res@cnLineLabelPlacementMode    = "Randomized"
  res@cnLineLabelFontColor        = "Gray15"
  res@cnLineDashSegLenF           = 0.3

  ; manually set the scale levels
  res@cnLevelSelectionMode        = "ManualLevels" 
  ; res@cnLevelSelectionMode        =  "AutomaticLevels" 
  res@cnMinLevelValF              = 0
  res@cnMaxLevelValF              = 400		
  res@cnLevelSpacingF             = 50

  res@cnFillOn                    = True
  res@cnFillPalette               = cmap(2:88,:)
  res@cnFillOpacityF              = 0.75 
  res@cnLinesOn                   = False ;True
  res@cnLineLabelsOn              = True
  ; see https://www.ncl.ucar.edu/Document/Graphics/Resources/cn.shtml#cnLevelFlags
  res@cnLevelFlags		          = new(139,"string")
  res@cnLevelFlags(:)		      = "NoLine"
  ;res@cnLevelFlags(0::20)         = "LineAndLabel"
  res@cnLevelFlags(0::20)         = "LineOnly"

  res@lbOrientation               = "vertical"
  ; add line separator to scale colours
  res@lbBoxLinesOn                = True

;---Zoom in on map area of interest
;	this will auto set based on the lat/lon in the data set
  res@mpMinLatF                   = min(pwv@lat1d)-.5
  res@mpMinLonF                   = min(pwv@lon1d)-.5
  res@mpMaxLatF                   = max(pwv@lat1d)+.5
  res@mpMaxLonF                   = max(pwv@lon1d)+.5

  res@mpFillOn                    = False
  res@mpOutlineDrawOrder          = "PostDraw"
  res@mpFillDrawOrder             = "PreDraw"

  res@pmTickMarkDisplayMode       = "Always"   ; nicer map tickmarks

;---Create the plot; it won't get drawn because gsnFrame was set to False.

  res@tiMainString = "RASP Potential Flight Distance Km"
  
  print ("NCL:Set settings up ...")

  plot = gsn_csm_contour_map(wks,pwv,res)
  
  print ("NCL:Made initital plot ...")

;************************************************
; Draw text on plot using NDC coordinates.
;************************************************
  txres               = True                     ; text mods desired
  txres@txFontHeightF = 0.01                     ; font smaller. default big

; page coordinates are normalized. go from 0->1 not 0->8" etc.
  gsn_text_ndc(wks,"Not for air navigational purposes",0.5,0.99,txres) 
  gsn_text_ndc(wks, env_plot_task_type,0.89,0.01,txres) 
  gsn_text_ndc(wks, env_glider,  0.892,0.025,txres) 
  gsn_text_ndc(wks, env_therm_pct,0.846,0.04,txres) 
  gsn_text_ndc(wks, env_model_name,0.875,0.055,txres) 

  txres@txFontHeightF = 0.018
  txres@txFontColor = "ForestGreen"
  gsn_text_ndc(wks,env_validity + " 06:00-18:00",0.5, 0.930, txres)
  
  txres@txFontHeightF = 0.012
  txres@txFontColor = "Blue"
  gsn_text_ndc(wks,env_exec_time ,0.5, 0.01, txres) 

  txres@txFontHeightF = 0.04
  txres@txFontColor = 3
  txres@txAngleF = 315
  gsn_text_ndc(wks,"Experimental",0.5, 0.5, txres) 

  print ("NCL:Added labels...")
  
  plot = gsn_csm_contour_map(wks,pwv,res)
  
  print ("NCL:Made update to plot ...")
    
;---Draw markers on the plot in the lat/lon locations.
; https://www.ncl.ucar.edu/Document/Graphics/Images/markers.png
  mkres               = True
  mkres@gsMarkerIndex = 1     ; dot
  gsn_coordinates(wks,plot,pwv,mkres)
  
  print ("NCL:Added points/markers ...")

; ----------------------------------------------------------------
  ; normal finish
  print ("NCL:Normal finish.")
  status_exit(0)

end 

