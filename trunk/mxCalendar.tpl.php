	<?php
	/**
	 * Author: Charles Sanders (charless.mxcalendar@gmail.com)
	 * Date: 01/01/2011
	 * Version: 0.1.2-rc2
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
	define('CAL_VERSION', '0.1.2-rc2');
	
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
			
			
	    <script src="http://ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools.js" type="text/javascript"></script>


<script src="media/script/mootools/moodx.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript" src="'.$siteURL.'assets/plugins/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>


	    
	<script type="text/javascript" src="'.$siteURL.'assets/modules/mxCalendar/scripts/datepicker2/datepicker.js"></script>
	<link rel="stylesheet" type="text/css" href="'.$siteURL.'assets/modules/mxCalendar/scripts/datepicker2/datepicker_jqui.css">
	
	<script type="text/javascript">
	window.addEvent(\'domready\', function(){
		var d1 = new DatePicker(\'.DatePicker.mxcStartDate\', { pickerClass: \'datepicker\', timePicker: true, format: \'Y-m-d H:i\', inputOutputFormat: \'Y-m-d H:i\' });
		var d2 = new DatePicker(\'.DatePicker.mxcEndDate\', { pickerClass: \'datepicker\', timePicker: true, format: \'Y-m-d H:i\', inputOutputFormat: \'Y-m-d H:i\' });
		var d3 = new DatePicker(\'.DatePicker\', { pickerClass: \'datepicker\', timePicker: false, allowEmpty: true, format: \'Y-m-d\', inputOutputFormat: \'Y-m-d\' });
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
	    
	    
	    //--- tab: Make a Donation
	    $output .= '
			<div class="tab-page" id="tabTemplateVariables4">  
			<h2 class="tab">Support mxCalendar</h2>
			<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables4" ) );</script>
			<div style="padding:20px;">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			    <input type="hidden" name="cmd" value="_s-xclick">
			    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
			    ">
			    <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			    <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			    </form>
			    <p>Please consider a donation to help support mxCalendar. Why is this a good thing, well it helps ensure that I can devote more time to mxCalendar make enhancements and fielding your request including support on your site or tweaks to fit your needs.<br /><br />Thank you for choosing mxCalendar and I hope you enjoy it.<br /><br />Cheers,<br />charless.mxCalendar</p>
			    </div>
			    <p>&nbsp;</p></div>';
	    
	    
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