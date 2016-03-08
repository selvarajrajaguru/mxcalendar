# Installation::Manager #

  1. Download the Modx\_mxCalendar.zip file
  1. Unzip folder to your favorite place
  1. Upload mxCalendar folder to your sites root **/assets/modules/** folder
  1. Copy contents of "snippets/mxCalendar.module.txt" file from the unzipped folder
  1. Log into your Manager interface and goto the Modules > Manage Modules section
  1. Select the **New Module** button
  1. In the **Module name** field place **mxcalendar**
  1. Past the content of "snippets/mxCalendar.module.txt" into the **Module code (php)** section
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


# Template Udates for CSS/JS #
  1. These are now automatically added to your rendered HTML head area via the module, no coding or template changes required.


# Give it a Try #
  1. **mxCalendar (default display)**: Place the basic snippet coed block into your resource page where you want the calendar to display using default settings.
> ` [!mxcalendar?!] `

  1. **mxCalendar w/AJAX**: A quick example of the AJAX mode would be as follows, except make sure you also have a resource page with the same snippet code using the **(blank)** page template to return only the snippet results. @ajaxPageID = (blank) template resource id
```
[!mxcalendar? &mxcAjaxPageId=`50` !] 
```
```
Note:
@mxcAjaxPageID = (blank) template resource id
```

  1. **AJAX Response mxCalendar Page**: The AJAX response page would have the snippet call setup with the &mxcFullCalendarPgId parameter set to link back to full calendar view as follows
```
[!mxcalendar? &mxcFullCalendarPgId=`48` !]
```

# Internationalization #
This module now supports this via selecting the ModX CMS configuration langauage settings and checking for that matching language file in the mxCalendar language folder. If that language file is not present then the default English version will be used.

To create a new language file, copy the current english.lang file in the /assets/modules/mxCalendar/lang/ folder and save it to the same folder with the proper language name before the ".lang" file extension. Then simply do your translations, please send your translated version to us so we can include them in future releases.

# Theme #

... working on documentation ...