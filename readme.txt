=== Plugin Name ===
Contributors: mikeage, KosherJava
Donate link: http://paypal.com/send/to/paypal@mikeage.net
Tags: jewish, hebrew, dates, formatting
Requires at least: 2.0
Tested up to: 3.2
Stable tag: 2.1.0

This plugin allows WordPress to easily show Hebrew dates instead of (or in addition to) the standard Gregorian dates. No theme changes are required.

== Description ==

This plugin allows WordPress to easily show Hebrew dates instead of (or in addition to) the standard Gregorian dates. No theme changes are required.

This plugin is based on the Hebrew Date plugin from KosherJava. All bugs are mine, though.

This plugin is slightly similar to http://wordpress.org/extend/plugins/wordpress-hebrew-date/, but much more robust. It's also older, although not on wordpress.org

== Installation ==

Unzip the zip file, copy to /path/to/wordpress/wp-content/plugins/, activate, and configure! Note that sunset correction is disabled by default, since there's no meaningful default value for Latitude / Longitude

By default, the following APIs are intercepted:
the_time()
the_date()
get_the_time()
get_the_date()
get_comment_time()
get_comment_date()

== Frequently Asked Questions ==

= Why? =

Because.

== Screenshots ==

1. A sample of a post showing dates replaced.
2. The config screen.

== Changelog ==

= 2.1.0 =

* Support WP 3.2's TwentyEleven with its HTML comment escaping

= 2.0.3 =

* Fix for anyone using PHP < 5.3.0 and a date containing a suffix (3rd, 5th, etc instead of 3, 5).

= 2.0.2 =

* Remove superflous single quote after the year
* Switch ' and &quot; to &1523; and &#1524; [proper unicode]. Thanks to KosherJava for the tip 


= 2.0.1 = 

* Fixed a bug for English (both Ashkenazim and Sefardic) transliterations not appearing (introduced in 2.0.0)
* Added the option to remove the quotes from the Hebrew dates (requested by http://www.amotherinisrael.com/)

= 2.0.0 =

* Complete rewrite of the date identification; much more reliable
* Code actually looks slightly sane now
* New `get_the_hebrew_date()` and `the_hebrew_date()` APIs, which can be used in any theme. The interception of the other calls, while much more robust now, still is not the ideal.

= 1.0.4 =

* Support for both `the_date()` and `get_the_date()` (required for WordPress 3.0's 2010 theme)

= 1.0.3 =

* Enhanced currentHebrewDate API (see the help on the config screen, or the discussion proposals here)
* Start of some decent documentation
* Proper URL

= 1.0.2 =

* Code no longer breaks things! [fix for the_time()]

= 1.0.1 =

* Don't ask where 1.0.0 is. Just don't.
* Database config (instead of coded in the file)
* Configuration screen shows when an update was performed
* Option of showing Hebrew (or transliterated) only, Gregorian - Hebrew, or Hebrew - Gregorian
* Lots of code cleanup.
* Easier to add languages (I'd like to add an academic spelling as well)
* Support for full names (Marcheshvan, Menachem Av)
* XHTML 1.1 compliant! (span rtl)
* Hebrew (default) or Transliterated (Latin character set) characters
* Transliteration according Ashkenazi (default) or Sefardi pronunciation
* Sunset correction enabled or disabled (default) by Latitude / Longitude
* Based on an idea I "borrowed" from Jacob Fresco, I've added the ability to insert the current Hebrew Date (with sunset correction!) anywhere in a theme. Simply add `<?php if (function_exists('hebrewDateCurrent')) { hebrewDateCurrent();} ?>` to a theme. 

== Upgrade Notice ==

= None =

== Known Issues ==

* The sunset routine cannot take into account DST, since the wordpress offset is GMT +/-, not a timezone (not to mention the computational nightmare that is the Israeli timezone).

