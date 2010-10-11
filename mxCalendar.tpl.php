	<?php
	/**
	 * Author: Charles Sanders (charless.mxcalendar@gmail.com)
	 * Date: 07/30/2010
	 * Version: 0.0.7-rc4
	 * 
	 * Purpose: Creates a easy module for administrators to manage events.
	 * For: MODx CMS 0.9.6 - 1.0.X(www.modxcms.com)
	**/
	global $theme;
	global $table;
	global $_lang;
	global $siteURL;
	
	$basePath = $modx->config['base_path'];
	$siteURL = $modx->config['site_url'];
	
	/** CONFIGURATION SETTINGS **/
	
	//-- set to false to hide past events
	$showPast = false;
	
	/** END CONFIGURATION SETTINGS **/
	
	define('CAL_URL', 'http://code.google.com/p/mxcalendar');
	define('CAL_VERSION', '0.0.7-rc4');
	
	// define base path
	define('CAL_MOD_PATH', $basePath.'assets/modules/mxCalendar/');
	define('CAL_CONFIG_PATH', CAL_MOD_PATH . 'config/');
	
	
	//-- get theme
	$tb_prefix = $modx->db->config['table_prefix'];
	$theme = $modx->db->select('setting_value', '`' . $tb_prefix . 'system_settings`', 'setting_name=\'manager_theme\'', '');
	$theme = $modx->db->getRow($theme);
	$theme = ($theme['setting_value'] <> '') ? '/' . $theme['setting_value'] : '';
	
	//-- include core class file
	include_once CAL_MOD_PATH.'mxCalendar.class.php';
	if(class_exists("mxCal_APP_CLASS")){
		$mxCalApp = new mxCal_APP_CLASS();
	}
	
	//-- output
	//--- head
	$output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html '.($modx->config['manager_direction'] == 'rtl' ? 'dir="rtl"' : '').' lang="english" xml:lang="english"> 
			<head>
			<title>mxCalendar Manager</title> 
			<script type="text/javascript"></script>
			<link rel="stylesheet" type="text/css" href="media/style' . $theme . '/style.css" />
			
			<link rel="stylesheet" type="text/css" href="'.$siteURL.'assets/modules/mxCalendar/themes/default/css/mxCalendar.css" />
			
			<script type="text/javascript" src="'.$siteURL.'manager/media/script/tabpane.js"></script>
			<script type="text/javascript" src="'.$siteURL.'assets/modules/mxCalendar/scripts/jquery-1.3.2.min.js"></script>
			
	    <script src="http://ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools.js" type="text/javascript"></script>
	    <script src="'.$siteURL.'assets/modules/mxCalendar/scripts/moodalbox121/js/mootools.js" type="text/javascript"></script>
	<script type="text/javascript" src="'.$siteURL.'assets/modules/mxCalendar/scripts/datepicker2/datepicker.js"></script>
	<link rel="stylesheet" type="text/css" href="'.$siteURL.'assets/modules/mxCalendar/scripts/datepicker2/datepicker.css">
	<script type="text/javascript">
						window.addEvent(\'domready\', function(){
							//new DatePicker(\'.DatePicker\', { pickerClass: \'datepicker_vista\', timePicker: true, format: \'Y-m-d H:i\' });
							$$(\'input.DatePicker\').each( function(el){
								new DatePicker(el);
							});
	
						});
					       </script>
	
			       
			       
	</head>';
			
	//--- body
	$output .= '<body>
		    <div class="sectionHeader">mxCalendar Manager <span id="mxcVersion">Version: '.$mxCalApp->_getConfigVersion().'</span></div>
		    <div class="sectionBody">
			<div class="tab-pane" id="tabPanel">
			    <script type="text/javascript"> 
				tpResources = new WebFXTabPane( document.getElementById( "tabPanel" ) ); 
			    </script>
		    ';
	
	//-- setup
	if(!file_exists( CAL_CONFIG_PATH.'config.xml')){
	    $output .= $mxCalApp->_install_mxCalendar();
	} elseif ($mxCalApp->_getConfigVersion() != CAL_VERSION){
	    //-- check for upgrade script next version
	    $output .= $mxCalApp->_upgrade_mxCalendar();
	} else {
	    //-- Render module actions after form actions handled
	    $mxCalApp->update_Configuration();
	    $mxCalApp->_mgrAddEdit();
	   
	    
	
	    //--- tab: Manage Items
	    $output.= '<div class="tab-page" id="tabTemplateVariables">  
			<h2 class="tab">Events</h2>  
			<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables" ) );</script> 
			    ';  
	    $output.=$mxCalApp->ListEvents(true);
	    $output.='<p>&nbsp;</p>
		      </div>';
	    
	    //--- tab: Add event       
	    $output.= "\n<!-- start event -->\n".'<div class="tab-page" id="tabTemplateVariables2">  
			<h2 class="tab">Add/Edit Event</h2>
			<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables2" ) );</script> 
			';
	    $output.= $mxCalApp->AddEvent(true);
	    $output.='<p>&nbsp;</p></div>'."\n<!-- end event -->";    
	
	
	    //--- tab: Category Manager
	    $output.= '<div class="tab-page" id="tabTemplateVariablesCat">  
			<h2 class="tab">Categories</h2>
			<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariablesCat" ) );</script> 
			';
	    $output.= $mxCalApp->CategoryMgr(); 
	    $output.='<p>&nbsp;</p></div>';
	
	    //--- tab: Configuration Manager
	    $output.= '<div class="tab-page" id="tabTemplateVariables3">  
			<h2 class="tab">Configuration</h2>
			<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables3" ) );</script> 
			';
	    $output.= $mxCalApp->Configuration();
	    $output.='<p>&nbsp;</p></div>';
	    
	    
	    //-- end tab-pane
	    $output .= '</div>';
	
	}
	
	//-- end body
	$output .='
		      </div>
		    </body>';
	
	//--- footer
	$output .= '';
	
	?>