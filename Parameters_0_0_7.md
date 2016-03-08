# Introduction #

Listing of all current module views parameters that can be set in the snippet request. Note that when a parameter is passed it will override both the language file and configuration setting(s) and be used for each matching display type.

# Global #

Global parameters used by all display functions inside the calendar class.

  * mxcFullCalendarPgId - ModX document resource ID of the page where the main full calendar view snippet call resides `[`integer`]`
  * mxcType - mxCalendar main flag to set which display to use `[`full=calendar (default), list=event list`]`
  * mxcAjaxPageId - mxCalendar Ajax response page; blank template page with snippet call `[`integer`]`
  * mxcDefaultCatId - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6-rc2] - (int) Set the default cateogry (id column in manager) of the events to disply
  * mxcDefaultCatIdLock = (boolean; default Null) Block the category listing when set to true; Also overrides the Manager
  * mxcStartDateFormat = Event details date/time format override; Valid PHP strftime() format
  * mxcEndDateFormat = Event details date/time format override; Valid PHP strftime() format

# Event List Parameters #

Possible parameters when using the snippet call with the &type=`list` call

  * mxcTplEventListWrap - Outer most template container for event list display `[`string chunk name`]`
  * mxcTplEventListWrapClass - set the wrapper CSS Class value `[`string`]`
  * mxcTplEventListWrapId - set the wrapper CSS Class value `[`string`]`
  * mxcTplEventListWrapTitle - Title heading displayed above the event listings `[`string`]`
  * mxcTplEventListMoreLink - Text link used to link to the full calendar view `[`string`]`
  * mxcTplEventListItemWrap - Template file for each individual calendar item in the list `[`string chunk name`]`
  * mxcEventListMaxCnt - Set the maximum number of events to display in the list `[`integer`]`
  * mxcEventListItemId - event item CSS ID property value `[`string`]`
  * mxcEventListItemClass - event item CSS Class property value `[`string`]`
  * _1_ mxcEventListAjaxPaginate - set the mxCalendar pagination for event list `[`true|false`]`
  * mxcStartDate = Set the event list start date using strtotime() format ex: &mxcStartDate=`````last month````` &mxcStartDate=`````-2 month`````
  * mxcEventListTitleLink = Disable event list title links `[`1|0; default 1`]`
  * mxcTypeLocked = (boolean; default fales) When set to true enables multiple mxCalendar display types to run forcing that ones display not to ajust.This is good for those wishing to have a sidebar rail Event List view as well as a content page of the full calendar.

# Calendar View Parameters #

Possible parameters when using the snippet call with the &type=`full` and is also the default snippet call

  * mxcShowTimeSpan - Enable or disable event timestamps for start and end display `[`true|false`]`
  * mxcShowDuration - Enable or disable event duration display `[`true|false`]`
  * mxcExcludeWeekends - Enable or disable weekends from dispaly `[`true=no weekends|false=display weekends`]`
  * mxcStartDayID - Set the day of week to start the calendar display `[`integer 0-6`]`
  * mxcMonthInnerContainerID - Inside container CSS ID property value `[`string`]`
  * mxcMonthInnerContainerClass - Inside container CSS Class property value `[`string`]`
  * mxcMonthContainerID- Inside container CSS ID property value `[`string`]`
  * mxcMonthContianerClass- Inside container CSS Class property value `[`string`]`
  * mxcTplMonthOuter - Chunk name for the outer most month container `[`month.container.html`]`
  * mxcTplMonthInner - Chunk name for the inside month container `[`month.inner.container.html`]`
  * mxcTplMonthHeading - Chunk name for theme heading override `[`month.heading.html`]`
  * mxcTplMonthDayBase - Chunk name for theme day outer wrap override `[`month.inner.day.base.html`]`
  * mxcTplMonthRow - Chunk name for theme row outer wrap override `[`month.inner.row.html`]`
  * mxcTplMonthEvent - Chunk name for the theme event item override `[`month.inner.day.event.html`]`
  * mxcTplMonthDay - Chunk name for the outer day theme override `[`month.inner.day.html`]`

# Event Detail View #

  * mxcTplEventDetail - Theme override for event detail view `[`string chunk name`]`
  * mxcDateTimeSeperator - Text to use to split the start and end date/times ex:&nbsp;-&nbsp; `[`string`]`
  * mxcEventDetailBackBtnClass - back to full calendar view link CSS class name `[`string`]`
  * mxcEventDetailBackBtnTitle - back to full calendar link text value `[`string`]`



_1_ Items build in for future releases