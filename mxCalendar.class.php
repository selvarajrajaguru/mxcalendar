<?php
if(!class_exists("mxCal_APP_CLASS")){
	class mxCal_APP_CLASS {
		var $version = '0.1.3b';
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
			    $sql_installer = trim(str_replace('#__', $pre,file_get_contents($modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$this->version.'.upgrade.mysql')));
			    
			    if($sql_installer){
				$cnt = 0;
				foreach(explode('##',$sql_installer) AS $sql){
				    if(!empty($sql)){
					$cnt ++;
					//echo '('.$cnt.')<pre><code>'.$sql.'</pre></code>';
					$result = $modx->db->query($sql);
				    }
				}
				$this->output .= '<div class="fm_message"><h2>'.$this->version.' Update completed</h2><form method="post" action=""><input type="submit" name="submit" value="Continue" /></form></div>';
				//Install Completed
				$modx->logEvent(0, 3, '<p><strong>Upgrade to mxCalendar '.$this->version.' via install file ('.$modx->config['base_path'].'assets/modules/mxCalendar/includes/install/'.$this->version.'.upgrade.mysql)</strong></p>');
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
				foreach(explode('##',$sql_installer) AS $sql){
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
                        $result = $modx->db->select('id,title,description,category,restrictedwebusergroup,link,linkrel,linktarget,location,displayGoogleMap,start,startdate,starttime,end,enddate,endtime,event_occurance,event_occurance_rep,_occurance_properties,lastrepeat,customFields', $modx->getFullTableName($this->tables['events']),'id = '.$_REQUEST['fmeid'] );
                        if( $modx->db->getRecordCount( $result ) ) {
                            $output .= '<ul>';
                            $editArr = $modx->db->getRow( $result );
                        }
                    } else { $editArr = array(); }
					
		    
					$this->output .= '<h1>'.(isset($_REQUEST['fmeid']) ? _mxCalendar_ae_headingEdit.' '.$editArr['title'] : _mxCalendar_ae_headingAdd).'</h1>';
                    
                    
                    //-- Get the custom field type
		    $arr_customFieldTypes = json_decode($this->config['mxcCustomFieldTypes'],true);
			    
                    //-- Create custom field types
                    $dyn_form_output = '';
		    $dyn_form_vals = json_decode($editArr['customFields'], TRUE);
		    $dyn_form_vals = count($dyn_form_vals) ? $dyn_form_vals : $arr_customFieldTypes;
                    if(count($arr_customFieldTypes[0])){
								foreach($dyn_form_vals AS $cft){
									if($cft['name'] && $cft['label']){
									$dyn_form_tpl = '<div class="fm_row"><label>%1$s</label><div><small style="color:blue;">[+mxc%3$s+]</small><br />%2$s</div></div>';
									if($this->debug) $dyn_form_output .= '<pre><code>'.var_dump($cft).'</code></pre>';
									SWITCH($cft['type']){
										case 'text':
											$dyn_form_output .= sprintf(
												$dyn_form_tpl,
												htmlentities($cft['label']),
												sprintf( '<input type="text" name="mxcft_%1$s" value="%2$s">',htmlentities($cft['name']),($cft['val'] ? $cft['val'] : htmlentities($cft['default'])) ),
												$cft['name']
												);
											break;
										case 'datetime':
										case 'date':
										case 'time':
											$dyn_form_output .= sprintf(
														$dyn_form_tpl,
														htmlentities($cft['label']),
														str_replace('tvmxcft_'.$cft['name'],'mxcft_'.$cft['name'],$this->renderRTE($cft['type'],'mxcft_'.$cft['name'],($cft['val'] ? $cft['val'] : $cft['default']),'')),
														$cft['name']
														);
											break;
										case 'image':
											$dyn_form_output .=sprintf(
														$dyn_form_tpl,
														htmlentities($cft['label']),
											'<script type="text/javascript">
												var lastImageCtrl;
												var lastFileCtrl;
												function OpenServerBrowser(url, width, height ) {
													var iLeft = (screen.width  - width) / 2 ;
													var iTop  = (screen.height - height) / 2 ;
					
													var sOptions = \'toolbar=no,status=no,resizable=yes,dependent=yes\' ;
													sOptions += \',width=\' + width ;
													sOptions += \',height=\' + height ;
													sOptions += \',left=\' + iLeft ;
													sOptions += \',top=\' + iTop ;
					
													var oWindow = window.open( url, \'FCKBrowseWindow\', sOptions ) ;
												}			
												function BrowseServer(ctrl) {
													lastImageCtrl = ctrl;
													var w = screen.width * 0.7;
													var h = screen.height * 0.7;
													OpenServerBrowser(\'/modxevo/manager/media/browser/mcpuk/browser.html?Type=images&Connector=/modxevo/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=/modxevo/\', w, h);
												}
												
												function BrowseFileServer(ctrl) {
													lastFileCtrl = ctrl;
													var w = screen.width * 0.7;
													var h = screen.height * 0.7;
													OpenServerBrowser(\'/modxevo/manager/media/browser/mcpuk/browser.html?Type=files&Connector=/modxevo/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=/modxevo/\', w, h);
												}
												
												function SetUrl(url, width, height, alt){
													if(lastFileCtrl) {
														var c = document.cal_form[lastFileCtrl];
														if(c) c.value = url;
														lastFileCtrl = \'\';
													} else if(lastImageCtrl) {
														//alert(\'New Image: \'+lastImageCtrl+\' == URL: \'+url);
														var c = document.cal_form[lastImageCtrl];
														var p = document.getElementById(\'_pv'.$cft['name'].'\').src = \'/modxevo/\'+url;
														if(c) c.value = url;
														lastImageCtrl = \'\';
													} else {
														return;
													}
												}
												</script><input type="text" id="'.$cft['name'].'" name="mxcft_'.$cft['name'].'"  value="'.($cft['val'] ? $cft['val'] : $cft['default']).'"  style="" onchange="documentDirty=true;" />&nbsp;<input type="button" value="Insert" onclick="BrowseServer(\''.$cft['name'].'\')" />
												'.'<label>&nbsp;</label><img name="_pv'.$cft['name'].'" id="_pv'.$cft['name'].'" src="../'.($cft['val'] ? $cft['val'] : $cft['default']).'" alt="" />', $cft['name']);
											break;
										
										case 'select':
											$opt_arr = explode(',', $cft['options']);
											if(is_array($opt_arr) && count($opt_arr)){
												$opts='';
												foreach($opt_arr AS $o){
													$opts .= '<option value="'.$o.'" '.($cft['val'] == $o ? "selected=selected" : '').'>'.$o.'</option>';
												}
												$dyn_form_output .= sprintf(
																$dyn_form_tpl,
																htmlentities($cft['label']),
																'<select name="mxcft_'.$cft['name'].'">'.$opts.'</select>',
																$cft['name']
															   );
											}
											break;
										case 'resource':
											
											$resc_list = $modx->getAllChildren((!empty($cft['default']) && is_numeric($cft['default'])?(int)$cft['default']:0), 'menuindex', 'ASC', 'id, alias, menutitle');
											$opts = '<option value=""></option>';
											foreach($resc_list AS $v){
												$opts .= '<option value="'.$v['id'].'" '.($cft['val'] == $v['id'] ? 'selected="selected"' : '').'>['.$v['id'].'] '.(!empty($v['menutitle']) ? $v['menutitle'] : $v['alias']).'</option>';
											}
											$dyn_form_output .= sprintf(
																$dyn_form_tpl,htmlentities($cft['label']),
																'<select name="mxcft_'.$cft['name'].'" onChange="">'.$opts.'</select>',
																$cft['name']
														   );
											//** Set the UI render to use the resource fields as placeholders for the mxCalendar ?? title override - how to make that happen...
											
											break;
										default:
											//-- Do nothing
											//var_dump($cft);
											break;
									}
									
								}
						}
					}
                    //-- Get language file labels
		    $fm_label = explode(',', _mxCalendar_ae_labels);
                    $fm_columns = $this->get_columns($this->tables['events']);
                    $this->output .= '<form id="fm_bsApp" name="cal_form" method="post" action="">'."\n";
                    if(!empty($dyn_form_output)) $this->output .= '<fieldset><legend>'._mxCalendar_con_mscCustomFieldLegend.'</legend>'.$dyn_form_output.'</fieldset>';
                    $x=0;
		    foreach($fm_columns as $key=>$val){
				
                        //-- List of excluded table columns [DO NOT EDIT]
                        $excluded = array('id','active','start','end', 'repeat', 'event_occurance', '_occurance_wkly', 'event_occurance_rep', 'lastrepeat', '_occurance_properties','lastrepeat','customFields');
                        //-- Make sure it's not an excluded column
                        if(!in_array($val[0], $excluded)){
                            $tooltip = ($this->tooltip[$val[0]]) ? '<img  title="'.$this->tooltip[$val[0]].'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />' : '';
                            SWITCH ($val[1]){
                                case 'text':
                                    if($val[0] == 'description'){
										$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry">'.$this->renderRTE('richtext','fm'.$val[0],$editArr[$val[0]],'').$tooltip.'</div></div>'."\n";
									} else {
										$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="text" name="fm'.$val[0].'" value="'.$editArr[$val[0]].'" />'.$tooltip.'</div></div>'."\n";
									}
                                    break;
                                case 'date':
                                    if($val[0] == 'startdate'){
                                      $editSD = ($editArr['start'] != '0000-00-00 00:00:00' && !empty($editArr['start']) ? strftime('%Y-%m-%d %H:%M', strtotime($editArr['start'])) : strftime('%Y-%m-%d %H:%M')); //$editArr['start'];
									  $editSDF = $editArr['start'];
									  $dateCSSClass = 'mxcStartDate';
                                    }
                                    elseif($val[0]=='enddate'){
										$editSD = ($editArr['end'] != '0000-00-00 00:00:00' && !empty($editArr['end']) ? strftime('%Y-%m-%d %H:%M', strtotime($editArr['end'])) : strftime('%Y-%m-%d %H:%M')); //$editArr['end'];
										$editSDF = $editArr['end'];
										$dateCSSClass = 'mxcEndDate';
                                    }
                                    else{
										$editSD = null;
										$dateCSSClass = '';
                                    }
                                    
				    $advancedDateEntry=$this->config['mxcAdvancedDateEntry'];
				    if($advancedDateEntry){
						$this->output .= "\t<div class=\"fm_row\"><label>".$fm_label[$x]."</label><div class='fm_entry'><input type=\"text\" value=\"".$editSD."\" name=\"fm".$val[0]."\">".$tooltip."</div></div>";
				    } else {
						$this->output .= "\t".$this->_makeDateSelector($val[0], $fm_label[$x], $tooltip, $editSD, $dateCSSClass)."\n";
						$this->output .= "\t</div>"; //-- Fixed broken HTML tags cuasing tabs after Events to not display
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
									
									//-- Temporary Fix for Display Mode for Web User Group
									$param['mxcMgrWUGSelectionMode'] = 'checkbox';
									
									//-- Selection Mode Default Option Values
									$thisWUGDL_defaultCombo = '<option value="" '.( empty($editArr[$val[0]]) ? 'selected=selected' : '' ).'>'._mxCalendar_con_PublicView.'</option>';
									$thisWUGDL_defaultCheckbox = '<input name="fm'.$val[0].'[]" type="checkbox" value="" '.( empty($editArr[$val[0]]) ? 'checked="yes"' : '' ).'>'._mxCalendar_con_PublicView.'<br />';								
									
									//-- Loop through the web user groups and build the selection list
									foreach($this->getWebGroups() AS $group){
										SWITCH($param['mxcMgrWUGSelectionMode']){
											case 'checkbox':
												$selected = (in_array($group['id'], explode(',',$editArr[$val[0]])) ) ? 'checked="yes"' : '';
												$thisWUGDL .= '<input name="fm'.$val[0].'[]" type="checkbox" value="'.$group['id'].'" '.$selected.'>'.$group['name'].'<br />'.PHP_EOL;
												break;
											
											default:
												$selected = (in_array($group['id'], explode(',',$editArr[$val[0]])) ) ? 'selected=selected' : '';
												$thisWUGDL .= '<option value="'.$group['id'].'" '.$selected.'>'.$group['name'].'</option>';
												break;
										}
									}
									$mgrModeHTML = ($param['mxcMgrWUGSelectionMode'] == 'checkbox' ? $thisWUGDL_defaultCheckbox.$thisWUGDL : '<select name="fm'.$val[0].'[]" multiple="multiple">'.$thisWUGDL_defaultCombo.$thisWUGDL.'</select>' );
									$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry">'.$mgrModeHTML.$tooltip.'</div></div>'."\n";

									
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
            </select>
	</div>';
	$this->output .= "\t".$this->_makeDateSelector('event_occur_until',
						       $fm_label[(count($fm_label)-1)],
						       $tooltip,
						       ($editArr['lastrepeat'] != '0000-00-00 00:00:00' && !empty($editArr['lastrepeat']) ? date('Y-m-d', strtotime($editArr['lastrepeat']) ) : ''),
						       ''
						      ).'<img  title="'.$this->tooltip['event_occurance_rep'].'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />'."\n";
	//$this->output.='</fieldset>'."\n";
	$this->output.='</div>'."\n";
		    
		    $fmeid = ($_REQUEST['fmeid']) ? '<input type="hidden" id="fmeid" name="fmeid" value="'.$_REQUEST['fmeid'].'">' : '';
                    $this->output .= "\t".'</fieldset><div class="fm_row"><div class="fm_actions">
                                        <input type=\'submit\' name=\'fmaction\' value=\'Cancel\' onclick="document.cookie=\'webfxtab_tabPanel=0;path=/;\'">
					
                                        <input type="submit" name="fmaction" value="'.(!empty($_REQUEST['fmeid']) ? _mxCalendar_btn_updateEvent : _mxCalendar_btn_addEvent).'" />
                                        '.$fmeid.'
                                      </div></div>'."\n";
                    $this->output .= '</form>'."\n";
                   
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
			
			//-- Create the array to hold all custom form element values
			$dyn_kv = array();
			//-- Create a default array to ensure all property values are held
			$dyn_kv_default = array('label'=>'', 'name'=>'','type'=>'','options'=>'','default'=>'','required'=>'false');
			
			//-- Save new configuration settings on update
			if($this->params['action'] == 'updateConfig'){
				foreach($_POST as $k=>$v){
					$fields=array('value'=>$v);
					//-- Only update if it's a number as it's a direct relationship to the record ID
					if(is_int($k)) $modx->db->update( $fields, $modx->db->config['table_prefix'].$this->tables['config'], "id=".$k);
					
					//-- Check if the field is a custom form element
					if(substr($k,0,6) == 'mxcft_'){
						$exp = explode('_',$k);
							//-- Sample form name "mxcft_default_2"
							if(count($dyn_kv[$exp[2]]) < 6 )
								$dyn_kv[$exp[2]] = $dyn_kv_default;
							$dyn_kv[$exp[2]][$exp[1]]=$v;

						
					}
				}
				$dyn_kv=array_filter($dyn_kv);
				//-- Update custom field entry
				if(count($dyn_kv)) {
					$dyn_nf = json_encode($dyn_kv);
					//$fields = array('value'=>'[{"label":"Performer","name":"performer","type":"select","options":"Atrist One, Next Artist, So on","default":"Enter text","required":"false"},{"label":"Ticket","name":"ticket","type":"text","options":"","default":"00.00","required":"false"},{"label":"Event Image","name":"eventimage","type":"image","options":"","default":"","required":"false"},{"label":"Event Landing Page","name":"landingpage","type":"resource","options":"","default":"","required":"false"}]');
					$fields = array('value'=>$dyn_nf);
					$modx->db->update( $fields, $modx->db->config['table_prefix'].$this->tables['config'], "param='mxcCustomFieldTypes'");
				}
			
			if($this->debug){
				echo '<strong>Submitted values:</strong><br />';
				print_r($_REQUEST);
				echo '<br /><br />';
				
				echo '<strong>New Save JSON:</strong><br />';
				echo $dyn_nf;
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
				
			//-- Create dynamic field types
			$configCustFields = json_decode($myConfig['mxcCustomFieldTypes'][1],true);
			if(count($configCustFields))
				$arr_mxcustomFieldTypes = array_merge(json_decode($myConfig['mxcCustomFieldTypes'][1],true)	, array(array("label"=>"","name"=>"","type"=>"text","options"=>"","default"=>"","required"=>"false")));
			else
				$arr_mxcustomFieldTypes = array(array("label"=>"","name"=>"","type"=>"text","options"=>"","default"=>"","required"=>"false"));
				
			$dyn_form_output = '';
			$dyn_form_output_tpl = '
				<table>
					<tr>
						%1$s
					</tr>
					
					%2$s
					
					<tr colspan="'.count(array_keys($arr_mxcustomFieldTypes)).'">
						<td>%3$s</td>
					</tr>
				</table>
			';
			//-- Create a dynamic list of the column headings for the custom field type
			$dyn_form_output_h = '';
			foreach(array_keys($arr_mxcustomFieldTypes) AS $k=>$v){
				//$dyn_form_output_h .= '<th>'.$v.'</th>'.PHP_EOL;
			}
			$uid_cft = 0;
			$empty_set_cnt=0;
			//-- Create the row with values for each custom field type
			foreach($arr_mxcustomFieldTypes AS $cft){
				$arr_cnt=array_values(array_filter($cft));
				$is_valid_set = ((count($arr_cnt)==2 && $empty_set_cnt==0) || count($arr_cnt)>2) ? true : false;
				if((count($arr_cnt)==2 && $empty_set_cnt==0)){
					$empty_set_cnt++;
				}
				//-- DEBUG FOR EMPTY RECORDS FOR ADDIGN/REMOVING Custom Fields
				if($this->debug) echo "Arr cnt: ".count($arr_cnt).' Empty Set Cnt: '.$empty_set_cnt.'<br />';
				
				if($is_valid_set){
					$dyn_form_output .= '<tr valign=top>';
					$cft_type=$cft['type'];
					foreach($cft AS $k=>$v){
						if($uid_cft == (int)0) $dyn_form_output_h .= '<th>'.strtoupper($k).'</th>'.PHP_EOL;
						$dyn_form_output .= '<td>';
						SWITCH($k){
							default:
								$dyn_form_output .= sprintf( '<input type="text" name="mxcft_%1$s_'.$uid_cft.'" value="%2$s">',$k,$v );
								break;
							case 'options':
								if($cft['type'] == 'select' || $cft['type'] == 'resource')
								$dyn_form_output .= sprintf( '<input type="text" id="mxcft_'.$uid_cft.'" name="mxcft_%1$s_'.$uid_cft.'" value="%2$s">',$k,$v );
								else
								$dyn_form_output .= sprintf( '<input style="visibility:hidden;" type="text" id="mxcft_'.$uid_cft.'" name="mxcft_%1$s_'.$uid_cft.'" value="%2$s">',$k,$v );;
								break;
							case 'type':
								$arr_valid_form_types = array('text', 'image', 'datetime', 'date', 'time', 'select', 'resource');
								$select_types='';
								foreach($arr_valid_form_types AS $t){
									$onChange_to_select = ' onChange="if(this.value == \'select\'||this.value == \'resource\'){ document.getElementById(\'mxcft_'.$uid_cft.'\').style.visibility=\'visible\';}else{document.getElementById(\'mxcft_'.$uid_cft.'\').style.visibility=\'hidden\';}"';
									$select_types .= '<option value="'.$t.'" '.($t == $v ? 'selected="selected"' : '').'>'.$t.'</option>';
								}
								$dyn_form_output .= sprintf( '<select name="mxcft_%1$s" '.$onChange_to_select.'>%2$s</select>', $k.'_'.$uid_cft, $select_types);
								
								//-- Sub set for the select to add the options:
								if($v == 'select'){
									
									//$dyn_form_output .= '<br /><label>Options: <small>Comma list</small></label><input type="text" name="mxcft_'.$k.'_'.$uid_cft.'_list" value="'.$v.'">';
								}
								break;
							case 'required':
								$dyn_form_output .= '<input type="checkbox" name="mxcft_'.$k.'_'.$uid_cft.'" '.($v == 'on' ? 'checked' : '').'>';
								break;
						}
						//-- Show the placeholder value for insertion in chunks/templates
						if($k == 'name'|$k == 'label'){
							$dyn_form_output .= '<small>Placeholder: <font color="blue">[+mxc'.($k=='name'?$v:$cft['name']).($k == 'label' ? '-label' :'').'+]</font></small>';
						}
						$dyn_form_output .= '</td>';
					}
					$dyn_form_output .= '</tr>';
					$uid_cft++;
				} else {
					$empty_set_cnt++;
				}

			}
			$dyn_form_list = sprintf($dyn_form_output_tpl, $dyn_form_output_h, $dyn_form_output,'<!-- <button name="action" value="save">-- save --</button>-->');
				
				
			$langDOW = explode(',', _mxCalendar_cl_headinWeekDays);
			$this->output .= '
			<table width="750">
			<tr>
			<td width="50%" valign="top">
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
					<select name="'.$myConfig['mxcAdvancedDateEntry'][0].'" id="mt_'.$myConfig['mxcAdvancedDateEntry'][0].'">
						<option value="0" '.($myConfig['mxcAdvancedDateEntry'][1] == 0 ? 'selected=selected':'').'>False</option>
						<option value="1" '.($myConfig['mxcAdvancedDateEntry'][1] == 1 ? 'selected=selected':'').'>True</option>
					</select>
					<img  title="'._mxCalendar_con_mxcAdvancedDateEntryTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>
				
				<div class="fm_row"><label>'._mxCalendar_con_mxcJSCodeLibrary.'</label>
					<select id="mt_'.$myConfig['mxcJSCodeLibrary'][0].'" name="'.$myConfig['mxcJSCodeLibrary'][0].'">
						<option value="0" '.(!$myConfig['mxcJSCodeLibrary'][1] ? 'selected=selected' : '').' >'._mxCalendar_gl_btnDisable.'</option>
						<option value="1" '.($myConfig['mxcJSCodeLibrary'][1] == 1 ? 'selected=selected' : '').'>MooTools<option>
						<option value="2" disabled>JQuery</option>
					</select>
					<img  title="'._mxCalendar_con_mxcJSCodeLibraryTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
				</div>
				<div class="fm_row" id="mt_'.$myConfig['mxcJSCodeSource'][0].'"><label>'._mxCalendar_con_mxcJSCodeSource.'</label>
					<input type="text" id="mv_'.$myConfig['mxcJSCodeSource'][0].'" name="'.$myConfig['mxcJSCodeSource'][0].'" value="'.$myConfig['mxcJSCodeSource'][1].'">
					<img  title="'._mxCalendar_con_mxcJSCodeSourceTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" />
					<div class="clear "><small>
						<a href="#" onClick="document.getElementById(\'mv_'.$myConfig['mxcJSCodeSource'][0].'\').value=\'manager/media/script/mootools/mootools.js\';return false;">'._mxCalendar_con_mxcJSCodeSourceDefaultLocal.'</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						<a href="#" onClick="document.getElementById(\'mv_'.$myConfig['mxcJSCodeSource'][0].'\').value=\'http://ajax.googleapis.com/ajax/libs/mootools/1.1/mootools.js\';return false;">'._mxCalendar_con_mxcJSCodeSourceDefaultGoogle.'</a></small></div>
				</div>
				    <script type="text/javascript" src="../assets/modules/mxCalendar/scripts/Fx.Slide.js"></script>
				<script>
				window.addEvent(\'domready\', function(){
					document.getElementById("mt_'.$myConfig['mxcJSCodeSource'][0].'").style.display="'.(!$myConfig['mxcJSCodeLibrary'][1] ? 'none' : 'block').'";
					document.getElementById("mt_'.$myConfig['mxcJSCodeLibrary'][0].'").onchange = function () {
						if(this.value == "1"){
							document.getElementById("mt_'.$myConfig['mxcJSCodeSource'][0].'").style.display="block";
						} else {
							document.getElementById("mt_'.$myConfig['mxcJSCodeSource'][0].'").style.display="none";
						}
						
					};

				});
				</script>
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

				<div class="fm_row"><label>'._mxCalendar_con_mxcMonthInnerHeadingRowID.'</label><input name="'.$myConfig['mxcMonthInnerHeadingRowID'][0].'" value="'.$myConfig['mxcMonthInnerHeadingRowID'][1].'"><img  title="'._mxCalendar_con_mxcMonthInnerHeadingRowIDTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcMonthInnerHeadingRowClass.'</label><input name="'.$myConfig['mxcMonthInnerHeadingRowClass'][0].'" value="'.$myConfig['mxcMonthInnerHeadingRowClass'][1].'"><img  title="'._mxCalendar_con_mxcMonthInnerHeadingRowClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcMonthListTodayOnly.'</label><select name="'.$myConfig['mxcMonthListTodayOnly'][0].'"><option value="1" '.($myConfig['mxcMonthListTodayOnly'][1] == 1 ? 'selected=selected' : '').'>True</option><option value="0" '.($myConfig['mxcMonthListTodayOnly'][1] == 0 ? 'selected=selected' : '').'>False</option></select><img  title="'._mxCalendar_con_mxcMonthListTodayOnlyTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcMonthHasEventClass.'</label><input type="input" name="'.$myConfig['mxcMonthHasEventClass'][0].'" value="'.$myConfig['mxcMonthHasEventClass'][1].'" /><img  title="'._mxCalendar_con_mxcMonthHasEventClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mxcMonthNoEventClass.'</label><input type="input" name="'.$myConfig['mxcMonthNoEventClass'][0].'" value="'.$myConfig['mxcMonthNoEventClass'][1].'" /><img  title="'._mxCalendar_con_mxcMonthNoEventClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				
				<!-- 0.1.3b -->
				<div class="fm_row"><label>'._mxCalendar_con_mxcMonthEventItemClass.'</label><input name="'.$myConfig['mxcEventMonthUrlClass'][0].'" value="'.$myConfig['mxcEventMonthUrlClass'][1].'"><img  title="'._mxCalendar_con_mxcMonthEventItemClassTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mscMonthEventItemStyle.'</label><input name="'.$myConfig['mxcEventMonthUrlStyle'][0].'" value="'.$myConfig['mxcEventMonthUrlStyle'][1].'"><img  title="'._mxCalendar_con_mscMonthEventItemStyleTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mscMonthEventItemStartDate.'</label><input name="'.$myConfig['mxcEventDetailStateDateStamp'][0].'" value="'.$myConfig['mxcEventDetailStateDateStamp'][1].'"><img  title="'._mxCalendar_con_mscMonthEventItemStartDateTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mscMonthEventItemStartTime.'</label><input name="'.$myConfig['mxcEventDetailStateTimeStamp'][0].'" value="'.$myConfig['mxcEventDetailStateTimeStamp'][1].'"><img  title="'._mxCalendar_con_mscMonthEventItemStartTimeTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mscMonthEventItemEndDate.'</label><input name="'.$myConfig['mxcEventDetailEndDateStamp'][0].'" value="'.$myConfig['mxcEventDetailEndDateStamp'][1].'"><img  title="'._mxCalendar_con_mscMonthEventItemEndDateTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>
				<div class="fm_row"><label>'._mxCalendar_con_mscMonthEventItemEndTime.'</label><input name="'.$myConfig['mxcEventDetailEndTimeStamp'][0].'" value="'.$myConfig['mxcEventDetailEndTimeStamp'][1].'"><img  title="'._mxCalendar_con_mscMonthEventItemEndTimeTT.'" src="'.$modx->config['base_url'].'manager/media/style/'.$modx->config['manager_theme'].'/images/icons/information.png" class="Tips1" /></div>

				
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
			
			<tr>
				<td colspan=2>
				<fieldset><legend>Custom Field(s)</legend>'.$dyn_form_list.'</fieldset>
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
                    /** Depreciated 0.1.0-rc
		      $startValuesSplit = explode(' ', strftime("%Y-%m-%d %H:%M",strtotime($param['fmstartdate'])));
                      $endValuesSplit = explode(' ', strftime("%Y-%m-%d %H:%M",strtotime($param['fmenddate'])));
		    **/
		    
		    if($this->debug){
		    print_r($param);
		    echo "<br /><br />";
		    }
		    
		    $dyn_config_opts = json_decode($this->config['mxcCustomFieldTypes'],true);
		    $dyn_config = array();
		    if(count($dyn_config_opts)){
				foreach($dyn_config_opts AS $cft){
				$dyn_config[$cft['name']] = array('name'=>$cft['name'], 'label'=>$cft['label'],'options'=>$cft['options'],'default'=>$cft['default'],'type'=>$cft['type']);
				}
		    }
		    //-- Title Override on Save of Resource if no title is defined in mxCalendar we use the resource title
		    $rescTitle = '';
		    
		    $dyn_form_vals = array();
		    foreach($param AS $k=>$v){
			if(substr($k, 0, 6) == 'mxcft_' ){
				$label = str_replace('mxcft_', '', $k);
				//$dyn_form_vals[$label]=$v;
				$dyn_form_vals[$label]=array('val'=>$v, 'name'=>$dyn_config[$label]['name'], 'type'=>$dyn_config[$label]['type'],'label'=>$dyn_config[$label]['label'],'options'=>$dyn_config[$label]['options'], 'default'=>$dyn_config[$label]['default']);
				if($dyn_config[$label]['type'] == 'resource'){
					//-- Get predefined document values to use in mxCalendar
					$array_doc = $modx->getPageInfo((int)$v,1,'pagetitle');
					if($array_doc['pagetitle']){
						$rescTitle=$array_doc['pagetitle'];
					} else {
						//-- Check now for unpublished doc
						$array_doc = $modx->getPageInfo((int)$v,0,'pagetitle');
						$rescTitle=$array_doc['pagetitle'];
					}
				}
			}
		    }
		    $dyn_form_vals = json_encode($dyn_form_vals);
		    
		    //-- Create @param for entry
		    $mxcEventTitle = trim($param['fmtitle']);
		    $mxcEventTitle = ((!empty($mxcEventTitle) && $mxcEventTitle != '')?$mxcEventTitle:$rescTitle);
                    $sT = $modx->db->escape(htmlentities( $mxcEventTitle ));
                    $sD = $modx->db->escape($param['fmdescription']);
                    $sC = $modx->db->escape($param['fmcategory']);
		    $sWG = $modx->db->escape(implode(',',$_POST['fmrestrictedwebusergroup']));
                    $sL = $modx->db->escape($param['fmlink']);
                    $sLR = $modx->db->escape($param['fmlinkrel']);
                    $sLT = $modx->db->escape($param['fmlinktarget']);
		    $sLoc = $modx->db->escape($param['fmlocation']);
                    /** Depreciated 0.1.0-rc
		     * $sSD = $startValuesSplit[0]; //--Start Date stamp
                     * $sST = $startValuesSplit[1]; //--Start Time stamp
                     * $sED = $endValuesSplit[0];	 //--End Date stamp
                     * $sET = $endValuesSplit[1];   //--End Time stamp
		     **/
                    
                    $table_name = $modx->getFullTableName( $this->tables['events'] );

		    $repOccOn=array();
		    if(count($param['fmevent_occurance_on'])){
		    foreach($param['fmevent_occurance_on'] AS $rep)
			$repOccOn[] = $rep[0];
		    }
			
		    $ar_Events = $this->_getRepeatDates($param['fmevent_occurance'], (int)$param['fmevent_occurance_rep'],365, $param['fmstartdate'],( !empty($param['fmevent_occur_until']) ? $param['fmevent_occur_until'] : $param['fmenddate']), $repOccOn);

			$reOcc = $ar_Events;
		    $last_reOcc = explode(',', $ar_Events);
		    $last_reOcc = (count($last_reOcc) ? $last_reOcc[count($last_reOcc)-1] : NULL);
		    if($this->debug) print("Reoccur Date<br />".$ar_Events);
		    
		    $str_fmStartDate = $param['fmstartdate'];
		    $str_fmEndDate = ($param['fmenddate'] ? $param['fmenddate'] : $param['fmstartdate']);
		    
		    //-- Depreciated as of 0.1.0-rc1
		    //-- $str_fmStartDate = $param['fmstartdate'].' '.$param['startdate_htime'].':'.$param['startdate_mtime'].$param['startdate_apm'];
		    //-- Depreciated as of 0.1.0-rc1
		    //-- $str_fmEndDate = $param['fmenddate'].' '.$param['enddate_htime'].':'.$param['enddate_mtime'].$param['enddate_apm'];
		
		//-- Check for advanced date entry format
		//-- Depreciated as of 0.1.0-rc1
		//-- $str_fmStartDate = ( $this->config['mxcAdvancedDateEntry'] ? $param['fmstartdate'] : $str_fmStartDate);
		//-- Depreciated as of 0.1.0-rc1
		//-- $str_fmEndDate = ( $this->config['mxcAdvancedDateEntry'] ? $param['fmenddate'] : $str_fmEndDate);		    
		
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
                                    'start'	 => strftime("%Y-%m-%d %H:%M" , strtotime($str_fmStartDate)), 
                                    'startdate'	 => strftime('%Y-%m-%d' , strtotime($str_fmStartDate)),
                                    'starttime'  => strftime('%H:%M:%S',strtotime($str_fmStartDate)),
                                    'end'	 => strftime('%Y-%m-%d %H:%M' , strtotime($str_fmEndDate)),
                                    'enddate'    => strftime('%Y-%m-%d' , strtotime($str_fmEndDate)),
                                    'endtime'    => strftime('%H:%M:%S' , strtotime($str_fmEndDate)),
				    '`repeat`'	 => $reOcc,
				    'lastrepeat' => ($param['fmevent_occur_until'] || !empty($param['fmevent_occur_until']) ? $param['fmevent_occur_until'] : '') , //$last_reOcc,
				    'event_occurance' => $modx->db->escape($param['fmevent_occurance']),
				    '_occurance_wkly' => $modx->db->escape($param['fm_occurance_on']),
				    'event_occurance_rep' => $modx->db->escape((int)$param['fmevent_occurance_rep']),
				    '_occurance_properties' => implode(',',$repOccOn),
				    'customFields' => $dyn_form_vals
                                    );
		    
					if($this->debug) print_r($fields);
                    if($method == _mxCalendar_btn_addEvent){
                        $NID = $modx->db->insert( $fields, $table_name);
                        if($NID) $_POST = array();
                    } else {
                        $result = $modx->db->update( $fields, $table_name, 'id='.$_POST['fmeid']);
                        $NID = $param['fmeid'];
                        $_POST = array();
                    }
                    return "($NID) $sT :: $result";
                }
                
                //*************************//
                //**** Front End Views ****//
                //*************************//
                function MakeCalendar($params=array()){
                    global $modx;
		    if(!empty($this->config['mxcLocalization']))
		    $thisLocal = setlocale(LC_ALL, $this->config['localization']);

                    $defaultParam = array(
                                   'mxcType'=>'full',
				   'mxcDefaultCatIdLock'=>Null
                                  );
                    $param = array_merge($defaultParam, $this->config, $params);
                    
                    if(($param['mxcType']=='full' & empty($_REQUEST['details'])  & $param['mxcTypeLocked'] != true ) || (!isset($param['mxcType']) & empty($_REQUEST['details'])  & $param['mxcTypeLocked'] != true ))
                        //-- DISPLAY FULL CALENDAR
			include_once 'includes/calendar.inc.php';
		    elseif(!empty($_REQUEST['details'])  & $param['mxcTypeLocked'] != true )
                        //-- DISPLAY EVENT DETAIL VIEW
			return $this->MakeEventDetail((int)$_REQUEST['details'],$param);
                    else
                        //-- DISPLAY EVENT LIST VIEW
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
		    
		    //-- OS Type check to switch varible for strftime()
		    //$OS = $_SERVER['SERVER_SOFTWARE'];
		    //echo '(#1038) OS TYPE => '.$OS.'<br />';
		    
		    
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
					//-- Strikeout past date times
					$datetime_now = date(_mxCalendar_ed_dateformat);
					$str_dates = '';
					foreach($dateList AS $d){
						$str_dates .= ($d > $datetime_now) ? '<li style="text-decoration:line-through;color:red;">'.$d.'</li>' : $d;
					}
					$str_repeatDates = ((count($dateList) && $this->config['eventlist_multiday']) ? "<span ='mxcRepeatEventItem'>".$str_dates.implode('<br />',$dateList)."</span>" : '');
					
					//-- Display only the selected event occurance date/time value
					$str_repeatDates =  $dates[$param['r']];
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
					    //-- Split addresses for multiple points on the map
					    $addressList = explode('|', $p_val['location']);
					    
					    $mygeoloc = new geoLocator;
					    $mygeoloc->host = $this->config['GOOGLE_MAP_HOST'];
					    $mygeoloc->apikey = $this->config['GOOGLE_MAP_KEY'];
					    $mygeoloc->canvas = $this->config['mxcGoogleMapDisplayCanvasID'];
					    $mygeoloc->autofitmap = (count($addressList) > 1 ? true : false);
					    
					    foreach($addressList as $loc){
						$mygeoloc->getGEO($loc);
					    }

						$googleMap='';
						//-- Build Google MAP JS Section
						if($param['ajaxPageId'] != $modx->documentIdentifier && (int)$param['ajaxPageId']!==0){
							$googleMap = '<div id="'.$this->config['mxcGoogleMapDisplayCanvasID'].'" style="width: '.$this->config['mxcGoogleMapDisplayWidth'].'; height: '.$this->config['mxcGoogleMapDisplayHeigh'].'"><img src="/blank.gif" alt="" onload="initialize();" /></div>';
						if($this->config['mxcGoogleMapDisplayLngLat'])
							$googleMap .= $mygeoloc->output;
							$this->_addGoogleMapJS($mygeoloc->mapJSv3, true);
						}
						else {
							$googleMap = '<div id="'.$this->config['mxcGoogleMapDisplayCanvasID'].'" style="width: '.$this->config['mxcGoogleMapDisplayWidth'].'; height: '.$this->config['mxcGoogleMapDisplayHeigh'].';"><img src="/blank.gif" alt="" onload="initialize();" /></div>';
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
				$modx->setPlaceholder('mxcEventDetailStartDateTime',strftime((isset($param['mxcStartDateFormat']) ? $param['mxcStartDateFormat'] : _mxCalendar_ed_start_dateformat),strtotime( (isset($_REQUEST['r']) && $_REQUEST['r']!="" ? $dates[$_REQUEST['r']].' '.$p_val['starttime'] : $p_val['start']) )));
				$modx->setPlaceholder('mxcEventDetailEndDateTime',strftime((isset($param['mxcEndDateFormat']) ? $param['mxcEndDateFormat'] : _mxCalendar_ed_end_dateformat),strtotime( (isset($_REQUEST['r']) && $_REQUEST['r']!="" ? $dates[$_REQUEST['r']].' '.$p_val['endtime'] : $p_val['end']) )));
				$modx->setPlaceholder('mxcEventDetailDateTimeReoccurrences',$str_repeatDates);
				$modx->setPlaceholder('mxcEventDetailLabelLocation',($p_val['location']?($this->config['mxcEventDetailLabelLocation']? $this->config['mxcEventDetailLabelLocation'] :_mxCalendar_ed_location):''));
				$modx->setPlaceholder('mxcEventDetailLocation',str_replace('|','<br />',$p_val['location']));
				$modx->setPlaceholder('mxcEventDetailDescription',$p_val['description']);
				$modx->setPlaceholder('mxcEventDetailGoogleMap',$googleMap);
				
				
				/** START THE CUSTOM FIELDs **/
				$EventArr_cft = array();
				$cft_event = json_decode($p_val['customFields'],true);
				$dyn_config_opts = json_decode($this->config['mxcCustomFieldTypes'],true);
				$dyn_resource_opts = array();
				//-- Grab the "resource" field type to get the TV's that should be used
				foreach($dyn_config_opts AS $cft){
					$cft_type=$cft['type'];
					if($cft_type == 'resource'){
					    $dyn_resource_opts[$cft['name']]=$cft['options'];
					}
				} //-- end loop of custom field types
	
				//-- Loop through the custom fields
				if(count($cft_event)){
				    foreach($cft_event AS $l=>$v){
					switch($v['type']){
					    default:
						$modx->setPlaceholder('mxc'.$l, $v['val']);
						break;
					    case 'image':
						$modx->setPlaceholder('mxc'.$l, '<img src="'.$v['val'].'" alt="" />');
						break;
					    case 'resource':
						//-- Get the TV's as set in the options for the resource in the configuration tab of mxCalendar
						if(!empty($dyn_resource_opts[$l])){
						    $tvVals = $modx->getTemplateVarOutput(explode(',',$dyn_resource_opts[$l]),(int)$v['val'],1);
						    if(count($tvVals) && is_array($tvVals)){
								foreach ($tvVals AS $k=>$tvVal){
								$modx->setPlaceholder('mxc'.$k,  $tvVal);
								}
							}
						}
						//-- Get predefined document values to use in mxCalendar
						$array_doc = $modx->getPageInfo((int)$v['val'],1,'pagetitle, description, alias, content');
						$modx->setPlaceholder('mxcpagetitle', $array_doc['pagetitle']);
						$modx->setPlaceholder('mxcdescription', $array_doc['description']);
						$modx->setPlaceholder('mxcalias', $array_doc['alias']);
						$modx->setPlaceholder('mxccontent', $array_doc['content']);
						$modx->setPlaceholder('mxc'.$l,  $v['val']);
						break;
					}
					
				    }
				}
				/** END THE CUSTOM FIELDs **/
				
                        // event date start
                        $modx->setPlaceholder('mxcEventDetailStateDateStamp', ($param['mxcEventDetailStateDateStamp'] ? strftime($param['mxcEventDetailStateDateStamp'],strtotime($p_val['start'])) : $p_val['start']));
                        // event time end
                        $modx->setPlaceholder('mxcEventDetailStateTimeStamp', ($param['mxcEventDetailStateTimeStamp'] ? strftime($param['mxcEventDetailStateTimeStamp'],strtotime($p_val['start'])) : $p_val['start']));
                        // event date end
                        $modx->setPlaceholder('mxcEventDetailEndDateStamp', ($param['mxcEventDetailEndDateStamp'] ? strftime($param['mxcEventDetailEndDateStamp'],strtotime($p_val['end'])) : $p_val['end'] ));
                        // event time end
                        $modx->setPlaceholder('mxcEventDetailEndTimeStamp', ($param['mxcEventDetailEndTimeStamp'] ? strftime($param['mxcEventDetailEndTimeStamp'],strtotime($p_val['end'])) : $p_val['end'] ));
				
				}//end loop
			    }
				if(!empty($this->config["mxCalendarTheme"])){
				    $activeTheme = $this->_getActiveTheme();
				    $this->_addCSS('<link rel="stylesheet" type="text/css" href="assets/modules/mxCalendar/themes/'.$this->config['mxCalendarTheme'].'/'.$activeTheme["themecss"].'" /> ');
				}
			    
			    if($param['mxcAddMooJS'] || $param['mxcJSCodeLibrary']) $this->_addMooJS();
			    
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
					};
					
					window.onload = initialize;
 
				</script>';
			else   $modx->regClientStartupScript('
				<script type="text/javascript">
					function initialize() {
					  '.$jsCode.'
					}
					function loadScript() {
					  var script = document.createElement("script");
					  script.type = "text/javascript";
					  script.src = "http://maps.google.com/maps/api/js?sensor=false&callback=initialize";
					  document.body.appendChild(script);
					}
					
					window.onload = loadScript;
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
					$month=strftime("%m", strtotime($e['start']));
					$day=$datePieces[2];
					$mxcStartDateFilter = isset($mxcStartDate) ? strftime('%Y-%m-%d', strtotime($mxcStartDate)) : strftime('%Y-%m-%d');
					if(strftime('%Y-%m-%d', strtotime($e['start'])) >= $mxcStartDateFilter){
						if($e['DurationDays']) $e['end']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d ',$e['start']).'23:59'));
						$ar_events[]=$e;
					}
					$or = $e;
					
					if($e['DurationDays']){
						$originalStartDay = $e['start'];
						$originalEndDay = $e['end'];
						for($x=1;$x<=$e['DurationDays'];$x++){
							$newOccDate = strtotime("+ $x day", strtotime($originalStartDay));
							//$e['start']=strftime('%Y-%m-%d', mktime(0, 0, 0, strftime('%m', $newOccDate) , strftime('%d', $newOccDate), strftime('%Y', $newOccDate)) );
							//-- Adjust the starttime and endtime if part of the sequence
							if($x<$e['DurationDays']){
								$e['start']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d ',$newOccDate).'00:00'));
								$e['end']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d ',$newOccDate).'23:59'));
							}elseif($x==$e['DurationDays']){
								$e['start']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d ',$newOccDate).'00:00'));
								$e['end']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d ',$newOccDate). $e['endtime'] ));
							}else{ //-- Is the first occurance
								$e['start']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d %H:%M',$newOccDate)));
								$e['end']=strftime('%Y-%m-%d %H:%M', strtotime(strftime('%Y-%m-%d ',$newOccDate).strftime(' %H:%M', $e['end'])));
							}

							if(strftime('%Y-%m-%d', $newOccDate) >= strftime('%Y-%m-%d'))
							$ar_events[]=$e;
							if($this->debug){
								echo "Start: ".$originalStartDay." => <br />&nbsp;&nbsp;".$x ." of ".$e['DurationDays'].": ".strftime('%Y-%m-%d %H:%M',$newOccDate)."<br />";
								echo 'Original:<br /><pre><code>'.print_r($or).'</code></pre>';
								
							}
						}
						
					}
					if(!empty($e['repeat'])){
					    $sub_dates = explode(',',$or['repeat']);
					    $rcnt='0';
					    foreach($sub_dates as $child_event){
						    $e['start']=$child_event;
						    if(strftime('%Y-%m-%d', strtotime($e['start'])) >= strftime('%Y-%m-%d'))
						    {
								$e['repeatID'] = $rcnt;
								$ar_events[]=$e;
								if($e['DurationDays']){
									for($x=1;$x<=$e['DurationDays'];$x++){
										$e['start']=strftime('%Y-%m-%d', mktime(strftime('%H', strtotime($child_event)), date('i', strtotime($child_event)), 0, date('m', strtotime($child_event)) , date('d', strtotime($child_event))+$x, date('y', strtotime($child_event))) );
										//$ar_events[]=$e;
									}
									
								}
						    }
						    $rcnt++;
					    }
					}
				}
				//-- Sort the results by start date
				$ar_events = $this->multisort($ar_events,'start','description','title','end','location','eid','link','linkrel','linktarget','repeatID','customFields');
			} else {
			    foreach( $records as $event ) {
				$ar_events[] = $event;
			    }
			}

			//-- Add MoodalBox if mxcAjaxPageId is set
			if(!empty($param['mxcAjaxPageId']) != $modx->documentIdentifier){

			}
			
			//-- Loop through the new sorted list of events
			$evCnt=0;
			foreach ($ar_events as $event){
                            //-- Event template @param chunk array
                            $datePieces = explode("-", $event['startdate']);
                            $month=strftime("%b", strtotime($event['start']));
                            $day=$datePieces[2];

			    //-- Set the URL for the event title
			    $mxcEventDetailURL = (is_numeric((int)$param['mxcAjaxPageId']) && !empty($param['mxcAjaxPageId']) && $param['mxcAjaxPageId'] != $modx->documentIdentifier ? $modx->makeUrl((int)$param['mxcAjaxPageId'],'', '&details='.$event['eid'].(is_numeric($event['repeatID']) ? '&r='.$event['repeatID'] : ''), 'full') : $modx->makeUrl((int)$param['mxcFullCalendarPgId'],'','details='.$event['eid'].(is_numeric($event['repeatID']) ? '&r='.$event['repeatID'] : '') ));
			    $mxcEventDetailAJAX = ($param['mxcAjaxPageId'] != $modx->documentIdentifier ? 'moodalbox' : 'moodalbox ');
			    if((bool)$param['mxcEventListTitleLink'] == false){
					$title = $event['title'];
					$eventURL='';
			    }elseif(($param['mxcFullCalendarPgId'] || $param['mxcAjaxPageId']) && empty($event['link'])){
					$eventURL = $mxceventDetailURL;
					$title='<a href="'.$mxcEventDetailURL.'" class=" '.$mxcEventDetailAJAX.$param['mxcEventListItemClass'].'"  '.($event['linktarget'] ? 'target="'.$event['linktarget'].'"' : '').' rel="'.$event['linkrel'].'" onclick="">'.$event['title'].'</a>';
			    }else{
					$eventURL = $modx->makeUrl((int)$event['link'],'','','full');
					$title = ( !empty($event['link'])?(is_numeric($event['link'])? '<a href="'.$modx->makeUrl((int)$event['link'],'','','full').'" '.($event['linktarget'] ? 'target="'.$event['linktarget'].'"' : '').' rel="'.$event['linkrel'].'" class="'.$mxcEventDetailAJAX.'">'.$event['title'].'</a>':'<a href="'.$event['link'].'" rel="'.$event['linkrel'].'" target="'.$event['linktarget'].'">'.$event['title'].'</a>'): $event['title'] );
				}
			    
			    //-- Add required JS Library items
			    if(isset($param['mxcAjaxPageId']) && is_numeric((int)$param['mxcAjaxPageId'])){
				//$this->_buildJSlib();
				
				$this->_addJS('
				    <link rel="stylesheet" type="text/css" href="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/shadowbox/shadowbox.css">
				    <script type="text/javascript" src="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/shadowbox/shadowbox.js"></script>
				    <script type="text/javascript">
				    Shadowbox.init({
					// skip the automatic setup again, we do this later manually
					skipSetup: true
				    });
				    window.onload = function() {
				    
					// set up all anchor elements with a "movie" class to work with Shadowbox
					Shadowbox.setup(".moodalbox", {
					    //gallery:            "Name of the Gallery",
					    //autoplayMovies:     true,
					    //height:     350,
					    width:      650,
					    //modal: false, // Setting to true disables the standard ESC and outside click to close modal window, good for html content that has clickable elements
					    //enableKeys: tue, // Allow keys to  invoke behavior; disable for forms in modal
					});
				    
				    };
				    </script>
				    ');
				
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
				'mxcEventListItemEndDateStamp' => strftime($this->config['mxcEventListItemEndDateStamp'], strtotime($event['end'])),
				//-- add in custom fields and new placeholders r0.1.3b,
				'mxcEventTitle' => $event['title'],
				'mxcEventUrl' => $eventURL,
				'mxcEventUrlRel' => $mxcEventDetailAJAX.$event['linkrel'],
				'mxcEventUrlTarget' => $event['linktarget'],
				'mxcEventDetailStateDateStamp' => ($param['mxcEventDetailStateDateStamp'] ? strftime($param['mxcEventDetailStateDateStamp'],strtotime($event['start'])) : $event['start']),
				'mxcEventDetailStateTimeStamp' => ($param['mxcEventDetailStateTimeStamp'] ? strftime($param['mxcEventDetailStateTimeStamp'],strtotime($event['start'])) : $event['start']),
				'mxcEventDetailEndDateStamp' => ($param['mxcEventDetailEndDateStamp'] ? strftime($param['mxcEventDetailEndDateStamp'],strtotime($event['end'])) : $event['end']),
				'mxcEventDetailEndTimeStamp' => ($param['mxcEventDetailEndTimeStamp'] ? strftime($param['mxcEventDetailEndTimeStamp'],strtotime($event['end'])) : $event['end'])
				);
				
                        /** START THE CUSTOM FIELDs **/
                        $EventArr_cft = array();
                        $cft_event = json_decode($event['customFields'],true);
                        $dyn_config_opts = json_decode($this->config['mxcCustomFieldTypes'],true);
                        $dyn_resource_opts = array();
						//-- Create the row with values for each custom field type
						foreach($dyn_config_opts AS $cft){
							$cft_type=$cft['type'];
                                if($cft_type == 'resource'){
                                    $dyn_resource_opts[$cft['name']]=$cft['options'];
                                }
                        } //-- end loop of custom field types

                        //-- Loop through the custom fields
                        if(count($cft_event)){
                            foreach($cft_event AS $l=>$v){
                                switch($v['type']){
                                    default:
                                        $EventArr_cft['mxc'.$l] = $v['val'];
                                        break;
                                    case 'image':
                                        $EventArr_cft['mxc'.$l] = '<img src="'.$v['val'].'" alt="" />';
                                        break;
                                    case 'resource':
                                        //-- Get the TV's as set in the options for the resource in the configuration tab of mxCalendar
                                        if(!empty($dyn_resource_opts[$l])){
                                            $tvVals = $modx->getTemplateVarOutput(explode(',',$dyn_resource_opts[$l]),(int)$v['val'],1);
                                            if(count($tvVals) && is_array($tvVals)){
												foreach ($tvVals AS $k=>$tvVal){
													$EventArr_cft['mxc'.$k] = $tvVal;
												}
											}
                                        }
                                        //-- Get predefined document values to use in mxCalendar
                                        $array_doc = $modx->getPageInfo((int)$v['val'],1,'pagetitle, description, alias, content');
                                        $EventArr_cft['mxcpagetitle'] = $array_doc['pagetitle'];
                                        $EventArr_cft['mxcdescription'] = $array_doc['description'];
                                        $EventArr_cft['mxcalias'] = $array_doc['alias'];
                                        $EventArr_cft['mxccontent'] = $array_doc['content'];
                                        $EventArr_cft['mxc'.$l] =  $v['val'];
                                        break;
                                }
                                
                            }
                        }
                        /** END THE CUSTOM FIELDs **/
                        $ar_eventDetail = array_merge($ar_eventDetail, $EventArr_cft);

                            //-- check for event list template over-ride chunk
                            if(!empty($param['mxcTplEventListItemWrap'])){
                                $events .= $modx->parseChunk($param['mxcTplEventListItemWrap'], $ar_eventDetail, '[+', '+]');
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
			$_mxcCalCategoryFilter = (boolean)$this->config['mxcGetCategoryListUIFilterActive'] == true ? $this->mxcGetCategoryListUIFilter($this->config['mxcGetCategoryListUIFilterType']) : '';
			if(!is_null($param['mxcDefaultCatIdLock']))
			    $_mxcCalCategoryFilter = ($param['mxcDefaultCatIdLock'] == 'false' ||(boolean)$param['mxcDefaultCatIdLock'] == false ? $this->mxcGetCategoryListUIFilter($this->config['mxcGetCategoryListUIFilterType']) : '' );

			
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
		    
		    if($param['mxcAddMooJS'] || $param['mxcJSCodeLibrary']) $this->_addMooJS();
		    
		    return $this->output;
                }
		
		
		
		
		//--Add mutli-dimensional sorting
		function multisort($array, $sort_by, $key1, $key2=NULL, $key3=NULL, $key4=NULL, $key5=NULL, $key6=NULL, $key7=NULL, $key8=NULL, $key9=NULL, $key10=NULL){
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
			if (isset($key10)){
			    $return_array[$pos][$key10] = $array[$pos][$key10];
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
			/*
			$this->_addJS('
			    <script type="text/javascript" src="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/js/moodalbox.v1.2.full.js"></script>
			');
			$this->_addCSS('<link rel="stylesheet" href="'.$this->m->config['site_url'].'assets/modules/mxCalendar/scripts/moodalbox121/css/moodalbox.css" type="text/css" media="screen" />');
			*/
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
    
			    $formFilters = '&fmfiltertime='.$_REQUEST['fmfiltertime'].'&CategoryId='.$_REQUEST['CategoryId'].'&fmeventlistpagelimit='.$_REQUEST['fmeventlistpagelimit'];
			
			for($x=0;$x<$resltLastPage;$x++)
			    $paginationLinks .= "&nbsp;&nbsp;<a href='".(($x == $page) ? '#' : "?{$qs}&pg={$x}").$formFilters."' class='mxcPage".($x==$page ? 'active' : '')."'>".($x + 1)."</a> ";
			    
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
			    ORDER BY startdate, starttime, enddate, endtime
                            LIMIT '.$n;
                    $results = $modx->db->query($eventsSQL);
                    if($this->debug){
			echo '[DEBUG: _getNEvents()]<br />Date: '.$date;
			echo '<br>SQL:<br>'.$eventsSQL.'<br>';
		    }
		    return $results;
                }

                //-- Get Events Single Day and return the array
                function _getEventsSingleDay($date=null,$calendarDate=NULL,$CatId=null){
                    global $modx;
		    $month = (!is_null($calendarDate) ? strftime('%m', strtotime($calendarDate)) : strftime('%Y-%m-$d') );
                    $date = (!is_null($date)) ? $date : strftime("%Y-%m-%d") ;
		    $calDateMonth = strftime('%m',strtotime($date));
                    $enddate = strtotime ( "+1 month" , strtotime ( strftime("%Y-$month-1") ) ) ;
                    $enddate = strftime ( "%Y-%m-%d" , $enddate );

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

		    //-- Collect user defined filters
		    $usrStrWhere = $_REQUEST['fmusrfilterCat'];
		    $catID = $_REQUEST['CatId'] ? ' AND C.id IN ('.(int)$_REQUEST['CatId'].')' : '';
                    $eventsSQL = 'SELECT *,E.id as eid, E.category as catID, C.name as category, C.foregroundcss, C.backgroundcss, C.inlinecss, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime, `repeat` 
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE
				((startdate >= \''.$date.'\'
				OR 
				enddate >= \''.$date.'\')
				or `repeat` REGEXP \'[[:alnum:]]+\' )				
				and E.active=1
				and C.active = 1 '.($CatId != null && !empty($_REQUEST['CatId']) ? ' and E.category IN ('.$CatId.') ' : '').' 
				AND '.($WHERE_WGP && count($WHERE_WGP) ? '('.implode(' OR ',$WHERE_WGP).' OR ( E.restrictedwebusergroup = \'\' OR E.restrictedwebusergroup <=> NULL ))' : '( E.restrictedwebusergroup = \'\' OR E.restrictedwebusergroup <=> NULL )' ).'  
                            ORDER BY startdate, starttime, enddate, endtime';
                    $results = $modx->db->query($eventsSQL);
                    if($this->debug) echo "SQL: <br />".$eventsSQL;
                    
                    if($modx->db->getRecordCount($results) > 0){
                        while($data = $modx->db->getRow($results)){
                            //$dayOfMonth = explode('-', $data['startdate']);
                            $dayOfMonth = trim(strftime('%e', strtotime($data['startdate']))); //(int)$dayOfMonth[2];
                            //** Bug fix for Windows non-parse of the %e; update to global fix and cleaner integration
			    if(!$dayOfMonth){
				$dayOfMonth = trim(strftime('%#d', strtotime($data['startdate'])));
			    }
			    
                            //$endDayOfMonth = explode('-', $data['enddate']);
                            $endDayOfMonth = trim(strftime('%e', strtotime($data['enddate']))); //(int)$endDayOfMonth[2];
                            //** Bug fix for Windows non-parse of the %e; update to global fix and cleaner integration
			    if(!$endDayOfMonth){
				$endDayOfMonth = trim(strftime('%#d', strtotime($data['enddate'])));
			    }			    

			    
			    $match = explode('-', $data['startdate']);
			    $dataPieces = explode('-', $date);
                            
			    
			    //-- Remove the duplicate of the reoccurance date on the month
			    if($match[1]=== $dataPieces[1]){ 
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
					'endtime' => $data['endtime'],
					'customFields' => $data['customFields']
				);
				if($this->debug) echo '<h3>Fir st Date for '.$data['title'].':</h3><pre><code>'.$data['startdate'].'</code></pre>';
			    }
			    
                            #***** add multiple day records *****#
                            //if($dayOfMonth < $endDayOfMonth  && $match[2] !== $dataPieces[2] && $match[1] === $dataPieces[1]){
			    if($this->debug) echo 'DurationDays: '.$data['DurationDays'].' for '.$data['title'].'<br />';
			    if($data['DurationDays']){
                                $dif = $dayOfMonth + 1;
                                //for($x=($dayOfMonth+1);$x<=$endDayOfMonth;$x++){
				for($x=1;$x<=$data['DurationDays'];$x++){
                                    if($this->debug) echo "<br />MD:  ".$x."  ".$match[1].$dayOfMonth."==".$dataPieces[1].$endDayOfMonth;
				    //-- Only add the spanning date if within the given month
					$doM = trim(strftime('%e', strtotime("+ $x day", strtotime($data['start']))));
					$dM = strftime('%m', strtotime("+ $x day", strtotime($data['start'])));
				    
				    if($this->debug) echo 'Day of Month: => '.$doM.' match to passed <strong>'.$match[1].' origMonth: '.$calDateMonth.' <=> Month: '.$dM.'</strong><br />';
				    
				    if( $dM == $calDateMonth){
					$eventsByDay[$doM][] = array(
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
						'start' => strftime('%Y-%m-%d', strtotime("+ $x day", strtotime($data['start']))), //$data['start'],
						'startdate' => strftime('%Y-%m-%d', strtotime("+ $x day", strtotime($data['startdate']))), //$data['startdate'],
						'starttime' => $data['starttime'],
						'end' => $data['end'],
						'enddate' => strftime('%Y-%m-%d', strtotime("+ $x day", strtotime($data['start']))), //$data['enddate'],
						'endtime' => $data['endtime'],
						'customFields' => $data['customFields']
					);
					if($this->debug) echo '<h3>Repeat if Duration:</h3><pre><code>'.print_r($eventsByDay[$doM]).'</code></pre>';
				    }
                                }
				//echo "&nbsp;&nbsp;&nbsp;Does span multiple days.<br />";
                            } else {
				//-- Does not span multiple days
				//echo "&nbsp;&nbsp;&nbsp;Does not span multiple days.<br />";
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
							'repeat' => $int_repeatItem,
							'customFields' => $data['customFields']
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
									'repeat' => $int_repeatItem,
									'customFields' => $data['customFields']
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
		
		function so($a,$b){
			return strcmp($a,$b);
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
                function _makeDateSelector($field=NULL, $label=NULL, $tooltip=NULL, $val=NULL, $class=NULL){
                    global $modx;
		    $formEntries = '<div class="fm_row"><label>'.$label.'</label><div class="fm_entry">';
                    $fmDATE = ($_POST['fm'.$field]) ? $_POST['fm'.$field] : $val;
		    $theme = $modx->config['manager_theme'];
		    //cal_form
		    $autoEndDateUpdate = ($field == 'startdate' ? 'document.forms[\'cal_form\'].elements[\'fmenddate\'].value=this.value;" ' : '');
                    $formEntries .= '<input id="fm'.$field.'" name="fm'.$field.'" class="DatePicker '.$class.'" value="'.$val.'" onblur="" /><a title="Remove Date" onclick="this.previousSibling.value=\'\';this.previousSibling.previousSibling.value=\'\'; return true;" onmouseover="window.status=\'Remove date\'; return true;" onmouseout="window.status=\'\'; return true;" style="position:relative;left:0;cursor:pointer; cursor:hand"><img src="media/style/'.$theme.'/images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="Remove date" /></a><br /><em>YYYY-MM-DD</em>';
                    $formEntries .= $tooltip.'</div><div style="display:block;height:7px;clear:both;"></div>';
                    return $formEntries;
                }
                
		//-- Render RTE
		function renderRTE($type='image',$field,$defaultValue='',$style=''){
			//-- Uses the ModX manager/includes/tmplvars.inc.php file
			// renderFormElement($field_type, $field_id, $default_text, $field_elements, $field_value, $field_style='')
			global $modx;
			
			include_once(MODX_BASE_PATH.'manager/includes/tmplvars.inc.php');
			require_once(MODX_BASE_PATH.'manager/includes/tmplvars.commands.inc.php');
			
			
			$event_output = $modx->invokeEvent("OnRichTextEditorInit"
							   , array('editor'=>$modx->config['which_editor'], 'elements'=>array('tv'.$field)));
			if(is_array($event_output)) {
				$editor_html = implode("",$event_output);
			}
			$rte_html = renderFormElement($type, $field, $defaultValue, '', '',$style);
			return str_replace('tv'.$field, $field, $rte_html.$editor_html);
		}
		
                //-- Depreciated 0.1.0-rc
		//-- Make RTE
                function makeRTE($field){
			global $modx;
                    $rte = <<<EORTE
<script language="javascript" type="text/javascript" src="%base%assets/plugins/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript" src="%base%assets/plugins/tinymce/js/xconfig.js"></script>
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
		  external_link_list_url : "%base%assets/plugins/tinymce/tinymce.linklist.php",
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
		var cmsURL = '%base%manager/media/browser/mcpuk/browser.php?Connector=%base%manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=%base%&editor=tinymce&editorpath=%base%assets/plugins/tinymce';    // script URL - use an absolute path!
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
		    //-- Enable the debugger (Manager)
		    $debug = false;
		    
		    $theParameter = array('MODE'=>$frequencymode, 'interval'=>$interval, 'frequency'=>$frequency, 'StartDate'=>$startDate, 'EndDate'=>$endDate, 'OnWeedkDay'=>$onwd);
		    if($debug){
			echo "Date repeat function paramters are:<br />";
			foreach($theParameter AS $key=>$val)
				echo $key.'=>'.$val.'<br />';
		    }
		    
		    //-- Check the Date and build the repeat dates
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
                            
			    //-- Get the first repeat Day of Week if the same as start date's Day of Week
			    $curWeek = $startWeek = strftime('%W',strtotime($startDate));
			    $occurance = strftime('%Y-%m-%d',strtotime($startDate));
			    $nextWeek = strftime('%Y-%m-%d', strtotime('next monday', strtotime($startDate)));
			    //-- Loop through days until the end of current week
			    while($curWeek == $startWeek){
				    $occurance = strftime('%Y-%m-%d',strtotime('next day', strtotime($occurance)));
				    $curWeek= strftime('%W',strtotime($occurance));
				    
				    //-- Get occurance day of week int
                                    $thisDOW = strftime('%w',strtotime("next day",strtotime($occurance)));
				    
                                    //-- Get the valid date formated of occurance
                                    $occDate = strftime('%Y-%m-%d', strtotime("next day",strtotime($occurance)));
				    
                                    //-- Check if the date is one of the assigned and less than the end date
                                    if(in_array($thisDOW, $onwd) && $curWeek == $startWeek && strtotime($occDate) < strtotime($nextWeek) && strtotime($occDate) > strtotime($startDate)){
                                        if($debug) echo $occDate." MATCH on $thisDOW (start week) :: CurWk=$curWeek :: StartWk=$startWeek :: NextWk=$nextWeek<br />";
                                        $ar_Recur[] = $occDate;
                                    } else {
                                        if($debug  && $curWeek == $startWeek && strtotime($occDate) < strtotime($nextWeek)) echo $occDate." (start week)<br />";
                                    }
			    }

			    $startDate  = strftime('%Y-%m-%d', strtotime($startDate.' last mon '));
			    if($debug) echo '<strong>Start date MONDAY of that week: </strong>: '.$startDate.'<br />';
			    $startDate = strftime('%Y-%m-%d', strtotime($startDate.' + '.$interval.' weeks'));
			    if($debug)
				echo '<strong>Next Valid Repeat Week Start Date: </strong>: '.$startDate.'<br />'.
				     'Modified start: '.$startDate.' with adjusted interval: '.($interval).'<br />'.
				     'Frequency: '.$frequency.' with the max repeat of: '.($frequency*7).'<br />';
			    
			    //-- Created a new loop to limit the possibility of almost endless loop
			    $newDate = $startDate;

			    while(strtotime($newDate) <= strtotime($endDate)){
                                if($debug) echo "x={$x}<br />";
                                $occurance = strftime('%Y-%m-%d', strtotime($newDate));
				
                                /*** r0.0.6b fix ***/
                                $lastweek=sprintf("%02d", (strftime('%W',strtotime($occurance)) ));
                                if($debug) echo 'Week of: '.$lastweek."<br />";
                                $year = strftime('%Y',strtotime($occurance));
                                for ($i=0;$i<=6;$i++){
                                    
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
				    //-- Reset the date for while loop validation
				    $newDate = strftime('%Y-%m-%d', strtotime($occurance.' + '.$interval.' weeks'));
                                }
                                if(!$valid) break;
			    }
			    if($debug) echo '<strong><em>'.count($ar_Recur).'<em> total matches dates added.</strong>';
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
			$js_code = '<script src="'.$this->config['mxcJSCodeSource'].'" type="text/javascript"></script>';
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
			
			//$XML = simplexml_load_file($dir."/".$this->config['mxCalendarTheme']."/theme.xml");
			$XML = simplexml_load_string(file_get_contents($dir."/".$this->config['mxCalendarTheme']."/theme.xml"));
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
