<?php
if(!class_exists("mxCal_APP_CLASS")){
	class mxCal_APP_CLASS {
		var $user_id;
		var $params = array();
		var $default_limit;
		var $output;
		var $tables;
                var $tooltip;
		//-- Messages
		var $message;
                var $debug = false;
                
		function __construct() {
                    //-- Form tooltips
                    $this->tooltip = array(
                                'title'=>_mxCalendar_gl_tt_title,
                                'link'=>_mxCalendar_gl_tt_link,
				'location' => _mxCalendar_gl_tt_location
                                );
                    
		    //--Store short list of full table names
                    $this->tables=array(
				'events'=>'mxCalendar_events',
				'pastevents'=>'mxCalendar_pastevents',
                                'categories'=>'mxCalendar_categories',
                                'config'=>'mxCalendar_config'
				);
		    $this->params = $_POST;
		    $this->default_limit = 100;
		    $this->name = __CLASS__;
                    
		    SWITCH($this->params['fmaction']){
                        case "edit":
				$this->EditSpotting($_POST['fmeid']);
				break;
			case "unpublish":
				$this->AddCategories($this->pubunpubCat());
				break;
                        case "search":
                                $this->ListSpottings(false,true);
			default:
				//--Do Nothing (ERROR)
				break;
		    }
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
                
                // Installation
                function _install_mxCalendar(){
			global $modx;
                        $db_setup = 0;
                        $user = $modx->db->config['user'];
                        $db = $modx->db->config['dbase'];
			$pre = $modx->db->config['table_prefix'];
			$tables = $this->get_tables("{$pre}mxCalendar%");
                        
			//-- CREATE FUTURE EVENTS TABLE 
                        if(!in_array("{$pre}mxCalendar_events", $tables)) {
			$sql = <<<EOD
				CREATE TABLE IF NOT EXISTS {$db}.`{$pre}mxCalendar_events` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `title` varchar(256) NOT NULL,
				  `description` TEXT NOT NULL,
				  `category` tinyint(3) NOT NULL DEFAULT '1',
                                  `link` TEXT NULL,
                                  `linkrel` TEXT NULL,
                                  `linktarget` TEXT NULL,
				  `location` TEXT NULL,
				  `displayGoogleMap` tinyint(1) NOT NULL DEFAULT '0',
				  `start` DATETIME NOT NULL,
                                  `startdate` DATE NOT NULL,
                                  `starttime` TIME NOT NULL,
                                  `end` DATETIME NOT NULL,
                                  `enddate` DATE NOT NULL,
                                  `endtime` TIME NOT NULL,
				  `active` tinyint(1) NOT NULL DEFAULT '1',
				  `repeat` TEXT NULL,
				  `event_occurance` VARCHAR (1) NULL,
				  `_occurance_wkly` VARCHAR (10) NULL,
				  `event_occurance_rep` tinyint(2) NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
EOD;
				$modx->db->query($sql);
				$sql_manual .= "{$sql}\n";
				$db_setup++;
			}
                        
                        //-- CREATE PAST EVENTS TABLE
			if(!in_array("{$pre}mxCalendar_pastevents", $tables)) {
			$sql = <<<EOD
				CREATE TABLE IF NOT EXISTS {$db}.`{$pre}mxCalendar_pastevents` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `title` varchar(256) NOT NULL,
				  `description` blob NOT NULL,
                                  `link` TEXT NULL,
                                  `linkrel` TEXT NULL,
                                  `linktarget` TEXT NULL,
				  `location` TEXT NULL,
				  `displayGoogleMap` tinyint(1) NOT NULL DEFAULT '0',
				  `category` tinyint(3) NOT NULL DEFAULT '1',
				  `start` DATETIME NOT NULL,
                                  `startdate` DATE NOT NULL,
                                  `starttime` TIME NOT NULL,
                                  `end` DATETIME NOT NULL,
                                  `enddate` DATE NOT NULL,
                                  `endtime` TIME NOT NULL,
				  `active` tinyint(1) NOT NULL DEFAULT '1',
				  `repeat` TEXT NULL,
				  `event_occurance` VARCHAR (1) NULL,
				  `_occurance_wkly` VARCHAR (10) NULL,
				  `event_occurance_rep` tinyint(2) NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
EOD;
				$modx->db->query($sql);
				$sql_manual .= "{$sql}\n";
				$db_setup++;
			}
                        
			//-- CREATE CATEGORIES TABLE
                        if(!in_array("{$pre}mxCalendar_categories", $tables)) {
			$sql = "
				CREATE TABLE IF NOT EXISTS {$db}.`{$pre}mxCalendar_categories` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `name` varchar(256) NOT NULL,
                                  `foregroundcss` char(6) NULL,
                                  `backgroundcss` char(6) NULL,
                                  `inlinecss` TINYTEXT NULL,
				  `active` tinyint(1) NOT NULL DEFAULT '1',
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
				$modx->db->query($sql);
				$sql_manual .= "{$sql}\n";
                                
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_categories`(name) Values('General');");
                                
				$db_setup++;
			}
                        
			//-- CREATE CONFIG TABLE
                        if(!in_array("{$pre}mxCalendar_config", $tables)) {
			$sql = "CREATE TABLE IF NOT EXISTS {$db}.`{$pre}mxCalendar_config` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `param` varchar(256) NOT NULL,
				  `value` TINYTEXT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
				$modx->db->query($sql);
				$sql_manual .= "{$sql}\n";
                                
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('disptooltip',1);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('liststyle_limit',5);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('dispduration',1);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('dispeventtime',1);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('enableprevnext',1);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('useajax',0);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('calSMwidth','125px;');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('calFULLwidth','100%');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('calstartday',0);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('calweekends',0);");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('caltdbordercss','666666');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('caldatestampbgcss','CCCCCC');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('caldatestamptxtcss','000000');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('titleatodaybgcss','000000');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('titleatodaytxtcss','FFFFFF');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('titleatodaybordercss','000000');");
                                $modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('MAPS_HOST','maps.google.com');");
				$modx->db->query("INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('MAPS_KEY','');");
				#INSERT INTO {$db}.`{$pre}mxCalendar_config`(param, value) Values('',);

				$db_setup++;
			}
                        
                        if($db_setup) { $tables = $this->get_tables("{$pre}mxCalendar%"); }
                        if($db_setup != 4){
                            //Error Maker
                            $modx->logEvent(0, 3, "<p><strong>"._mxCalendar_gl_installFailTable."</strong></p><p>Give CREATE TABLE rights to user {$user} or run the following SQL as a user with CREATE TABLE permissions <br /><pre>{$sql_manual}</pre></p>", $source='Module: mxCalendar');
                            $this->output.=_mxCalendar_gl_installFail."<br />\n";
                        } else {
                            //Install Completed
                            $fh = fopen( $modx->config['base_path'].'assets/modules/mxCalendar/config/config.xml', 'w') or die("Unable to save configuration file. Please make sure write permission is granted on the folder (".$modx->config['base_path']."assets/modules/mxCalendar/config/)");
                            $stringData = '<?xml version="1.0" encoding="UTF-8"?><setup>Yes</setup>'."\n".'<date>'.DATE('l jS \of F Y h:i:s A').'</date><version>'.CAL_VERSION.'</version>';
                            fwrite($fh, $stringData);
                            fclose($fh);
                            $this->output.=_mxCalendar_gl_installSucceess."<br /><form method='POST' action=''><input type='submit' value='Start now ...' /></form>\n";
                        }
                    return $this->output;
                }
                
                
                //**** Manager Views ****//
                function ListEvents($params = false){
                    global $modx;
                    
                    //-- Perform delete prior to new listing @postback
                    if(isset($_POST['fmeid']) & $_POST['fmaction'] == 'delete') {
                        $modx->db->delete($modx->getFullTableName($this->tables['events']), 'id='.$_POST['fmeid']);
                        $this->output .= '<h3 style="color:#ff0000;">Removed event.</h3>';
                    }
                    
                    $result = $this->_getEvents();
		    $_mxCal_cont_col = explode(',',_mxCalendar_gl_columns);
                    if($modx->db->getRecordCount($result) > 0) {
                        //$this->output .= "<table><tbody><tr><th>".$_mxCal_cont_col[0]."</th><th>".$_mxCal_cont_col[1]."</th><th>".$_mxCal_cont_col[2]."</th><th>".$_mxCal_cont_col[3]."</th><th>".$_mxCal_cont_col[4]."</th><th>Reoccuring Event</th><th>&nbsp;</th></tr>";
                        $this->output .= "<table><tbody><tr>";
				foreach(explode(',', _mxCalendar_el_labels) AS $label)
				$this->output .= "<th>".trim($label)."</th>";
			$this->output .= "<th></th></tr>";
			$records = $modx->db->makeArray($result);
                        //if(count($records) > 0){
                            $evodbg = ' style="background-color:#ccc" ';
                            foreach( $records as $event ) {
                                $evodbg = ($evodbg) ? '' : ' style="background-color:#ccc" ';
                                $evLastOccurance = explode(',',$event['repeat']);
				
				$this->output .='
                                <tr'.$evodbg.'>
                                    <td>'.$event['eid'].'</td>
                                    <td>'.$event['title'].'</td>
                                    <td>'.$event['category'].'</td>
                                    <td>'.date(_mxCalendar_el_timeformat, strtotime($event['start'])).'</td>
                                    <td>'.date(_mxCalendar_el_timeformat, strtotime($event['end'])).'</td>
				    <td>'.(!empty($event['repeat']) ? date(_mxCalendar_el_timeformat_date, strtotime($evLastOccurance[count($evLastOccurance)-1])) : '').'</td>
                                    <td><form method="post" action="" onSubmit="document.cookie=\'webfxtab_tabPanel=1;path=/;\'"><input type="hidden" name="fmeid" value="'.$event['eid'].'"><input type="submit" name="fmaction" value="'._mxCalendar_gl_btnEdit.'"><input type="submit" name="fmaction" value="'._mxCalendar_gl_btnDelete.'" onClick="return confirm(\''._mxCalendar_gl_btnConfirm.'\')"></form></td>
                                </tr>
                                ';
                            }
                        //}
                        $this->output .= "</tbody></table>";
                    } else {
                        $this->output .='<h2>'._mxCalendar_gl_noevents.'</h2>';
                        $this->output .='<h3>'._mxCalendar_gl_quicklist.'</h3>';
                        $result = $this->_getNEvents();
                        //-- Rem for GenRel: Old display method
			/*
			$this->output .= $modx->db->getHTMLGrid($result,array(
                                'fields'=>'eid,title,category,startdate,starttime,enddate,endtime,DurationDays,DurationTime',
                                'columns'=>'id,title,category,startdate,starttime,enddate,endtime,DurationDays,DurationTime',
                                'cssClass'=>'',
                                'columnHeaderClass'=>'heading',
                                'columnHeaderStyle'=>'border:1px solid #000;',
                                'cellPadding' => '4',
                                'pageSize' => '25',
                                'colAligns' => 'left',
                                'colVAligns' => 'top',
                                'pagerLocation' => 'top'
                        ));
			*/
                    }
                    
                    //$this->output.=$this->MakeCalendar();
                    return $this->output;
                }
                
                //-- Add New Event Form/Actions
                function AddEvent($params=false){
                    global $modx;
                    $this->output = '';
                    
                    if($_POST['fmaction'] == 'save' || $_POST['fmaction'] == 'update'){
                        $saved=$this->_saveEvent($_POST['fmaction']);
                        if($saved)
                            $this->output .= '<h3>'.str_replace("|*rec*|", $saved, _mxCalendar_ae_success).'</h3>'; //$saved
                        else
                            $this->output .= '<h3>'.str_replace("|*rec*|", $saved, _mxCalendar_ae_fail).'</h3>';
                        
                    }

                    
                    //-- Form action and label properties
                    $fmAction = (!isset($_REQUEST['fmeid'])) ? 'save' : 'update';
                    $fmActionLabel = (!isset($_REQUEST['fmeid'])) ? _mxCalendar_btn_save : _mxCalendar_btn_update;
                    
                    if($_REQUEST['fmeid']){
                        //-- Get record to edit
                        $result = $modx->db->select('id,title,description,category,link,linkrel,linktarget,location,displayGoogleMap,start,startdate,starttime,end,enddate,endtime', $modx->getFullTableName($this->tables['events']),'id = '.$_REQUEST['fmeid'] );
                        if( $modx->db->getRecordCount( $result ) ) {
                            $output .= '<ul>';
                            $editArr = $modx->db->getRow( $result );
                        }
                    } else { $editArr = array(); }
                    //-- Get language file labels
		    $fm_label = explode(',', _mxCalendar_ae_labels);
                    $fm_columns = $this->get_columns($this->tables['events']);
                    $this->output .= '<form id="fm_bsApp" name="cal_form" method="post" action="">'."\n";
                    $x=0;
		    foreach($fm_columns as $key=>$val){
                        //-- List of excluded table columns
                        $excluded = array('id','active','start','end', 'repeat', 'event_occurance', '_occurance_wkly', 'event_occurance_rep');
                        //-- Make sure it's not an excluded column
                        if(!in_array($val[0], $excluded)){
                            $tooltip = ($this->tooltip[$val[0]]) ? '<img  title="'.$this->tooltip[$val[0]].'" src="'.$modx->config['base_url'].'manager/media/style/MODxCarbon/images/icons/information.png" class="Tips1" />' : '';
                            SWITCH ($val[1]){
                                case 'text':
                                    if($val[0] == 'description')
                                    $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><textarea id="fm'.$val[0].'" name="fm'.$val[0].'">'.$editArr[$val[0]].'</textarea>'.$this->makeRTE($val[0]).$tooltip.'</div></div>'."\n";
				    else
                                    $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="text" name="fm'.$val[0].'" value="'.$editArr[$val[0]].'" />'.$tooltip.'</div></div>'."\n";
                                    break;
                                case 'date':
                                    if($val[0] == 'startdate')
                                      $editSD = $editArr['start'];
                                    elseif($val[0]=='enddate')
                                      $editSD = $editArr['end'];
                                    else
                                      $editSD = null;
                                    $this->output .= "\t".$this->_makeDateSelector($val[0], $fm_label[$x], $tooltip, $editSD)."\n";
                                    break;
                                case 'time':
                                    //-- We'll use the date picker field and extract the time
                                    break;
                                default:
                                    if($val[0] == 'category'){
                                      foreach($this->getCategories() as $cats){
                                        foreach($cats as $catsKey=>$catsVal){
                                            $selected = ($editArr[$val[0]] == $catsKey) ? 'selected=selected' : '';
                                            $thisSDL .= '<option value="'.$catsKey.'" '.$selected.'>'.$catsVal.'</option>';
                                        }
                                      }
                                      $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><select name="fm'.$val[0].'">'.$thisSDL.'</select>'.$tooltip.'</div></div>'."\n";
				    } elseif($val[0] == 'displayGoogleMap'){
					$this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="checkbox" id="fm'.$val[0].'" name="fm'.$val[0].'" value="1" '.($editArr[$val[0]] ? 'checked="checked"' : "").' />'.$tooltip.'</div></div>'."\n";
                                    } else {
                                      $this->output .= "\t".'<div class="fm_row"><label>'.$fm_label[$x].'</label><div class="fm_entry"><input type="text" name="fm'.$val[0].'" value="'.$editArr[$val[0]].'" />'.$tooltip.'</div></div>'."\n";
                                    }
                                    break;
                            }
			$x++;
                        }
			
                    }
                    
			//-- TEST Reoccurances fmevent_occurance, fm_occurance_wkly[], fmevent_occurance_rep
			$this->output .= "\t\t".'
    <fieldset id="mxcalendar-repeat" style="border:1px solid #ccc;">
        <legend>'.trim($fm_label[(count($fm_label)-5)]).':</legend>
        <div claa="fm_row" ><label>'.trim($fm_label[(count($fm_label)-4)]).':</label>
            <select name="fmevent_occurance" onChange="if(this.value == \'w\'){ $(\'fm_occurance_wkly\').setStyle(\'display\',\'block\') } else {$(\'fm_occurance_wkly\').setStyle(\'display\',\'none\')}">
                <option value="0"></option>
                <option value="d">Daily</option>
                <option value="w">Weekly</option>
                <option value="m">Monthly</option>
                <option value="y">Yearly</option>
            </select>
        </div>
        
        <div id="fm_occurance_wkly" class="fm_row" style="display:none;"><label>'.trim($fm_label[(count($fm_label)-3)]).':</label>
            <input type="checkbox" name="fmevent_occurance_on[]" value="0" /><span class="fm_form_rLabel">S</span>
            <input type="checkbox" name="fmevent_occurance_on[]" value="1" /><span class="fm_form_rLabel">M</span>
            <input type="checkbox" name="fmevent_occurance_on[]" value="2" /><span class="fm_form_rLabel">T</span>
            <input type="checkbox" name="fmevent_occurance_on[]" value="3" /><span class="fm_form_rLabel">W</span>
            <input type="checkbox" name="fmevent_occurance_on[]" value="4" /><span class="fm_form_rLabel">T</span>
            <input type="checkbox" name="fmevent_occurance_on[]" value="5" /><span class="fm_form_rLabel">F</span>
            <input type="checkbox" name="fmevent_occurance_on[]" value="6" /><span class="fm_form_rLabel">S</span>
        </div>
        
        <div class="fm_row"><label>'.trim($fm_label[(count($fm_label)-2)]).':</label>
            <select name="fmevent_occurance_rep">';
                
                    $x=1;
                    while($x <= 30){
                        $this->output.="\t\t"."<option value='$x'>$x</option>";
                        $x++;
                    }
                $this->output.="\t".'
            </select>';
	$this->output .= "\t".$this->_makeDateSelector('event_occur_until', $fm_label[(count($fm_label)-1)], $tooltip, $editSD)."\n";
	$this->output.='</fieldset>'."\n";
		    
		    $fmeid = ($_REQUEST['fmeid']) ? '<input type="hidden" name="fmeid" value="'.$_REQUEST['fmeid'].'">' : '';
                    $this->output .= "\t".'<div class="fm_row"><div class="fm_actions">
                                        <input type="hidden" name="fmaction" value="'.$fmAction.'" />
                                        <input type="submit" name="submit" value="'.$fmActionLabel.'" />
                                        '.$fmeid.'
                                      </div></div>'."\n";
                    $this->output .= '</form>'."\n";
                    $this->output .= $this->makeRTE('fmdescription');
                   
                    return $this->output;
                }
                
                
		// Configuration
		function Configuration(){
			global $modx;
			$results = $modx->db->query('SELECT * FROM '.$modx->db->config['table_prefix'].$this->tables['config']); //$this->get_columns($this->tables['config']);
			$this->output = '';
			$this->output .= '<h2>Configuration Settings</h2>';
			$this->output .= '<form id="fm_bsApp" name="config_form" method="post" action="">'."\n";
			
			while($row = $modx->db->getRow($results, 'assoc')) {
			    $this->output .= '<div class="fm_row"><label>'.$row['param'].'</label><input type="text" value="'.$row['value'].'" /></div>';
			}
			$this->output .= '<input type="submit" name="update" value="'._mxCalendar_btn_update.'" />';
			return $this->output.'</form>';
		}
		
                //-- Save New Event
                function _saveEvent($method){
                    global $modx;
                    $param = $_POST;
                    //-- Break apart the dates
		    $startValues = date_parse($param['fmstartdate']);
                    $startValuesSplit = explode(' ', $param['fmstartdate']);
                    $endValues = date_parse($param['fmenddate']);
                    $endValuesSplit = explode(' ', $param['fmenddate']);
		    
		    //-- Create @param for entry
                    $sT = $modx->db->escape($param['fmtitle']);
                    $sD = $modx->db->escape($param['fmdescription']);
                    $sC = $modx->db->escape($param['fmcategory']);
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
		    foreach($param['fmevent_occurance_on'] AS $rep)
			$repOccOn[] = $rep[0];
			
		    $ar_Events = $this->_getRepeatDates($param['fmevent_occurance'], (int)$param['fmevent_occurance_rep'],365, $param['fmstartdate'],( !empty($param['fmevent_occur_until']) ? $param['fmevent_occur_until'] : $param['fmenddate']), $repOccOn);
		    $reOcc = $ar_Events;
		    if($this->debug) print("Reoccur Date<br />".$ar_Events);
		    
                    $fields = array('title'	 => $sT,
                                    'description'=> $sD,
                                    'category'	 => $sC,
                                    'link'       => $sL,
                                    'linkrel'    => $sLR,
                                    'linktarget' => $sLT,
				    'location'   => $sLoc,
				    'displayGoogleMap' => (int)$param['fmdisplayGoogleMap'],
                                    'start'	 => $param['fmstartdate'],
                                    'startdate'	 => $sSD,
                                    'starttime'  => $sST,
                                    'end'	 => $_POST['fmenddate'],
                                    'enddate'    => $sED,
                                    'endtime'    => $sET,
				    '`repeat`'	 => $reOcc,
				    'event_occurance' => $modx->db->escape($param['fmevent_occurance']),
				    '_occurance_wkly' => $modx->db->escape($param['fm_occurance_on']),
				    'event_occurance_rep' => $modx->db->escape((int)$param['fmevent_occurance_rep'])
                                    );
                    if($method == 'save'){
                        $NID = $modx->db->insert( $fields, $table_name);
                        if($NID) $_POST = array();
                    } else {
                        $modx->db->update( $fields, $table_name, 'id='.$_POST['fmeid']);
                        $NID = $param['fmeid'];
                        $_POST = array();
                    }
                    return $NID;
                }
                
                //*************************//
                //**** Front End Views ****//
                //*************************//
                function MakeCalendar($params=array()){
                    global $modx;

                    $defaultParam = array(
                                   'type'=>'full'
                                  );
                    $param = array_merge($defaultParam, $params);
                    
                    //$theEvents = $this->_getEvents();
                    if(($param['type']=='full' & empty($_REQUEST['details'])) || (!isset($param['type']) & empty($_REQUEST['details'])))
                        //-- DISPLAY FULL CALENDAR (roadmap -> change to tpl chunks)
			include_once 'includes/calendar.inc.php';
                    elseif(!empty($_REQUEST['details']))
                        echo $this->MakeEventDetail((int)$_REQUEST['details'],$param);
                    else
                        echo $this->MakeUpcomingEventsList($param);
                }
                
                //***********************************//
                //**** Make Events Details View  ****//
                //***********************************//
                function MakeEventDetail($id,$param){
                    global $modx;

                    $table = $modx->getFullTableName( $this->tables['events'] );
                    $result = $modx->db->select( '*', $table, 'id='.$id);
                    $content = $modx->db->makeArray( $result );
		    
                    $defaultParam = array(
                                   'tplEventDetail'=>null,
                                  );
                    $param = array_merge($defaultParam, $param);
                
			if($param['ajaxPageId'] != $modx->documentIdentifier && (int)$param['ajaxPageId']!==0)
				$this->output = "<div class='bsCalBack'><a href='".$modx->makeUrl((int)$param['ajaxPageId'] )."'>"._mxCalendar_ed_title."</a></div>";
			if($modx->db->getRecordCount($result))  {
			    foreach( $content as $p_val ) {
				//-- Parse Outer Template
				if(!empty($param['tplEventDetail'])){
					$chunkArr = array(
					    'title'=>$p_val['title'],
					    'strDTLabel'=>_mxCalendar_ed_dt,
					    'startTime' => date(_mxCalendar_ed_dateformat,strtotime($p_val['start'])),
					    'endTime' => date(_mxCalendar_ed_dateformat, strtotime($p_val['end'])),
					    'location' => $p_val['location'],
					    'content'=>$p_val['description']
					);
					$this->output .= $modx->parseChunk($param['tplEventDetail'], $chunkArr, '[+', '+]');
				} else {
					$this->output .= "<h1>".$p_val['title']."</h1>".
					  "<h3>"._mxCalendar_ed_dt." ".date(_mxCalendar_ed_dateformat,strtotime($p_val['start']))." - ".date(_mxCalendar_ed_dateformat, strtotime($p_val['end']))."</h3>".
					  "<h3>"._mxCalendar_ed_location." ".$p_val['location']."</h3>".$p_val['description'];
				}
				
				//-- Add google Map API
				if($p_val['location'] && $p_val['displayGoogleMap']){
					include_once($modx->config['base_path'].'assets/modules/mxCalendar/includes/google_geoloc.class.inc');
					//-- Output the Address results
					if(class_exists("geoLocator") && $p_val['location']){
					    $mygeoloc = new geoLocator;
					    $mygeoloc->host = 'http://maps.google.com';
					    $mygeoloc->apikey = '';
					    
					    $addressList = explode('|', $p_val['location']);
					    foreach($addressList as $loc){
						$mygeoloc->getGEO($loc);
					    }
					    $this->output .= '<div id="map_canvas" style="width: 500px; height: 300px"></div>';
					    $this->output .= $mygeoloc->output;
					    
						$this->output .= '
						<script src="[(site_url)]manager/media/script/mootools/mootools.js" type="text/javascript"></script>
						<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
						<script type="text/javascript">
						//on dom ready...
						window.addEvent(\'domready\', function() {
						
						  var map;
						
						';
						$this->output .= $mygeoloc->mapJSv3;
						$this->output .= '
						});
						

						  
						</script>
						';
					    
					} else {
					    echo 'No class found.';
					}
				}
				
				}//end loop
			    }
			    return $this->output;


                }
                
                //***********************************//
                //**** Make Upcoming Events View ****//
                //***********************************//
                function MakeUpcomingEventsList($param=array()){
                    global $modx;
                    $defaultParam = array(
                                   'type'=>null,
                                   'tplWrap'=>null,
                                   'tplWrapClass'=>null,
                                   'tplWrapId'=>null,
                                   'tplWrapTitle'=>_mxCalendar_ev_title,
                                   'tplEvent'=>null,
                                   'maxCnt'=>5,
                                   'ajaxPaginate'=>null,
                                   'fullCalendarPgId'=>null,
                                   'linkText'=>_mxCalendar_ev_link
                                  );
                    $param = array_merge($defaultParam, $param);
                
                    //-- Parse Outer Template
                    if(!empty($param['tplWrap'])){
                        $chunkArr = array(
                            'title'=>$calTitle,
                            'class'=>$tplWrapClass,
                            'id'=>$tplWrapId
                        );
                        $eventsWrap = $modx->parseChunk($param['tplWrap'], $chunkArr, '[+', '+]');
                    } else {
                        //-- DEFAULT EVENT WRAP
                        $eventsWrap = '<div id="calendar" class="block"><h2>'.$param['tplWrapTitle'].'</h2>';
                    }
                    
                    //-- Prase Event template and loop through the events
                    $events = '';
                    $records = $modx->db->makeArray($this->_getNEvents(date("Y-m-j"),(int)$param['maxCnt']));
                    
                    //-- check the count before entering the loop
                    if(count($records) > 0){
                        foreach( $records as $event ) {
                        //for($i=0; $i<le($event);$i++){
                            //-- Event template @param chunk array
                            $datePieces = explode("-", $event['startdate']);
                            $month=date("M", strtotime($event['start']));
                            $day=$datePieces[2];
                            if($param['fullCalendarPgId'])
                                $title='<a href="'.$modx->makeUrl((int)$param['fullCalendarPgId'],'','details='.$event['eid']).'">'.$event['title'].'</a>';
                            else
                                $title=$event['title'];
                            $location=$event['location'];
                            
                            //-- check for event template
                            if(!empty($param['tplEvent'])){
                                $chunkArr = array(
                                    'month' => $month,
                                    'day' => $day,
                                    'title' => $title,
                                    'location'=> $location
                                );
                                $events .= $modx->parseChunk($param['tplEvent'], $chunkArr, '[+', '+]');
                            } else {
                                //-- DEFAULT EVENT OUTPUT
                                $events .= '<div class="event">
                                  <div class="date">
                                    <div class="month"><p>'.$month.'</p></div>
                                      <p>'.$day.'</p>
                                  </div>
                                  <h6>'.$title.'</h6>
                                  <p>'.$location.'</p>
                                </div>';
                            }
                        }//}
                        if(is_int((int)$param[fullCalendarPgId])){
                            $url = $modx->makeUrl((int)$param[fullCalendarPgId]);
                        }else
                            $url = null;
                        if(!empty($param['fullCalendarPgId']))
                         $events .= '<p><a href="'.$url.'" class="readmore">'.$param['linkText'].'</a></p> ';
                    } else {
                        $events = '<p class="noevents">'._mxCalendar_ev_none.'</p>';
                    }
                    
                    $this->output = $eventsWrap.$events.'</div>'; //str_replace('|*events*|',$events,$eventsWrap);
                    return $this->output;
                }
                
                //**************************//
                //**** GLOBAL FUNCTIONS ****//
                //**************************//
                //-- Get Events and return the array
                function _getEvents($date=null,$stopdate=null){
                    global $modx;
                    $date = (!is_null($date)) ? $date : date("Y-m-1") ;
                    $enddate = strtotime ( "+1 year" , strtotime ( date("Y-m-1") ) ) ;
                    $enddate = date ( "Y-m-d" , $enddate );
                    $eventsSQL = 'SELECT *,E.id as eid,E.category as catID, C.name as category, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE startdate >= \''.$date.'\' and enddate < \''.$enddate.'\' and E.active=1
                            ORDER BY startdate';
                    $results = $modx->db->query($eventsSQL);
                    return $results;
                }
                
                //-- Get Next (N) Events and return the array
                function _getNEvents($date=null,$n=10){
                    global $modx;
                    $date = (!is_null($date)) ? $date : date("Y-m-1") ;
                    $enddate = strtotime ( "+1 month" , strtotime ( date("Y-m-1") ) ) ;
                    $enddate = date ( "Y-m-d" , $enddate );
                    $eventsSQL = 'SELECT *,E.id as eid ,E.category as catID, C.name as category, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE startdate >= \''.$date.'\' or  `repeat` REGEXP \''.$date.'\' and E.active=1
                            ORDER BY startdate
                            LIMIT '.$n;
                    $results = $modx->db->query($eventsSQL);
                    return $results;
                }

                //-- Get Events Single Day and return the array
                function _getEventsSingleDay($date=null,$month="m"){
                    global $modx;
                    $date = (!is_null($date)) ? $date : date("Y-m-d") ;
                    $enddate = strtotime ( "+1 month" , strtotime ( date("Y-$month-1") ) ) ;
                    $enddate = date ( "Y-m-d" , $enddate );
		    
                    $eventsSQL = 'SELECT *,E.id as eid, E.category as catID, C.name as category, (SELECT DATEDIFF(E.enddate, E.startdate)) as DurationDays, (SELECT TIMEDIFF(E.endtime, E.starttime)) as DurationTime, `repeat` 
                            FROM '.$modx->getFullTableName($this->tables['events']).' as E
                            LEFT JOIN '.$modx->getFullTableName($this->tables['categories']).' as C
                             ON E.category = C.id
                            WHERE (startdate >= \''.$date.'\' and enddate < ADDDATE(\''.$date.'\', INTERVAL 1 MONTH))  or `repeat` REGEXP \'[[:alnum:]]+\' and E.active=1
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
			    $repeatOccurances = explode(',', $data['repeat']);
                            if(!empty($data['repeat']) && $match[1] !== $dataPieces[1]){
				
				foreach($repeatOccurances AS $rep){
					$endRepDayOfMonth = explode('-', $rep);
					$endRepDayOfMonth = (int)$endRepDayOfMonth[2];
					
					$endRepDayPieces = explode('-', $rep);
					//echo "<br />REP: $rep   ".$endRepDayPieces[1] .'=='. $dataPieces[1];
					if($endRepDayOfMonth<=$endDayOfMonth  && $endRepDayPieces[1] == $dataPieces[1])
						$eventsByDay[$endRepDayOfMonth][] = array(
							//'endDay' => $endDayOfMonth,
							'id'=>$data['eid'],
							'title'=>$data['title'],
							'description'=>$data['description'],
							'DurationDays'=>$data['DurationDays'],
							'DurationTime'=>$data['DurationTime'],
							'link' => $data['link'],
							'linkrel' => $data['linkrel'],
							'linktarget' => $data['linktarget'],
							'start' => $rep,
							'startdate' => $data['startdate'],
							'starttime' => $data['starttime'],
							'end' => $rep,
							'enddate' => $data['enddate'],
							'endtime' => $data['endtime']
						);
						
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
                    $result = $modx->db->select( 'id, name', $table, '', 'name ASC', '' );
                    $list = $modx->db->makeArray( $result );
                    //$listArr = array();
                    foreach( $list as $p_val ) {		
                            $listArr[] = array($p_val['id']=>$p_val['name']);
                    }
                    return $listArr;
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
                    $formEntries = '<div class="fm_row"><label>'.$label.'</label><div class="fm_entry">';
                    $fmDATE = ($_POST['fm'.$field]) ? $_POST['fm'.$field] : $val;
                    $formEntries .= <<<EOF
                    <input id="fm{$field}" name="fm{$field}" class="DatePicker" value="{$fmDATE}" onblur="documentDirty=true;" /><a onclick="document.forms[0].elements['fm{$field}'].value=''; return true;" onmouseover="window.status='Remove date'; return true;" onmouseout="window.status=''; return true;" style="cursor:pointer; cursor:hand"><img src="/manager/media/style/MODxCarbon/images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="Remove date" /></a>
EOF;
                    $formEntries .= $tooltip.'</div><div style="display:block;height:7px;clear:both;"></div>';
                    $formEntries .= "\n<script type=\"text/javascript\">
                                        window.addEvent('domready', function(){
                                                var dpOffset = -9;
                                                var dpformat =\"YYYY-mm-dd hh:mm\"; //hh:mm
                                                \t\t new DatePicker($('fm{$field}'), {'yearOffset': dpOffset,'format':dpformat});\n
                                        });
                                       </script>\n";
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
		    //$frequencymode = 'm';
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
				if(strtotime($occurance) <= strtotime($endDate) && $x < $frequency ){
				    $ar_Recur[] = $occurance;
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
				if(strtotime($occurance) <= strtotime($endDate) && $x < $frequency ){
				    $ar_Recur[] = $occurance;
				    if($debug) echo $occurance."< -is less than -> ".$endDate.'<br />';
				}
				else{
				    if($debug) echo $occurance."||-is eq or greater than -||".$endDate.'<br />';
					break;
				}
			    }
			    break;
			case "w":
			    while (++$x){
				$occurance = date('Y-m-d  H:i:s', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate)) , date('d', strtotime($startDate))+(($x*7)*$interval), date('y', strtotime($startDate)) ) );
				    //////////////////////////////////////////////
				    $occurance2 = date('Y-m-d', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate))+$x , date('d', strtotime($startDate)), date('y', strtotime($startDate))) );
				    $newdatePieces = explode("-", $occurance2);
				    $jd=cal_to_jd( CAL_GREGORIAN, $newdatePieces[1],$newdatePieces[2], $newdatePieces[0] );
				    $jdow = jddayofweek($jd);
				    $isMatch = false;
				    if(!empty($onwd)){
					while (list ($key,$val) = @each ($onwd)) {
					    $isMatch = true; 
					}
				    }
				    //////////////////////////////////////////////
				if((strtotime($occurance) <= strtotime($endDate) || (in_array($jdow,$onwd) && strtotime($occurance) <= strtotime($endDate)) ) && $x < $frequency ){
				    $ar_Recur[] = date('Y-m-d', strtotime($occurance));
				    if($debug) echo $occurance."< -is less than (jd->".$jd."|jdow->".$jdow.") -> ".$endDate.'<br />';
				}
				else{
				    if($debug) echo $occurance."||-is eq or greater than (jd->".$jd."|jdow->".$jdow.") -||".$endDate.'<br />';
				    if($x>=$frequency)
					break;
				}
			    }
			    break;    
			case "y":
			    while (++$x){
				$occurance = date('Y-m-d  H:i:s', mktime(date('H', strtotime($startDate)), date('i', strtotime($startDate)), 0, date('m', strtotime($startDate)) , date('d', strtotime($startDate)), date('y', strtotime($startDate))+($x*$interval) ) );
				if(strtotime($occurance) <= strtotime($endDate) && $x < $frequency ){
				    $ar_Recur[] = $occurance;
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
			//if($this->debug)
				if($debug){
				echo "THE OCC DATES:<br />";
				print_r($ar_Recur);
				}
			return implode(',', $ar_Recur);
			//--Used for debugging
			/*
			foreach($ar_Recur as $eventitem){
			    SWITCH($dateTimeFormat){
				default:
				case 'full':
				    echo date($dateFormat.' '.$timeFormat, strtotime($eventitem)).' - '.date($timeFormat, strtotime($endDate)).'<br />';
				    break;
				case 'date':
				    echo date($dateFormat, strtotime($eventitem)).'<br />';
				    break;
				case 'time':
				    echo date($timeFormat, strtotime($eventitem)).' - '.date($timeFormat, strtotime($endDate)).'<br />';
				    break;
			    }
			}
			*/
		    }
		}

        }
}

//-- create new mxCalendar
if(class_exists("mxCal_APP_CLASS")){
	$mxCalApp = new mxCal_APP_CLASS();
}
?>