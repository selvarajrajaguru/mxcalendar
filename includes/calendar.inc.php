<?php
/*-- added to setup params ---*/                        
    //-- show the duration (true, false)
    $showDuration = (isset($param['showDuration'])) ? $param['showDuration'] : false;
/*-- added to setup params ---*/                            
    //-- show the time stamp of the event
    $showTimeSpan = (isset($param['showTimeSpan'])) ? $param['showDuration'] : true;
/*-- added to setup params [Use ajax Page or current document ---*/                            
    $ajaxPageId = isset($param['ajaxPageId']) ? $param['ajaxPageId'] : $modx->documentIdentifier;
    
    
    
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
    }elseif($newMonth == 1 & $type == ''){
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
    // -- was testing with this
    /*if($monthOffset != 0){
        $thismonth = $newdatePieces[1];
        $thisyear = $newdatePieces[0];
        $jd = cal_to_jd( CAL_GREGORIAN, $newdatePieces[1],$newdatePieces[2], $newdatePieces[0] );
    }*/
    $jd = cal_to_jd( CAL_GREGORIAN, $newdatePieces[1],$newdatePieces[2], $newdatePieces[0] );
    #echo "TimeStamp 3: ".$newdate."<br />";
////////////////////////////////////\
    
    //-- full year for mysql and mssql friendly search
    $fulldate = date("Y-d-m");

/*-- add to setup params ---*/                            
    //-- Exclude Weekends
    $excludeWeekends =  (isset($param['excludeWeekends'])) ? $param['excludeWeekends'] : false;
    
    //-- find out the number of days in the month
    $numdaysinmonth = cal_days_in_month( CAL_GREGORIAN, $thismonth, $thisyear );

/*-- add to setup params ---*/                            
    //-- set the calendar weekday start range: 0=Sunday - 6=Saturday
    //-- (0 = Sunday, 1 = Monday, etc)
    $startDayID = (isset($param['startDayID'])) ? $param['startDayID'] : 0;

    //-- Do Not alter: Forces Monday - Friday on excluded weekend view
    if($excludeWeekends) $startDayID=0;
    
    //-- return the start day as an int (0 = Sunday, 1 = Monday, etc)
    $startday = jddayofweek( $jd , 0);
    if($startDayID > 0)
        $startday = $startday - $startDayID;
        if($startday < 0)
            $startday = 7 + $startday;

        
    $monthname = jdmonthname( $jd, 1 );
    
    if($m=$modx->insideManager()){
        //-- Make Manager Friendly URLs
        $preURL = '?a='.$_REQUEST['a'].'&amp;id='.$_REQUEST['id'].'&amp;dt='.$newdate.'&amp;offset='.($monthOffset-1).'&amp;type=pre';
        $nextURL = '?a='.$_REQUEST['a'].'&amp;id='.$_REQUEST['id'].'&amp;dt='.$newdate.'&amp;offset='.($monthOffset+1).'&amp;type=next';
    } else {
        //-- Make SEO Friendly URLs
        $preURL = $modx->makeUrl($modx->documentIdentifier,'','&dt='.$newdate.'&offset='.($monthOffset-1).'&type=pre','full');
        $nextURL = $modx->makeUrl($modx->documentIdentifier,'','&dt='.$newdate.'&offset='.($monthOffset+1).'&type=next','full');
    }
?>

<style>
.tool-tip {
    background-color:#fff;
    border:1px solid #4d4d4d;
	color: #4d4d4d;
	width: 139px;
	z-index: 13000;
}
 
.tool-title {
	font-weight: bold;
	font-size: 11px;
	margin: 0;
	color: #4d4d4d;
	padding: 8px 8px 4px;
	background: url(bubble.png) top left;
}
 
.tool-text {
	font-size: 11px;
	padding: 4px 8px 8px;
	background: url(bubble.png) bottom right;
}
</style>
<script src="[(site_url)]manager/media/script/mootools/mootools.js" type="text/javascript"></script>
<script src="[(site_url)]manager/media/script/mootools/moodx.js" type="text/javascript"></script>
<script>
//-- ToolTip (Duration,Time Span)
/*
window.addEvent('domready', function(){
    //-- Tips 3
    var Tips3 = new Tips($$('.mxModal'), {
            showDelay: 400,
            hideDelay: 400,
            fixed: true
    });
});
*/
</script>
<script type="text/javascript" src="[(site_url)]assets/modules/mxCalendar/scripts/moodalbox121/js/moodalbox.v1.2.full.js"></script> 
<link rel="stylesheet" href="[(site_url)]assets/modules/mxCalendar/scripts/moodalbox121/css/moodalbox.css" type="text/css" media="screen" />


<div id="bsCalendar">      
<table id="calbody">
    <tr>
        <td colspan="7">
            <table cellpadding="0" cellspacing="0" class="cal_month">
                <tr>
                    <td><span id="prevMonth"><a href="<?php echo $preURL; ?>">&lt;&lt; Previous</a></span></td>
                    <td><strong><?php echo $monthname; ?></strong></td>
                    <td><span id="nextMonth"><a href="<?php echo $nextURL; ?>">Next &gt;&gt;</a></span></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <?php
        //-- Start heading weekday loop
        $headingsWeekDay = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
        $curWD = $startDayID;
        for( $wdcnt = 1; $wdcnt <= 7; $wdcnt ++ ) {
            if($curWD+1 > 7) $curWD = 0;
            
            if(($curWD != 0 && $curWD != 6 && $excludeWeekends) || !$excludeWeekends)
                //-- use hard coded calendar headings
                //echo "<td><strong>".$headingsWeekDay[$curWD]."</strong></td>";
                //-- use Julian Day of week
                echo "<td><strong>".jddayofweek( cal_to_jd( CAL_GREGORIAN, date( "m" ),date( $curWD ), date( "Y" ) ) , 1)."</strong></td>";
            $curWD++;
        }
        ?>
    </tr>
    <tr>
<?php
    //-- Set leading empty cells counter
    $emptycells = 0;
    //--v0.0.1
    //$counterStart = ($excludeWeekends) ? 1 : 0;
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
    for( $counter = $counterStart; $counter < $startday; $counter ++ ) {
        echo "\t\t<td>&nbsp;</td>\n";
        $emptycells ++;
    }
   
    //-- Add the days
    $rowcounter = $emptycells;
    $numinrow = ($excludeWeekends) ? 5 : 7;
    
    //-- Force end of row when excluding weekends and first day is a Sat/Sun
    if($excludeWeekends & $startday > 5)
        echo "\t</tr>\n";
    
    //-- CALL TO DB FOR CURRENT MONTH EVENTS
    #$eventsSQL = 'SELECT * FROM eventsdb WHERE startdate >= '.$fulldate.' and active=1 ORDER BY startdate, enddate';
    #$thisDayEvents = $this->_getEventsSingleDay(date("$thisyear-$thismonth-1"),$thismonth);
    
    //echo "Date: ".$newdate."<br />";
    $thisDayEvents = $this->_getEventsSingleDay($newdate );
    if($this->debug){   
        print_r($thisDayEvents);
    }
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
    for( $counter = $dayStartWeekendExclusion; $counter <= $numdaysinmonth; $counter ++ ) {
        // Check to see if we exlcude weekends
        $thisDayofWeek = jddayofweek( cal_to_jd( CAL_GREGORIAN, date( "m" ),date($counter), date( "Y" ) ) , 0);
        if(($thisDayofWeek != 0 && $thisDayofWeek != 6 && $excludeWeekends) || !$excludeWeekends){
            $rowcounter ++;
            $classToday = ($counter == (int)date("j")) ? ' class="today"' : '' ;
            echo "\t\t<td".$classToday." width='12%'><div class='datestamp'>$counter</div>";
            
            //-- List events on this date
            if($thisDayEvents){
                $events = "<div class='event' style=''>";
                if(array_key_exists($counter, $thisDayEvents)){
                    $multipleEventCnt = 0;
                    foreach($thisDayEvents[$counter] as $calEvents){
                        $calEvents['DurationTime'] = str_replace('-','',$calEvents['DurationTime']);
                        $durDay = ((int)$calEvents['DurationDays'] > 0) ? $calEvents['DurationDays'].'d' : null ;
                        $durTime = ((int)substr($calEvents['DurationTime'], 1, 2) > 0 || (int)substr($calEvents['DurationTime'], 0, 2) > 0) ? substr($calEvents['DurationTime'], 0, strpos($calEvents['DurationTime'], ":")).'h '.substr($calEvents['DurationTime'], 3, -3).'m' : null ;
                        $durTime = ($durDay) ? ' '.$durTime : $durTime;
                        $dur = ((boolean)$param['showDuration']===false) ? '' : "(<span class='durantion'>".$durDay.$durTime."</span>)";

                        $timeSpan = ($param['showTimeSpan']=='false') ? '' : date(_mxCalendar_ev_time, strtotime('1969-01-01 '.$calEvents['starttime']))." - ".date(_mxCalendar_ev_time, strtotime('1969-01-01 '.$calEvents['endtime']));

                        $linkURL = (is_int((int)$calEvents['link'])) ? $modx->makeUrl((int)$calEvents['link'], '', '', 'full') : $modx->makeUrl($modx->documentIdentifier,"", $calEvents['link'], 'full') ;
                        
                        $title = '<a id="tipsModal'.$counter.$multipleEventCnt.'" title="'.$calEvents['title'].'" class="tt mxModal" href="'.$modx->makeUrl((int)$ajaxPageId,'', '&details='.$calEvents['id'], 'full').'" rel="'.($ajaxPageId != $modx->documentIdentifier ? 'moodalbox' : '').'" target="'.$calEvents['linktarget'].'" title="" >'.$calEvents['title'].'</a>';
                        
                        //-- Output the Event(s) Details
                        $events .= "    <div class='Tips1'>
                                        <div class='modalContent' id='tips".$counter.$multipleEventCnt."' class=''>
                                            ".$calEvents['description']."
                                        </div>
                                        <span class='title'>".$title." ".$dur."</span>";
                        $events .= "    <span class='description' id='ev".$counter.$multipleEventCnt."'>".$timeSpan."</span></div>";
                        
                        
                    $multipleEventCnt++;
                    }
                }
                $events .= "</div>";
                echo $events;
            } else
             echo "<div class='event'></div>";
            //-- end date event call
            echo "</td>\n";
            
            
            if( $rowcounter % $numinrow == 0 ) {
                echo "\t</tr>\n";
                if( $counter < $numdaysinmonth ) {
                    echo "\t<tr>\n";
                }
                $rowcounter = 0;
            }
        }
    }
    //-- Add ending empty cells
    if( $rowcounter != $numinrow && $rowcounter > 0 ) {
        for( $counter = 0; $counter < ($numinrow - $rowcounter); $counter ++ ) {
            echo "\t\t<td>&nbsp;</td>\n";
            $emptycells ++;
        }
    }
?>
    </tr>
</table>
</div>