# Calendar (monthly) View #

## File: month.container.html ##
**Description: Outer most container**

### Placeholders: ###
  * xcMonthContainerID - Outer most container CSS ID
  * xcMonthContianerClass - Outer most container CSS Class name
  * xcMonthCotianerHeading - Contianer for navigation, month, and year; not required if using place holders outside theme file
  * xcMonthInsideContianer - Inside container where calendar layout is wrapped


## File: month.heading.html ##
**Description: Used to display theme controlled layout of calendar navigation(prev/next), month, and year**

### Placeholders: ###
  * xcMonthHeadingPrevious - Previous month navigation link
  * xcMonthHeadingNext - Next month navigation link
  * xcMonthHeadingLabelMonth - Display the assigned month display (ex: April)
  * xcMonthHeadingLabelYear - Display the assigned year dispaly (ex: 2010)



## File: month.inner.container.html ##
**Description: Inside container for the monthly calendar view.**

### Placeholders: ###
  * xcMonthInnerContainerID - Inner container CSS ID
  * xcMonthInnerContainerClass - Inner container CSS Class name
  * xcMonthInnerRows - Inner container row item holder



## File: month.inner.row.html ##
**Description: Inner container row container
Chunk Override Parameter: &mxcTplMonthRow**

### Placeholders: ###
  * xcMonthInnerRowID - Inner row container CSS ID
  * xcMonthInnerRowClass - Inner row container CSS Class name
  * xcMonthInnerRowDays - Inner row container row item holder


## File: month.inner.day.base.html ##
**Description: Outer date container used for empty fills and headings; doesn't contain all placeholders like the month.inner.day.html theme file
Chunk Override Parameter: &mxcTplMonthDayBase**


## File: month.inner.day.html ##
**Description: Outer Date container
Chunk Override Parameter: &mxcTplMonthDay**

### Placeholders: ###
  * xcMonthInnerDayID - Day container CSS ID
  * xcMonthInnerDayClass - Day container CSS class (setup in config tab)
  * xcMonthInnerDayLabelClass - Day label CSS Class name
  * xcMonthInnerDayLabel - Day of the month (1-[29-31])
  * xcMonthOuterEventClass- Event list container CSS Class
  * xcMonthOuterEventID - Event list container CSS ID
  * xcMonthInnerEvents - Events listings


## File: month.inner.day.event.html ##
**Description: Event item(s) display for each event
Chunk Override Parameter: &mxcTplMonthEvent**

### Placeholders: ###
  * xcMonthInnerEventID - Event list container CSS ID
  * xcMonthInnerEventClass- Event list container CSS Class [default=Tips1]
  * xcMonthInnerEventUID - Event unique id
  * xcMonthInnerEventDescription - Event content/description text
  * xcMonthInnerEventCategory - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - Event Category name displayed
  * xcMonthInnerEventTitleClass - Event title CSS class [default=title]
  * xcMonthInnerEventTitle - Event title string
  * xcMonthInnerEventDuration - Events duration content
  * xcMonthInnerEventTimestamp - Event timestamp content



## File: month.inner.day.event.duration.html ##
**Descript: Event duration rendered format**

### Placeholders: ###
  * xcMonthInnerEventDurationClass - Duration CSS class
  * xcMonthInnerEventDurationDays - Duration of days (calculated days)
  * xcMonthInnerEventDurationTime - time in hh:mm



## File: month.inner.day.event.timestamp.html ##
**Description: Event timestamp layout**

### Placeholders: ###
  * xcMonthInnerEventTimestampClass - Event timestamp CSS class
  * xcMonthInnerEventTimestampStart - Event start date/time
  * xcMonthInnerEventTimestampEnd - Event end date/time


---


# Event List #

## File: event.list.event.html ##
**Description: Used to set the display for each event in the Event List (&mxcType=`list`) display**

### Placeholders: ###
  * xcEventListItemId - event item container CSS ID property value
  * xcEventListItemClass - event item container CSS Class property value
  * xcEventListItemTitle - event item Title
  * xcEventListItemLabelDateTime - **Not in use**
  * xcEventListItemMonth - event item month holder
  * xcEventListItemStartDateTime - event start date/time
  * xcEventListItemDateTimeSeperator - event defined seperator for start and end date/times
  * xcEventListItemEndDateTime - event end date/time
  * xcEventListItemDateTimeReoccurrences - **Not in use**
  * xcEventListItemLabelLocation - heading over address; presented only when address fields is entered on event
  * xcEventListItemLocation - event location value
  * xcEventListItemDescription - event description/content value
  * xcEventListItemTimeStyle - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - CSS style class name
  * xcEventListItemStateDateStamp - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - Valid PHP strftime format for start date/time of event
  * xcEventListItemEndDateStamp - [[r0](https://code.google.com/p/mxcalendar/source/detail?r=0).0.6] - Valid PHP strftime format for start date/time of event


## File: event.list.container.html ##
**Description: Used to set the display for event list view**

### Placeholders: ###
  * xcEventListContainerId
  * xcEventListContainerClass
  * xcEventListContainerTitle
  * xcEventList
  * xcEventListMoreClass
  * xcEventListMoreLabel