# Installation::Manager #

  1. Download the Modx\_mxCalendar.zip file
  1. Unzip folder to your favorite place
  1. Upload mxCalendar folder to your sites root **/assets/modules/** folder
  1. Copy contents of "snippets/mxCalendar.module.txt" file from the unzipped folder
  1. Log into your Manager interface and goto the Modules > Manage Modules section
  1. Select the **New Module** button
  1. In the **Module name** field place **mxcalendar**
  1. Past the content of "snippets/mxCalendar.module.txt" into the **Module code (php)** section
```
/**
 * Author: Charles Sanders @ BIGSHOT Interactive
 * Date: 02/14/2010
 * Version: 0.0.1
 * 
 * Purpose: Creates a easy module for administrators to manage events.
 * For: MODx CMS 0.9.6 - 1.0.X (www.modxcms.com)
**/
//-- Get the language file 
include_once $modx->config['base_path'].'assets/modules/mxCalendar/lang/en-EN.lang';

//-- include core class file
include_once $modx->config['base_path'].'assets/modules/mxCalendar/mxCalendar.tpl.php';

return $output;
```
  1. Select **Save**
  1. Click the gear icon next to the new entry "mxcalendar" and select **Run Module**
  1. You should see a screen saying the installation was successful, so click the **Start** button
  1. Now you are in the new manager

_note: you need to select another menu tab or log-out and back in to view the new Menu item under the tabs for Modules, which should be labeled "mxcalendar"._


# Installation::Snippet #
  1. Assuming you are still logged into the Manager select **Elements > Manage Elements > Snippets**
  1. Select **New Snippet**
  1. In the Snippet name field put **mxcalendar**
  1. Copy and past content of "snippets/mxCalendar.snippet.txt" into the **Snippet code (php)** section
```
<?php
/**
 * Author: Charles Sanders
 * Date: 02/14/2009
 * Version: 0.0.1
 * 
 * Purpose: Creates a easy module for administrators to manage events.
 * For: MODx CMS 0.9.6 - 1.0.X (www.modxcms.com)
 *
 * Visit http://code.google.com/p/mxcalendar/ for full list of parameters
 *
 * Enjoy!
**/
global $modx;

//-- Get LANGUAGE file
include_once $modx->config['base_path'].'assets/modules/mxCalendar/lang/en-EN.lang';

//-- include core class file
include_once $modx->config['base_path'].'assets/modules/mxCalendar/mxCalendar.class.php';

//-- Example Use ----- //
// [!mxcalendar? &ajaxPageId=`50` &showTimeSpan=`false` &showDuration=`true`!]
// @ajaxPageId is the resource (page) on your site with the same snippet call to the calendar
//             with the "Uses Template" set to (blank)
// ------------------- //

//-- Setup the parameters for calendar
//**** THE BASICS JUST TO MAKE SURE ****//
$bsCalParams = array(
 'type'=>(isset($type)?$type:'full'),
 'fullCalendarPgId'=>(isset($fullCalendarPgId)?$fullCalendarPgId:$modx->documentIdentifier),
 'ajaxPageId'=> (isset($ajaxPageId)?$ajaxPageId:NULL),
 'showTimeSpan' => (isset($showTimeSpan)? $showTimeSpan : true),
 'showDuration' => (isset($showDuration)? $showDuration : false),
 'excludeWeekends' => (isset($excludeWeekends)? $excludeWeekends : false),
 'startDayID'=>(isset($startDayID)? $startDayID: 0),
);

//**** Aux Parameters ****//
if(isset($type))
  $bsCalParams['type'] = $type;
if(isset($tplWrap))
  $bsCalParams['tplWrap'] = $tplWrap;
if(isset($tplWrapClass))
  $bsCalParams['tplWrapClass'] = $tplWrapClass;
if(isset($tplWrapId))
  $bsCalParams['tplWrapId'] = $tplWrapId;
if(isset($tplEvent))
  $bsCalParams['tplEvent'] = $tplEvent;
if(isset($maxCnt))
  $bsCalParams['maxCnt'] = $maxCnt;
if(isset($ajaxPaginate))
  $bsCalParams['ajaxPaginate'] = $ajaxPaginate;
if(isset($fullCalendarPgId))
  $bsCalParams['fullCalendarPgId'] = $fullCalendarPgId;
if(isset($linkText))
  $bsCalParams['linkText'] = $linkText;

return $mxCalApp->MakeCalendar($bsCalParams);
?>
```

# Template Udates for CSS/JS #
  1. Add the following to your template head area
```
<link rel="stylesheet" type="text/css" href="assets/modules/mxCalendar/styles/mxCalendar.css" /> 
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
```
> > _note: This is assuming (a): your site is at web-root (b): you set the `<base href="``[(site_url``)]"></base>`_

# Give it a Try #
  1. Now simply place the snippet cod block into your resource page where you want the calendar to display using default settings.

> ` [!mxcalendar?!] `

  1. A quick example of the AJAX mode would be as follows, except make sure you also have a resource page with the same snippet code using the **(blank)** page template to return only the snippet results. @ajaxPageID = (blank) template resource id
```
[!mxcalendar? &ajaxPageId=`50` !] 
```
```
Note:
@ajaxPageID = (blank) template resource id
```

# Internationalization #
You can add or change the language file by updating the language include call in both the snippet and module code blocks.

```
//-- Get LANGUAGE file
include_once $modx->config['base_path'].'assets/modules/mxCalendar/lang/en-EN.lang';
```

Just change the "en-EN.lang" with the new file you loaded to the same folder.