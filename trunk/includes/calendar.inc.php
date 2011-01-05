<?php
/**
 * Author: Charles Sanders (charless.mxcalendar@gmail.com)
 * Date: 11/30/2010
 * Version: 0.1.2-rc2
 * 
 * Purpose: Creates a easy module for administrators to manage events.
 * For: MODx CMS 0.9.6 - 1.0.X (www.modxcms.com)
 *
 * Visit http://code.google.com/p/mxcalendar/ for full list of parameters
 *
 * Enjoy!
**/

/*-- added to setup params ---*/                        
    //-- show the duration (true, false)
    $showDuration = (!empty($this->config['dispduration']) ? $this->config['dispduration'] : false);
    $showDuration = (!empty($param['mxcShowDuration'])) ? $param['mxcShowDuration'] : $showDuration;
    
/*-- added to setup params ---*/                            
    //-- show the time stamp of the event
    $showTimeSpan = (!empty($this->config['dispeventtime']) ? $this->config['dispeventtime'] : true);
    $showTimeSpan = (!empty($param['mxcShowTimeSpan'])) ? $param['mxcShowTimeSpan'] : $showTimeSpan;
    
/*-- added to setup params [Use ajax Page or current document] ---*/                            
    $ajaxPageId = !empty($param['mxcAjaxPageId']) ? $param['mxcAjaxPageId'] : $modx->documentIdentifier;
    

//--Get the theme datils view
$themeHeader = $this->_getTheme('month.heading',$this->config['mxCalendarTheme']); 
$themeRow = $this->_getTheme('month.inner.row',$this->config['mxCalendarTheme']); 
$themeDayBasic = $this->_getTheme('month.inner.day.base',$this->config['mxCalendarTheme']); 
$themeDay = $this->_getTheme('month.inner.day',$this->config['mxCalendarTheme']); 
$themeEvent = $this->_getTheme('month.inner.day.event',$this->config['mxCalendarTheme']);
    
    
    //-- get this month and this years as an int
    //-- thedate
    $thisdatestamp = (isset($_REQUEST['dt'])) ? $_REQUEST['dt'] : date("Y-m-j");
    if(isset($_REQUEST['dt'])){
        $thismonth = ( int ) date( "m", $thisdatestamp );
        $thisyear = date( "Y", $thisdatestamp );
        $istoday = date( "j", $thisdatestamp );
    } else {
        $thismonth = ( int ) date( "m");
        $thisyear = date( "Y");
        $istoday = date( "j");
    }
    
    //-- create a calendar object based on the first day of the month
    $jd = cal_to_jd( CAL_GREGORIAN, date( "m" ),date(1), date( "Y" ) );
    
    //-- Move to next or previous months
    $date = date("Y-m-1");
    $monthOffset = (!empty($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0;
    $type = (!empty($_REQUEST['type']) && $_REQUEST['type'] == 'next')? '+' : '';
        
    $newMonth = $thismonth + $monthOffset;
    if($newMonth > 12)
        $newdate =     strtotime ( $type.$monthOffset." month +1 year" , strtotime ( $date ) ) ;
    elseif($newMonth < 1)
        $newdate =     strtotime ( $type.$monthOffset." month -1 year" , strtotime ( $date ) ) ;
    else
        $newdate = strtotime ( $type.$monthOffset." month" , strtotime ( $date ) ) ;
    //----------------//
    $newDateStamp = (isset($_REQUEST['dt'])) ? $_REQUEST['dt'] : $thisyear.'-'.$thismonth.'-'.$istoday;
    $datePieces = explode("-", $newDateStamp);
    $newYear = $datePieces[0];
    $newMonth = $datePieces[1];
    $newDay = $datePieces[2];
    if($newMonth == 12 & $type == '+'){
        $evMonth = 1;
        $evYear = $newYear + 1;
    }elseif($newMonth == 1 & $type == '' &  isset($_REQUEST['dt'])){
        $evMonth = 12;
        $evYear = $newYear - 1;
    }else{
        if($type == '+' &  isset($_REQUEST['dt'])){
            $evMonth = $newMonth+1;
        }elseif(isset($_REQUEST['dt'])){
            $evMonth = $newMonth-1;
        }else{
            $evMonth = $newMonth;
        }
        $evYear = $newYear;
    }
    //----------------//
    $newdate = date('Y-m-j', strtotime($evYear.'-'.$evMonth.'-1')); //date ( 'Y-m-j' , strtotime($evYear.'-'.$evMonth.'-1') ); //'Y-m-j'
    $newdatePieces = explode("-", $newdate);
    $jd = cal_to_jd( CAL_GREGORIAN, $newdatePieces[1],$newdatePieces[2], $newdatePieces[0] );
    
    //-- full year for mysql and mssql friendly search
    $fulldate = date("Y-d-m");

/*-- add to setup params ---*/                            
    //-- Exclude Weekends
    $excludeWeekends = (!empty($this->config['calweekends']) ? $this->config['calweekends'] : false );
    $excludeWeekends =  (!empty($param['mxcExcludeWeekends'])) ? $param['mxcExcludeWeekends'] : $excludeWeekends;
    
    //-- find out the number of days in the month
    $numdaysinmonth = cal_days_in_month( CAL_GREGORIAN, $newdatePieces[1], $newdatePieces[0] );


/*-- add to setup params ---*/                            
    //-- set the calendar weekday start range: 0=Sunday - 6=Saturday
    //-- (0 = Sunday, 1 = Monday, etc)
    $startDayID = (!empty($this->config['calstartday'])) ? $this->config['calstartday'] : 0;
    $startDayID = (!empty($param['mxcStartDayID'])) ? $param['mxcStartDayID'] : $startDayID;

    //-- Do Not alter: Forces Monday - Friday on excluded weekend view
    if($excludeWeekends) $startDayID=0;
    
    //-- return the start day as an int (0 = Sunday, 1 = Monday, etc)
    $startday = jddayofweek( $jd , 0);
    if($startDayID > 0)
        $startday = $startday - $startDayID;
        if($startday < 0)
            $startday = 7 + $startday;
    
    //-- Set the localization format for the calendar month label
    $mxcMonthFormat = (!isset($param['mxcMonthLabelFormat']) ? '%B' : $param['mxcMonthLabelFormat']);

$monthname =strftime($mxcMonthFormat,  mktime(0, 0, 0, $newdatePieces[1],$newdatePieces[2], $newdatePieces[0] ) );
//-- Create a language file override for the Month labels when localization becomes an issues
if(defined('_mxCalendar_gl_Months') && $param['mxcGlobalMonthsOverride']==true){
    $arr_MonthLabel_override = explode(',', _mxCalendar_gl_Months);
    $monthname = $arr_MonthLabel_override[$newdatePieces[1]-1];
} 

    if($modx->insideManager()){
        //-- Make Manager Friendly URLs
        $preURL = '?a='.$_REQUEST['a'].'&amp;id='.$_REQUEST['id'].'&amp;dt='.$newdate.'&amp;offset='.($monthOffset-1).'&amp;type=pre';
        $preURLRel = substr($preURL,1);
        $nextURL = '?a='.$_REQUEST['a'].'&amp;id='.$_REQUEST['id'].'&amp;dt='.$newdate.'&amp;offset='.($monthOffset+1).'&amp;type=next';
    } else {
        //-- Make SEO Friendly URLs
        $preURL =$modx->makeUrl($modx->documentIdentifier,'','&dt='.$newdate.'&offset='.($monthOffset-1).'&type=pre'.(!empty($_REQUEST['CatId']) && is_numeric($_REQUEST['CatId']) ? '&CatId='.$_REQUEST['CatId'] : ''),'full');
                    //'javascript: loadCalendar(this, \''.'&dt='.$newdate.'&offset='.($monthOffset-1).'&type=pre'.(!empty($_REQUEST['CatId']) && is_numeric($_REQUEST['CatId']) ? '&CatId='.$_REQUEST['CatId'] : '').'\')';
		  //$modx->makeUrl($modx->documentIdentifier,'','&dt='.$newdate.'&offset='.($monthOffset-1).'&type=pre'.(!empty($_REQUEST['CatId']) && is_numeric($_REQUEST['CatId']) ? '&CatId='.$_REQUEST['CatId'] : ''),'full');
        $preURLRel = '&dt='.$newdate.'&offset='.($monthOffset-1).'&type=pre'.(!empty($_REQUEST['CatId']) && is_numeric($_REQUEST['CatId']) ? '&CatId='.$_REQUEST['CatId'] : '');
        $nextURL = $modx->makeUrl($modx->documentIdentifier,'','&dt='.$newdate.'&offset='.($monthOffset+1).'&type=next'.(!empty($_REQUEST['CatId']) && is_numeric($_REQUEST['CatId']) ? '&CatId='.$_REQUEST['CatId'] : ''),'full'); //'javascript: loadCalendar(this, \''.'&dt='.$newdate.'&offset='.($monthOffset+1).'&type=next'.(!empty($_REQUEST['CatId']) && is_numeric($_REQUEST['CatId']) ? '&CatId='.$_REQUEST['CatId'] : '').'\')'; 
    }

if($param['mxcAddMooJS'])
  $this->_addJS('<script src="'.$this->config['mxcJSCodeSource'].'" type="text/javascript"></script>');

//-- Add ToolTip JS and CSS
if($this->config['disptooltip']){
    $this->_addJS('
    <script>
    //-- ToolTips (Duration,Time Span)
    window.addEvent(\'domready\', function(){
        //-- Tips 3
        /*
        var Tips3 = new Tips($$(\'.mxModal\'), {
                showDelay: 400,
                hideDelay: 400,
                fixed: true
        });
        */
        var customTips = $$(\'.mxModal\');
        var toolTips = new Tips(customTips);
    });
    </script>');
    
}
if($this->param['mxcAjaxPageId'] != $modx->documentIdentifier){
    $this->_addJS('
    <script type="text/javascript" src="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/js/moodalbox.v1.2.full.js"></script>
    ');
    $this->_addCSS('<link rel="stylesheet" href="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/css/moodalbox.css" type="text/css" media="screen" />');
}

$testAjaxCalNavigation = false;
if($testAjaxCalNavigation){
  //$paramHash = '';
  //$param['q']=$modx->makeUrl(50);
  foreach(array_filter($param) AS $k=>$v)
    $paramHash .= ', '.$k.': "'.$v.'"';

    $frontEnd_AjaxCalNavigation = '
    <script type="text/javascript">
        window.addEvent(\'domready\', function(){

        $(\'#mxcprevMonthAJAX\').addEvent(\'click\', function(e) {
                var element = $(\'bsCalendar\');
		new Event(e).stop();
		//asuming that the backend snippet is located in document 49
		new Ajax("[~49~]",{
		//get all the variable/value pairs from the form
			postBody:$(this).getProperty(\'href\'), // $(\'demoForm\').toQueryString(),
                        onComplete:showResponse,
			update:element
		}).request();

        
        }); //-- End Event tracker
        
        }); //-- End DOM Ready
        
	function showResponse(request){
		alert("Update completed.");
		//$(\'bsCalendar\').appendText("completed...");
	}
    </script>';
    //-- Add JavaScript to header to support Ajax Calendar navigation
    $this->_addJS($frontEnd_AjaxCalNavigation);
}

if(!empty($this->config["mxCalendarTheme"])){
    $activeTheme = $this->_getActiveTheme();
    $this->_addCSS('<link rel="stylesheet" type="text/css" href="assets/modules/mxCalendar/themes/'.$this->config['mxCalendarTheme'].'/'.$activeTheme["themecss"].'" /> ');
}
//-- Not used in this view
// $this->_addJS('<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>');

        
//-- Add Heading Area
$modx->setPlaceholder('mxcMonthHeadingPrevious', $preURL);
$modx->setPlaceholder('mxcMonthHeadingPreviousRel', $preURLRel);
$modx->setPlaceholder('mxcMonthHeadingPreviousLabel', (!empty($param['mxcMonthHeadingPreviousLabel']) ? $param['mxcMonthHeadingPreviousLabel'] : _mxCalendar_cl_labelPrevious));
$modx->setPlaceholder('mxcMonthHeadingNext', $nextURL);
$modx->setPlaceholder('mxcMonthHeadingNextLabel', (!empty($param['mxcMonthHeadingNextLabel']) ? $param['mxcMonthHeadingNextLabel'] : _mxCalendar_cl_labelNext));
$modx->setPlaceholder('mxcMonthHeadingLabelMonth', $monthname);
$modx->setPlaceholder('mxcMonthHeadingLabelYear', date("Y", strtotime($newdate)));

//-- Get Heading Container for Theme
if(!empty($param['mxcTplMonthHeading'])){
        //--Get user modified theme over-ride
        $_mxcCalRow = $modx->getChunk($param['mxcTplMonthHeading']);
} else {
        //--Get the theme heading
        $_mxcCalRow = $this->_getTheme('month.heading',$this->config['mxCalendarTheme']);
}

    //-- Start heading weekday loop
    $headingsWeekDay = explode(',',_mxCalendar_cl_headinWeekDays);
    $ar_heading = ($startDayID==0 ? explode(',',_mxCalendar_cl_headinWeekDays) : array_merge( array_slice($headingsWeekDay, $startDayID, null, true),  array_slice($headingsWeekDay, 0, $startDayID, true) ));
    if($this->debug) print_r($ar_heading);
    $_mxcCalHeadings = '';
    foreach($ar_heading AS $k=>$h) {
        if(($k != 0 && $k !== 6 && $excludeWeekends) || !$excludeWeekends){
            $dayArr = array(
                'mxcMonthInnerDayID' => '',
                'mxcMonthInnerDayClass' => '',
                'mxcMonthInnerDayBasic' => trim($h)
            );
            //-- Parse Day Container Theme
            if(!empty($param['mxcTplMonthDayBase'])){
                //--Get user theme over-ride chunk
                $_mxcCalHeadings .= $modx->parseChunk($param['mxcTplMonthDayBase'], $dayArr, '[+', '+]');
            } else {
                //--Get the theme event
                $_mxcCalHeadings .= $this->parseTheme($themeDayBasic, $dayArr);
            } 
        }
    }

    //-- Add calendar heading row
    $rowArr = array(
        'mxcMonthInnerRowID' => (isset($param['mxcMonthInnerHeadingRowID']) ? $param['mxcMonthInnerHeadingRowID'] : ''),
        'mxcMonthInnerRowClass' => (isset($param['mxcMonthInnerHeadingRowClass']) ? $param['mxcMonthInnerHeadingRowClass'] : ''),
        'mxcMonthInnerRowDays' => $_mxcCalHeadings
    );
    if(!empty($param['mxcTplMonthRow'])){
        //--Get user theme over-ride chunk
        $_mxcCalRow .= $modx->parseChunk($param['mxcTplMonthRow'], $rowArr, '[+', '+]');
    } else {
        //--Get the theme event
        $_mxcCalRow .= $this->parseTheme($themeRow, $rowArr);
    }

    //-- Set leading empty cells counter
    $emptycells = 0;
    //--v0.0.2
    switch($startday){
        case 6:
        case 0:
            if($excludeWeekends)
            $counterStart = $startday;
            else
            $counterStart = 0;
            break;
        default:
            if($excludeWeekends)
                $counterStart = 1;
            else
                $counterStart = 0;
    }

    //-- Add leading empty cells
    $_mxcCalEmptyCells = '';
    for( $counter = $counterStart; $counter < $startday; $counter ++ ) {
        $dayArr = array(
            'mxcMonthInnerDayID' => '',
            'mxcMonthInnerDayClass' => '',
            'mxcMonthInnerDayBasic' => ''
        );
        //-- Parse Day Container Theme
        if(!empty($param['mxcTplMonthDayBase'])){
            //--Get user theme over-ride chunk
            $_mxcCalEmptyCells .= $modx->parseChunk($param['mxcTplMonthDay'], $dayArr, '[+', '+]');
        } else {
            //--Get the theme event
            $_mxcCalEmptyCells .= $this->parseTheme($themeDayBasic, $dayArr);
        } 
        $emptycells ++;
    }
    
    //-- Add the days
    $rowcounter = $emptycells;
    $numinrow = ($excludeWeekends) ? 5 : 7;
    
    //-- Force end of row when excluding weekends and first day is a Sat/Sun
    if($excludeWeekends & $startday > 5){
        //-- Store all the rows for the calendar
        $rowArr = array(
            'mxcMonthInnerRowID' => (isset($mxcMonthInnerRowID) ? $mxcMonthInnerRowID : ''),
            'mxcMonthInnerRowClass' => (isset($mxcMonthInnerRowClass) ? $mxcMonthInnerRowClass : ''),
            'mxcMonthInnerRowDays' => $_mxcCalEmptyCells
        );
        //-- Parse Day Container Theme
        if(!empty($param['mxcTplMonthRow'])){
            //--Get user theme over-ride chunk
            $_mxcCalRow .= $modx->parseChunk($param['mxcTplMonthRow'], $rowArr, '[+', '+]');
        } else {
            //--Get the theme event
            $_mxcCalRow .= $this->parseTheme($themeRow, $rowArr);
        }
        $_mxcCalEmptyCells = '';
        //echo $_mxcCalRow;
        
    }
    
    $thisDayEvents = $this->_getEventsSingleDay($newdate, null, ($_REQUEST['CatId'] ? $_REQUEST['CatId'] : (!empty($param['mxcDefaultCatId']) ? $param['mxcDefaultCatId'] : null) ) );
    if($this->debug){ echo '<h2>calendar.inc.php => $thisDayEvents</h2><pre><code></code></pre><br />'; print_r($thisDayEvents); }
    
    if($this->debug) echo '<h2>calendar.inc.php => $thisDayEvents</h2><p>Totoal  of '.count($thisDayEvents).' events returned</p>';
    
    //-- Set the default start first day of the month based on weekend view
    switch($startday){
        case 0:
            if($excludeWeekends)
            $dayStartWeekendExclusion = 2;
            else
            $dayStartWeekendExclusion = 1;
            break;
        case 6:
            if($excludeWeekends)
            $dayStartWeekendExclusion = 3;
            else
            $dayStartWeekendExclusion = 1;
            break;
        default:
            $dayStartWeekendExclusion = 1;
            break;
    }


    //-- Start the Daily Listings
    $_mxcDayItems = '';
    for( $counter = $dayStartWeekendExclusion; $counter <= $numdaysinmonth; $counter ++ ) {
        //-- Check to see if we exlcude weekends
        $thisDayofWeek = jddayofweek( cal_to_jd( CAL_GREGORIAN, date( "m" ),date($counter), date( "Y" ) ) , 0);
        if(($thisDayofWeek != 0 && $thisDayofWeek != 6 && $excludeWeekends) || !$excludeWeekends){
            $rowcounter ++;
            
            if($counter == (int)date("j") && $evMonth == (int)date("m") && $this->config['mxcCalendarActiveDayDisplay'])
                $classToday = $this->config['mxcCalendarActiveDayClass'];
            elseif($counter == (int)date("j") && $this->config['mxcCalendarActiveDayDisplay'])
                $classToday = $this->config['mxcCalendarActiveDayClass'];
            else
                $classToday=NULL;
                
            //-- Check if config/param for listing 'todays' events only
            $_todayOnlyCheck = ( isset($param['mxcMonthListTodayOnly']) && $param['mxcMonthListTodayOnly']) ? (($counter == (int)date("j")) ? true : false) : true;
            
            //-- List events on this date
            $events='';
            if(is_array($thisDayEvents) && $_todayOnlyCheck ){
                
                
                if(is_array($thisDayEvents[$counter])){
                    
                    if($this->debug){
                        echo '<h2>Event key found for day '.$counter.'</h2><p><pre><code>';
                        print_r($thisDayEvents[$counter]);
                        echo '</code></pre></p><hr size=1 /><br />';
                    }

                    $multipleEventCnt = 0;
                    
                    
                    foreach($thisDayEvents[$counter] as $calEvents){
                        //-- Fix future dates not in current month view
                        if(strftime('%Y', strtotime($calEvents['start'])) == strftime('%Y', strtotime($newdate)) ){
                        $calEvents['DurationTime'] = str_replace('-','',$calEvents['DurationTime']);
                        $durDay = ((int)$calEvents['DurationDays'] > 0) ? $calEvents['DurationDays'].'d' : false ;
                        $durTime = ((int)substr($calEvents['DurationTime'], 1, 2) > 0 || (int)substr($calEvents['DurationTime'], 0, 2) > 0) ? substr($calEvents['DurationTime'], 0, strpos($calEvents['DurationTime'], ":")).'h '.(substr($calEvents['DurationTime'], 3, -3) != '00' ? substr($calEvents['DurationTime'], 3, -3).'m' : '') : null ;
                        $durTime = ($durDay) ? ' '.$durTime : $durTime;
                        $dur = ((boolean)$this->config['dispduration']===false ) ? '' : "(<span class='durantion'>".$durDay.$durTime."</span>)";
                        $dur = (!empty($param['showDuration']) ? (((boolean)$param['showDuration']===false) ? '' : "(<span class='durantion'>".$durDay.$durTime."</span>)") : $dur );
                        $timeSpan = ((boolean)$this->config['dispeventtime']===false) ? '' : strftime(_mxCalendar_ev_time, strtotime('1969-01-01 '.$calEvents['starttime']))." - ".strftime(_mxCalendar_ev_time, strtotime('1969-01-01 '.$calEvents['endtime']));
                        $timeSpan = (!empty($param['showTimeSpan']) ? ((boolean)$param['showTimeSpan']===false ? '' : strftime(_mxCalendar_ev_time, strtotime('1969-01-01 '.$calEvents['starttime']))." - ".strftime(_mxCalendar_ev_time, strtotime('1969-01-01 '.$calEvents['endtime']))) : $timeSpan);
                        $toolTip = explode(' ',strip_tags($calEvents['description']));
                        $toolTip = array_slice($toolTip, 0, 75);
                        if(!$param['mxcEventTitleLink']){
                            $title = '<a id="mxc'.$counter.$multipleEventCnt.'"  title="'.$calEvents['title'] . ($this->config['disptooltip'] ? ': '.implode(' ',$toolTip) : '').'" class="tt mxModal" href="'.(!empty($calEvents['link']) ? (is_numeric((int)$calEvents['link']) ? $modx->makeUrl((int)$calEvents['link'], '', (is_numeric($calEvents['repeat']) ? '&r='.$calEvents['repeat'] : ''), 'full') : $calEvents['link']) : $modx->makeUrl((int)$ajaxPageId,'', '&details='.$calEvents['id'].(is_numeric($calEvents['repeat']) ? '&r='.$calEvents['repeat'] : ''), 'full')).'" rel="'.(!empty($calEvents['link']) ? $calEvents['linkrel'] : ($ajaxPageId != $modx->documentIdentifier ? 'moodalbox' : '')).'" target="'.(!empty($calEvents['link']) ? $calEvents['linktarget'] : $calEvents['linktarget']).'" style="color:inherit;display:block;position:relative;padding:3px;">'.$calEvents['title'].'</a>';
                        } else {
                            $mxcNodeWrap = (!isset($param['mxcEventTitleNode']) ? 'span' : $param['mxcEventTitleNode']);
                            $title = '<'.$mxcNodeWrap.' id="mxc'.$counter.$multipleEventCnt.'"  title="'.$calEvents['title'] . ($this->config['disptooltip'] ? ': '.implode(' ',$toolTip) : '').'" class="tt mxModal" >'.$calEvents['title'].'</'.$mxcNodeWrap.'>';
                        }
                        
                        //-- Set properties for theme
                        $EventArr = array(
                                'mxcMonthInnerEventID' => '',
                                'mxcMonthInnerEventClass' => '',
                                'mxcMonthInnerEventUID' => '',
                                'mxcMonthInnerEventDescription' => '',//$calEvents['description'],
                                'mxcMonthInnerEventTitleClass' => 'title',
                                'mxcMonthInnerEventTitle' => $title,
                                'mxcMonthInnerEventDuration' => $dur,
                                'mxcMonthInnerEventTimestamp' => $timeSpan,
                                //--r0.0.6
                                'mxcMonthInnerEventCategory' =>$calEvents['category'],
                                'mxcMonthInnerEventCategoryForegroundCss' => ($calEvents['cateogryCSS'][1] ? 'color:'.$calEvents['cateogryCSS'][1].';' : ''),
                                'mxcMonthInnerEventCategoryBackgroundCss' => ($calEvents['cateogryCSS'][2] ? 'background-color:'.$calEvents['cateogryCSS'][2].';display:block;' : ''),
                                'mxcMonthInnerEventCategoryInlineCss' => $calEvents['cateogryCSS'][3]
                            );
                        
                        //-- Parse Event Detail Template
                        if(!empty($param['mxcTplMonthEvent'])){
                            //--Get user modified theme over-ride
                            $events .= $modx->parseChunk($param['mxcTplMonthEvent'], $EventArr, '[+', '+]');
                        } else {
                            //--Get the theme event
                            $events .= $this->parseTheme($themeEvent, $EventArr);
                        }
                                
                    $multipleEventCnt++;
                    }
                    }//--End If check for current month of event
                } //-- end if event found on give day counter
                else {
                    // -- DO NOTHING
                }
            } 
            
            $dayArr = array(
                'mxcMonthInnerDayID' => (isset($mxcMonthInnerDayID) ? $mxcMonthInnerDayID : ''),
                'mxcMonthInnerDayClass' => trim($classToday.' '.(array_key_exists($counter, (array)$thisDayEvents) ? $param['mxcMonthHasEventClass'] : '' )), //(boolean)$param['mxcMonthListTodayOnly'] === true && $_todayOnlyCheck === false && 
                'mxcMonthInnerDayLabelClass' => (isset($mxcMonthInnerDayLabelClass) ? $mxcMonthInnerDayLabelClass : 'datestamp'),
                'mxcMonthInnerDayLabel' => $counter,
                'mxcMonthInnerEvents' => $events,
                'mxcMonthOuterEventClass' => (isset($mxcMonthOuterEventClass) ? $mxcMonthOuterEventClass : 'event'),
                'mxcMonthOuterEventID' => (isset($mxcMonthOuterEventID) ? $mxcMonthOuterEventID : '')
            );
            
            //-- Parse Day Container Theme
            if(!empty($param['mxcTplMonthDay'])){
                //--Get user theme over-ride chunk
                $_mxcDayItems .= $modx->parseChunk($param['mxcTplMonthDay'], $EventArr, '[+', '+]');
            } else {
                //--Get the theme event
                $_mxcDayItems .= $this->parseTheme($themeDay, $dayArr);
            }            
            
            //-- Parse Row Container Theme
            $rowArr = array(
                'mxcMonthInnerRowID' => (isset($mxcMonthInnerRowID) ? $mxcMonthInnerRowID : ''),
                'mxcMonthInnerRowClass' => (isset($mxcMonthInnerRowClass) ? $mxcMonthInnerRowClass : ''),
                'mxcMonthInnerRowDays' => (!empty($_mxcCalEmptyCells) ? $_mxcCalEmptyCells : '').$_mxcDayItems
            );
            

            if( $rowcounter % $numinrow == 0 ) {
                if(!empty($param['mxcTplMonthRow'])){
                    //--Get user theme over-ride chunk
                    $_mxcCalRow .= $modx->parseChunk($param['mxcTplMonthRow'], $rowArr, '[+', '+]');
                } else {
                    //--Get the theme event
                    $_mxcCalRow .= $this->parseTheme($themeRow, $rowArr);
                }
                $_mxcDayItems = ''; //-- Reset the day(s) holder
                if(!empty($_mxcCalEmptyCells)) $_mxcCalEmptyCells=''; //-- clear empty cell holder
                if( $counter < $numdaysinmonth ) {
                    $_mxcDayItems = ''; //-- Reset the day(s) holder
                    if(!empty($_mxcCalEmptyCells)) $_mxcCalEmptyCells=''; //-- clear emtpy cell holder
                }
                $rowcounter = 0;
            }
        }
    }
    //-- Add ending empty cells
    if( $rowcounter != $numinrow && $rowcounter > 0 ) {
        $_mxcCalEmptyCells='';
        for( $counter = 0; $counter < ($numinrow - $rowcounter); $counter ++ ) {
            //\\echo "\t\t<td>&nbsp;</td>\n";
            $dayArr = array(
                'mxcMonthInnerDayID' => '',
                'mxcMonthInnerDayClass' => '',
                'mxcMonthInnerDayBasic' => ''
            );
            //-- Parse Day Container Theme
            if(!empty($param['mxcTplMonthDayBase'])){
                //--Get user theme over-ride chunk
                $_mxcCalEmptyCells .= $modx->parseChunk($param['mxcTplMonthDay'], $dayArr, '[+', '+]');
            } else {
                //--Get the theme event
                $_mxcCalEmptyCells .= $this->parseTheme($themeDayBasic, $dayArr);
            } 
            $emptycells ++;
        }
        
        //-- Add ending empty cells
        $rowArr = array(
            'mxcMonthInnerRowID' => (isset($mxcMonthInnerRowID) ? $mxcMonthInnerRowID : ''),
            'mxcMonthInnerRowClass' => (isset($mxcMonthInnerRowClass) ? $mxcMonthInnerRowClass : ''),
            'mxcMonthInnerRowDays' => $_mxcDayItems.(!empty($_mxcCalEmptyCells) ? $_mxcCalEmptyCells : '')
        );
        
        if(!empty($param['mxcTplMonthRow'])){
            //--Get user theme over-ride chunk
            $_mxcCalRow .= $modx->parseChunk($param['mxcTplMonthRow'], $rowArr, '[+', '+]');
        } else {
            //--Get the theme event
            $_mxcCalRow .= $this->parseTheme($themeRow, $rowArr);
        }        
    }
    
    //-- Return all calendar row items
    //echo $_mxcCalRow;
    
$modx->setPlaceholder('mxcMonthInnerRows', $_mxcCalRow);
$modx->setPlaceholder('mxcMonthInnerContainerID', (!empty($param['mxcMonthInnerContainerID']) ? $param['mxcMonthInnerContainerID'] : 'calbody'));
$modx->setPlaceholder('mxcMonthInnerContainerClass',(!empty($param['mxcMonthInnerContainerClass']) ? $param['mxcMonthInnerContainerClass'] : ''));
$modx->setPlaceholder('mxcAjaxJS','<script type="text/javascript">var ajax_anchors = [];
		$A($$(\'a\')).each(function(el){
			// we use a regexp to check for links that 
			// have a rel attribute starting with "moodalbox"
			if(el.rel && el.href && el.rel.test(\'^moodalbox\', \'i\')) {
				el.onclick = MOOdalBox.click(el);
				ajax_anchors.push(el);
			}
		});</script>');
//-- Get Inside Container for Theme
if(!empty($param['mxcTplMonthInner'])){
        //--Get user modified theme over-ride
        $_mxcCalInner = $modx->getChunk($param['mxcTplMonthInner']);
} else {
        //--Get the theme heading
        $_mxcCalInner = $this->_getTheme('month.inner.container',$this->config['mxCalendarTheme']);
}

//-- Get Configuration Item for Category UI Filter display
$_mxcCalCategoryFilter = (boolean)$this->config['mxcGetCategoryListUIFilterActive'] == true ? $this->mxcGetCategoryListUIFilter($this->config['mxcGetCategoryListUIFilterType']) : '';
if(!is_null($param['mxcDefaultCatIdLock']))
    $_mxcCalCategoryFilter = ($param['mxcDefaultCatIdLock'] == 'false' ||(boolean)$param['mxcDefaultCatIdLock'] == false ? $this->mxcGetCategoryListUIFilter($this->config['mxcGetCategoryListUIFilterType']) : '' );


//-- Set the placeholder values for outermost container
$modx->setPlaceholder('mxcMonthContainerID', (!empty($param['mxcMonthContainerID']) ? $param['mxcMonthContainerID'] : 'bsCalendar'));
$modx->setPlaceholder('mxcMonthContianerClass', (!empty($param['mxcMonthContianerClass']) ? $param['mxcMonthContianerClass'] : ''));
$modx->setPlaceholder('mxcMonthCotianerHeading',(!empty($param['mxcMonthCotianerHeading']) ? $param['mxcMonthCotianerHeading'] : ''));
$modx->setPlaceholder('mxcMonthInsideContianer',$_mxcCalInner);
$modx->setPlaceholder('mxcCategoryFilters', $_mxcCalCategoryFilter);

//-- Get Inside Container for Theme
if(!empty($param['mxcTplMonthOuter'])){
        //--Get user modified theme over-ride
        $_mxcCal = $modx->getChunk($param['mxcTplMonthOuter']);
} else {
        //--Get the theme heading
        $_mxcCal = $this->_getTheme('month.container',$this->config['mxCalendarTheme']);
}

echo $_mxcCal;
  

?>
    

