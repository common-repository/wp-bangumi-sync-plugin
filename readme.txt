=== Wordpress Bangumi Sync Plugin ===
Contributors: TheC
Requires at least: 2.9
Tested up to: 3.3.1

A simple plugin which helps you to send a copy of your new post at wordpress to your bound bangumi account.

== Something you should know ==

1. You need to enable CURL in your php configuration.
2. Connection with bangumi may not always be successful for serveral reasons and for now we don't have methods to try later automatically, you'll need to do that yourself.
3. If you have any probloms using this plugin, please contact me at `http://thec.me/w/bangumi-sync-plugin/`

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Fill in your bangumi id and password in `options -> bangumi sync plugin`.



== Changelog ==

= 1.1 =
1. Color code in Hex will be transformed correctly to UBB code now.
2. `&nbsp;` in html code will be transformed to ` ` now.

= 1.0 =
First version