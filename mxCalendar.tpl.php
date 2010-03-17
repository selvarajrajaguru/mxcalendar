<?php
/**
 * Author: Charles Sanders
 * Date: 02/14/2010
 * Version: 0.0.1
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

define('CAL_URL', '');
define('CAL_VERSION', '0.0.1');

// define base path
define('CAL_MOD_PATH', $basePath.'assets/modules/mxCalendar/');
define('CAL_CONFIG_PATH', CAL_MOD_PATH . 'config/');


/* No support for multi-lingual files currently
//-- include language file
$manager_language = $modx->config['manager_language'];
$sql = "SELECT setting_name, setting_value FROM ".$modx->getFullTableName('user_settings')." WHERE setting_name='manager_language' AND user=" . $modx->getLoginUserID();
$rs = $modx->db->query($sql);
if ($modx->db->getRecordCount($rs) > 0) {
    $row = $modx->db->getRow($rs);
    $manager_language = $row['setting_value'];
}

include_once CAL_MOD_PATH.'lang/english.lang.php';
if($manager_language!="english")
{
    if (file_exists(CAL_MOD_PATH.'lang/'.$manager_language.'.lang.php'))
    {
         include_once CAL_MOD_PATH.'lang/'.$manager_language.'.lang.php';
    }
}
*/

//-- get theme
$tb_prefix = $modx->db->config['table_prefix'];
$theme = $modx->db->select('setting_value', '`' . $tb_prefix . 'system_settings`', 'setting_name=\'manager_theme\'', '');
$theme = $modx->db->getRow($theme);
$theme = ($theme['setting_value'] <> '') ? '/' . $theme['setting_value'] : '';

//-- include core class file
include_once CAL_MOD_PATH.'mxCalendar.class.php';

//-- output
//--- head
$output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html '.($modx->config['manager_direction'] == 'rtl' ? 'dir="rtl"' : '').' lang="english" xml:lang="english"> 
		<head>
                <title>mxCalendar Manager</title> 
                <script type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="media/style' . $theme . '/style.css" />
                
                <link rel="stylesheet" type="text/css" href="'.$siteURL.'assets/modules/mxCalendar/styles/mxCalendar.css" />
                
                <script type="text/javascript" src="'.$siteURL.'manager/media/script/tabpane.js"></script>
		<script type="text/javascript" src="'.$siteURL.'assets/modules/mxCalendar/scripts/jquery-1.3.2.min.js"></script>
                
    <script src="'.$siteURL.'manager/media/script/mootools/mootools.js" type="text/javascript"></script>
    <script src="'.$siteURL.'manager/media/script/mootools/moodx.js" type="text/javascript"></script>
                        <script type="text/javascript" src="'.$siteURL.'manager/media/calendar/datepicker.js"></script>
			<script type="text/javascript">
                        window.addEvent(\'domready\', function(){
                            var Tips1 = new Tips($$(\'.Tips1\'));
                                var dpOffset = -9;
                                var dpformat ="YYYY-mm-dd";
                                //new DatePicker($(\'fmstartdate\'), {\'yearOffset\': dpOffset,\'format\':dpformat});	
                        });
                       </script>
</head>';
                
//--- body
$output .= '<body>
            <div class="sectionHeader">mxCalendar Manager</div>
            <div class="sectionBody">
                <div class="tab-pane" id="tabPanel">
                    <script type="text/javascript"> 
                        tpResources = new WebFXTabPane( document.getElementById( "tabPanel" ) ); 
                    </script>
            ';

//-- setup
//-- setup
if(!file_exists( CAL_CONFIG_PATH.'config.xml')){
    $output .= $mxCalApp->_install_mxCalendar();
} else {
    //-- Render module actions

    //--- tab: Manage Items
    $output.= '<div class="tab-page" id="tabTemplateVariables">  
		<h2 class="tab">Events</h2>  
		<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables" ) );</script> 
		    ';  
    $output.=$mxCalApp->ListEvents(true);
    $output.='<p>&nbsp;</p>
	      </div>';
    
    //--- tab: Configuration Manager
    /*
    $output.= '<div class="tab-page" id="tabTemplateVariables3">  
		<h2 class="tab">Configuration</h2>
		<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables3" ) );</script> 
		';
    $output.= $mxCalApp->Configuration();
    $output.='<p>&nbsp;</p></div>';
    */
    
    //--- tab: Add event       
    $output.= '<div class="tab-page" id="tabTemplateVariables2">  
		<h2 class="tab">Add New Event</h2>
		<script type="text/javascript">tpResources.addTabPage( document.getElementById( "tabTemplateVariables2" ) );</script> 
		';
    $output.= $mxCalApp->AddEvent(true);
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