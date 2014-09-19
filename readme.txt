=== toznyauth ===
Contributors: kirk_at_tozny
Donate link: http://www.tozny.com/
Tags: authentication, auth
Requires at least: 3.0.1
Tested up to: 4.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add TOZNY as an authentication option to your WordPress.

== Description ==

TOZNY provides world-class login security for your users, without vulnerable passwords.

Simple passwords are easy to remember, which makes them easy to break. Complex passwords are hard to remember, and require that we write them down. Both of these leave organizations vulnerable to security threats.
Phones serve as a unique identifier, bringing together something you know and something you have for flexible, layered security. Simply put, phones eliminate the need for usernames and passwords.

The toznyauth WordPress plugin adds TOZNY as an authentication option to your WordPress.

== Installation ==

1. Install the plugin
	1. Download the Tozny Wordpress plugin in zip format
	2. Login to your Wordpress install and go to Plugins > Add new
	3. Click on the link to the zip page
	4. Use the form to upload the zip file.

2. Configure your plugin
	1. Login to your Tozny account at https://admin.tozny.com.
	2. Navigate to the "Keys" area of your realm. Keys are how the plugin talks to Tozny.
	3. Click on the name of the key you want to use, or create a new one
	4. Your key information will appear midway down the page.
	5. Go back to your Wordpress install and go to the Tozny configuration page.  Unless you know what you are doing, we recommend leaving the API URL set to https://api.tozny.com and the "Allow users to add devices" checked
	6. Copy and paste your key information into the appropriate fields and click Save Changes.

3. Add Tozny authentication to your Wordpress user. Each of your Wordpress users will need to take these steps to get Tozny configured for themselves.
	1. Go to "Edit your profile" in the top right of your Wordpress account
	2. Check the "Use Tozny to login" checkbox and click "Update Profile" at the bottom of the page
	3. Scan the QR code that appears using the Tozny app on your phone.

== Changelog ==

= 0.9.5 =
 * Updated info in this readme.txt file.

= 0.9.4 =
 * Updated company name from SEQRD, LLC to TOZNY, LLC.

= 0.9.3 =
 * Updated Plugin to use latest TOZNY hosted JavaScript.
