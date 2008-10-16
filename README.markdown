`mcw_templates`
===============

`mcw_templates` is a TextPattern admin plugin that enables the trivial export of pages, forms, and CSS rules to files in a specified folder for convenient editing, and the subsequent import of new and updated files.  This means that you're no longer bound to the `textarea` inside TextPattern when you work on your site's structure and design.

The plugin supports multiple sets of templates, so you can rapidly import a new template, or export your current template for external editing without disturbing templates you've exported in the past.

Download
--------

[Download the current version of the plugin (__0.2__)][1].

[1]: /file_download/4

Requirements
------------

This plugin is __beta__ in every sense of the word, as it's only been tested on my <strong>4.03</strong> installation.  It might work on other version, but no promises!

Regardless of where it's been tested, this plugin messes around with your database.  _Do not use it without backing up your database_.

Setup
-----

By default, the plugin looks for a directory named `_templates`
in your `textpattern` directory.  If the directory doesn't exist, the plugin will attempt to create it the first time you export your templates. This creation will almost certainly fail, since the`textpattern` directory usually isn't writable.  In that case, you'll need to create this  directory, and ensure that the web server has write access.  If your site is hosted at `/users/home/myuser/web/public/`, then the following commands    could be used:
    
    cd /users/home/myuser/web/public/
    mkdir ./textpattern/_templates
    chmod 777 ./textpattern/_templates

Usage
-----

To use the plugin, simply select 'import' or 'export' from the dropdown on the plugin's tab (e.g. `extensions` -> `Template Files`).

When exporting, you're asked for an export set name, which is used as the subdirectory name.  On import, you're asked to choose from the export sets available.  Additionally, an export into `preimport-data` is run before each import for backup purposes.

Couldn't be simpler.

Credits
-------

This plugin is more or less a total rewrite of [Scott Woods' Link Template 2 File][2].  His idea was brilliant, I just reworked everything so that it fit into a TextPattern admin plugin, and added a lot of error checking and documentation.  Kudos, Scott!

[2]: http://www.woods-fehr.com/txp/8/link-template-2-file