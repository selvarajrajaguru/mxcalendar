Release Notes: 0.0.6 - Apr 24, 2010
**Updated FileList:
> |- mxCalendar.class.php
> |- lang/english.lang
> |- includes/google\_geoloc.class.inc
> |- includes/calendar.inc.php
> |- includes/install/0.0.6.upgrade.mysql
> |- includes/install/mxCalendar.mysql.install.inc
> |-**snippets/mxCalendar.module.txt
> |- **snippets/mxCalendar.snippet.txt
> |- themes/default/css/mxCalendar.css
> |- themes/default/views/month.inner.day.event.html**


  * = Manual update required of Snippet and/or Module code in ModX manager using the new code file

Change Log (not all inclusive):
- Repeating date detail view now list activly click event date not the parent date
- Added date/time stamp to Event List theme [configuration,theme](manager.md)(exp: [+mxcEventListItemStateDateStamp+] - [+mxcEventListItemEndDateStamp+])
- Manager Add Event all day event flag for quick date selection
- ToolTip enabled for Monthly view roll-over TT of first 75 words of event description
- Google Address space in address fix, effected some systems not all
- Google api key removed (support now through V3 AJAX)
- Google API V3 upgrade
- Google map enable/disable lat/lng from display added to configuration tab
- Manager Configuration support to adjust map width and height
- Updated typo of 'Calendear' in configuration tables and code references
- Added category options to configuration tab
- Expanded category features to UI for event filtering
- Extended support of Internationalization date display
- Fixed tooltip reference and typos in configuration tab
- Extended support of manager theme icons other then default
- New configuration value for mutliple day event list CSS style when spanning more than one day per event (not repeat, but acutal event duration)
- Added entries to language file

New Placeholders
File: event.list.event.html
::mxcEventListItemTimeStyle
::mxcEventListItemStateDateStamp
::mxcEventListItemEndDateStamp

File: month.inner.day.event.html
::mxcMonthInnerEventCategory   