=== MediaPro ===
Contributors: philgraci
Tags: mp3, audio, music, spokenword
Requires at least: 3.5
Tested up to: 4.0.1
Stable tag: 1.0
License: GPLv3

Creates posts using Media Library ID3 information.

== Description ==

This plugin allows you to upload or select songs from your Media Library.

You can then create multiple posts or one post with a playlist.

The posts are generated from the song's ID3 information.

MP3 and M4A files are supported, as well as iTunes purchases.

Many different ID3 tags are available, and you can customize how you want to import.

When the posts are created they are automatically set to Draft so you can review
the information and set publish dates.


== Installation ==

1. Upload the plugin directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the plugin page and start creating posts.

== Frequently Asked Questions ==

= ID3 Version =

Currently we only retrieve ID3v2 tags.

= ID3 Tag names =

All fields referenced are based on how iTunes uses the field name.  For instance, iTunes has a field called 'Grouping'.  This is actually stored inside ID3 tag 'content_group_description'.  MediaPro refers to this as Grouping.

You may edit your tags in any editor, however it is recommended to check in iTunes to ensure that fields are being stored as expected.

= Any other questions =

Please feel free to email me.  phil@triagency.com

== Changelog ==

= 1.0 =
* Initial commit
