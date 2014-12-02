=== MP3 to Post ===
Contributors: paulsheldrake
Tags: mp3, podcasting, id3, podcast, podcaster, audio, music, spokenword
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.2.3
License: GPLv3

Creates posts using MP3 ID3 information.

== Description ==

This plugin creates a folder that you can STFP or SSH MP3 files in to and then 
scans the folder to create the posts from the MP3 ID3 information.  

The posts use the ID3 title and comments information to create the title and post
content.  

When the posts are created they are automatically set to Draft so you can review
the information and set publish dates.


== Installation ==

1. Upload the plugin directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Upload mp3 files to /wp-content/uploads/mp3-to-post
4. Go to the plugin page(under the Settings tab) and start creating posts.

== Frequently Asked Questions ==

= How can I look at/edit ID3 information =

You can use iTunes to edit ID3 information.   If you right click on the MP3 file there should be an option for 'Get Info' in the menu.   Clicking that open a dialogue where you can set the information.   Something to note is that setting the information in iTunes doesn't always set the ID3v1 and ID3v2 tags, it often just sets the v1 tags.  

If you go to download.com and search for 'ID3 Editor' you will find a variety of free options if iTunes isn't working for you.

In Windows you can also right click the mp3 file and select Properties.  In the Summary tab there is an option to enter ID3 information.

= Any other questions =

Please feel free to email me.  paul.sheldrake@gmail.com

== Changelog ==

= 1.2.0 =
* Check plugin works for WordPress version 4.0.  Creating POT file for translators.

= 1.1.0 =
* Added the ability to set categories on posts.  When you set the genre in the ID3 info that will be used as the category.

= 1.0.3.2 =
* Fixed version number

= 1.0.3 =
* Added a fix to make the mp3-to-post work with other plugins that use the GetID3 library.  Thanks to citizenkeith for following up on this.

= 1.0.2 =
* Updated the read me, added slightly more useful error messaging and fixed a problem with SVN using the wrong version

= 1.0.1 =
* Now show more information about the files in the folder before they are uploaded

= 1.0 =
* Initial commit

