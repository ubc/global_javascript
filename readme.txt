=== Global Javascript ===

Contributors: psmagicman (Julien Law), ctlt-dev, ubcdev
Donate link:
Tags: plugin, javascript, editor, ubc, appearance, global, js, dynamic
Requires at least: 3.5
Tested up to: 3.5
Stable tag:
License: GNU General Public License
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple Javascript writing/editing tool

=== Description ===

Allows the user to create custom javascript for their Wordpress powered site on a global level.
The Javascript made by the user of the plugin will be loaded after any other Javascript that is used by other plugins and/or themes.

=== Installation ===

1. Upload 'global-javascript' folder to the '/wp_content/plugins' directory. Or alternatively install through the WordPress admin dashboard.
2. Activate the plugin through the 'Plugins' menu in the WordPress admin dashboard.
3. Navigate to the 'Appearance' tab in the WordPress admin dashboard sidebar.
4. Click on the link titled 'Custom JS'

=== Changelog ===

v0.10.3 - cleaned up some of the code as well as changing the editor name to be more descriptive and similar to plugin name

v0.10.2 - fixed a bug where previously uploaded files are not deleted

v0.10.1 - fixed a bug where single line comments were not being replaced
        - plugin now saves external javascript to a custom directory in uploads by site and creates one if not available

v0.10 - added a regex replacer to prepare for minification in next release
      - added additional saving that utilizes unix timestamping to prep for future loading method

v0.9.2 - changed the behaviour of the Javascript saving and loading

v0.9.1 - fixed a bug where files were not being saved properly using the new method
       - fixed a bug where the redirect was giving a permissions error

v0.9 - changed the way javascript is saved
        * previous method of saving was producing unintended results
        * new method still requires some testing on multisites
        * new method involves the use of the wp_filesystem
        * codex.wordpress.org/Filesystem_API
     - changed the way the paths work
        * this new method should work regardless of what the parent directory is called

v0.8 - changed the way javascript is injected to the page 
        * javascript is now injected from an external file
     - javascript is now saved to an external javascript file as well
     - current handling of saving needs to be updated; may be security issues

v0.7 - changed the code to a class instead

v0.6.1 - fixed some typos

v0.6 -  updated default text and info for global-javascript.php

v0.5 -  added stylesheets to the editor
     -  changed the names of some style class tags inside codemirror.js 

v0.4 -  codemirror javascript mode code should have more meaning now

v0.3 -  beautified the codemirror javascript mode code

v0.2 -  changed code to edit javascript instead of css

v0.1 -  core code created from the improved simpler css plugin by CTLT
