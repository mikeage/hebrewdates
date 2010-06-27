=== Plugin Name ===
Contributors: mikeage, KosherJava
Donate link: http://paypal.com/send/to/paypal@mikeage.net
Tags: jewish, hebrew, dates, formatting
Requires at least: 2.0
Tested up to: 3.0
Stable tag: 1.0.3

This plugin allows WordPress to easily show Hebrew dates instead of (or in addition to) the standard Gregorian dates. No theme changes are required.

== Description ==

This plugin allows WordPress to easily show Hebrew dates instead of (or in addition to) the standard Gregorian dates. No theme changes are required.

This plugin is based on the Hebrew Date plugin from KosherJava. All bugs are mine, though.

== Installation ==

Unzip the zip file, copy to /path/to/wordpress/wp-content/plugins/, activate, and configure! Note that sunset correction is disabled by default, since there's no meaningful default value for Latitude / Longitude

By default, the following APIs are intercepted:
the_time()
get_comment_date()

== Frequently Asked Questions ==

= Why? =

Because.

== Screenshots ==

1. A sample of a post showing dates replaced.

2. The config screen.

== Changelog ==

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

