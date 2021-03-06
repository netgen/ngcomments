Netgen Comments extension installation instructions
===================================================

Requirements
------------

* eZ Publish 4.7+
* eZ JSCore
* eZ Comments extension


Important info to know before using Netgen Comments
---------------------------------------------------

After activating Netgen Comments on freshly installed eZ Publish instance with
eZ Comments enabled, all commenting capabilities will be automatically turned
into their respective AJAX versions. In this case, you don't need to take any
extra steps to get the extension working.

However, if you have an eZ Publish instance in which you already created your own
designs, and by that, modified default eZ Comments templates to suit your needs,
you will need to modify your commenting templates to match Netgen Comments.
This is because templates in Netgen Comments, although originating from eZ Comments,
are modified in a way to support AJAX based editing. Modifications were not intensive,
but nevertheless they are not compatible with standard eZ Comments templates.


Installation
------------

 1. Unpack/unzip

    Unpack the downloaded package into the `extension` directory of your eZ Publish installation.

 2. Activate extension

    Activate the extension by using the admin interface ( Setup -> Extensions ) or by
    prepending `ngcomments` to `ActiveExtensions[]` in `settings/override/site.ini.append.php`:

    ````ini
    [ExtensionSettings]
    ActiveExtensions[]=ngcomments
    ````

 3. Regenerate autoload array

    Run the following from your eZ Publish root folder

    `php bin/php/ezpgenerateautoloads.php --extension`

    Or go to Setup -> Extensions and click the "Regenerate autoload arrays" button

 4. Clear caches

    Clear all caches (from admin 'Setup' tab or from command line).
