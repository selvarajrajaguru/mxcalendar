# Calendar (monthly) View #

## File: month.container.html ##
**Description: Outer most container**

### Placeholders: ###
  * mxcMonthContainerID - Outer most container CSS ID
  * mxcMonthContianerClass - Outer most container CSS Class name
  * mxcMonthCotianerHeading - Contianer for navigation, month, and year; not required if using place holders outside theme file
  * mxcMonthInsideContianer - Inside container where calendar layout is wrapped


## File: month.heading.html ##
**Description: Used to display theme controlled layout of calendar navigation(prev/next), month, and year**

### Placeholders: ###
  * mxcMonthHeadingPrevious - Previous month navigation link
  * mxcMonthHeadingNext - Next month navigation link
  * mxcMonthHeadingLabelMonth - Display the assigned month display (ex: April)
  * mxcMonthHeadingLabelYear - Display the assigned year dispaly (ex: 2010)



## File: month.inner.container.html ##
**Description: Inside container for the monthly calendar view.**

### Placeholders: ###
  * mxcMonthInnerContainerID - Inner container CSS ID
  * mxcMonthInnerContainerClass - Inner container CSS Class name
  * mxcMonthInnerRows - Inner container row item holder



## File: month.inner.row.html ##
**Description: Inner container row container
Chunk Override Parameter: &mxcTplMonthRow**

### Placeholders: ###
  * mxcMonthInnerRowID - Inner row container CSS ID
  * mxcMonthInnerRowClass - Inner row container CSS Class name
  * mxcMonthInnerRowDays - Inner row container row item holder


## File: month.inner.day.base.html ##
**Description: Outer date container used for empty fills and headings; doesn't contain all placeholders like the month.inner.day.html theme file
Chunk Override Parameter: &mxcTplMonthDayBase**


## File: month.inner.day.html ##
**Description: Outer Date container
Chunk Override Parameter: &mxcTplMonthDay**

### Placeholders: ###
  * mxcMonthInnerDayID - Day container CSS ID
  * mxcMonthInnerDayClass - Day container CSS class (setup in config tab)
  * mxcMonthInnerDayLabelClass - Day label CSS Class name
  * mxcMonthInnerDayLabel - Day of the month (1-[29-31])
  * mxcMonthOuterEventClass- Event list container CSS Class
  * mxcMonthOuterEventID - Event list container CSS ID
  * mxcMonthInnerEvents - Events listings


## File: month.inner.day.event.html ##
**Description: Event item(s) display for each event
Chunk Override Parameter: &mxcTplMonthEvent**

### Placeholders: ###
  * mxcMonthInnerEventID - Event list container CSS ID
  * mxcMonthInnerEventClass- Event list container CSS Class [default=Tips1]
  * mxcMonthInnerEventUID - Event unique id
  * mxcMonthInnerEventDescription - Event content/description text
  * mxcMonthInnerEventCategory - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - Event Category name displayed
  * mxcMonthInnerEventTitleClass - Event title CSS class [default=title]
  * mxcMonthInnerEventTitle - Event title string
  * mxcMonthInnerEventDuration - Events duration content
  * mxcMonthInnerEventTimestamp - Event timestamp content
  * mxcMonthInnerEventCategory - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.06-rc2] - Event category title holder



## File: month.inner.day.event.duration.html ##
**Descript: Event duration rendered format**

### Placeholders: ###
  * mxcMonthInnerEventDurationClass - Duration CSS class
  * mxcMonthInnerEventDurationDays - Duration of days (calculated days)
  * mxcMonthInnerEventDurationTime - time in hh:mm



## File: month.inner.day.event.timestamp.html ##
**Description: Event timestamp layout**

### Placeholders: ###
  * mxcMonthInnerEventTimestampClass - Event timestamp CSS class
  * mxcMonthInnerEventTimestampStart - Event start date/time
  * mxcMonthInnerEventTimestampEnd - Event end date/time


---


# Event List #

## File: event.list.event.html ##
**Description: Used to set the display for each event in the Event List (&mxcType=`list`) display**

### Placeholders: ###
  * mxcEventListItemId - event item container CSS ID property value
  * mxcEventListItemClass - event item container CSS Class property value
  * mxcEventListItemTitle - event item Title
  * mxcEventListItemLabelDateTime - **Not in use**
  * mxcEventListItemMonth - event item month holder
  * mxcEventListItemStartDateTime - event start date/time
  * mxcEventListItemDateTimeSeperator - event defined seperator for start and end date/times
  * mxcEventListItemEndDateTime - event end date/time
  * mxcEventListItemDateTimeReoccurrences - **Not in use**
  * mxcEventListItemLabelLocation - heading over address; presented only when address fields is entered on event
  * mxcEventListItemLocation - event location value
  * mxcEventListItemDescription - event description/content value
  * mxcEventListItemTimeStyle - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - CSS style class name
  * mxcEventListItemStateDateStamp - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - Valid PHP strftime format for start date/time of event
  * mxcEventListItemEndDateStamp - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - Valid PHP strftime format for start date/time of event
  * mxcEventListItemTimeStyle - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6-rc2] - CSS style class name
  * mxcEventListItemStateDateStamp - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6-rc2] - Event start date/time stamp PHP strftime() format
  * mxcEventListItemEndDateStamp - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6-rc2] - Event start date/time stamp PHP strftime() format


## File: event.list.container.html ##
**Description: Used to set the display for event list view**

### Placeholders: ###
  * mxcEventListContainerId
  * mxcEventListContainerClass
  * mxcEventListContainerTitle
  * mxcEventList
  * mxcEventListMoreClass
  * mxcEventListMoreLabel
  * mxcCategoryFilters - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6-rc2] - Category listing position