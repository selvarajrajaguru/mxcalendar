# Parameters :: mxCalendar 0.0.1 Snippet #

## type ##

---

**Purpose**
> Set the rendering mode for the calendar to display in.

**Options**
> "full"
> "list"

**Default**
> "full"


## tplWrap ##

---

**Purpose**
> Set list type chunk outer wrapper template name.

**Options**
> Any valid MODx chunk with valid markers contained within
  * title
  * class
  * id

**Default**
> mxCalendar configured default


## tplWrapClass ##

---

**Purpose**
> User defined class name to tplWrap container.

**Options**
> Any valid user defined CSS class name

**Default**
> blank

## tplWrapId ##

---

**Purpose**
> User defined ID property to tplWrap container.

**Options**
> Any valid user defined CSS ID name

**Default**
> blank

## tplEvent ##

---

**Purpose**
> User defined chunk for inner Event detail list.

**Options**
> Any valid MODx chunk with valid markers contained within
  * month
  * day
  * title
  * location

**Default**
> mxCalendar configured default

## tplEventDetail ##
**Purpose**
> Usesr defined chunk for Event Detail view.

**Options**
> Any valid MDOx chunk with valid markers contained within
  * title
  * strDTLabel (text label before time range)
  * startTime
  * endTime
  * content
  * location
  * repeatDates

  * Google Map**_when option checked will display under all other chunk content_**Default**> mxCaledar configured default**

## maxCnt ##

---

**Purpose**
> Define max number of events to return

**Options**
> _Numeric_ [0-9]

**Default**
> _Numeric_ 5

## ajaxPaginate ##

---

**NOT IN USE IN RELEASE 0.0.1**


## fullCalendarPgId ##

---

**Purpose**
> Enable / Disable event listing linking from event list to calendar details

**Options**
> Valid ModX document ID of the _full_ type calendar page.

**Default**
> Non-linked heading


## linkText ##

---

**Purpose**
> Set button label to view full calendar at end of event _list_ type.

**Options**
> Any combination of characters [a-z], underscores, spaces, and numbers [0-9]

**Default**
> Language file defined value


## excludeWeekends ##

---

**Purpose**
> Enable / Disable weekend days from view (Saturday, Sunday)

**Options**
> true (1) / false (0)

**Defautl**
> _false_ = _Display weekends_


## startDayID ##

---

**Purpose**
> Set which week day to start from in the _full_ type view

**Options**
> _Numeric_ [- 6](0.md)  (0=Sunday,1=Monday,2=Tuesday,....)

**Default**
> _Numeric_ 0 (Sunday)

**Override**
> when _excludeWeekends_ is set to _true_ this defaults to 0

## showTimeSpan ##

---

**Purpose**
> Enable / Disable the _startTime_ and _endTime_ in display

**Options**
> true (1) / false (0)

**Default**
> true

## showDuration ##

---

**Purpose**
> Enable / Disable the event total duration in display

**Options**
> true (1) / false (0)

**Default**
> false