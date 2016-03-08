# Calendar with Defaults #

`[!mxcalendar!]`

# Calendar with Duration, no Time stamp, AJAX detail view #

`[!mxcalendar? &ajaxPageId=````50````` &showTimeSpan=`````false````` &showDuration=`````true````` !]``
  * ajaxPageId: The number to the MODx resource document ID that has the _mxcalendar_ snippet set with the **(blank)** template
    * In the AJAX resource page place the basic `[!mxcalendar!]` call
  * showTimeSpan: False=Remvoes the _startTime_ and _endTime_ values from display
  * showDuration: True=Displays the event total time as a duration


# Event List with template, click through headline, 3 events #

`[!mxcalendar?  &type=````list````` &maxCnt=`````3````` &fullCalendarPgId=`````49````` &linkText=`````See More Events````` !]``