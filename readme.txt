=== Tozny Authentication ===
Contributors:      kirk_at_tozny, ijones_at_tozny, ericmann
Donate link:       https://www.tozny.com/
Tags:              admin, two-factor, login, password, username, user management, authentication, authenticator, security
Requires at least: 3.0.1
Tested up to:      4.7.3
License:           GPLv2
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Add TOZNY as an authentication option to your WordPress.

== Description ==

TOZNY provides world-class login security for your users, without vulnerable passwords.

Simple passwords are easy to remember, which makes them easy to break. Complex passwords are hard to remember, and require that we write them down. Both of these leave organizations vulnerable to security threats.
Phones serve as a unique identifier, bringing together something you know and something you have for flexible, layered security. Simply put, phones eliminate the need for usernames and passwords.

The toznyauth WordPress plugin adds TOZNY as an authentication option to your WordPress.

== Installation ==

1. Install the plugin
	1. Download the Tozny WordPress plugin in zip format
	2. Login to your WordPress install and go to Plugins > Add new
	3. Click on the link to the zip page
	4. Use the form to upload the zip file.

2. Configure your plugin
	1. Login to your Tozny account at https://admin.tozny.com.
	2. Navigate to the "Keys" area of your realm. Keys are how the plugin talks to Tozny.
	3. Click on the name of the key you want to use, or create a new one
	4. Your key information will appear midway down the page.
	5. Go back to your WordPress install and go to the Tozny configuration page.  Unless you know what you are doing, we recommend leaving the API URL set to https://api.tozny.com and the "Allow users to add devices" checked
	6. Copy and paste your key information into the appropriate fields and click Save Changes.

3. Add Tozny authentication to your WordPress user. Each of your WordPress users will need to take these steps to get Tozny configured for themselves.
	1. Go to "Edit your profile" in the top right of your WordPress account
	2. Check the "Use Tozny to login" checkbox and click "Update Profile" at the bottom of the page
	3. Scan the QR code that appears using the Tozny app on your phone.

== Changelog ==

= 1.1.6 =
 * Verified latest WordPress compatibility
 * Verified latest jQuery compatibility
 * Moved stock Tozny PHP SDK into its own folder for ease of updates
 * Added localization support for the 'toznyauth' textdomain
 * Updated authorization redirects to avoid a "Headers already sent" error

= 1.1.5 =
 * Fixed bug that prevented non-admin users from adding devices.

= 1.1.4 =
 * Updated enrollment QR to use ThickBox modal.

= 1.1.3 =
 * Ajax callback added for Tozny enrollment
 * tested on WordPress 4.1.1
 * Moved embedded JavaScript into external javascript files.

= 1.1.2 =
 * Changed usage of wp_redirect to wp_safe_redirect

= 1.1.1 =
 * Added styles folder for moving inline and embedded CSS into separate CSS files.

= 1.1.0 =
 * Changed license of included Realm and User SDK files to GPLv2.
 * Updated included Realm and User SDK classes to use wp_remote_get() instead of file_get_contents().
 * Updated Copyright information.
 * Edits to readme.txt file: Wordpress is now WordPress, Tags were updated.
 * Storage of tozny_activate parameter now uses sanitize_text_field.
 * Removed included jQuery.
 * Moved Tozny assets over to using WordPress queuing.
 * Correctly escaped values using WordPress esc_ functions.
 * Added docblocks documentation to plugin code.


= 1.0.3 =
 * Adding .gitignore file.
 * Adding missing Changelog notes for v.1.0.2.
 * moving Tested version from 4.0 to 4.1
 * updated SDK libs to latest version
  
= 1.0.2 =
 * Updated Tozny SDK-PHP files to use latest from https://github.com/tozny/sdk-php

= 0.9.5 =
 * Updated info in this readme.txt file.

= 0.9.4 =
 * Updated company name from SEQRD, LLC to TOZNY, LLC.

= 0.9.3 =
 * Updated Plugin to use latest TOZNY hosted JavaScript.
