# Other Page Languages plugin for Typesetter CMS #

## About

With 'Other Page Languages' you can manage pages in different languages on your Typesetter CMS website. Although it has some similarities to [Multi-Language Manager](https://github.com/Typesetter/Multi-Language), it has a different purpose.
Where Multi-Language Manager is intended to manage entire multilingual websites, this plugin focuses on only offering individual pages in alternative languages. This can make sense, for example, if you offer certain products or services to certain target groups. The plugin was developed for an international franchise with a steadily growing number of local branches and languages. Of course, it can also serve well in other cases.

* Informs a visitor that the current page is also available in other language(s).
* Checks if the web browser's preferred language(s) are available and emphasizes them, or, if enabled, automatically redirects to the most suitable language (Multi-Language Manager will only do this on the homepage).
* Adds a 'Other Languages' icon to the Admin Top Bar which opens a dialog for editing the language assignments and page-associations.
* Although (or rather because) the plugin works independently of Multi-Language Manager, I would advise against using both in parallel.

See also [Typesetter Home](https://www.typesettercms.com), [Typesetter on GitHub](https://github.com/Typesetter/Typesetter)


## How To Use ###
The plugin comes with `OtherPageLangs` Typesetter Gadgets to tell the visitor about other available languages. Currently (as of ver. 1.0), we have two of them:
* `OtherPageLangs` is the name of the default Gadget. It will work in all themes.
* `OtherPageLangs_BS4` is the gadget suited for Bootstrap 4-based themes.

More may follow.

### Add the Gadget ###
To add the Gadget to your website, choose one of the following methods:
* If there is already a suitable 'area slot' in your theme's template, insert the gadget there via Layout Manager. Most themes do not have a suitable slot, which would rather be located somewhwere between main menu and content area (maybe add one if you know how)
* Alternatively, if you have file access to template.php, you might want to call the Gadget directly at an appropriate code position by inserting `<?php gpOutput::GetGadget('OtherPageLangs'); ?>`.
* The most flexible but potentially laborious way is to add the Gadgets to your pages' content by using 'File Inlude' sections. With this method, make sure to always add the Gadget to all associated pages.

You can't see your Gadget? Everything is fine, read on!

### Principles ###
To make the plugin do its job, it needs to know a few things. It needs to know which pages belong to each other in order to form what we call 'page-associations'. And it also needs to know what language each page is in.
As long as no such page-associations are defined, the Gadget will not show up at all. That's intended - there is still nothing to tell. So you need to create such page-associations. Before you start, here is the logic:
* You can associate as many pages as you want under the following conditions.
* Each page can only appear once and only inside one single association. When attached to a new association, it will be detached from a possible former one.
* Each language can only appear once inside an association.
* Each page must have a language assigned to it. Changing a page's language later is always possible but it must not conflict with other languages inside the association.

### Assigning Languages to Pages and Associate Them ###
In order to create page-language associations …
* Log into your Typesetter site. OK, you guessed this one ;)
* If you haven't already done so, create the pages (in different languages) you want to link to each other. You cannot associate pages that do not yet exist. However, they do not have to be in a menu.
* Navigate to one of these pages, it doesn't matter which one.
* Once the plugin is installed, there is a new button in the Admin Top Bar. Click it.
* A dialog box pops up which lists the currently associated pages. It initially only contains the current page.
* Next to the current page entry there is the language dropdown. It will initially show Typesetter's user interface language. If you want to change it, do it right now. 
* To change a page's language you first need to unlock the dropdown via the gray lock icon next to it.
* At the bottom of the list you see the 'New association' row with the 'Add' link. Click it to add another page to the list.
* The new field on the left will show an 'autocomplete' list of all available pages. Select one. 
* It case the selected page already has a language assigned to it, it will show up in the (then locked) language dropdown. If not, select one.
* Once you added all pages, click the 'Save' button.
* Now the Gadget should show up.

### Automatic Redirection ###
Automatic Redirection will not redirect logged-in users. If you want to check if it works, simply open your Typesetter website in a private window or different browser, or log-out.
The fact that the button is located in the top right corner of the config dialog leads to the assumption that the setting would only apply to the current page-association, but that's not true. In fact, it switches a global setting. As soon as an Admin Page is implemented, this will most likely change.


## Screenshots ##
![Screenshot-01-Config](/screenshot-01-config.jpg?raw=true)
![Screenshot-02-Gadget](/screenshot-02-gadget.jpg?raw=true)


## Supported Languages ##
36, see [/i18n/i18n.php](https://github.com/juek/OtherPageLangs/blob/master/i18n/i18n.php)


## Current Version ##
1.0


## TODOs ##
* i18n of the admin UI
* implement MenuPageTrashed hook to automatically purge leftover entries
* plugin admin page to show all and possibly edit associations
* add more gadgets
* add more languages


## Change Log ##
* 1.0 Intial version


## Requirements ##
* Typesetter CMS 5.0+


## Manual Installation ##
1. Download the [master ZIP archive](https://github.com/juek/OtherPageLangs/archive/master.zip)
2. Upload the extracted folder 'OtherPageLangs-master' to your server into the /addons directory
3. Install using Typesetter's Admin Toolbox &rarr; Plugins &rarr; Manage &rarr; Available &rarr; Other Page Languages


## License ##
GPL version 2
