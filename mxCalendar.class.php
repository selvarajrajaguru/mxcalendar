<?php
if(!class_exists("mxCal_APP_CLASS")){
	class mxCal_APP_CLASS {
		var $version = '0.0.7-rc4';
		var $user_id;
		var $params = array();
		var $config = array();
		var $default_limit;
		var $output;
		var $tables;
                var $tooltip;
		//-- Messages
		var $message;
		var $config_message;
                var $debug = false;
		var $userWebUserGroups = array();
                
		function __construct() {
                    //-- Form tooltips
                    $this->tooltip = array(
                                'title'=>_mxCalendar_gl_tt_title,
                                'link'=>_mxCalendar_gl_tt_link,
				'location' => _mxCalendar_gl_tt_location,
				'event_occurance_rep' => _mxCalendar_gl_tt_repeatCount
                                );
                    
		    //--Store short list of full table names
                    $this->tables=array(
				'events'=>'mxcalendar_events',
				'pastevents'=>'mxcalendar_pastevents',
                                'categories'=>'mxcalendar_categories',
                                'config'=>'mxcalendar_config'
				);
		    $this->params = $_REQUEST;
		    $this->_buildConfig();
		    
		    $this->default_limit = 100;
		    $this->name = __CLASS__;
                    
 		}
                
		// Get an array of tables
		function get_tables($like='') {
			global $modx;
			$tables = array();
			$db = $modx->db->config['dbase'];
			$pre = $modx->db->config['table_prefix'];
		       
			if($like) { $like = "LIKE '{$like}'"; }
		       
			$tables_sql = "SHOW TABLES FROM {$db} {$like};";
			$result = $modx->db->query($tables_sql);
		       
			while($row = $modx->db->getRow($result)) {
			 $tables[] = current($row);
			}
		
		 return $tables;
		}
               
		// Get an array of columns
		function get_columns($table='') {
                    global $modx;
                    $columns = array();
                    $db = $modx->db->config['dbase'];
                    $pre = $modx->db->config['table_prefix'];
                    $col_sql = "SHOW COLUMNS FROM ".$modx->getFullTableName($table).";";
                    $result = $modx->db->query($col_sql);
                    while($row = $modx->db->getRow($result, 'assoc')) {
                        $columns[] = array($row['Field'],$row['Type'],$row['Extra']);
                    }
                    return $columns;
		}
                
		//-- Upgrade Installation
		function _upgrade_mxCalendar(){
			global $modx;
			if(file_exists($modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$this->version.'.upgrade.mysql')){
			    $pre = $modx->db->config['table_prefix'];
			    $sql_installer = str_replace('#__', $pre,file_get_contents($modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$this->version.'.upgrade.mysql'));
			    //echo $sql_installer;
			    
			    if($sql_installer){
				foreach(explode(';',$sql_installer) AS $sql){
				    if(!empty($sql)){
					$result = $modx->db->query($sql);
				    }
				}
				$this->output .= '<div class="fm_message"><h2>'.$this->version.' Update completed</h2><form method="post" action=""><input type="submit" name="submit" value="Continue" /></form></div>';
				//Install Completed
				$fh = fopen( $modx->config['base_path'].'assets/modules/mxCalendar/config/config.xml', 'w+') or die("Unable to save configuration file. Please make sure write permission is granted on the folder (".$modx->config['base_path']."assets/modules/mxCalendar/config/)");
				$stringData = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<mxCalendar>'."\n".'<setup>Yes</setup>'."\n".'<date>'.DATE('l jS \of F Y h:i:s A').'</date><version>'.$this->version.'</version>'."\n".'</mxCalendar>';
				fwrite($fh, $stringData);
				fclose($fh);
			    } else {
				$modx->logEvent(0, 3, '<p><strong>Unable to upgrade mxCalendar tables via install file ('.$modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$this->version.'.upgrade.mysql)</strong></p>');
				$this->output .= '<div class="fm_error">Unable to complete the upgrade, please check the system log.</div>';
			    }
			    return $this->output;
			}else{
			    return '<strong>Error: </strong>Upgrade is not supported for this version, please back up your database and remove your "_mxCalendar" tables and "/assets/mxCalendar/config/config.xml" file and try again.<br /><br />Your Current Version is: '.$this->_getConfigVersion();
			}
		}
		
                //-- Installation
                function _install_mxCalendar(){
			global $modx;
                        $db_setup = 0;
                        $user = $modx->db->config['user'];
                        $db = $modx->db->config['dbase'];
			$pre = $modx->db->config['table_prefix'];
			$tables = $this->get_tables("{$pre}mxcalendar%");
			$installer = 'mxCalendar.mysql.install.inc';
			
			if(!count($tables)){
			    $sql_installer = str_replace('#__', $pre,file_get_contents($modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$installer));
			    if($sql_installer){
				foreach(explode(';',$sql_installer) AS $sql){
				    if($sql){
				    $result = $modx->db->query($sql);
				    //$this->output .= $sql;
				    if($result){
					$theTable = preg_match('/CREATE TABLE IF NOT EXISTS `(.*)`/', $sql, $matches);
					if(!empty($matches[1]))
					$this->output .= '<h2>'.$matches[1].' table created successfully</h2>';
					$db_setup++;
				    }
				    }
				}
			    } else {
				$modx->logEvent(0, 3, '<p><strong>Unable to locate mxCalendar sql install file ('.$modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$installer.')</strong></p>');
				$this->output .= 'Unable to load the installer file. ('.$modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$installer.')';
			    }
			}
                        if($db_setup) { $tables = $this->get_tables("{$pre}mxcalendar%"); }
                        if(count($tables) != 4){
                            //Error Maker
                            $modx->logEvent(0, 3, "<p><strong>"._mxCalendar_gl_installFailTable."</strong></p><p>Missing tables. Give CREATE TABLE rights to user {$user} or run the following SQL as a user with CREATE TABLE permissions</p>", $source='Module: mxCalendar');
                            $this->output.=_mxCalendar_gl_installFail."<br />\n";
                        } else {
                            //Install Completed
                            $fh = fopen( $modx->config['base_path'].'assets/modules/mxCalendar/config/config.xml', 'w') or die("Unable to save configuration file. Please make sure write permission is granted on the folder (".$modx->config['base_path']."assets/modules/mxCalendar/config/)");
                            $stringData = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<mxCalendar>'."\n".'<setup>Yes</setup>'."\n".'<date>'.DATE('l jS \of F Y h:i:s A').'</date><version>'.CAL_VERSION.'</version>'."\n".'</mxCalendar>';
                            fwrite($fh, $stringData);
                            fclose($fh);
                            $this->output.= '<strong><font color="green">'._mxCalendar_gl_installSucceess."</font></strong><br /><form method='POST' action=''><input type='submit' value='Start now ...' /></form>\n";
                        }
                    return $this->output;
                }

		//-- Build config params
		function _buildConfig(){
			global $modx;
			$tables = $this->get_tables($modx->db->config['table_prefix']."mxcalendar%");
			
			if(!empty($tables)){
				$results = $modx->db->query('SELECT * FROM '.$modx->db->config['table_prefix'].$this->tables['config']); 
				while($row = $modx->db->getRow($results, 'assoc')) {
				    $this->config[$row['param']]=$row['value'];
				}
			}
		}                
                
		//***********************//
                //**** Manager Views ****//
		//***********************//
                function ListEvents($params = false){
                    global $modx;
                    
                    //-- Perform delete prior to new listing @postback
                    if(isset($_POST['fmeid']) & $_POST['fmaction'] == 'delete') {
                        $modx->db->delete($modx->getFullTableName($this->tables['events']), 'id='.$_POST['fmeid']);
                        $this->output .= '<h3 style="color:#ff0000;">Removed event.</h3>';
                    }
                    //-- setup filters [fmfiltermatch fmfiltertime]
		        $filter = array();
			$filter_or=array();
			switch($_REQUEST['fmfiltertime']){
				case "0":
				default:
					//-- none (all events) as this is the default config
					$filter_or[] = ' E.startdate >=  \''.date("Y-m-d").'\' ';
					$filter_or[] = ' E.`lastrepeat` >= \''.date("Y-m-d").'\'';
					$filter_or[] = ' E.enddate >  \''.date("Y-m-d").'\'';
					break;
				case "1":
					//-- 
					$filter[] = ' E.startdate <=  \''.date("Y-m-d").'\' ';
					$filter[] = ' E.`lastrepeat` <= \''.date("Y-m-d").'\'';
					$filter[] = ' E.enddate <  \''.date("Y-m-d").'\'';
					break;
				case "all":
					//-- all events
					$filter[] = ' E.active=1';
					break;
			}
			$fmCat = $_REQUEST['CategoryId'];
			switch($fmCat){
				default:
					//-- Add any category filtering
					if(!empty($fmCat))
						$filter[] = ' E.category = '.(int)$fmCat;
					break;
			}
			if(!empty($_REQUEST['fmfiltermatch']))
				$filter[] = ' title REGEXP \''.$modx->db->escape($_REQUEST['fmfiltermatch']).'\' OR description REGEXP \''.$modx->db->escape($_REQUEST['fmfiltermatch']).'\'';
			
			

                    $result = $this->_getEventsPagination($_REQUEST['fmeventlistpagelimit'],(isset($_REQUEST['pg']) ? $_REQUEST['pg'] : 0 ), $filter, $filter_or);
		    $pagination = $result[1];
		    $result = $result[0];
		    
		    $mgr_PagerSizeLimits_dl = array(10,25,50,75,100,250,'ALL');
		    $html_mgr_PagerSizeDL='';
		    foreach($mgr_PagerSizeLimits_dl AS $limiter)
			$html_mgr_PagerSizeDL .= '<option value="'.$limiter.'" '.((string)$limiter == (string)$_REQUEST['fmeventlistpagelimit'] ? 'selected=selected' : '').'>'.$limiter.'</option>';
		    
		    $html_mgr_CateogryFilter = $this->mxcGetCategoryListUIFilter('select',true);
		    
		    $_mxCal_cont_col = explode(',',_mxCalendar_gl_columns);
                    if($modx->db->getRecordCount($result) > 0) {
                        $this->output .= "<table><tbody>
			<tr><td colspan='7' align='left'><form method='POST' action='' style='float:left;width:auto;'><input type='submit' name='submit' value='Add New Event' onclick=\"document.cookie='webfxtab_tabPanel=1;path=/;'\"></form><form method='POST' action='' style='float:left;width:auto;'>&nbsp;&nbsp;<input type='text' value='".$_REQUEST['fmfiltermatch']."' name='fmfiltermatch'/><select name='fmfiltertime'><option value='0' ".($_REQUEST['fmfiltertime']==0 ? 'selected="selected"' : '' ).">Upcoming Events</option><option value='1' ".($_REQUEST['fmfiltertime']==1 ? 'selected="selected"' : '' ).">Past Events</option><option value='all' ".($_REQUEST['fmfiltertime']=='all' ? 'selected="selected"' : '' ).">All Events</option></select>".$html_mgr_CateogryFilter."<input type='submit' name='submit' value='"._mxCalendar_btn_go."' onclick=\"document.cookie='webfxtab_tabPanel=0;path=/;'\">&nbsp;<select name='fmeventlistpagelimit'>".$html_mgr_PagerSizeDL."</select>"._mxCalendar_gl_pagesite."</form></td></tr>
			<tr><td colspan='7' align='center'>".$pagination." </td></tr>
				<tr>";
				foreach(explode(',', _mxCalendar_el_labels) AS $label)
				$this->output .= "<th>".trim($label)."</th>";
			$this->output .= "<th></th></tr>";
			$records = $modx->db->makeArray($result);
                            $evodbg = ' style="background-color:#ccc" ';
                            foreach( $records as $event ) {
                                $timeFormat = $this->config['mgrAddClockTwentryFourHour'] == true ? 'Y-m-d G:i:s' : _mxCalendar_el_timeformat;
				$evodbg = ($evodbg) ? '' : ' style="background-color:#ccc" ';
                                $evLastOccurance = explode(',',$event['repeat']);
				
				$this->output .='
                                <tr'.$evodbg.'>
                                    <td>'.$event['eid'].'</td>
                                    <td>'.$event['title'].'</td>
                                    <td>'.$event['category'].'</td>
                                    <td>'.date($timeFormat, strtotime($event['start'])).'</td>
                                    <td>'.date($timeFormat, strtotime($event['end'])).'</td>
				    <td>'.(!empty($event['repeat']) ? date(_mxCalendar_el_timeformat_date, strtotime($evLastOccurance[count($evLastOccurance)-1])) : '').'</td>
                                    <td><form method="post" action="" onSubmit=""><input type="hidden" name="fmeid" value="'.$event['eid'].'" ><input type="submit" name="fmaction" value="'._mxCalendar_gl_btnEdit.'" onclick="document.cookie=\'webfxtab_tabPanel=1;path=/;\'"><input type="submit" name="fmaction" value="'._mxCalendar_gl_btnDelete.'" onClick="document.cookie=\'webfxtab_tabPanel=0;path=/;\'; return confirm(\''._mxCalendar_gl_btnConfirm.'\')"></form></td>
                                </tr>
                                ';
                            }
                        $this->output .= "<tr><td colspan='7' align='center'>".$pagination."</td></tr></tbody></table>";
                    } else {
                        $this->output .="<form method='POST' action='' style='float:left;width:auto;'><input type='submit' name='submit' value='Add New Event' onclick=\"document.cookie='webfxtab_tabPanel=1;path=/;'\"></form><form method='POST' action='' style='float:left;width:auto;'>&nbsp;&nbsp;<input type='text' value='".$_REQUEST['fmfiltermatch']."' name='fmfiltermatch'/><select name='fmfiltertime'><option value='0' ".($_REQUEST['fmfiltertime']==0 ? 'selected="selected"' : '' ).">Upcoming Events</option><option value='1' ".($_REQUEST['fmfiltertime']==1 ? 'selected="selected"' : '' ).">Past Events</option><option value='all' ".($_REQUEST['fmfiltertime']=='all' ? 'selected="selected"' : '' ).">All Events</option></select><input type='submit' name='submit' value='Go' onclick=\"document.cookie='webfxtab_tabPanel=0;path=/;'\"></form>".'<div class="clear"></div><h2>'._mxCalendar_gl_noevents.'</h2>';
                        $this->output .='<h3>'._mxCalendar_gl_quicklist.'</h3>';
                        $result = $this->_getNEvents();
                    }
                    return $this->output;
                }
                
		//-- Manager::Add New
		function _mgrAddEdit(){
		    $this->message = '';
                    if($_POST['fmaction'] == _mxCalendar_btn_addEvent || $_POST['fmaction'] == _mxCalendar_btn_updateEvent ){
                        $saved=$this->_saveEvent($_POST['fmaction']);
                        if($saved)
                            $this->message .= $this->_makeMessageBox(str_replace("|*rec*|", $saved, _mxCalendar_ae_success)); //$saved
                        else
                            $this->message .= $this->_makeMessageBox(str_replace("|*rec*|", $saved, _mxCalendar_ae_fail),1);
                        
                    }
		}
		
                //-- Manager::Add New Event Form/Actions
                function AddEvent($params=false){
                    global $modx;
                    
		    $this->output = $this->message;
		    $this->message='';

                    //-- Form action and label properties
                    $fmAction = (!isset($_REQUEST['fmeid'])) ? 'save' : 'update';
                    $fmActionLabel = (!isset($_REQUEST['fmeid'])) ? _mxCalendar_btn_save : _mxCalendar_btn_update;
                    
                    if(!empty($_REQUEST['fmeid'])){
                        //-- Get record to edit
                        $result = $modx->db->select('id,title,description,category,restrictedwebusergroup,link,linkrel,linktarget,location,displayGoogleMap,start,startdate,starttime,end,enddate,endtime,event_occurance,event_occurance_rep,_occurance_properties,lastrepeat', $modx->getFullTableName($this->tables['events']),'id = '.$_REQUEST['fmeid'] );
                        if( $modx->db->getRecordCount( $result ) ) {
                            $output .= '<ul>';
                            $editArr = $modx->db->getRow( $result );
                        }
                    } else { $editArr = array(); }
		    $this->output .= '<h1>'.(isset($_REQUEST['fmeid']) ? _mxCalendar_ae_headingEdit.' '.$editArr['title'] : _mxCalendar_ae_headingAdd).'</h1>';
                    //-- Get language file labels
		    $fm_label = explode(',', _mxCalendar_ae_labels);
                    $fm_columns = $this->get_columns($this->tables['events']);
                    $this->output .= '<form id="fm_bsApp" name="cal_form" method="post" action="">'."\n";
                    $x=0;
		    foreach($fm_columns as $key=>$val){
                        //-- List of excluded table columns [DO NOT EDIT]
                        $excluded = array('id','active','start','end', 'repeat', 'event_occurance', '_occurance_wkly', 'event_occurance_rep', 'lastrepeat', '_occurance_properties','lastrepeat');
                        //-- Make sure it's not an excluded column
                        if(!in_array($val[0], $excluded)){
                            $tooltip = ($this->tooltip[$val[0]]) ? '<img  title="'.$this->tooltip[$val[0]].'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />' : '';
                            SWITCH ($val[1]){
                                case 'text':
                                    if($val[0] == 'description'){
                                    $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><textarea id="fm'.$val[0].'" name="fm'.$val[0].'">'.$editArr[$val[0]].'</textarea>'.$this->makeRTE($val[0]).$tooltip.'</div></div>'."\n";
				    
				    } else {
					$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="text" name="fm'.$val[0].'" value="'.$editArr[$val[0]].'" />'.$tooltip.'</div></div>'."\n";
				    }
                                    break;
                                case 'date':
                                    if($val[0] == 'startdate'){
                                      $editSD = ($editArr['start'] != '0000-00-00 00:00:00' && !empty($editArr['start']) ? date('Y-m-d', strtotime($editArr['start'])) : date('Y-m-d')); //$editArr['start'];
				      $editSDF = $editArr['start'];
                                    }
                                    elseif($val[0]=='enddate'){
                                      $editSD = ($editArr['end'] != '0000-00-00 00:00:00' && !empty($editArr['end']) ? date('Y-m-d', strtotime($editArr['end'])) : date('Y-m-d')); //$editArr['end'];
				      $editSDF = $editArr['end'];
                                    }
                                    else{
                                      $editSD = null;
                                    }
                                    
				    $advancedDateEntry=$this->config['mxcAdvancedDateEntry'];
				    if($advancedDateEntry){
					$this->output .= "\t<div class=\"fm_row\"><label>".$fm_label[$x]."</label><div class='fm_entry'><input type=\"text\" value=\"".$editSD."\" name=\"fm".$val[0]."\">".$tooltip."</div></div>";
				    } else {
					$this->output .= "\t".$this->_makeDateSelector($val[0], $fm_label[$x], $tooltip, $editSD)."\n";
				    }
				    
				    //- Add the dropdown list for time selector
				    $dl_output='';
				    $dlx = 1;
				    
				    
				    $counter_clock = ((boolean)$this->config['mgrAddClockTwentryFourHour'] == true) ? '' : array('am','pm') ;
				    $counter_limit = ((boolean)$this->config['mgrAddClockTwentryFourHour'] == true) ? 24 : 12 ;
				    if((boolean)$this->config['mgrAddClockTwentryFourHour'] == true){
					$myHour = date('G', strtotime($editSDF));
					$myMinutes = date('i', strtotime($editSDF));
					
				    } else {
					$myHour = date('g', strtotime($editSDF));
					$myMinutes = date('i', strtotime($editSDF));
					$myAPM = date('a', strtotime($editSDF));
				    }
				    
				    
				    while($dlx<=$counter_limit){
					$dl_output .= "<option value='".sprintf('%2d',$dlx)."' ".$isselected." ".($myHour == $dlx ? 'selected=selected' : '').">".sprintf("%2d",$dlx)."</option>";
					$dlx++;
				    }
				    
				    //-- Add the AM/PM selector if not 24 hour clock
				    $amPM_sel = '';
				    if((boolean)$this->config['mgrAddClockTwentryFourHour'] !== true){
					foreach($counter_clock AS $cl_label)
						$amPM_sel .= '<option value="'.$cl_label.'" '.($myAPM == $cl_label ? 'selected=selected' : '').'>'.$cl_label.'</option>';
					$amPM_sel = '<select name="'.$val[0].'_apm">'.$amPM_sel.'</select>'; 
				    } else { $amPM_sel = ''; }
				    
				    if(!$advancedDateEntry){
					$this->output .= "\t<div class=\"fm_row\"><label>&nbsp;</label><div class='fm_entry'>Time: <select name='".$val[0]."_htime'>".$dl_output."</select>";
                                   
					//-- Make the minutes dropdown list increment every 5 min
					$dl_output='';
					$dlxm = 0;
					while(($dlxm*5)<60){
					    $dl_output .= "<option value='".sprintf('%02d',($dlxm * 5))."' ".($myMinutes == sprintf('%02d',($dlxm * 5)) ? 'selected="selected"' : '').">".sprintf('%02d',($dlxm * 5))."</option>";
					    $dlxm++;
					}
					$this->output .= "\t<select name='".$val[0]."_mtime'>".$dl_output."</select>".$amPM_sel."</div></div>";
					$this->output .= "\n</div>\n\n";
				    }
				    
				    break;
                                case 'time':
                                    //-- We'll use the date picker field and extract the time
                                    break;
                                default:
                                    if($val[0] == 'category'){
                                      foreach($this->getCategories() as $cats){
                                        foreach($cats as $catsKey=>$catsVal){
                                            $selected = ($editArr[$val[0]] == $catsKey) ? 'selected=selected' : '';
					    $selected = ((empty($editArr[$val[0]]) && $catsVal[1] == 1) ? 'selected=selected' : $selected );
                                            $thisSDL .= '<option value="'.$catsKey.'" '.$selected.'>'.$catsVal[0].'</option>';
                                        }
                                      }
                                      $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><select name="fm'.$val[0].'">'.$thisSDL.'</select>'.$tooltip.'</div></div>'."\n";
				    } elseif($val[0] == 'displayGoogleMap'){
					$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="checkbox" id="fm'.$val[0].'" name="fm'.$val[0].'" value="1" '.($editArr[$val[0]] ? 'checked="checked"' : "").' />'.$tooltip.'</div></div>'."\n";
				    }  elseif($val[0] == 'restrictedwebusergroup'){
					$thisWUGDL = '<option value="" '.( empty($editArr[$val[0]]) ? 'selected=selected' : '' ).'>'._mxCalendar_con_PublicView.'</option>';
					foreach($this->getWebGroups() AS $group){
						$selected = (in_array($group['id'], explode(',',$editArr[$val[0]])) ) ? 'selected=selected' : '';
						$thisWUGDL .= '<option value="'.$group['id'].'" '.$selected.'>'.$group['name'].'</option>';
					}
					$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><select name="fm'.$val[0].'[]" multiple="multiple">'.$thisWUGDL.'</select>'.$tooltip.'</div></div>'."\n";
				    } else {
                                      $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="text" name="fm'.$val[0].'" value="'.$editArr[$val[0]].'" />'.$tooltip.'</div></div>'."\n";
                                    }
                                    break;
                            }
			$x++;
                        }
			
                    }
                    
			//-- Reoccurances fmevent_occurance, fm_occurance_wkly[], fmevent_occurance_rep
			$this->output .= "\t\t".'
    <fieldset id="mxcalendar-repeat" style="border:1px solid #ccc;">
        <legend>'.trim($fm_label[(count($fm_label)-5)]).':</legend>
        <div class="fm_row" ><label>'.trim($fm_label[(count($fm_label)-4)]).':</label>
            <select name="fmevent_occurance" onChange="if(this.value == \'w\'){ $(\'fm_occurance_wkly\').setStyle(\'display\',\'block\') } else {$(\'fm_occurance_wkly\').setStyle(\'display\',\'none\')}">
                <option value="0"></option>
                <option value="d" '.($editArr['event_occurance'] == 'd' ? 'selected="selected"': '' ).'>Daily</option>
                <option value="w" '.($editArr['event_occurance'] == 'w' ? 'selected="selected"': '' ).'>Weekly</option>
                <option value="m" '.($editArr['event_occurance'] == 'm' ? 'selected="selected"': '' ).'>Monthly</option>
                <option value="y" '.($editArr['event_occurance'] == 'y' ? 'selected="selected"': '' ).'>Yearly</option>
            </select>
        </div>
        
        <div id="fm_occurance_wkly" class="fm_row" style="'.($editArr['event_occurance'] != 'w' ? 'display:none;' : '' ).'"><label>'.trim($fm_label[(count($fm_label)-3)]).':</label>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="0" '.(in_array(0,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">S</span>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="1"  '.(in_array(1,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">M</span>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="2"  '.(in_array(2,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">T</span>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="3"  '.(in_array(3,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">W</span>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="4"  '.(in_array(4,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">T</span>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="5"  '.(in_array(5,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">F</span>
            <input class="checkbox" type="checkbox" name="fmevent_occurance_on[]" value="6"  '.(in_array(6,explode(',', $editArr['_occurance_properties'])) ? 'checked=checked' : '').'/><span class="fm_form_rLabel">S</span>
        </div>
        
        <div class="fm_row"><label>'.trim($fm_label[(count($fm_label)-2)]).':</label>
            <select name="fmevent_occurance_rep">';
                
                    $x=1;
                    while($x <= 30){
                        $this->output.="\t\t"."<option value='$x' ".($editArr['event_occurance_rep'] == $x ? 'selected="selected"': '' ).">$x</option>";
                        $x++;
                    }
                $this->output.="\t".'
            </select>';
	$this->output .= "\t".$this->_makeDateSelector('event_occur_until', $fm_label[(count($fm_label)-1)], $tooltip, ($editArr['lastrepeat'] != '0000-00-00 00:00:00' && !empty($editArr['lastrepeat']) ? date('Y-m-d', strtotime($editArr['lastrepeat'])) : '')).'<img  title="'.$this->tooltip['event_occurance_rep'].'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />'."\n";
	$this->output.='</fieldset>'."\n";
		    
		    $fmeid = ($_REQUEST['fmeid']) ? '<input type="hidden" id="fmeid" name="fmeid" value="'.$_REQUEST['fmeid'].'">' : '';
                    $this->output .= "\t".'<div class="fm_row"><div class="fm_actions">
                                        <input type=\'submit\' name=\'fmaction\' value=\'Cancel\' onclick="document.cookie=\'webfxtab_tabPanel=0;path=/;\'">
					
                                        <input type="submit" name="fmaction" value="'.(!empty($_REQUEST['fmeid']) ? _mxCalendar_btn_updateEvent : _mxCalendar_btn_addEvent).'" />
                                        '.$fmeid.'
                                      </div></div>'."\n";
                    $this->output .= '</form>'."\n";
                    $this->output .= $this->makeRTE('fmdescription');
                   
                    return $this->output;
                }

		//-- Manage categories
                function CategoryMgr($params = false){
                    global $modx;
                    $this->output='';
		    
                    //-- Perform delete prior to new listing @postback
                    if(isset($_POST['fmcid']) && $_POST['fmaction'] == 'delete') {
			//-- get the default category id
			$defaultCat = $modx->db->select( 'id', $modx->getFullTableName($this->tables['categories']), 'isdefault=1', '', '1');
			echo 'Returned default category count of <storng>'.$modx->db->getRecordCount( $defaultCat ).'</strong>';
			if($modx->db->getRecordCount( $defaultCat ) >= 1){
				$defaultCat = $modx->db->getRow( $defaultCat, 'num' );
				$defaultCat = $defaultCat[0];
			} else {
				//-- make the next active item default if none already set
				$modx->db->update('isdefault=1',$modx->getFullTableName($this->tables['categories']),'active=1','','1');
				//-- now go get the new default category id
				$defaultCat = $modx->db->select( 'id', $modx->getFullTableName($this->tables['categories']), 'isdefault=1', '', '1');
				if($modx->db->getRecordCount( $defaultCat ) >= 1){
					$defaultCat = $modx->db->getRow( $defaultCat, 'num' );
					$defaultCat = $defaultCat[0];
				} else {
					$defaultCat = 0;
				}
			}

			if($defaultCat == $_POST['fmcid'])
				$thisError .= '&bull;&nbsp;'._mxCalendar_ct_ErrorIsdefault.'<br />';
			else {
				//-- change current events to the default category before deleting category
				$modx->db->update('category='.$defaultCat,$modx->getFullTableName($this->tables['events']),'category='.$_POST['fmcid']);
			
				//-- remove the category is not currently set as default
				$modx->db->delete($modx->getFullTableName($this->tables['categories']), 'id='.$_POST['fmcid']);
				$fmMessage = _mxCalendar_ct_deleted;
			}
                    }
		    
		    
		    //-- Perform Default Update prior to new lisint @postback
		    if(isset($_POST['isdefault_x']) && !empty($_POST['fmcid'])){
			//-- change current default category to false
			$modx->db->update('isdefault=0',$modx->getFullTableName($this->tables['categories']),'isdefault=1','','1');
			//-- set the new default category
			$modx->db->update('isdefault=1',$modx->getFullTableName($this->tables['categories']),'id='.$_POST['fmcid'],'','1');
		    }

                    //-- Perform Publish and Un-Publish prior to new listing @postback
                    if(isset($_POST['fmcid']) & ($_POST['fmaction'] == _mxCalendar_gl_btnUnPublish || $_POST['fmaction'] == _mxCalendar_gl_btnPublish)) {
                        //$modx->db->delete($modx->getFullTableName($this->tables['categories']), 'id='.$_POST['fmcid']);
                        $modx->db->update('active='.($_POST['fmPub'] ? 0 : 1),$modx->getFullTableName($this->tables['categories']),'id='.$_POST['fmcid']);
			$fmMessage = ($_POST['fmaction'] == _mxCalendar_gl_btnUnPublish ? _mxCalendar_ct_unpublish : _mxCalendar_ct_publish);
                    }
		    
                    //-- Perform Disable and Enable prior to new listing @postback
                    if(isset($_POST['fmcid']) & ($_POST['fmaction'] == _mxCalendar_gl_btnDisable || $_POST['fmaction'] == _mxCalendar_gl_btnEnable)) {
                        //$modx->db->delete($modx->getFullTableName($this->tables['categories']), 'id='.$_POST['fmcid']);
                        $modx->db->update('disable='.($_POST['fmDis'] ? 0 : 1),$modx->getFullTableName($this->tables['categories']),'id='.$_POST['fmcid']);
			$fmMessage = ($_POST['fmaction'] == _mxCalendar_gl_btnDisable ? _mxCalendar_ct_disable : _mxCalendar_ct_enable);
                    }
                    
		    //-- Perform add and update prior to new listing @postback
			$fields = array(
				'name' => $modx->db->escape($_POST['fmname']),
				'foregroundcss' => $modx->db->escape($_POST['fmforegroundcss']),
				'backgroundcss' => $modx->db->escape($_POST['fmbackgroundcss']),
				'inlinecss' => $modx->db->escape($_POST['fminlinecss']) 
			);
		    if($_POST['fmaction'] == 'update' & !empty($_POST['fmupdate'])){
			$modx->db->update($fields,$modx->getFullTableName($this->tables['categories']),'id='.$_POST['fmupdate']);
		    }
		    
		    $catForm = '
		    <div class="fm_message" style="%19$s">%20$s</div>
		    <h2>%10$s</h2>
		    <div class="fm_error" style="%18$s">%13$s</div>
		    <form id="fm_bsApp" action="" method="post">
			<fieldset>
			<div class="fm_row"><label class="%14$s">%1$s</label><div class="fm_entry"><input type="text" name="fmname" value="%2$s"></div></div>
			<div class="fm_row"><label class="%15$s">%3$s</label><div class="fm_entry"><input type="text" name="fmforegroundcss" value="%4$s"></div></div>
			<div class="fm_row"><label class="%16$s">%5$s</label><div class="fm_entry"><input type="text" name="fmbackgroundcss" value="%6$s"></div></div>
			<div class="fm_row"><label class="%17$s">%7$s</label><div class="fm_entry"><input type="text" name="fminlinecss" value="%8$s"></div></div>
			<div class="fm_row"><label></label><div class="fm_entry"><input type="submit" name="fmsubmit" value="%9$s"></div></div>
			<input type="hidden" name="fmaction" value="%11$s">
			<input type="hidden" name="fmupdate" value="%12$s">
			</fieldset>
		    </form>
		    ';
		    
		    $rid=false;
		    if($_POST['fmaction'] == 'addnewCat' || $_POST['fmaction'] == 'updateCat'){
			//-- Validate the form first
			$isValid = true;
			$thisError = '';
			$patternRGB = '/^(rgb\()+([0-9]{1,3}[,]{1}[0-9]{1,3}[,]{1}[0-9]{1,3})+(\))+$/';
			$patternHex = '/^([#]{1})([a-zA-Z0-9]{6})+$/';
			$fmErrClass = 'fm_error';
			if(!preg_match($patternRGB,$fields['foregroundcss']) & !preg_match($patternHex,$fields['foregroundcss']) & !empty($fields['foregroundcss'])){
			   $isValid=false;
			   $thisError .= '&bull;&nbsp;'._mxCalendar_ct_ErrorForeground.'<br />';
			   $fmErr_15 = true;
			}
			if(!preg_match($patternRGB,$fields['backgroundcss']) & !preg_match($patternHex,$fields['backgroundcss']) & !empty($fields['backgroundcss'])){
			   $isValid=false;
			   $thisError .= '&bull;&nbsp;'._mxCalendar_ct_ErrorBackground.'<br />';
			   $fmErr_16 = true;
			}
			
			if($isValid){
				if($_POST['fmaction'] == 'addnewCat'){
				$modx->db->insert($fields,$modx->getFullTableName($this->tables['categories']));
				$rid = $modx->db->getInsertId();
				}
				if($_POST['fmaction'] == 'updateCat'){
				$modx->db->update($fields,$modx->getFullTableName($this->tables['categories']),'id='.$_POST['fmcid']);
				$rid = $_POST['fmcid'];
				}
			}
			//-- Build the form with replacement values
			$this->output .=sprintf($catForm, _mxCalendar_ct_labelName,$fields['name'],_mxCalendar_ct_labelForeground,$fields['foregroundcss'],_mxCalendar_ct_labelBackground,$fields['backgroundcss'],_mxCalendar_ct_labelClass,$fields['inlinecss'],_mxCalendar_btn_save,_mxCalendar_ct_labelHeadingAdd.' ','addnewCat','',$thisError, (isset($fmErr_14) ? $fmErrClass : ''), (isset($fmErr_15) ? $fmErrClass : ''), (isset($fmErr_16) ? $fmErrClass : ''), (isset($fmErr_17) ? $fmErrClass : ''), ($isValid ? 'display:none;' : ''), ($isValid ? '' : 'display:none;'), '<h3>'._mxCalendar_gl_MsgItemSaved.' '.$fields['name'].'</h3>' );
		    } elseif($_POST['fmaction'] == _mxCalendar_gl_btnEdit && !empty($_POST['fmcid'])) {
			$sql_listCats = 'SELECT *
				    FROM '.$modx->getFullTableName($this->tables['categories']).' as C
				WHERE id='.$_POST['fmcid'];
			$result = $modx->db->query($sql_listCats);
			$item = $modx->db->getRow( $result, 'both' );
			//-- Build the form with replacement values
			$this->output .=sprintf($catForm, _mxCalendar_ct_labelName.'<input type="hidden" name="fmcid" value="'.$_POST['fmcid'].'">',$item['name'],_mxCalendar_ct_labelForeground,$item['foregroundcss'],_mxCalendar_ct_labelBackground,$item['backgroundcss'],_mxCalendar_ct_labelClass,$item['inlinecss'],_mxCalendar_btn_save,_mxCalendar_ct_labelHeadingEdit.'<strong>'.$item['name'].'</strong>','updateCat','',$thisError, '', (isset($fmErr_15) ? $fmErrClass : ''), (isset($fmErr_16) ? $fmErrClass : ''), (isset($fmErr_17) ? $fmErrClass : ''), 'display:none;', 'display:none;', '' );
		    } else {
			//-- Display add category form
			$this->output .=sprintf($catForm, _mxCalendar_ct_labelName,'',_mxCalendar_ct_labelForeground,'#000000',_mxCalendar_ct_labelBackground,'#FFFFFF',_mxCalendar_ct_labelClass,'',_mxCalendar_btn_save,_mxCalendar_ct_labelHeadingAdd,'addnewCat','',(!empty($thisError) ? $thisError : ''),'','','','',(!empty($thisError) ? '' : 'display:none;'),(isset($fmMessage) ? '' : 'display:none;'),(isset($fmMessage) ? $fmMessage : '') );
		    }
		    
                    //-- Get the current active categories to list
		    $sql_listCats = 'SELECT *
				FROM '.$modx->getFullTableName($this->tables['categories']).' as C
                            
                            ORDER BY name ASC';
                    $result = $modx->db->query($sql_listCats);
		    
		    $_mxCal_cont_col = explode(',',_mxCalendar_ct_columns);
                    if($modx->db->getRecordCount($result) > 0) {
                        $this->output .= "<table><tbody>			
				<tr>";
				foreach($_mxCal_cont_col AS $label)
				$this->output .= "<th>".trim($label)."</th>";
			$this->output .= "<th></th></tr>";
			$records = $modx->db->makeArray($result);
                            $evodbg = ' style="background-color:#ccc" ';
                            foreach( $records as $row ) {
                                $evodbg = ($evodbg) ? '' : ' style="background-color:#ccc" ';
				$evodbg = ($row['id'] == $rid ? ' style="background-color:#00CC66;" ' : $evodbg);
				$this->output .='
                                <tr'.$evodbg.'>
                                    <td>'.$row['id'].'</td>
                                    <td bgcolor="'.$row['backgroundcss'].'" ><span style="color:'.$row['foregroundcss'].';background-color:'.$row['backgroundcss'].';" class="'.$row['inlinecss'].'">'.$row['name'].'</span></td>
				    <td align="center"><form method="post" action="" onSubmit=""><input type="hidden" name="fmcid" value="'.$row['id'].'" >'.($row['isdefault'] == 0 ? '<input type="image" src="media/style/'.$modx->config['manager_theme'].'/images/icons/cancel.png" name="isdefault">' : '<img src="media/style/'.$modx->config['manager_theme'].'/images/icons/save.png" alt=""/>' ).'</form></td>
                                    <td>'.$row['foregroundcss'].'</td>
				    <td>'.$row['backgroundcss'].'</td>
				    <td>'.$row['inlinecss'].'</td>
                                    <td><form method="post" action="" onSubmit="document.cookie=\'webfxtab_tabPanel=2;path=/;\'">
					<input type="hidden" name="fmcid" value="'.$row['id'].'" >
					<input type="hidden" name="fmPub" value="'.$row['active'].'">
					<input type="hidden" name="fmDis" value="'.$row['disable'].'">
					<input type="submit" name="fmaction" value="'._mxCalendar_gl_btnEdit.'" onclick="">
					<input type="submit" name="fmaction" value="'._mxCalendar_gl_btnDelete.'" onClick=" return confirm(\''._mxCalendar_gl_btnConfirm.'\')">
					<input type="submit" name="fmaction" value="'.($row['active'] ? _mxCalendar_gl_btnUnPublish : _mxCalendar_gl_btnPublish).'" onClick="">
					<input type="submit" name="fmaction" value="'.(!$row['disable'] ? _mxCalendar_gl_btnDisable : _mxCalendar_gl_btnEnable).'" onClick="">
				    </form></td>
                                </tr>
                                ';
                            }
                        $this->output .= "</tbody></table>";
                    } 
                    return $this->output;
                }
                
                
		//-- Manager::Configuration Update
		function update_Configuration(){
			global $modx;
			//-- Save new configuration settings on update
			if($this->params['action'] == 'updateConfig'){
				foreach($_POST as $k=>$v){
					$fields=array('value'=>$v);
				 if(is_int($k)) $modx->db->update( $fields, $modx->db->config['table_prefix'].$this->tables['config'], "id=".$k);
				}
				$this->config_message = $this->_makeMessageBox(_mxcalendar_con_update);
			}
		}
		
		//-- Manager::Configuration Tab
		function Configuration(){
			global $modx;
			
			//-- Get all configuration items
			$results = $modx->db->query('SELECT * FROM '.$modx->db->config['table_prefix'].$this->tables['config']); //$this->get_columns($this->tables['config']);
			$this->output = ''.$this->config_message;
			$this->output .= '<h2>'._mxcalendar_con_title.'</h2>';
			$this->output .= '<form id="fm_bsApp" name="config_form" method="post" action="">'."\n";
			$myConfig = array();
			while($row = $modx->db->getRow($results, 'assoc')) {
			   $myConfig[$row['param']]=array($row['id'],$row['value']);
			}
						
			//-- Get list of theme files
			        $dir = $modx->config['base_path']."assets/modules/mxCalendar/themes";
				$listDir = array();
				$themeOptions = '';
				if($handler = opendir($dir)) {
				    while (($sub = readdir($handler)) !== FALSE) {
					if ($sub != "." && $sub != ".." && $sub != "Thumb.db" && substr($sub,0,1) != ".") {
					    if(is_file($dir."/".$sub)) {
						// $listDir[] = $sub;
					    }elseif(is_dir($dir."/".$sub)){
						//-- read the theme.xml file
						$themeProperties = array();
						//-- Get list of theme files
						$XML = simplexml_load_file($dir."/".$sub."/theme.xml");
						$themeProperties["name"] = (string)$XML->themename;
						$themeProperties["description"] = (string)$XML->themedescription;
						$themeProperties["themelogo"] = (string)$XML->themelogo;
						$themeProperties["themecss"] = (string)$XML->themecss;
						$themeProperties["authorname"] = (string)$XML->author->name;
						$themeProperties["authorsite"] = (string)$XML->author->siteurl;
						$themeProperties["pubdate"] = (string)$XML->pubdate;
						

						$themeOptions .= '<option value="'.$sub.'" '.($myConfig['mxCalendarTheme'][1] == $sub ? 'selected=selected' : '').'>'.$themeProperties['name'].'</option>';
						if(empty($themeInfo))
							$themeInfo = ($myConfig['mxCalendarTheme'][1] == $sub ? "<p style='clear:both;padding-top:10px;'>".(!empty($themeProperties["themelogo"]) ? "<img src='".$modx->config['site_url']."assets/modules/mxCalendar/themes/".$sub."/".$themeProperties["themelogo"]."' style='float:left;padding:0 5px 5px 0;' alt='icon'/>" : '')._mxCalendar_con_LabelThemeCreatedby." ".(!empty($themeProperties["authorsite"]) ? '<a href="'.$themeProperties["authorsite"].'" target="_blank">'.$themeProperties["authorname"].'</a>' : $themeProperties["authorname"])."<br /><strong>"._mxCalendar_con_LabelThemeDescription."</strong><br />".$themeProperties["description"]."</p>" : '');
					    }
					}
				    }   
				    closedir($handler);
				}
			$langDOW = explode(',', _mxCalendar_cl_headinWeekDays);
			$this->output .= '
			<table width="750">
			<tr><td width="50%" valign="top">
			<!-- left column -->
			<fieldset>
				<legend>'._mxCalendar_con_LegendGlobal.'</legend>
				<div class="fm_row"><label>'._mxCalendar_con_LabelTooltip.'</label><select name="'.$myConfig['disptooltip'][0].'"><option value="0" '.($myConfig['disptooltip'][1] == 0 ? 'selected=selected':'').'>False</option><option value="1" '.($myConfig['disptooltip'][1] == 1 ? 'selected=selected':'').'>True</option></select><img  title="'._mxCalendar_con_LabelTooltipTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				

				<div class="fm_row"><label>'._mxCalendar_con_LabelCalMiniWidth.'</label><input type="input" name="'.$myConfig['calSMwidth'][0].'" value="'.$myConfig['calSMwidth'][1].'" disabled=disabled/><img  title="'._mxCalendar_con_LabelCalMiniWidthTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelCalFullWidth.'</label><input type="input" name="'.$myConfig['calFULLwidth'][0].'" value="'.$myConfig['calFULLwidth'][1].'" /><img  title="'._mxCalendar_con_LabelCalFullWidthTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				
				<div class="fm_row"><label>'._mxCalendar_con_LabelStartDOW.'</label>
					<select name="'.$myConfig['calstartday'][0].'">
					<option value="0" '.($myConfig['calstartday'][1] == 0 ? 'selected=selected':'').'>'.$langDOW[0].'</option>
					<option value="1" '.($myConfig['calstartday'][1] == 1 ? 'selected=selected':'').'>'.$langDOW[1].'</option>
					<option value="2" '.($myConfig['calstartday'][1] == 2 ? 'selected=selected':'').'>'.$langDOW[2].'</option>
					<option value="3" '.($myConfig['calstartday'][1] == 3 ? 'selected=selected':'').'>'.$langDOW[3].'</option>
					<option value="4" '.($myConfig['calstartday'][1] == 4 ? 'selected=selected':'').'>'.$langDOW[4].'</option>
					<option value="5" '.($myConfig['calstartday'][1] == 5 ? 'selected=selected':'').'>'.$langDOW[5].'</option>
					<option value="6" '.($myConfig['calstartday'][1] == 6 ? 'selected=selected':'').'>'.$langDOW[6].'</option>
					</select><img  title="'._mxCalendar_con_LabelStartDOWTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>
				
				<div class="fm_row"><label>'._mxCalendar_con_LabelWeekends.'</label><select name="'.$myConfig['calweekends'][0].'"><option value="0" '.($myConfig['calweekends'][1] == 0 ? 'selected=selected':'').'>False</option><option value="1" '.($myConfig['calweekends'][1] == 1 ? 'selected=selected':'').'>True</option></select><img  title="'._mxCalendar_con_LabelWeekendsTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				
				<div class="fm_row"><label>'._mxCalendar_con_LabelMultiDayList.'</label><select name="'.$myConfig['eventlist_multiday'][0].'"><option value="0" '.($myConfig['eventlist_multiday'][1] == 0 ? 'selected=selected':'').'>False</option><option value="1" '.($myConfig['eventlist_multiday'][1] == 1 ? 'selected=selected':'').'>True</option></select><img  title="'._mxCalendar_con_LabelMultiDayListTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				
				<div class="fm_row"><label>'._mxCalendar_con_LabelClockType.'</label><select name="'.$myConfig['mgrAddClockTwentryFourHour'][0].'"><option value="0" '.($myConfig['mgrAddClockTwentryFourHour'][1] == 0 ? 'selected=selected':'').'>False</option><option value="1" '.($myConfig['mgrAddClockTwentryFourHour'][1] == 1 ? 'selected=selected':'').'>True</option></select><img  title="'._mxCalendar_con_LabelClockTypeTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				
				<div class="fm_row">
					<label>'._mxCalendar_con_mxcAdvancedDateEntry.'</label>
					<select name="'.$myConfig['mxcAdvancedDateEntry'][0].'">
						<option value="0" '.($myConfig['mxcAdvancedDateEntry'][1] == 0 ? 'selected=selected':'').'>False</option>
						<option value="1" '.($myConfig['mxcAdvancedDateEntry'][1] == 1 ? 'selected=selected':'').'>True</option>
					</select>
					<img  title="'._mxCalendar_con_mxcAdvancedDateEntryTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>				
			</fieldset>
			<fieldset>
				<legend>'._mxCalendar_con_LegendTheme.'</legend>
				<div class="fm_row">
				<label>'._mxCalendar_con_LabelTheme.'</label><select name="'.$myConfig['mxCalendarTheme'][0].'">'.$themeOptions.'</select><img  title="'._mxCalendar_con_LabelThemeTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				'.$themeInfo.'
				</div>
			</fieldset>
			<fieldset>
				<legend>'._mxCalendar_con_LegendGoogleMap.'</legend>
				<div class="fm_row"><label>'._mxCalendar_con_LabelGoogleMapHost.'</label><input type="input" name="'.$myConfig['GOOGLE_MAPS_HOST'][0].'" value="'.$myConfig['GOOGLE_MAPS_HOST'][1].'" /><img  title="'._mxCalendar_con_LabelGoogleMapHostTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelGoogleMapKey.'</label><input type="input" name="'.$myConfig['GOOGLE_MAPS_KEY'][0].'" value="'.$myConfig['GOOGLE_MAPS_KEY'][1].'" /><img  title="'._mxCalendar_con_LabelGoogleMapKeyTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGoogleMapDisplayCanvasID.'</label><input type="input" name="'.$myConfig['mxcGoogleMapDisplayCanvasID'][0].'" value="'.$myConfig['mxcGoogleMapDisplayCanvasID'][1].'" /><img  title="'._mxCalendar_con_mxcGoogleMapDisplayCanvasIDTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGoogleMapDisplayWidth.'</label><input type="input" name="'.$myConfig['mxcGoogleMapDisplayWidth'][0].'" value="'.$myConfig['mxcGoogleMapDisplayWidth'][1].'" /><img  title="'._mxCalendar_con_mxcGoogleMapDisplayWidthTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGoogleMapDisplayHeigh.'</label><input type="input" name="'.$myConfig['mxcGoogleMapDisplayHeigh'][0].'" value="'.$myConfig['mxcGoogleMapDisplayHeigh'][1].'" /><img  title="'._mxCalendar_con_mxcGoogleMapDisplayHeighTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGoogleMapDisplayLngLat.'</label><select name="'.$myConfig['mxcGoogleMapDisplayLngLat'][0].'"><option value="1" '.($myConfig['mxcGoogleMapDisplayLngLat'][1] == 1 ? 'selected=selected' : '').'>True</option><option value="0" '.($myConfig['mxcGoogleMapDisplayLngLat'][1] == 0 ? 'selected=selected' : '').'>False</option></select><img  title="'._mxCalendar_con_mxcGoogleMapDisplayLngLatTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
			</fieldset>
			</td>
			<td width="50%"  valign="top">
			
			<!-- right column -->
			<fieldset>
				<legend>'._mxCalendar_con_LegendCategory.'</legend>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGetCategoryListUIFilterActive.'</label><select name="'.$myConfig['mxcGetCategoryListUIFilterActive'][0].'"><option value="0" '.($myConfig['mxcGetCategoryListUIFilterActive'][1] == '0' ? 'selected=selected' : '').'>False</option><option value="1" '.($myConfig['mxcGetCategoryListUIFilterActive'][1] == '1' ? 'selected=selected' : '').'>True</option></select><img  title="'._mxCalendar_con_mxcGetCategoryListUIFilterActiveTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGetCategoryListUIFilterLabel.'</label><input type="input" name="'.$myConfig['mxcGetCategoryListUIFilterLabel'][0].'" value="'.$myConfig['mxcGetCategoryListUIFilterLabel'][1].'" /><img  title="'._mxCalendar_con_mxcGetCategoryListUIFilterLabelTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGetCategoryListUIFilterLabelTag.'</label><select name="'.$myConfig['mxcGetCategoryListUIFilterLabelTag'][0].'"><option value="H2" '.($myConfig['mxcGetCategoryListUIFilterLabelTag'][1] == 'H2' ? 'selected=selected' : '').'>H2</option><option value="H3" '.($myConfig['mxcGetCategoryListUIFilterLabelTag'][1] == 'H3' ? 'selected=selected' : '').'>H3</option><option value="span" '.($myConfig['mxcGetCategoryListUIFilterLabelTag'][1] == 'span' ? 'selected=selected' : '').'>span</option><option value="P" '.($myConfig['mxcGetCategoryListUIFilterLabelTag'][1] == 'P' ? 'selected=selected' : '').'>P</option></select><img  title="'._mxCalendar_con_mxcGetCategoryListUIFilterLabelTagTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGetCategoryListUIFilterLabelTagClass.'</label><input type="input" name="'.$myConfig['mxcGetCategoryListUIFilterLabelTagClass'][0].'" value="'.$myConfig['mxcGetCategoryListUIFilterLabelTagClass'][1].'" /><img  title="'._mxCalendar_con_mxcGetCategoryListUIFilterLabelTagClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcGetCategoryListUIFilterType.'</label><select name="'.$myConfig['mxcGetCategoryListUIFilterType'][0].'"><option value="0" '.($myConfig['mxcGetCategoryListUIFilterType'][1] == 'list' ? 'selected=selected' : '').'>'._mxCalendar_con_mxcGetCategoryListUIFilterType_A.'</option><option value="select" '.($myConfig['mxcGetCategoryListUIFilterType'][1] == 'select' ? 'selected=selected' : '').'>'._mxCalendar_con_mxcGetCategoryListUIFilterType_B.'</option></select><img  title="'._mxCalendar_con_mxcGetCategoryListUIFilterTypeTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
			</fieldset>
			<fieldset>
				<legend>'._mxCalendar_con_LegendMonthView.'</legend>
				<div class="fm_row"><label>'._mxCalendar_con_LabelDuration.'</label><select name="'.$myConfig['dispduration'][0].'"><option value="0" '.($myConfig['dispduration'][1] == 0 ? 'selected=selected' : '').'>False</option><option value="1" '.($myConfig['dispduration'][1] == 1 ? 'selected=selected' : '').'>True</option></select><img  title="'._mxCalendar_con_LabelDurationTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelTimespan.'</label><select name="'.$myConfig['dispeventtime'][0].'"><option value="0" '.($myConfig['dispeventtime'][1] == 0 ? 'selected=selected' : '').'>False</option><option value="1" '.($myConfig['dispduration'][1] == 1 ? 'selected=selected' : '').'>True</option></select><img  title="'._mxCalendar_con_LabelTimespanTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelCalendarActiveDayClass.'</label><input type="input" name="'.$myConfig['mxcCalendarActiveDayClass'][0].'" value="'.$myConfig['mxcCalendarActiveDayClass'][1].'" /><img  title="'._mxCalendar_con_LabelCalendarActiveDayClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelCalendarActiveDayDisplay.'</label><select name="'.$myConfig['mxcCalendarActiveDayDisplay'][0].'"><option value="1" '.($myConfig['mxcCalendarActiveDayDisplay'][1] == 1 ? 'selected=selected' : '').'>True</option><option value="0" '.($myConfig['mxcCalendarActiveDayDisplay'][1] == 0 ? 'selected=selected' : '').'>False</option></select><img  title="'._mxCalendar_con_LabelCalendarActiveDayDisplayTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
			</fieldset>
			<fieldset>
				<legend>'._mxCalendar_con_LegendEventList.'</legend>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventListHeading.'</label><input type="text" name="'.$myConfig['mxcEventDetailLabelHeading'][0].'" value="'.$myConfig['mxcEventDetailLabelHeading'][1].'"><img  title="'._mxCalendar_con_LabelEventListHeadingTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelLocationEV.'</label><input type="input" name="'.$myConfig['mxcEventListLabelLocation'][0].'" value="'.$myConfig['mxcEventListLabelLocation'][1].'" /><img  title="'._mxCalendar_con_LabelLocationEVTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventListMoreLink.'</label><input type="text" name="'.$myConfig['mxcLabelEventListMoreLink'][0].'" value="'.$myConfig['mxcLabelEventListMoreLink'][1].'"><img  title="'._mxCalendar_con_LabelEventListMoreLinkTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelListLimit.'</label><input class="int" type="input" name="'.$myConfig['liststyle_limit'][0].'" value="'.$myConfig['liststyle_limit'][1].'" /><img  title="'._mxCalendar_con_LabelListLimitTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				

				<div class="fm_row"><label>'._mxCalendar_con_LabelEventListItemID.'</label>
				<input type="input" name="'.$myConfig['mxcEventListItemId'][0].'"
				value="'.$myConfig['mxcEventListItemId'][1].'" />
				<img  title="'._mxCalendar_con_LabelEventListItemIDTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
			
				
				<div class="fm_row">
					<label>'._mxCalendar_con_LabelEventListItemClass.'</label>
					<input type="input"
					name="'.$myConfig['mxcEventListEventClass'][0].'"
					value="'.$myConfig['mxcEventListEventClass'][1].'" />
					<img  title="'._mxCalendar_con_LabelEventListItemClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>				

				<div class="fm_row">
					<label>'._mxCalendar_con_mxcEventListItemMultiDayStyle.'</label>
					<input type="input"
					name="'.$myConfig['mxcEventListItemMultiDayStyle'][0].'"
					value="'.$myConfig['mxcEventListItemMultiDayStyle'][1].'" />
					<img  title="'._mxCalendar_con_mxcEventListItemMultiDayStyleTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>
				
				<div class="fm_row">
					<label>'._mxCalendar_con_mxcEventListItemStateDateStamp.'</label>
					<input type="input"
					name="'.$myConfig['mxcEventListItemStateDateStamp'][0].'"
					value="'.$myConfig['mxcEventListItemStateDateStamp'][1].'"/>
					<img  title="'._mxCalendar_con_mxcEventListItemStateDateStampTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>
				<div class="fm_row">
					<label>'._mxCalendar_con_mxcEventListItemEndDateStamp.'</label>
					<input type="input"
					name="'.$myConfig['mxcEventListItemEndDateStamp'][0].'"
					value="'.$myConfig['mxcEventListItemEndDateStamp'][1].'"/>
					<img  title="'._mxCalendar_con_mxcEventListItemEndDateStampTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>

			</fieldset>
			<fieldset>
				<legend>'._mxCalendar_con_LegendEventDetail.'</legend>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventDetailId.'</label><input type="input" name="'.$myConfig['mxcEventDetailId'][0].'" value="'.$myConfig['mxcEventDetailId'][1].'" /><img  title="'._mxCalendar_con_LabelEventDetailIdTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventDetailClass.'</label><input type="input" name="'.$myConfig['mxcEventDetailClass'][0].'" value="'.$myConfig['mxcEventDetailClass'][1].'" /><img  title="'._mxCalendar_con_LabelEventDetailClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventDetailLabelDateTime.'</label><input type="input" name="'.$myConfig['mxcEventDetailLabelDateTime'][0].'" value="'.$myConfig['mxcEventDetailLabelDateTime'][1].'" /><img  title="'._mxCalendar_con_LabelEventDetailLabelDateTimeTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventDetailLabelLocation.'</label><input type="input" name="'.$myConfig['mxcEventDetailLabelLocation'][0].'" value="'.$myConfig['mxcEventDetailLabelLocation'][1].'" /><img  title="'._mxCalendar_con_LabelEventDetailLabelLocationTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventDetailBackToCalClass.'</label><input type="text" name="'.$myConfig['mxcEventDetailBackBtnClass'][0].'" value="'.$myConfig['mxcEventDetailBackBtnClass'][1].'"><img  title="'._mxCalendar_con_LabelEventDetailBackToCalClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_LabelEventDetailBackToCalTitle.'</label><input type="text" name="'.$myConfig['mxcEventDetailBackBtnTitle'][0].'" value="'.$myConfig['mxcEventDetailBackBtnTitle'][1].'"><img  title="'._mxCalendar_con_LabelEventDetailBackToCalTitleTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
			</fieldset>
			</td>
			</tr>
			</table>
			';
			$this->output .= '<input type="hidden" name="action" value="updateConfig"/><input type="submit" name="update" value="'._mxCalendar_btn_update.'" />';
			return $this->output.'</form>';
		}
		
                //-- Save New Event
                function _saveEvent($method){
                    global $modx;
                    $param = $_POST;
                    //-- Break apart the dates
                    $startValuesSplit = explode(' ', strftime("%Y-%m-%d %H:%M",strtotime($param['fmstartdate'])));
                    $endValuesSplit = explode(' ', strftime("%Y-%m-%d %H:%M",strtotime($param['fmenddate'])));
		    
		    //-- Create @param for entry
                    $sT = $modx->db->escape($param['fmtitle']);
                    $sD = $modx->db->escape($param['fmdescription']);
                    $sC = $modx->db->escape($param['fmcategory']);
		    $sWG = $modx->db->escape(implode(',',$_POST['fmrestrictedwebusergroup']));
                    $sL = $modx->db->escape($param['fmlink']);
                    $sLR = $modx->db->escape($param['fmlinkrel']);
                    $sLT = $modx->db->escape($param['fmlinktarget']);
		    $sLoc = $modx->db->escape($param['fmlocation']);
                    $sSD = $startValuesSplit[0]; //--Start Date stamp
                    $sST = $startValuesSplit[1]; //--Start Time stamp
                    $sED = $endValuesSplit[0];	 //--End Date stamp
                    $sET = $endValuesSplit[1];   //--End Time stamp
                    
                    $table_name = $modx->getFullTableName( $this->tables['events'] );

		    $repOccOn=array();
		    if(count($param['fmevent_occurance_on'])){
		    foreach($param['fmevent_occurance_on'] AS $rep)
			$repOccOn[] = $rep[0];
		    }
			
		    $ar_Events = $this->_getRepeatDates($param['fmevent_occurance'], (int)$param['fmevent_occurance_rep'],365, $param['fmstartdate'],( !empty($param['fmevent_occur_until']) ? $param['fmevent_occur_until'] : $param['fmenddate']), $repOccOn);
		    $reOcc = $ar_Events;
		    $last_reOcc = explode(',', $ar_Events);
		    $last_reOcc = $last_reOcc[count($last_reOcc)-1];
		    if($this->debug) print("Reoccur Date<br />".$ar_Events);
		    
		    $str_fmStartDate = $param['fmstartdate'].' '.$param['startdate_htime'].':'.$param['startdate_mtime'].$param['startdate_apm'];
		    $str_fmEndDate = $param['fmenddate'].' '.$param['enddate_htime'].':'.$param['enddate_mtime'].$param['enddate_apm'];
		
		//-- Check for advanced date entry format
		$str_fmStartDate = ( $this->config['mxcAdvancedDateEntry'] ? $param['fmstartdate'] : $str_fmStartDate);
		$str_fmEndDate = ( $this->config['mxcAdvancedDateEntry'] ? $param['fmenddate'] : $str_fmEndDate);		    
		if($this->config['mxcAdvancedDateEntry'])
			$str_fmEndDate = ( checkdate(strftime("%m",strtotime($str_fmEndDate)), strftime("%d",strtotime($str_fmEndDate)), strftime("%Y",strtotime($str_fmEndDate))) ? $str_fmEndDate : $str_fmEndDate.', '.$str_fmStartDate);
		
                    $fields = array('title'	 => $sT,
                                    'description'=> $sD,
                                    'category'	 => $sC,
				    'restrictedwebusergroup' => $sWG,
                                    'link'       => $sL,
                                    'linkrel'    => $sLR,
                                    'linktarget' => $sLT,
				    'location'   => $sLoc,
				    'displayGoogleMap' => (int)$param['fmdisplayGoogleMap'],
                                    'start'	 => strftime("%Y-%m-%d %H:%M:%S" , strtotime($str_fmStartDate)), 
                                    'startdate'	 => strftime('%Y-%m-%d' , strtotime($str_fmStartDate)),
                                    'starttime'  => strftime('%H:%M:%S',strtotime($str_fmStartDate)),
                                    'end'	 => strftime('%Y-%m-%d %H:%M:%S' , strtotime($str_fmEndDate)),
                                    'enddate'    => strftime('%Y-%m-%d' , strtotime($str_fmEndDate)),
                                    'endtime'    => strftime('%H:%M:%S' , strtotime($str_fmEndDate)),
				    '`repeat`'	 => $reOcc,
				    'lastrepeat' => $param['fmevent_occur_until'], //$last_reOcc,
				    'event_occurance' => $modx->db->escape($param['fmevent_occurance']),
				    '_occurance_wkly' => $modx->db->escape($param['fm_occurance_on']),
				    'event_occurance_rep' => $modx->db->escape((int)$param['fmevent_occurance_rep']),
				    '_occurance_properties' => implode(',',$repOccOn)
                                    );
		    if($this->debug) print_r($fields);
                    if($method == _mxCalendar_btn_addEvent){
                        $NID = $modx->db->insert( $fields, $table_name);
                        if($NID) $_POST = array();
                    } else {
                        $modx->db->update( $fields, $table_name, 'id='.$_POST['fmeid']);
                        $NID = $param['fmeid'];
                        $_POST = array();
                    }
                    return "($NID) $sT";
                }
                
                //*************************//
                //**** Front End Views ****//
                //*************************//
                function MakeCalendar($params=array()){
                    global $modx;
		    if(!empty($this->config['mxcLocalization']))
		    $thisLocal = setlocale(LC_ALL, $this->config['localization']);

                    $defaultParam = array(
                                   'mxcType'=>'full'
                                  );
                    $param = array_merge($defaultParam, $params);
                    
                    if(($param['mxcType']=='full' & empty($_REQUEST['details'])  & $param['mxcTypeLocked'] != true ) || (!isset($param['mxcType']) & empty($_REQUEST['details'])  & $param['mxcTypeLocked'] != true ))
                        //-- DISPLAY FULL CALENDAR (roadmap -> change to tpl chunks)
			include_once 'includes/calendar.inc.php';
		    elseif(!empty($_REQUEST['details'])  & $param['mxcTypeLocked'] != true )
                        return $this->MakeEventDetail((int)$_REQUEST['details'],$param);
                    else
                        return $this->MakeUpcomingEventsList($param);
                }
                
                //***********************************//
                //**** Make Events Details View  ****//
                //***********************************//
		function MakeEventDetail($id,$param){
                    global $modx;

                    $table = $modx->getFullTableName( $this->tables['events'] );
                    $result = $modx->db->select( '*', $table, 'id='.$id);
                    $eventSQL = 'SELECT *, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime
				FROM '.$table.' as E WHERE E.id='.$id.' LIMIT 1';
		    $result = $modx->db->query($eventSQL);
		    $content = $modx->db->makeArray( $result );
		    
		    
                    $defaultParam = array(
                                   'mxcTplEventDetail'=>null,
				   'mxcDateTimeSeperator'=>(!empty($this->config['mxcDateTimeSeperator'])?$this->config['mxcDateTimeSeperator']:_mxCalendar_gl_datetimeseperator),
				   'mxcEventDetailBackBtnClass'=>(!empty($this->config['mxcEventDetailBackBtnClass']) ? $this->config['mxcEventDetailBackBtnClass'] : '' ),
				   'mxcEventDetailBackBtnTitle'=>(!empty($this->config['mxcEventDetailBackBtnTitle']) ? $this->config['mxcEventDetailBackBtnTitle'] : _mxCalendar_ed_backToCaltitle ),
                                  );
                    $param = array_merge($defaultParam, $param);
                
			//-- Display back to calendar button
			if((int)$param['mxcFullCalendarPgId'] == (int)$modx->documentIdentifier){
				$backID = ($param['mxcAjaxPageId'] != $param['mxcFullCalendarPgId'] && empty($param['mxcFullCalendarPgId'])) ? $param['mxcAjaxPageId']  : $param['mxcFullCalendarPgId']  ;
				$modx->setPlaceholder('mxcEventDetailBackToCal', _mxCalendar_ed_backToCal);
				$modx->setPlaceholder('mxcEventDetailBackBtnURL',$modx->makeUrl((int)$backID));
				$modx->setPlaceholder('mxcEventDetailBackBtnClass',$param['mxcEventDetailBackBtnClass']);
				$modx->setPlaceholder('mxcEventDetailBackBtnTitle', $param['mxcEventDetailBackBtnTitle']);
			} else { $modx->setPlaceholder('mxcEventDetailBackToCal', ''); }
			
			//-- Parse through returned results and format for UI
			if($modx->db->getRecordCount($result))  {
			    foreach( $content as $p_val ) {
				//-- Build the reoccurring date output list
				$dateList=array();
				if(!empty($p_val['repeat'])){
					$dates = explode(',', $p_val['repeat']);
					$subDateX=0;
					foreach($dates AS $o){
						$dateList[] = strftime(_mxCalendar_ed_dateformat,
						mktime(date('H', strtotime($p_val['start'])), date('i', strtotime($p_val['start'])), 0, date('m', strtotime($o)) , date('d', strtotime($o)), date('y', strtotime($o)) )).
						$param['mxcDateTimeSeperator'].(($p_val['DurationDays'] ) ?
						 strftime(_mxCalendar_ed_dateformat, mktime(date('H', strtotime($p_val['end'])), date('i', strtotime($p_val['end'])), 0, date('m', strtotime($o)) , date('d', strtotime($o))+(int)$p_val['DurationDays'], date('y', strtotime($o)) ) ) :
						 strftime(_mxCalendar_ed_dateformat, mktime(date('H', strtotime($p_val['end'])), date('i', strtotime($p_val['end'])), 0, date('m', strtotime($o)) , date('d', strtotime($o)), date('y', strtotime($o)) ) )
						 );
						$subDateX++;
					}
					$str_repeatDates = ((count($dateList) && $this->config['eventlist_multiday']) ? "<span ='mxcRepeatEventItem'>".implode('<br />',$dateList)."</span>" : '');
				}
				
				
				//-- Parse Event Detail Template
				if(!empty($param['mxcTplEventDetail'])){
					//--Get user modified theme over-ride
					$this->output = $modx->getChunk($param['mxcTplEventDetail']);
				} else {
					//--Get the theme details view
					$this->output = $this->_getTheme('event.detail',$this->config['mxCalendarTheme']);
				}
				
				//-- Setup the title link check
				$title = ( !empty($p_val['link']) ? (is_numeric($p_val['link']) ? $modx->makeUrl((int)$p_val['link'])  : '<a href="'.$p_val['link'].'" rel="'.$p_val['linkrel'].'" target="'.$p_val['linktarget'].'">'.$p_val['title'].'</a>' ) : $p_val['title']);
				
				//-- Add google Map API
				if($p_val['location'] && $p_val['displayGoogleMap']){
					include_once($modx->config['base_path'].'assets/modules/mxCalendar/includes/google_geoloc.class.inc');
					//-- Output the Address results
					if(class_exists("geoLocator") && $p_val['location']){
					    $mygeoloc = new geoLocator;
					    $mygeoloc->host = $this->config['GOOGLE_MAP_HOST'];
					    $mygeoloc->apikey = $this->config['GOOGLE_MAP_KEY'];
					    
					    $addressList = explode('|', $p_val['location']);
					    foreach($addressList as $loc){
						$mygeoloc->getGEO($loc);
					    }

						$googleMap='';
						//-- Build Google MAP JS Section
						if($param['ajaxPageId'] != $modx->documentIdentifier && (int)$param['ajaxPageId']!==0){
						$googleMap = '<div id="map_canvas" style="width: '.$this->config['mxcGoogleMapDisplayWidth'].'; height: '.$this->config['mxcGoogleMapDisplayHeigh'].'"><img src="/blank.gif" alt="" onload="initialize();" /></div>';
						if($this->config['mxcGoogleMapDisplayLngLat'])
							$googleMap .= $mygeoloc->output;
						$this->_addGoogleMapJS($mygeoloc->mapJSv3, true);
						}
						else {
						$googleMap = '<div id="map_canvas" style="width: '.$this->config['mxcGoogleMapDisplayWidth'].'; height: '.$this->config['mxcGoogleMapDisplayHeigh'].';"><img src="/blank.gif" alt="" onload="initialize();" /></div>';
						if($this->config['mxcGoogleMapDisplayLngLat'])
							$googleMap .= $mygeoloc->output;
						$googleMap .= $this->_addGoogleMapJS($mygeoloc->mapJSv3, null, true);
						}
						
						
					    
					} else {
					    echo 'No class found.';
					}
				}
				//-- Adjust Repeat Date Value r0.0.6
				if(isset($_REQUEST['r']) && !empty($_REQUEST['r'])){
					
				}
				//-- Replace placeholders w/UI values
				$modx->setPlaceholder('mxcEventDetailId',$this->config['mxcEventDetailId']);
				$modx->setPlaceholder('mxcEventDetailClass',$this->config['mxcEventDetailClass']);
				$modx->setPlaceholder('mxcEventDetailTitle',$title);
				$modx->setPlaceholder('mxcEventDetailLabelDateTime',($this->config['mxcEventDetailLabelDateTime'] ? $this->config['mxcEventDetailLabelDateTime'] : _mxCalendar_ed_dt));
				$modx->setPlaceholder('mxcEventDetailDateTimeSeperator',$param['mxcDateTimeSeperator']);
				$modx->setPlaceholder('mxcEventDetailStartDateTime',strftime((isset($param['mxcStartDateFormat']) ? $param['mxcStartDateFormat'] : _mxCalendar_ed_start_dateformat),strtotime( (!empty($_REQUEST['r']) ? $dates[$_REQUEST['r']].' '.$p_val['starttime'] : $p_val['start']) )));
				$modx->setPlaceholder('mxcEventDetailEndDateTime',strftime((isset($param['mxcEndDateFormat']) ? $param['mxcEndDateFormat'] : _mxCalendar_ed_end_dateformat),strtotime( (!empty($_REQUEST['r']) ? $dates[$_REQUEST['r']].' '.$p_val['endtime'] : $p_val['end']) )));
				$modx->setPlaceholder('mxcEventDetailDateTimeReoccurrences',$str_repeatDates);
				$modx->setPlaceholder('mxcEventDetailLabelLocation',($p_val['location']?($this->config['mxcEventDetailLabelLocation']? $this->config['mxcEventDetailLabelLocation'] :_mxCalendar_ed_location):''));
				$modx->setPlaceholder('mxcEventDetailLocation',str_replace('|','<br />',$p_val['location']));
				$modx->setPlaceholder('mxcEventDetailDescription',$p_val['description']);
				$modx->setPlaceholder('mxcEventDetailGoogleMap',$googleMap);
				
				}//end loop
			    }
				if(!empty($this->config["mxCalendarTheme"])){
				    $activeTheme = $this->_getActiveTheme();
				    $this->_addCSS('<link rel="stylesheet" type="text/css" href="assets/modules/mxCalendar/themes/'.$this->config['mxCalendarTheme'].'/'.$activeTheme["themecss"].'" /> ');
				}
			    return $this->output;
                }
		
		
		
		//-- Load theme file(s)
		function _getTheme($view=NULL, $theme=NULL){
			global $modx;
			/** @theme = The theme as selected in manager configuration
			 *  @view  = Set which theme file should be loaded and returned for page rendering
			 *           Valid Views Are: event.detail, event.list.wrap, event.list.item,
			 *           
			 */
			$theme = (!empty($theme) ? $theme : 'default');
			if(!is_null($view)){
				$theme_view_file = $modx->config['base_path'].'assets/modules/mxCalendar/themes/'.$theme.'/views/'.$view.'.html';
				if(is_file($theme_view_file) && is_readable($theme_view_file))
					return file_get_contents($theme_view_file);
				elseif($theme != 'default' && is_file(str_replace($theme, 'default', $theme_view_file)) && is_readable(str_replace($theme, 'default', $theme_view_file)))
					return file_get_contents(str_replace($theme, 'default', $theme_view_file));
				else
					return 'Error: Unable to load theme file.<br />'.str_replace($modx->config['base_path'],'',$theme_view_file);			
			} else {
				return 'Error: Invalid <em>view</em>.';
			}
		}
                
		// -- Add the Google MAP API JS code to header
		function _addGoogleMapJS($jsCode, $useAddEvent=false, $return=false){
			global $modx;
			//-- Build Google MAP JS Section
			//--- Version 0.0.6-rc2 (upgraded)
			if($return) return '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
				<script type="text/javascript">
					// -- mxCalendar >=0.0.6-rc2
					function initialize() {
					  '.$jsCode.'
					}
					window.addEvent(\'domready\', function(){
					    initialize();
					}); 
				</script>';
			else   $modx->regClientStartupScript('
				<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
				<script type="text/javascript">
					function initialize() {
					  '.$jsCode.'
					}
					window.addEvent(\'domready\', function(){
					    initialize();
					}); 
				</script>');
			
		}
		
		
                //***********************************//
                //**** Make Upcoming Events View ****//
                //***********************************//
		function MakeUpcomingEventsList($param=array()){
                    global $modx;
                    $defaultParam = array(
                                   'mxcType'=>null,
                                   'mxcTplEventListWrap'=>null,
                                   'mxcTplEventListWrapClass'=>null,
                                   'mxcTplEventListWrapId'=>null,
                                   'mxcTplEventListWrapTitle'=>(($this->config['mxcEventDetailLabelHeading']) ? $this->config['mxcEventDetailLabelHeading'] : _mxCalendar_ev_title),
                                   'mxcTplEventListItemWrap'=>null,
                                   'mxcEventListMaxCnt'=>(is_numeric($this->config['liststyle_limit']) ? (int)$this->config['liststyle_limit'] : 5),
                                   'mxcEventListAjaxPaginate'=>null,
                                   'mxcFullCalendarPgId'=>null,
                                   'mxcTplEventListMoreLink'=>(($this->config['mxcLabelEventListMoreLink']) ? $this->config['mxcLabelEventListMoreLink'] : _mxCalendar_ev_link),
				   'mxcEventListItemId'=>(($this->config['mxcEventListItemId']) ? $this->config['mxcEventListItemId'] : ''),
				   'mxcEventListItemClass'=>(($this->config['mxcEventListItemClass']) ? $this->config['mxcEventListItemClass'] : ''),
				   'mxcEventListTitleLink'=>true
                                  );
		    //&mxcEventListItemClass=`mxModal`
                    $param = array_merge($defaultParam, $param);
		    
                    //-- Prase Event template and loop through the events
		    $mxcELStartDate = isset($param['mxcStartDate']) ? strftime("%Y-%m-%d",strtotime($param['mxcStartDate'])) : strftime("%Y-%m-%d") ;
                    $events = '';
                    $records = $modx->db->makeArray($this->_getNEvents($mxcELStartDate,(int)$param['mxcEventListMaxCnt'],$param['mxcDefaultCatId']));
                    
                    //-- check the count before entering the loop
                    if(count($records) > 0){
			$ar_events=array();
                        //-- @config display multi-day as unique event per each day
			if($this->config['eventlist_multiday']){
				foreach( $records as $e ) {
					//-- Event template @param chunk array
					$datePieces = explode("-", $e['startdate']);
					$month=strftime("%b", strtotime($e['start']));
					$day=$datePieces[2];
					$mxcStartDateFilter = isset($mxcStartDate) ? strftime('%Y-%m-%d', strtotime($mxcStartDate)) : strftime('%Y-%m-%d');
					if(strftime('%Y-%m-%d', strtotime($e['start'])) >= $mxcStartDateFilter)
					$ar_events[]=$e;
					$or = $e;
					if($e['DurationDays']){
						for($x=1;$x<=$e['DurationDays'];$x++){
							$e['start']=strftime('%Y-%m-%d', mktime(0, 0, 0, strftime('%m', strtotime($e['start'])) , date('d', strtotime($e['start']))+$x, date('y', strtotime($e['start']))) );
							if(strftime('%Y-%m-%d', strtotime($e['start'])) >= strftime('%Y-%m-%d'))
							$ar_events[]=$e;
						}
						
					}
					if(!empty($e['repeat'])){
					    $sub_dates = explode(',',$or['repeat']);
					    foreach($sub_dates as $child_event){
						    $e['start']=$child_event;
						    if(strftime('%Y-%m-%d', strtotime($e['start'])) >= strftime('%Y-%m-%d'))
						    $ar_events[]=$e;
						    if($e['DurationDays']){
							    for($x=1;$x<=$e['DurationDays'];$x++){
								    $e['start']=strftime('%Y-%m-%d', mktime(strftime('%H', strtotime($child_event)), date('i', strtotime($child_event)), 0, date('m', strtotime($child_event)) , date('d', strtotime($child_event))+$x, date('y', strtotime($child_event))) );
								    //$ar_events[]=$e;
							    }
							    
						    }
					    }
					}
				}
				//-- Sort the results by start date
				$ar_events = $this->multisort($ar_events,'start','description','title','end','location','eid','link','linkrel','linktarget');
			} else {
			    foreach( $records as $event ) {
				$ar_events[] = $event;
			    }
			}

			//-- Add MoodalBox if mxcAjaxPageId is set
			if(!empty($param['mxcAjaxPageId']) != $modx->documentIdentifier){
			    $this->_addJS('
			    <script type="text/javascript" src="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/js/moodalbox.v1.2.full.js"></script>
			    ');
			    $this->_addCSS('<link rel="stylesheet" href="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/css/moodalbox.css" type="text/css" media="screen" />');
			}
			
			//-- Loop through the new sorted list of events
			$evCnt=0;
			foreach ($ar_events as $event){
                            //-- Event template @param chunk array
                            $datePieces = explode("-", $event['startdate']);
                            $month=strftime("%b", strtotime($event['start']));
                            $day=$datePieces[2];

			    //-- Set the URL for the event title
			    $mxcEventDetailURL = (is_numeric((int)$param['mxcAjaxPageId']) && $param['mxcAjaxPageId'] != $modx->documentIdentifier ? $modx->makeUrl((int)$param['mxcAjaxPageId'],'', '&details='.$event['eid'].($calEvents['repeat'] ? '&r='.$calEvents['repeat'] : ''), 'full') : $modx->makeUrl((int)$param['mxcFullCalendarPgId'],'','details='.$event['eid']));
			    $mxcEventDetailAJAX = ($param['mxcAjaxPageId'] != $modx->documentIdentifier ? 'moodalbox ' : '');
			    if(!$param['mxcEventListTitleLink'])
				$title = $event['title'];
			    elseif(($param['mxcFullCalendarPgId'] || $param['mxcAjaxPageId']) && empty($event['link']))
				$title='<a href="'.$mxcEventDetailURL.'" class=" '.$param['mxcEventListItemClass'].'"  target="'.$event['linktarget'].'" rel="'.$mxcEventDetailAJAX.$event['linkrel'].'">'.$event['title'].'</a>';
			    else
				$title = ( !empty($event['link'])?(is_numeric($event['link'])? '<a href="'.$modx->makeUrl((int)$event['link'],'','','full').'" target="'.$event['linktarget'].'" rel="'.$event['linkrel'].' moodalbox">'.$event['title'].'</a>':'<a href="'.$event['link'].'" rel="'.$event['linkrel'].'" target="'.$event['linktarget'].'">'.$event['title'].'</a>'): $event['title'] );
			    
			    //-- Add required JS Library items
			    if(isset($param['mxcAjaxPageId']) && is_numeric((int)$param['mxcAjaxPageId'])){
				$this->_buildJSlib();
			    }
                            
			    $location=$event['location'];

			    $event['month']=utf8_encode(strftime('%b',strtotime($event['start'])));
			    $event['day']=strftime('%d', strtotime($event['start']));
			    $event['year']=strftime('%Y', strtotime($event['start']));
			    
				$ar_eventDetail = array(
				'mxcEventListItemId ' => (!empty($param['mxcEventListItemId']) ? $param['mxcEventListItemId'] : $this->config['mxcEventListItemId']),
				'mxcEventListItemClass' => (!empty($param['mxcEventListItemClass']) ? $param['mxcEventListItemClass'] : $this->config['mxcEventListEventClass']),
				'mxcEventListItemTitle' => $title,
				'mxcEventListItemLabelDateTime' => '', //-- not used in current version
				'mxcEventListItemMonth' => $event['month'],
				'mxcEventListItemStartDateTime' => $event['day'],
				'mxcEventListItemYear'=> $event['year'],
				'mxcEventListItemDateTimeSeperator' => ($event['DurationDays'] ? _mxCalendar_gl_multipledaydurationsperator : ''),
				'mxcEventListItemMultiDayStyle' => ($event['DurationDays'] ? $this->config['mxcEventListItemMultiDayStyle'] : ''),
				'mxcEventListItemEndDateTime' => ($event['DurationDays'] ?  strftime('%d',strtotime('+'.$event['DurationDays'].' day', strtotime($event['start']))) : ''), //--Issue 23 strtotime('+'.$event['DurationDays'].' day',$event['start'])
				'mxcEventListItemDateTimeReoccurrences' => '', //--not used in current version
				'mxcEventListItemLabelLocation' => ($location?($this->config['mxcEventListLabelLocation']? $this->config['mxcEventListLabelLocation'] :_mxCalendar_ev_labelLocation):''),
				'mxcEventListItemLocation' => $location,
				'mxcEventListItemDescription' => $event['description'],
				//-- add in full event date time output r0.0.6
				'mxcEventListItemStateDateStamp' => strftime($this->config['mxcEventListItemStateDateStamp'], strtotime($event['start'])),
				'mxcEventListItemEndDateStamp' => strftime($this->config['mxcEventListItemEndDateStamp'], strtotime($event['end']))
				);

                            //-- check for event list template over-ride chunk
                            if(!empty($param['mxcTplEventListItemWrap'])){
                                $events .= $modx->parseChunk($param['mxcTplEventListItemWrap '], $ar_eventDetail, '[+', '+]');
                            } else {
				//-- load the theme event list view
				$theme_eventlist_tpl =  $this->_getTheme('event.list.event',$this->config['mxCalendarTheme']);
				$keys = array();
				$values = array();
				foreach ($ar_eventDetail as $key=>$value) {
					$keys[] = '[+'.$key.'+]';
					$values[] = $value;
				}
				$events .= str_replace($keys,$values,$theme_eventlist_tpl);
				unset($keys,$values);
                            }
			    $evCnt++;
			    if($evCnt >= $param['mxcEventListMaxCnt'])
				break;
			    
                        }
                        if(is_numeric($param['mxcFullCalendarPgId'])){
                            $modx->setPlaceholder('mxcEventListMoreURL', $modx->makeUrl((int)$param['mxcFullCalendarPgId']));
                        }else
                            $modx->setPlaceholder('mxcEventListMoreURL', '');
                    } else {
			//-- Return Language file no-event notice message
                        $events = _mxCalendar_ev_none;
                    }
		    		   
			//-- check for event list template over-ride chunk
			if(!empty($param['mxcTplEventListWrap'])){
			    $this->output =  $modx->getChunk($param['mxcTplEventListWrap']);
			} else {
			    $this->output = $this->_getTheme('event.list.container',$this->config['mxCalendarTheme']);
			}
			
			//-- Get Configuration Item for Category UI Filter display
			$_mxcCalCategoryFilter = (boolean)$this->config['mxcGetCategoryListUIFilterActive'] === true ? $this->mxcGetCategoryListUIFilter($this->config['mxcGetCategoryListUIFilterType']) : '';
			$_mxcCalCategoryFilter = ((boolean)$param['mxcDefaultCatIdLock'] !== true ? $_mxcCalCategoryFilter : '');
			
			//-- Fix for multiple instances on single page (0.0.6-rc2)
			$ar_eventList['mxcEventListContainerId']='calendar';
			$ar_eventList['mxcEventListContainerClass']='block';
			$ar_eventList['mxcEventListContainerTitle']=$param['mxcTplEventListWrapTitle'];
			$ar_eventList['mxcEventList']=$events;
			$ar_eventList['mxcEventListMoreClass']='readmore';
			$ar_eventList['mxcEventListMoreLabel']=$param['mxcTplEventListMoreLink'];
			$ar_eventList['mxcCategoryFilters'] = $_mxcCalCategoryFilter;
			$keys = array();
			$values = array();
			foreach ($ar_eventList as $k=>$vv) {
				$keys[] = '[+'.$k.'+]';
				$values[] = $vv;
			}
			$this->output = str_replace($keys,$values,$this->output);
			unset($keys,$values);

                    
		    if(!empty($this->config["mxCalendarTheme"])){
			$activeTheme = $this->_getActiveTheme();
			$this->_addCSS('<link rel="stylesheet" type="text/css" href="assets/modules/mxCalendar/themes/'.$this->config['mxCalendarTheme'].'/'.$activeTheme["themecss"].'" /> ');
		    }
		    return $this->output;
                }
		
		
		
		
		//--Add mutli-dimensional sorting
		function multisort($array, $sort_by, $key1, $key2=NULL, $key3=NULL, $key4=NULL, $key5=NULL, $key6=NULL, $key7=NULL, $key8=NULL, $key9=NULL){
		    // set order
		    foreach ($array as $pos =>  $val)
			$tmp_array[$pos] = $val[$sort_by];
		    asort($tmp_array);
		   
		    // display the order you want
		    foreach ($tmp_array as $pos =>  $val){
			$return_array[$pos][$sort_by] = $array[$pos][$sort_by];
			$return_array[$pos][$key1] = $array[$pos][$key1];
			if (isset($key2)){
			    $return_array[$pos][$key2] = $array[$pos][$key2];
			    }
			if (isset($key3)){
			    $return_array[$pos][$key3] = $array[$pos][$key3];
			    }
			if (isset($key4)){
			    $return_array[$pos][$key4] = $array[$pos][$key4];
			    }
			if (isset($key5)){
			    $return_array[$pos][$key5] = $array[$pos][$key5];
			    }
			if (isset($key6)){
			    $return_array[$pos][$key6] = $array[$pos][$key6];
			    }
			if (isset($key7)){
			    $return_array[$pos][$key7] = $array[$pos][$key7];
			    }
			if (isset($key8)){
			    $return_array[$pos][$key8] = $array[$pos][$key8];
			    }
			if (isset($key9)){
			    $return_array[$pos][$key9] = $array[$pos][$key9];
			    }
			}
		    return $return_array;
		    }


                
                //**************************//
                //**** GLOBAL FUNCTIONS ****//
                //**************************//
		
		function _buildJSlib(){
			$confJSlib = 'JQ';
			$confJSlib = 'Moo';
			$confJSlib = 'cust'; //-- Get assigned resource URL
			$confJSlib = 'GJQ';  //-- Google jQuery lib compressed
			$confJSlib = 'GMoo'; //-- Google MooTools lib compressed
			
			
			// ThickBox Example: <a href="ajaxOverFlow.html?height=300&width=300" title="add a caption to title attribute / or leave blank" class="thickbox">Scrolling content</a>
			
			$this->_addJS('
			    <script type="text/javascript" src="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/js/moodalbox.v1.2.full.js"></script>
			');
			$this->_addCSS('<link rel="stylesheet" href="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/css/moodalbox.css" type="text/css" media="screen" />');
		}
		
                //-- Get Events and return the array
                function _getEvents($date=null,$stopdate=null){
                    global $modx;
                    $date = (!is_null($date)) ? $date : date("Y-m-1") ;
                    $enddate = date ( "Y-m-d" , strtotime ( "+1 year" , strtotime ( date("Y-m-1") ) ) );
                    $eventsSQL = 'SELECT *,E.id as eid,E.category as catID, C.name as category, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE startdate >= \''.$date.'\' and enddate < \''.$enddate.'\' and E.active=1
                            ORDER BY startdate';
                    $results = $modx->db->query($eventsSQL);
                    return $results;
                }
		
		//-- Events with pagination
                function _getEventsPagination($limit=20,$page=0,$filter=array(),$filter_or=array()){
                    global $modx;
		    $where_or = array();
		    $where = array();
		    if(empty($filter) && empty($filter_or)){
			$where_or[] = 'startdate >=  \''.date("Y-m-d").'\' ';
			$where_or[] = '`lastrepeat` >= \''.date("Y-m-d").'\'';
			$where_or[] = 'enddate <  \''.date("Y-m-d").'\'';
			$where[] =  'E.active=1';
		    } else {
			$where = $filter;
			$where[] =  'E.active=1';
			$where_or = $filter_or;
		    }
		    
		    
                    $eventsSQL = 'SELECT *,E.id as eid,E.category as catID, C.name as category, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE '.(empty($filter) ? implode(' or ',$where_or).' and ' : '').' '.implode(' and ', $where).'
                            ORDER BY startdate '.($limit != 'ALL' && !empty($limit) ? 'LIMIT '.($page*$limit).','.$limit.' ' : '');
                    $results = $modx->db->query($eventsSQL);

                    $eventsSQLpg = 'SELECT count(E.id)
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE '.(empty($filter) ? implode(' or ',$where_or).' and ' : '').' '.implode(' and ', $where).'
                            ORDER BY startdate';
                    $resultsCount = $modx->db->query($eventsSQLpg);
		    $data = mysql_fetch_array($resultsCount);
		    $numevents = $data[0];
		    
		    //-- create the pagination links
		    $resltLastPage = ($limit != 'ALL' && !empty($limit) ? ceil($numevents/$limit) : -1 );
		    if($resltLastPage <= 1)
			return array($results,null);
		    else {
			$paginationLinks = '';
    
			    $key = 'pg';
			    $url = preg_replace('/(.*)(' . $key . '=[0-9])+(.*)/i', '$1$3', $_SERVER['QUERY_STRING']  );
			    $qs = (substr($url,-1,1) == '&' ? substr($url, 0, -1) : $url);
    
			
			for($x=0;$x<$resltLastPage;$x++)
			    $paginationLinks .= "&nbsp;&nbsp;<a href='".(($x == $page) ? '#' : "?{$qs}&pg={$x}")."' class='mxcPage".($x==$page ? 'active' : '')."'>".($x + 1)."</a> ";
			    
			return array($results,$paginationLinks);
		    }
                }		
                
                //-- Get Next (N) Events and return the array
                function _getNEvents($date=null,$n=10,$CatId=Null){
                    global $modx;
		    

//-- Front end: returns logged in user's webgroup assignments [webgroup = web group id's user belongs to]
if($modx->getLoginUserID()){    
	$userInfo = $modx->db->makeArray(
	    $modx->db->select(
		'webgroup', 
		$modx->getFullTableName('web_groups'), 
		'`webuser`='.$modx->getLoginUserID()
	    )
	);
	//-- Web View Permission Where Builder
	foreach($userInfo AS $wu){
		foreach($wu AS $wp)
			$WHERE_WGP[] = 'FIND_IN_SET('.$wp['0'].',E.restrictedwebusergroup)';
	}
}

		    
                    $date = (checkdate(strftime("%m",$date),strftime("%d",$date),strftime("%Y",$date) )) ? $date : strftime("%Y-%m-%d") ;
                    $enddate = strtotime ( "+1 month" , strtotime ( date("Y-m-1") ) ) ;
                    $enddate = date ( "Y-m-d" , $enddate );
                    $eventsSQL = 'SELECT *,E.id as eid ,E.category as catID, E.repeat, C.name as category, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE (startdate >= \''.$date.'\' or enddate >= \''.$date.'\' or  `lastrepeat` >= \''.$date.'\') and E.active=1
			    AND C.active=1 '.(!is_null($CatId) ? ' and C.id IN ('.$CatId.') ' : '').'
                            AND '.($WHERE_WGP && count($WHERE_WGP) ? '('.implode(' OR ',$WHERE_WGP).' OR ( E.restrictedwebusergroup = \'\' OR E.restrictedwebusergroup <=> NULL ))' : '( E.restrictedwebusergroup = \'\' OR E.restrictedwebusergroup <=> NULL )' ).'  
			    ORDER BY startdate
                            LIMIT '.$n;
                    $results = $modx->db->query($eventsSQL);
                    if($this->debug){
			echo '[DEBUG: _getNEvents()]<br />Date: '.$date;
			echo '<br>SQL:<br>'.$eventsSQL.'<br>';
		    }
		    return $results;
                }

                //-- Get Events Single Day and return the array
                function _getEventsSingleDay($date=null,$month="m",$CatId=null){
                    global $modx;
                    $date = (!is_null($date)) ? $date : strftime("%Y-%m-%d") ;
                    $enddate = strtotime ( "+1 month" , strtotime ( strftime("%Y-$month-1") ) ) ;
                    $enddate = strftime ( "%Y-%m-%d" , $enddate );
		    
		    //-- Collect user defined filters
		    $usrStrWhere = $_REQUEST['fmusrfilterCat'];
		    $catID = $_REQUEST['CatId'] ? ' AND C.id IN ('.(int)$_REQUEST['CatId'].')' : '';
                    $eventsSQL = 'SELECT *,E.id as eid, E.category as catID, C.name as category, C.foregroundcss, C.backgroundcss, C.inlinecss, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime, `repeat` 
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE
				((startdate >= \''.$date.'\' and 
				enddate < ADDDATE(\''.$date.'\', INTERVAL 1 MONTH))
				or `repeat` REGEXP \'[[:alnum:]]+\' )				
				and E.active=1
				and C.active = 1 '.($CatId != null && !empty($_REQUEST['CatId']) ? ' and E.category IN ('.$CatId.') ' : '').' 
                            ORDER BY start';
                    $results = $modx->db->query($eventsSQL);
                    if($this->debug) echo "SQL: <br />".$eventsSQL;
                    
                    if($modx->db->getRecordCount($results) > 0){
                        while($data = $modx->db->getRow($results)){
                            $dayOfMonth = explode('-', $data['startdate']);
                            $dayOfMonth = (int)$dayOfMonth[2];
                            
                            $endDayOfMonth = explode('-', $data['enddate']);
                            $endDayOfMonth = (int)$endDayOfMonth[2];
			    
			    $match = explode('-', $data['startdate']);
			    $dataPieces = explode('-', $date);
                            
			    if($match[1]=== $dataPieces[1]){ //-- Remove the duplicate of the reoccurance date on the month
                            $eventsByDay[$dayOfMonth][] = array(
                                    //'endDay' => $endDayOfMonth,
                                    'id'=>$data['eid'],
                                    'title'=>$data['title'],
				    'category'=>$data['category'],
				    'cateogryCSS' => array($data['catID'],$data['foregroundcss'],$data['backgroundcss'],$data['inlinecss']),
                                    'description'=>$data['description'],
                                    'DurationDays'=>$data['DurationDays'],
                                    'DurationTime'=>$data['DurationTime'],
                                    'link' => $data['link'],
                                    'linkrel' => $data['linkrel'],
                                    'linktarget' => $data['linktarget'],
                                    'start' => $data['start'],
                                    'startdate' => $data['startdate'],
                                    'starttime' => $data['starttime'],
                                    'end' => $data['end'],
                                    'enddate' => $data['enddate'],
                                    'endtime' => $data['endtime']
                            );
			    }
			    
                            #***** add multiple day records *****#
                            if($dayOfMonth < $endDayOfMonth  && $match[2] !== $dataPieces[2] && $match[1] === $dataPieces[1]){
                                //$dif = $dayOfMonth + 1;
                                for($x=($dayOfMonth+1);$x<=$endDayOfMonth;$x++){
                                    if($this->debug) echo "<br />MD:  ".$x."  ".$match[1].$dayOfMonth."==".$dataPieces[1].$endDayOfMonth;
				    $eventsByDay[$x][] = array(
                                            //'endDay' => $endDayOfMonth,
                                            'id'=>$data['eid'],
                                            'title'=>$data['title'],
					    'category'=>$data['category'],
                                            'cateogryCSS' => array($data['catID'],$data['foregroundcss'],$data['backgroundcss'],$data['inlinecss']),
					    'description'=>$data['description'],
                                            'DurationDays'=>$data['DurationDays'],
                                            'DurationTime'=>$data['DurationTime'],
                                            'link' => $data['link'],
                                            'linkrel' => $data['linkrel'],
                                            'linktarget' => $data['linktarget'],
                                            'start' => $data['start'],
                                            'startdate' => $data['startdate'],
                                            'starttime' => $data['starttime'],
                                            'end' => $data['end'],
                                            'enddate' => $data['enddate'],
                                            'endtime' => $data['endtime']
                                    );
                                }
                            }
			    
			    // -- Add repeat dates as well now
			    
                            if(!empty($data['repeat']) ) //-- 0.0.3b Rem: && $match[1] !== $dataPieces[1]
			    {
				$repeatOccurances = explode(',', $data['repeat']);
				$int_repeatItem = 0;
				foreach($repeatOccurances AS $rep){
					$endRepDayOfMonth = explode('-', $rep);
					$endRepDayOfMonth = (int)$endRepDayOfMonth[2];
					
					$endRepDayPieces = explode('-', $rep);
					if($this->debug) echo "<br />REP: $rep   cond1=>".$endRepDayOfMonth.'<='.$endDayOfMonth.'  cond2=>'.$endRepDayPieces[1] .'=='. $dataPieces[1];
					$numdaysinmonth = cal_days_in_month( CAL_GREGORIAN, $endRepDayPieces[1], $endRepDayPieces[0] );
					if($endRepDayOfMonth<=$numdaysinmonth  && $endRepDayPieces[1] == $dataPieces[1]){
						$eventsByDay[$endRepDayOfMonth][] = array(
							//'endDay' => $endDayOfMonth,
							'id'=>$data['eid'],
							'title'=>$data['title'],
							'category'=>$data['category'],
							'cateogryCSS' => array($data['catID'],$data['foregroundcss'],$data['backgroundcss'],$data['inlinecss']),
							'description'=>$data['description'],
							'DurationDays'=>$data['DurationDays'],
							'DurationTime'=>$data['DurationTime'],
							'link' => $data['link'],
							'linkrel' => $data['linkrel'],
							'linktarget' => $data['linktarget'],
							'start' => $rep.' '.$data['starttime'],
							'startdate' => $data['startdate'],
							'starttime' => $data['starttime'],
							'end' => $rep.' '.$data['endtime'],
							'enddate' => $data['enddate'],
							'endtime' => $data['endtime'],
							'repeat' => $int_repeatItem
						);
						if($data['DurationDays']){
							for($r=($endRepDayOfMonth+1);$r<=($endRepDayOfMonth+$data['DurationDays']);$r++){
								$eventsByDay[$r][] = array(
									//'endDay' => $endDayOfMonth,
									'id'=>$data['eid'],
									'title'=>$data['title'],
									'category'=>$data['category'],
									'cateogryCSS' => array($data['catID'],$data['cid'],$data['foregroundcss'],$data['backgroundcss'],$data['inlinecss']),
									'description'=>$data['description'],
									'DurationDays'=>$data['DurationDays'],
									'DurationTime'=>$data['DurationTime'],
									'link' => $data['link'],
									'linkrel' => $data['linkrel'],
									'linktarget' => $data['linktarget'],
									'start' => $rep.' '.$data['starttime'],
									'startdate' => $data['startdate'],
									'starttime' => $data['starttime'],
									'end' => $rep.' '.$data['endtime'],
									'enddate' => $data['enddate'],
									'endtime' => $data['endtime'],
									'repeat' => $int_repeatItem
								);
							}
						}
					}
					$int_repeatItem++;
				}
                            } 
                        }
                    }
                    return $eventsByDay;
                }
                
                //-- Get Categories Array
                function getCategories(){
                    global $modx;
                    $output = '';
                    $table = $modx->getFullTableName( $this->tables['categories'] );
                    $result = $modx->db->select( 'id, name, isdefault', $table, 'disable=0', 'name ASC', '' );
                    $list = $modx->db->makeArray( $result );
                    //$listArr = array();
                    foreach( $list as $p_val ) {		
                            $listArr[] = array($p_val['id']=>array($p_val['name'],$p_val['isdefault']));
                    }
                    return $listArr;
                }
		
		//-- List Category Filter UI
		function mxcGetCategoryListUIFilter($displayType='list',$isMgr=false){
			// Support list types are list=List (ul>li), select=Select (select>option)
			global $modx;
			$table = $modx->getFullTableName( $this->tables['categories'] );
			$result = $modx->db->select( '*', $table, 'active=1', 'name ASC', '' );
			$list = $modx->db->makeArray( $result );
			$mxcGetCategoryListFilterHTML = ($isMgr ? '' : '<'.$this->config['mxcGetCategoryListUIFilterLabelTag'].' class="'.$this->config['mxcGetCategoryListUIFilterLabelTagClass'].'">'.$this->config['mxcGetCategoryListUIFilterLabel'].'</'.$this->config['mxcGetCategoryListUIFilterLabelTag'].'>');
			$uriParts = parse_url($_SERVER['QUERY_STRING']);
			$uriQS = '';
			foreach($uriParts AS $k=>$v)
			   $uriQS .= ($k != 'path' ? '&'.$k.'='.$v : '');
			switch($displayType){
				case 'select':
					$mxcCatOnChange = ($isMgr ? '' : 'window.location=\''.$modx->makeUrl($modx->documentIdentifier,'','',full).'?'.$uriQS.'&CatId=\'+this.value');
					$mxcGetCategoryListFilterHTML .= '<select name="CategoryId" onChange="'.$mxcCatOnChange.'">'."\n";
					$mxcGetCategoryListFilterHTML .= "\t".'<option value="" '.($this->params['CatId'] ? (empty($this->params['CatId']) ? 'selected=selected':'') : '').'>'._mxCalendar_el_dlAllCategories.'</option>'."\n";
					foreach( $list as $i ) {		
						$mxcGetCategoryListFilterHTML .= "\t".'<option value="'.$i['id'].'" '.($this->params['CatId'] ? ($this->params['CatId'] == $i['id']? 'selected=selected':'') : '').'>'.$i['name'].'</option>'."\n";
					}
					$mxcGetCategoryListFilterHTML .= '</select>'."\n";
					break;
				case 'list':
				default:
					$mxcGetCategoryListFilterHTML .= '<ul>'."\n";
					$urlFilter = $modx->makeUrl($modx->documentIdentifier,'',preg_replace('/&+/', '&', preg_replace('/(&(.*)(CatId=[0-9]*))|(&?(.*)(id=[0-9]*))/','',$_SERVER['QUERY_STRING'])),full);
					$mxcGetCategoryListFilterHTML .= "\t".'<li id="mxcCategory'.$i['id'].'" class="'.($this->params['CatId'] ? (empty($this->params['CatId']) ? 'mxcCategoryActive':'') : '').'"><a href=\''.$modx->makeUrl($modx->documentIdentifier).'\' class="" style="">'._mxCalendar_gl_all.'</a></li>'."\n";
					foreach( $list as $i ) {		
						$urlFilter = $modx->makeUrl($modx->documentIdentifier,'',$uriQS.'&CatId='.$i['id'],'',full);
						$mxcGetCategoryListFilterHTML .= "\t".'<li id="mxcCategory'.$i['id'].'" class="'.($this->params['CatId'] ? ($this->params['CatId'] == $i['id']? 'mxcCategoryActive':'') : '').'"><a href=\''.$urlFilter.'\' class="'.$i['inlinecss'].'" style="color:'.$i['foregroundcss'].';background-color:'.$i['backgroundcss'].';">'.$i['name'].'</a></li>'."\n";
					}
					$mxcGetCategoryListFilterHTML .= '</ul>'."\n";
					break;
			}
			return $mxcGetCategoryListFilterHTML;
		}

                //-- FORM FILE BROWSER SCRIPTING
                function _getFileBrowserScript(){
                    return "<script type=\"text/javascript\">
							var lastImageCtrl;
							var lastFileCtrl;
                                                        var field;
                                                        function OpenServerBrowser(url, width, height ) {
								var iLeft = (screen.width  - width) / 2 ;
								var iTop  = (screen.height - height) / 2 ;

								var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes' ;
								sOptions += ',width=' + width ;
								sOptions += ',height=' + height ;
								sOptions += ',left=' + iLeft ;
								sOptions += ',top=' + iTop ;

								var oWindow = window.open( url, 'CARIBWhaleBrowser', sOptions ) ;
							}			
							function BrowseServer(ctrl) {
								lastImageCtrl = ctrl;
								var w = screen.width * 0.7;
								var h = screen.height * 0.7;
								//OpenServerBrowser('".$modx->config['base_url']."/manager/media/browser/mcpuk/browser.html?Type=images&Connector=/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=".$modx->config['base_url']."', w, h);
                                                                OpenServerBrowser('/manager/media/browser/mcpuk/browser.html?Type=images&Connector=/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=".$modx->config['base_url']."', w, h);
							}
							
							function BrowseFileServer(ctrl) {
								lastFileCtrl = ctrl;
								var w = screen.width * 0.7;
								var h = screen.height * 0.7;
								 //OpenServerBrowser('".$modx->config['base_url']."/manager/media/browser/mcpuk/browser.html?Type=files&Connector=/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=".$modx->config['base_url']."', w, h);
                                                                 OpenServerBrowser('/manager/media/browser/mcpuk/browser.html?Type=files&Connector=/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=".$modx->config['base_url']."', w, h);
							}
							
							function SetUrl(url, width, height, alt){
								if(lastFileCtrl) {
									document.getElementById(lastFileCtrl).value=url;
                                                                        //var c = document.mutate[lastFileCtrl];
									//if(c) c.value = url;
									lastFileCtrl = '';
								} else if(lastImageCtrl) {
									//alert(url);
                                                                        document.getElementById(lastImageCtrl).value=url;
                                                                        //var c = document.mutate[lastImageCtrl];
									//if(c) c.value = url;
									lastImageCtrl = '';
								} else {
									return;
								}
							}
					</script>";
                }

                //-- BUILD DATE SELECTOR
                function _makeDateSelector($field, $label, $tooltip, $val){
                    global $modx;
		    $formEntries = '<div class="fm_row"><label>'.$label.'</label><div class="fm_entry">';
                    $fmDATE = ($_POST['fm'.$field]) ? $_POST['fm'.$field] : $val;
		    $theme = $modx->config['manager_theme'];
		    //cal_form
		    $autoEndDateUpdate = ($field == 'startdate' ? 'document.forms[\'cal_form\'].elements[\'fmenddate\'].value=this.value;" ' : '');
                    $formEntries .= '<input id="fm'.$field.'" name="fm'.$field.'" class="DatePicker" value="'.$fmDATE.'" onblur="documentDirty=true;{$autoEndDateUpdate}" /><a title="Remove Date" onclick="this.previousSibling.value=\'\'; return true;" onmouseover="window.status=\'Remove date\'; return true;" onmouseout="window.status=\'\'; return true;" style="position:relative;left:0;cursor:pointer; cursor:hand"><img src="media/style/'.$theme.'/images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="Remove date" /></a><br /><em>YYYY-MM-DD</em>';
                    $formEntries .= $tooltip.'</div><div style="display:block;height:7px;clear:both;"></div>';
                    return $formEntries;
                }
                
                //-- Make RTE
                function makeRTE($field){
			global $modx;
                    $rte = <<<EORTE
<script language="javascript" type="text/javascript" src="%base%assets/plugins/tinymce3241/jscripts/tiny_mce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript" src="%base%assets/plugins/tinymce3241/xconfig.js"></script>
<script language="javascript" type="text/javascript">
	tinyMCE.init({
		  theme : "advanced",
		  mode : "exact",
		  width : "90%",
		  height : "300",
		  relative_urls : true,
		  //document_base_url : "",
		  remove_script_host : true,
		  language : "en",
		  elements : "{$field}",
		  valid_elements : tinymce_valid_elements,
		  extended_valid_elements : tinymce_extended_valid_elements,
		  invalid_elements : tinymce_invalid_elements,
		  entity_encoding : "named",
		  cleanup: true,
		  apply_source_formatting : true,
		  remove_linebreaks : false,
		  convert_fonts_to_spans : "true",
		  file_browser_callback : "myFileBrowser",
		  external_link_list_url : "%base%assets/plugins/tinymce3241/tinymce.linklist.php",
		  theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,h6,div,blockquote,code,pre,address",
		  plugins : "table,style,advimage,advlink,searchreplace,print,contextmenu,paste,fullscreen,nonbreaking,xhtmlxtras,visualchars,media",
		  theme_advanced_buttons0 : "",
		  theme_advanced_buttons1 : "undo,redo,selectall,separator,pastetext,pasteword,separator,search,replace,separator,nonbreaking,hr,charmap,separator,image,link,unlink,anchor,media,separator,cleanup,removeformat,separator,fullscreen,print,code,help",
		  theme_advanced_buttons2 : "bold,italic,underline,strikethrough,sub,sup,separator,blockquote,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,styleselect,formatselect,separator,styleprops",
		  theme_advanced_buttons3 : "tablecontrols",
		  theme_advanced_buttons4 : "",
		  theme_advanced_toolbar_location : "top",
		  theme_advanced_toolbar_align : "left",
		  theme_advanced_path_location : "bottom",
		  theme_advanced_disable : "",
		  theme_advanced_resizing : false,
		  theme_advanced_resize_horizontal : false,
		  plugin_insertdate_dateFormat : "%Y-%m-%d",
		  plugin_insertdate_timeFormat : "%H:%M:%S",
		  onchange_callback : "myCustomOnChangeHandler",
		  button_tile_map : false 

	});
	function myFileBrowser (field_name, url, type, win) {		
		var cmsURL = '%base%manager/media/browser/mcpuk/browser.php?Connector=%base%manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=%base%&editor=tinymce&editorpath=%base%assets/plugins/tinymce3241';    // script URL - use an absolute path!
		switch (type) {
			case "image":
				type = 'images';
				break;
			case "media":
				break;
			case "flash": 
				break;
			case "file":
				type = 'files';
				break;
			default:
				return false;
		}
		if (cmsURL.indexOf("?") < 0) {
		    //add the type as the only query parameter
		    cmsURL = cmsURL + "?type=" + type;
		}
		else {
		    //add the type as an additional query parameter
		    // (PHP session ID is now included if there is one at all)
		    cmsURL = cmsURL + "&type=" + type;
		}
		
		tinyMCE.activeEditor.windowManager.open({
		    file : cmsURL,
		    width : screen.width * 0.7,  // Your dimensions may differ - toy around with them!
		    height : screen.height * 0.7,
		    resizable : "yes",
		    inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
		    close_previous : "no"
		}, {
		    window : win,
		    input : field_name
		});
		return false;
	}
function myCustomOnChangeHandler() {
	documentDirty = true;
}
</script>
EORTE;
                    return str_replace("%base%",  $modx->config['site_url'], $rte); //$rte;
                }
                
                //-- Check Date
                function isDate($date){
                    if (!isset($date) || $date=="")
                    {
                        return false;
                    }
                   
                    list($yy,$mm,$dd)=explode("-",$date);
                    if ($dd!="" && $mm!="" && $yy!="")
                    {
                        return checkdate($mm,$dd,$yy);
                    }
                   
                    return false;
                }
		
		//-- Mkae reoccuring event list
		function _getRepeatDates($frequencymode='d', $interval=1, $frequency='1',$startDate = '2010-01-11 18:00:00', $endDate = '2010-01-12 19:30:00',$onwd=array(0,1,2,3,4,5,6)){
		    //-- Date Output Format
		    $dateFormat = 'D n-j-Y'; //'Y-m-d h:i a';
		    //-- Time Output Format
		    $timeFormat = 'h:ia';
		    //-- Date Time Display (full=Date+Time,date=Date,time=Time)
		    $dateTimeFormat = 'full';
		    //-- Set Max Occurances not to exceed the end date
		    //$frequency = 365;
		    //-- Set the reoccurance mode (m=Months,d=Days,y=Years,w=Weeks)
		    //$frequencymode = 'w';
		    //-- The span (interval) between reoccurances
		    $interval = (!is_integer($interval)) ? 1 : $interval;
		    //-- Event Start Date
		    //$startDate = '2010-01-11 18:00:00';
		    //-- Event End Date
		    //$endDate = '2010-06-11 19:30:00';
		    //-- Holder of all events
		    $ar_Recur = array();
		    //-- Enable the debugger
		    $debug = false;
		    
		    $theParameter = array('MODE'=>$frequencymode, 'interval'=>$interval, 'frequency'=>$frequency, 'StartDate'=>$startDate, 'EndDate'=>$endDate, 'OnWeedkDay'=>$onwd);
		    if($debug){
			echo "Date repeat function paramters are:<br />";
			foreach($theParameter AS $key=>$val)
				echo $key.'=>'.$val.'<br />';
		    }
		    
		    //-- Check the Date and built the ocurrenc dates
		    //-- prior to PHP 5.1.0 you would compare with -1, instead of false
		    if (($timestamp = strtotime($startDate)) === false) {
			return false;
		    } else {
			SWITCH($frequencymode){
			case "d":
			    while (++$x){
				$occurance = date('Y-m-d H:i:s', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate)) , date('d', strtotime($startDate))+($x*$interval), date('y', strtotime($startDate))) );
				if(strtotime($occurance) <= strtotime($endDate) && $x < $frequency && strtotime($startDate) < strtotime($occurance)){
				    $ar_Recur[] = strftime('%Y-%m-%d', strtotime($occurance));
				    if($debug) echo $occurance."< -is less than (jd->".$jd."jdow->".$jdow.") -> ".$endDate.'<br />';
				}
				else{
				    if($debug) echo $occurance."||-is eq or greater (jd->".$jd."|jdow->".$jdow.") than -||".$endDate.'<br />';
				    break;
				}
			    }
			    break;
			case "m":
			    while (++$x){
				$occurance = date('Y-m-d H:i:s', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate))+($x*$interval) , date('d', strtotime($startDate)), date('y', strtotime($startDate))) );
				if(strtotime($occurance) <= strtotime($endDate) && $x < $frequency && strtotime($startDate) < strtotime($occurance)){
				    $ar_Recur[] = strftime('%Y-%m-%d', strtotime($occurance));
				    if($debug) echo $occurance."< -is less than -> ".$endDate.'<br />';
				}
				else{
				    if($debug) echo $occurance."||-is eq or greater than -||".$endDate.'<br />';
					break;
				}
			    }
			    break;
			case "w":
                            $valid = true;
                            
                            for($x=0;$x < $frequency*7;$x++){
				
                                if($debug) echo "x={$x}<br />";
				$occurance = date('Y-m-d  H:i:s', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate)) , date('d', strtotime($startDate))+(($x*7)*$interval), date('y', strtotime($startDate)) ) );
                                
                                /*** r0.0.6b fix ***/
                                $lastweek=sprintf("%02d", (strftime('%W',strtotime($occurance))-0));
                                if($debug) echo 'Last Week: '.$lastweek."<br />";
                                $year = strftime('%Y',strtotime($occurance));
                                for ($i=1;$i<=7;$i++){
                                    
				    //-- Get occurance day of week int
                                    $thisDOW = strftime('%w',strtotime("+{$i} day",strtotime($occurance)));
				    
                                    //-- Get the valid date formated of occurance
                                    $occDate = strftime('%Y-%m-%d', strtotime("+{$i} day",strtotime($occurance)));
                                    
                                    //-- Check if the date is one of the assigned and less than the end date
                                    if(in_array($thisDOW, $onwd) && strtotime($occDate) <= strtotime($endDate)){
                                        if($debug) echo $occDate." MATCH on $thisDOW <br />";
                                        $ar_Recur[] = $occDate;
                                    } else {
                                        if($debug) echo $occDate."<br />";
                                    }
                                    
                                    //-- If the date is past the end date end the loop
                                    if(strtotime($occDate) >= strtotime($endDate)){
                                        if($debug) echo "\t".strtotime($occDate) .' is greater than '. strtotime($endDate)."<br />";
                                        $valid = false; //-- End the loop
                                        break;
                                    }
                                }
                                if(!$valid) break;
			    }
			    break;    
			case "y":
			    while (++$x){
				$occurance = date('Y-m-d  H:i:s', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate)) , date('d', strtotime($startDate)), date('y', strtotime($startDate))+($x*$interval) ) );
				if(strtotime($occurance) <= strtotime($endDate) && $x < $frequency && strtotime($startDate) < strtotime($occurance)){
				    $ar_Recur[] = strftime('%Y-%m-%d', strtotime($occurance));
				    if($debug) echo $occurance."< -is less than -> ".$endDate.'<br />';
				}
				else{
				    if($debug) echo $occurance."||-is eq or greater than -||".$endDate.'<br />';
				    break;
				}
			    }
			    break;    
			}
			//-- Display the results to validate
                        if($debug){
                            echo "THE OCC DATES:<br />";
                            print_r($ar_Recur);
                        }
			return implode(',', $ar_Recur);
		    }
		}

		function parseTheme($themeFile, $themeArr, $prefix= "[+", $suffix= "+]") {
		    if (!is_array($themeArr)) {
			return $themeArr;
		    }
		    foreach ($themeArr as $key => $value) {
			$themeFile= str_replace($prefix . $key . $suffix, $value, $themeFile);
		    }
		    return $themeFile;
		}
		
		// -- Add mxCalendar CSS and supporting JS
		function _addCSS($str_mxCSS=''){
			global $modx;
			$modx->regClientCSS($str_mxCSS);
		}
		function _addMooJS(){
			global $modx;
			$js_code = '<script src="[(site_url)]manager/media/script/mootools/mootools.js" type="text/javascript"></script>';
			$modx->regClientStartupScript($js_code);
		}
		function _addJS($js_code){
			global $modx;
			$modx->regClientStartupScript($js_code);
		}
		
		//-- Get current configuration version number
		function _getConfigVersion(){
			global $modx;
			$XML = simplexml_load_file($modx->config['base_path']."assets/modules/mxCalendar/config/config.xml");
			return (string)$XML->version;
		}
		
		//-- Get the active theme configuration using SimpleXML reader
		function _getActiveTheme(){
			global $modx;
			//-- Get list of theme files
			$dir = $modx->config['base_path']."assets/modules/mxCalendar/themes";
			$listDir = array();
			$themeOptions = '';
			
			$XML = simplexml_load_file($dir."/".$this->config['mxCalendarTheme']."/theme.xml");
			$themeProperties["name"] = (string)$XML->themename;
			$themeProperties["description"] = (string)$XML->themedescription;
			$themeProperties["themelogo"] = (string)$XML->themelogo;
			$themeProperties["themecss"] = (string)$XML->themecss;
			$themeProperties["authorname"] = (string)$XML->author->name;
			$themeProperties["authorsite"] = (string)$XML->author->siteurl;
			$themeProperties["pubdate"] = (string)$XML->pubdate;
			
			return $themeProperties;
		}
		
		function _makeMessageBox($message='', $isError='0'){
		    return (!empty($message)? '<div class="'.($isError ? 'fm_error_' : 'fm_message').'" style="%19$s">'.$message.'</div>' : '');
		}
		
                //-- Get Webuser GROUPS
                function getWebGroups(){
                    global $modx;
                    $sql = 'SELECT id,name FROM '.$modx->getFullTableName('webgroup_names');
                    $resultArray = $modx->db->makeArray($modx->db->query($sql));
                    
		    $arr_Internal = array();
                    foreach( $resultArray as $p_val ) {
			$this->userWebUserGroups[] = $p_val;
			$arr_Internal[] = $p_val;
                            /*
			    foreach( $p_val as $m_key => $m_val ) {	
                                    $output .= '<strong>' . $m_key . ':</strong> ' . $m_val . '<br />';
                                    if($m_val != 'Super User')
                                        $this->userWebUserGroups[] = $m_val;
				    
                            }
			    */
                    }
		    if($this->debug)
			print_r($this->userWebuserGroups);
		    return $arr_Internal;
                }

        }
}
?>
