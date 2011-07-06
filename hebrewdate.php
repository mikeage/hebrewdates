<?php
/*
Plugin Name: Hebrew Date
Plugin URI: http://mikeage.net/content/software/hebrew-dates-in-wordpress/
Description: A plugin that provides Hebrew dates in Wordpress. Based on the <a href="http://www.kosherjava.com/wordpress/hebrew-date-plugin/">Hebrew Date</a> plugin by <a href="http://kosherjava.com">KosherJava</a>.
Version: 2.1.0
Author: Mike "Mikeage" Miller
Author URI: http://mikeage.net
 */

/*
This program is free software; you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation; either version 2 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if
not, write to the Free Software Foundation, Inc. 59 Temple Place - Suite 330, Boston, MA
02111-1307, USA or connect to: http://www.fsf.org/copyleft/gpl.html
 */

/* Arrays for month names */
// These are accessed as $GLOBALS['name']
$ashkenazMonths = array("Tishrei", "Cheshvan", "Kislev", "Teves", "Shevat", "Adar I", "Adar II", "Nisan", "Iyar", "Sivan", "Tamuz", "Av", "Elul", "Adar", "Marcheshvan", "Menachem Av");
$sefardMonths = array(	"Tishre", "Heshwan", "Kislev", "Tevet", "Shevat", "Adar I", "Adar II", "Nisan", "Iyar", "Sivan", "Tamuz", "Av", "Elul", "Adar", "Marheshwan", "Menahem Av");
$hebrewMonths = array(	"&#1514;&#1513;&#1512;&#1497;",   /* Tishrei */
"&#1495;&#1513;&#1493;&#1503;",   /* Cheshvan */
"&#1499;&#1505;&#1500;&#1493;",   /* Kislev */
"&#1496;&#1489;&#1514;",	  /* Teves */
"&#1513;&#1489;&#1496;",	  /* Sh'vat */
"&#1488;&#1491;&#1512; &#1488;'", /* Adar A */
"&#1488;&#1491;&#1512; &#1489;'", /* Adar B */
"&#1504;&#1497;&#1505;&#1503;",   /* Nissan */
"&#1488;&#1497;&#1497;&#1512;",	  /* Iyar */
"&#1505;&#1497;&#1493;&#1503;",	  /* Sivan */
"&#1514;&#1502;&#1493;&#1494;",	  /* Tamuz */
"&#1488;&#1489;",		  /* Av */
"&#1488;&#1500;&#1493;&#1500;",	  /* Elul */
"&#1488;&#1491;&#1512;",	  /* Adar (regular) */
"&#1502;&#1512;&#1495;&#1513;&#1493;&#1503;", /* Mar Cheshvan */
"&#1502;&#1504;&#1495;&#1501; &#1488;&#1489;", /* Menachem Av */
); 
define('GERSH',"&#1523;");
define('GERSHAYIM', "&#1524;");

/* Defines. Note that these are constants, not configuration options */
define('LATIN_CHARSET', 0);
define('HEBREW_CHARSET', 1);
define('HEBREW_SPELLING', 0);
define('ASHKENAZIC_SPELLING', 1);
define('SEFARDIC_SPELLING', 2);
define('SHOW_SHORT_MONTH', 0);
define('SHOW_FULL_MONTH', 1);
define('SHOW_HEBREW',0);
define('SHOW_HEBREW_THEN_GREGORIAN',1);
define('SHOW_GREGORIAN_THEN_HEBREW',2);

/* Wrapper functions; these all call the the same function, but pass the source of the request. We need this to know how to query for the time (for sunset correction) if we're only given a date */
function AddHebrewDateWrap_the_time($content, $format){ return AddHebrewDateToGregorian($content, $format, "the_time");  }
function AddHebrewDateWrap_the_date($content, $format){ return AddHebrewDateToGregorian($content, $format, "the_date"); }
function AddHebrewDateWrap_get_the_time($content, $format){ return AddHebrewDateToGregorian($content, $format, "get_the_time"); }
function AddHebrewDateWrap_get_the_date($content, $format){ return AddHebrewDateToGregorian($content, $format, "get_the_date"); }
function AddHebrewDateWrap_get_comment_time($content, $format) { return AddHebrewDateToGregorian($content, $format, "get_comment_time"); }
function AddHebrewDateWrap_get_comment_date($content, $format) { return AddHebrewDateToGregorian($content, $format, "get_comment_date"); }


/* Main Function. This function receives the string to be converted, the format string that generated the date (if available), and the source of the request. It returns a new string suitable for use in place, based on the configuration set in the Admin panel */ 
function AddHebrewDateToGregorian($content, $format = "", $originalRequest = null) {

	debug_print("Input content was |$content|, formatted as |$format|, from |$originalRequest|...");

	/* Once replaced, a date has &zwj;&zwj; prepended to it. This allows us to prevent an infinite loop since internally, we call functions to which we've been added as a filter */
	$doneAlready = strpos($content, "&zwj;&zwj;");
	if(false !== $doneAlready) { return $content;}

	/* Get the converted string */
	$convertedDate = GetHebrewDateString($content, $format, $originalRequest);

	if (null === $convertedDate) {
		return $content;
	}

	debug_print("Got back $convertedDate...");

	$date_order = get_option('hebrewdate_date_order');
	if ($date_order == SHOW_HEBREW) {
		$outputDate = $convertedDate;
	} else if ($date_order == SHOW_HEBREW_THEN_GREGORIAN) {
		$outputDate = $convertedDate . ' - ' . $content;
	} else if ($date_order == SHOW_GREGORIAN_THEN_HEBREW) {
		$outputDate = $content . ' - ' . $convertedDate;
	}

	return "&zwj;&zwj;" . $outputDate; 
}

/* This function actually handles the conversion. We have a sepereate function to support the public APIs, which don't need the "already-done" check and don't want to keep the Gregorian date */
function GetHebrewDateString($content, $format="", $originalRequest=null) {

	/* Sometimes there's an extra <br /> in dates. I don't remember why. */
	$content = str_replace("<br />", "", $content);

	$spelling = get_option('hebrewdate_spelling');
	$sunset_correction = get_option('hebrewdate_correct_sunset') ? true : false;

	debug_print("Converting |$content| as |$format| (from |$originalRequest|)...");
	if (empty($format)) {
		debug_print("No format string...");
		if (!isset($originalRequest)) {
			/* If we don't have a format string, and don't know where the request came from, ignore this */
			debug_print("And no source...");
			debug_print("Giving up.");
			return null;
		}
		switch ($originalRequest) {
		case the_time:
		case get_the_time:
		case get_comment_time:
			$format = get_settings('time_format');
			debug_print("Assuming time_format (|$format|)...");
			break;
		case the_date:
		case get_the_date:
		case get_comment_date:
			$format = get_settings('date_format');
			debug_print("Assuming date_format... (|$format|)...");
			break;
		default:
			/* Huh? */
			debug_print("Unknown source...");
			debug_print("Giving up.");
			return null;
		}
	}

	/* Now try and figure out what format $content is based on $format. This is very ugly looking, but it's the best I could come up with. strtotime is simply not reliable enough (even for full times, and certainly not for handling archives) */
	if (function_exists('date_parse_from_format')) {
		$new_parse = true;
		debug_print("New style...");
		$content_parsed = date_parse_from_format($format, $content);
	} else {
		$new_parse = false;
		debug_print("Old style...");
		$content = preg_replace("([0-9]st|nd|rd|th)","\\1",$content);
		$content_parsed = strptime($content, dateFormatToStrftime($format));
		$content_parsed['hour']=$content_parsed['tm_hour'];
		$content_parsed['minute']=$content_parsed['tm_min'];
		$content_parsed['day']=$content_parsed['tm_mday'];
		$content_parsed['month']=$content_parsed['tm_mon'] + 1;
		$content_parsed['year']=$content_parsed['tm_year'] + 1900;
	}
	$F = (false !== strpos($format, 'F')); 
	$m = (false !== strpos($format, 'm'));
	$M = (false !== strpos($format, 'M'));
	$n = (false !== strpos($format, 'n')) && $new_parse;
	$d = (false !== strpos($format, 'd'));
	$j = (false !== strpos($format, 'j'));
	$D = (false !== strpos($format, 'D'));
	$l = (false !== strpos($format, 'l'));
	$N = (false !== strpos($format, 'N'));
	$w = (false !== strpos($format, 'w'));
	$W = (false !== strpos($format, 'W'));
	$o = (false !== strpos($format, 'o'));
	$y = (false !== strpos($format, 'y'));
	$Y = (false !== strpos($format, 'Y'));
	$g = (false !== strpos($format, 'g'));
	$G = (false !== strpos($format, 'G')) && $new_parse;
	$h = (false !== strpos($format, 'h'));
	$H = (false !== strpos($format, 'H'));
	$i = (false !== strpos($format, 'i'));
	$U = (false !== strpos($format, 'U'));
	if (($F || $m || $M || $n) && ($d || $j || (($D || $l || $n || $w) && $W)) && ($o || $y || $Y))  {
		/* Full Date: date and (month OR (day AND week) AND year */
		debug_print("Full date...");
		$archiveFormat = "";
		$day = $content_parsed['day'];
		$month = $content_parsed['month'];
		$year = $content_parsed['year'];
		if (!(($g || $G || $h || $H) && $i)) {
			/* Time is not present; we'll try to extract it from the original source */
			debug_print("But no time...");
			switch ($originalRequest) {
			case get_comment_time:
			case get_comment_date:
				debug_print("Assuming a comment...");
				$content_parsed['hour']= get_comment_time('H');
				$content_parsed['minute'] = get_comment_time('i');
				break;
			case the_time:
			case get_the_time:
			case the_date:
			case get_the_date:
				debug_print("Checking: post or comment? ");
				if (get_comment_time('z')) {
					debug_print("Comment! ...");
					$content_parsed['hour']= get_comment_time('H');
					$content_parsed['minute'] = get_comment_time('i');
				} else {
					debug_print("Post! ...");
					$content_parsed['hour']= get_post_time('H');
					$content_parsed['minute'] = get_post_time('i');
				}
				break;
			default:
				debug_print("Unknown request: don't know how to get H:m...");
				debug_print("Assuming noontime...");
				$content_parsed['hour']=12;
				$content_parsed['minute']=0;
				break;
			}
		}
		$hour = $content_parsed['hour'];
		$min = $content_parsed['minute'];
	} else if ($U) {
		/* Why can't date_parse_from_format handle this?! For some reason, it always returns 0 for me... */
		debug_print("Got a unix epoch");
		$month = $content_parsed['month'] = date('m', $content);
		$year = $content_parsed['year'] = date('Y', $content);
		$day = $content_parsed['day'] = date('j', $content);
		$hour = $content_parsed['hour'] = date('H', $content);
		$min = $content_parsed['minute'] = date('i', $content);
		$archiveFormat = "";
	} else if (($F || $m || $M || $n) && ($o || $y || $Y))  {
		debug_print("Got a month and year ...");
		$day = 1;
		$month = $content_parsed['month'];
		$year = $content_parsed['year'];
		$archiveFormat = "month";
	} else if ($o || $y || $Y)  {
		debug_print("Got a year...");
		$day = 1;
		$month = 1;
		$year = $content_parsed['year'];
		$archiveFormat = "year";
	} else {
		debug_print("Can't handle this date format!");
		debug_print("Returning |$content|...");
		return null;
	}
	debug_print("It appears we're at $hour:$min:0 at $year-$month-$day...");
	$pdate = mktime($hour, $min, 0, $month, $day, $year);

	debug_print("Working with pdate=|$pdate|...");

	if ($sunset_correction && $archiveFormat == "") {
		$time_elapsed = 60*$hour + $min;
		$latitude = get_option('hebrewdate_latitude');
		$longitude = get_option('hebrewdate_longitude');
		$sunset = calcSunset($latitude, $longitude, 90.5, 
			$day, get_option('gmt_offset'));
		if ($time_elapsed > $sunset) {
			$adj_pdate = $pdate + 24*60*60;
		} else {
			$adj_pdate = $pdate;
		}
	} else { 
		$adj_pdate = $pdate;
	}
	debug_print("after sunset correction, adj_pdate = $adj_pdate...");

	switch ($archiveFormat) {
		/* For archives, we need to calculate the start and end dates */
	case "":
		$month = date('m',$adj_pdate);
		$day = date('j',$adj_pdate);
		$year = date('Y',$adj_pdate);
		$endDay = 0;
		$endMonth = 0;
		break;
	case "month":
		$day = 1;
		$month = date('m',$adj_pdate);
		$year = date('Y',$adj_pdate);
		$endDay = cal_days_in_month(CAL_GREGORIAN, $month, $year); 
		$endMonth = $month;
		break;
	case "year":
		$day = 1;
		$month = 1;
		$year = date('Y',$adj_pdate);
		$endDay = 31;
		$endMonth = 12;
		break;
	}

	debug_print("About to convert $year-$month-$day (and $year-$endMonth-$endDay archive-style)...");
	list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', jdtojewish(gregoriantojd($month, $day, $year)));
	list ($endHebrewMonth, $endHebrewDay, $endHebrewYear) = split ('/', jdtojewish(gregoriantojd($endMonth, $endDay, $year)));

	switch ($archiveFormat) {
	case "":
		break;
	case "month":
		/* The day is meaningless for an archive */
		$hebrewDay = "";
		break;
	case "year":
		/* Yearly archives do not include the months */
		$hebrewDay = "";
		$hebrewMonth = "";
		$endHebrewMonth = "";
		break;
	}

	$convertedDate = GetHebrewDateStringFromHebrewDate($spelling, $hebrewYear, $hebrewMonth, $hebrewDay, $endHebrewYear, $endHebrewMonth);

	return $convertedDate;
}
/* This function converts Hebrew numeric dates (13/1/5770) to Hebrew strings. It also creates the archive strings if given an endYear and endMonth */
function GetHebrewDateStringFromHebrewDate($spelling, $year, $month="", $day="", $endYear="", $endMonth="") {

	/* Are we going to be returning Latin letters or Hebrew ones? */
	$charset = get_option('hebrewdate_latin_display') ? LATIN_CHARSET : HEBREW_CHARSET;
	$useQuotes = get_option('hebrewdate_use_quotes') ? true: false;

	/* Defensive code */
	if ($spelling == SEFARDIC_SPELLING || $spelling == ASHKENAZIC_SPELLING) {
		$charset = LATIN_CHARSET;
	}

	/* Prepare the fields for the Hebrew date string */
	if ($day) {
		if ($charset == HEBREW_CHARSET) {
			$hebrewDayString = getHebrewDayString($day, $useQuotes);
		} else {
			$hebrewDayString = $day;
		}
	}

	$hebrewMonthString = getHebrewMonthString($spelling, $month, $year);
	if (!empty($endMonth) && ($endMonth != $month)) {
		$endHebrewMonthString = getHebrewMonthString($spelling, $endMonth, $endYear);
	}

	if ($charset == HEBREW_CHARSET) {
		$hebrewYearString = getHebrewYearString($year, $useQuotes);
	} else {
		$hebrewYearString = $year;
	}

	if (!empty($endYear) && $endYear != $year) {
		if ($charset == HEBREW_CHARSET) {
			$endHebrewYearString = getHebrewYearString($endYear, $useQuotes);
		} else {
			$endHebrewYearString = $endYear;
		}
	}


	debug_print("Results for D:$day M:$month EM:$endMonth Y:$year EY:$endYear are:");
	debug_print("D:$hebrewDayString M:$hebrewMonthString EM:$endHebrewMonthString Y:$hebrewYearString EY:$endHebrewYearString...");


	/* Begin building the actual string */
	if ($charset != HEBREW_CHARSET && $spelling == HEBREW_SPELLING) {
		$convertedDate = "<span dir=\"rtl\">";
	}
	if ($hebrewDayString) {
		$convertedDate .= $hebrewDayString;
	}
	if ($hebrewMonthString) {
		$convertedDate .= " $hebrewMonthString";
	}
	if (!empty($endHebrewMonthString) && empty($endHebrewYearString)) {
		$convertedDate .= " / $endHebrewMonthString";
	}
	if ($hebrewYearString) {
		$convertedDate .= " $hebrewYearString";
	}
	if (!empty($endHebrewMonthString) && !empty($endHebrewYearString)) {
		$convertedDate .= " / $endHebrewMonthString $endHebrewYearString"; 
	}
	if (empty($endHebrewMonthString) && !empty($endHebrewYearString)) {
		$convertedDate .= " / $endHebrewYearString"; 
	}
	if ($charset != HEBREW_CHARSET && $spelling == HEBREW_SPELLING) {
		$convertedDate .= "</span>";
	}
	return $convertedDate;
}

/*********************
 * Utility Functions *
 *********************/

function dateFormatToStrftime($dateFormat) {
   
    $caracs = array(
        // Day - no strf eq : S
		'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j', 'S' => '', /* We'll remove the suffix elsewhere */
        // Week - no date eq : %U, %W
        'W' => '%V', 
        // Month - no strf eq : n, t
        'F' => '%B', 'm' => '%m', 'M' => '%b',
        // Year - no strf eq : L; no date eq : %C, %g
        'o' => '%G', 'Y' => '%Y', 'y' => '%y',
        // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
        'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S',
        // Timezone - no strf eq : e, I, P, Z
        'O' => '%z', 'T' => '%Z',
        // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x 
        'U' => '%s'
    );
   
    return strtr((string)$dateFormat, $caracs);
} 
function debug_print() {
	$debug = true;
	$debug = false;
	if (isset($debug) && $debug) {
		$data = func_get_args();
		$string = array_shift($data);
		if (is_array(func_get_arg(1))) {
			$data = func_get_arg(1);
		}
		return vprintf($string,$data);
	}
}

function calcSunset($latitude, $longitude, $zenith, $yday, $offset) {
	/* I don't rememeber where I found this. Sorry. */
	$A = 1.5708;
	$B = 3.14159;
	$C = 4.71239;
	$D = 6.28319;
	$E = 0.0174533 * $latitude;
	$F = 0.0174533 * $longitude;
	$R = -M_PI/180*($zenith-90);

	$J = $C;
	$K = $yday + (($J - $F) / $D);
	$L = ($K * .017202) - .0574039; # Solar Mean Anomoly
	$M = $L + .0334405 * sin($L); # Solar True Longitude
	$M += 4.93289 + (3.49066E-04) * sin(2 * $L);
	while ($M < 0) {
		$M = ($M + $D);
	}
	while ($M >= $D) {
		$M = ($M - $D);
	}
	if (($M / $A) - intval($M / $A) == 0) {
		$M += 4.84814E-06;
	}
	$P = sin($M) / cos($M); 
	$P = atan2(.91746 * $P, 1);
	if ($M > $C) {
		$P += $D;
	} else{
		if ($M > $A) {
			$P += $B;
		}
	}

	$Q = .39782 * sin($M);
	$Q = $Q / sqrt(-$Q * $Q + 1); 
	$Q = atan2($Q, 1);
	$S = $R - (sin($Q) * sin($E));
	$S = $S / (cos($Q) * cos($E));

	$S = $S / sqrt(-$S * $S + 1);
	$S = $A - atan2($S, 1);
	$T = $S + $P - 0.0172028 * $K - 1.73364; 
	$U = $T - $F; 
	while ($U < 0) {
		$U = ($U + $D);
	}
	while ($U >= $D) {
		$U = ($U - $D);
	}

	$U = $U * 3.81972;
	$hour = intval($U);
	$min = intval(60*($U-$hour));
	$hour += $offset;
	return $hour * 60 + $min;
}

function isHebrewLeapYear($year) {
	/* Hebrew leap years are on a 19 year cycle: years 0, 3, 6, 8, 11, 14 and 17 have an extra month */
	switch ($year % 19) {
	case 0:
	case 3:
	case 6:
	case 8:
	case 11:
	case 14:
	case 17:
		return true;
	default:
		return false;
	}
}
/* Return the hebrew letters string for a day (0-30) */
function getHebrewDayString($day, $useQuotes) {
	$jTens = array("", "&#1497;", "&#1499;", "&#1500;", "&#1502;",
		"&#1504;", "&#1505;", "&#1506;", "&#1508;", "&#1510;");
	$jTenEnds = array("", "&#1497;", "&#1498;", "&#1500;", "&#1501;",
		"&#1503;", "&#1505;", "&#1506;", "&#1507;", "&#1509;");
	$jOnes = array("", "&#1488;", "&#1489;", "&#1490;", "&#1491;",
		"&#1492;", "&#1493;", "&#1494;", "&#1495;", "&#1496;");

	if($day < 10) { //single digit days get single quote appended
		$sb .= $jOnes[$day];
		if ($useQuotes) {
			$sb .= GERSH;
		}
	} else if($day == 15) { //special case 15
		$sb .= $jOnes[9];
		if ($useQuotes) {
			$sb .= GERSHAYIM;
		}
		$sb .= $jOnes[6];
	} else if($day == 16) { //special case 16
		$sb .= $jOnes[9];
		if ($useQuotes) {
			$sb .= GERSHAYIM;
		}
		$sb .= $jOnes[7];
	} else {
		$tens = $day / 10;
		$sb .= $jTens[$tens];
		if($day % 10 == 0) { // 10 or 20 single digit append single quote
			if ($useQuotes) {
				$sb .= GERSH;
			}
		} else if($day > 10) { // >10 display " between 10s and 1s
			if ($useQuotes) {
				$sb .= GERSHAYIM;
			}
		}
		$day = $day % 10; //discard 10s
		$sb .= $jOnes[$day];
	}
	return $sb;
}
function getHebrewMonthString($spelling, $month, $year) {

	$display_full = get_option('hebrewdate_display_full') ? SHOW_FULL_MONTH : SHOW_SHORT_MONTH;
	if($spelling == SEFARDIC_SPELLING) {
		$monthNames = $GLOBALS['sefardMonths'];
	} else if ($spelling == ASHKENAZIC_SPELLING) {
		$monthNames = $GLOBALS['ashkenazMonths'];
	} else if ($spelling == HEBREW_SPELLING) {
		$monthNames = $GLOBALS['hebrewMonths'];
	}
	if($month == 6) { // if Adar check for leap year
		if(isHebrewLeapYear($year)) {
			$month = $monthNames[5];
		} else {
			$month = $monthNames[13];
		}
	} else if ($month == 2 && $display_full == SHOW_FULL_MONTH) {
		$month = $monthNames[14]; // Marcheshvan
	} else if ($month == 12 && $display_full == SHOW_FULL_MONTH) {
		$month = $monthNames[15]; // Menachem Av
	} else {
		if (isset($monthNames[$month - 1])) $month = $monthNames[$month - 1];
		else $month = $monthNames[$month];
	}
	return $month;

}

function getHebrewYearString($year, $useQuotes) {
	$display_thousands = get_option('hebrewdate_display_thousands');
	$jAlafim = "&#1488;&#1500;&#1508;&#1497;&#1501;"; //word ALAFIM in Hebrew for display on years evenly divisable by 1000
	$jHundreds = array("", "&#1511;","&#1512;","&#1513;","&#1514;",	"&#1514;&#1511;","&#1514;&#1512;","&#1514;&#1513;", "&#1514;&#1514;", "&#1514;&#1514;&#1511;");
	$jTens = array("", "&#1497;", "&#1499;", "&#1500;", "&#1502;", "&#1504;", "&#1505;", "&#1506;", "&#1508;", "&#1510;");
	$jTenEnds = array("", "&#1497;", "&#1498;", "&#1500;", "&#1501;", "&#1503;", "&#1505;", "&#1506;", "&#1507;", "&#1509;");
	$jOnes = array("", "&#1488;", "&#1489;", "&#1490;", "&#1491;", "&#1492;", "&#1493;", "&#1494;", "&#1495;", "&#1496;");

	$singleDigitYear = isSingleDigitHebrewYear($year);
	$thousands = $year / 1000; //get # thousands

	$sb = "";

	//append thousands to String
	if($year % 1000 == 0) { // in year is 5000, 4000 etc
		$sb .= $jOnes[$thousands];
			if ($useQuotes) {
				$sb .= GERSH;
			}
		$sb .= "&#160;";
		$sb .= $jAlafim; //add # of thousands plus word thousand (overide alafim boolean)
	} else if($display_thousands) { // if alafim boolean display thousands
		$sb .= $jOnes[$thousands];
			if ($useQuotes) {
				$sb .= GERSH;
			}
		$sb .= "&#160;";
	}
	$year = $year % 1000;//remove 1000s
	$hundreds = $year / 100; // # of hundreds
	$sb .= $jHundreds[$hundreds]; //add hundreds to String
	$year = $year % 100; //remove 100s
	if($year == 15) { //special case 15
		$sb .= $jOnes[9];
		if ($useQuotes) {
			$sb .= GERSHAYIM;
		}
		$sb .= $jOnes[6];
	} else if($year == 16) { //special case 16
		$sb .= $jOnes[9];
		if ($useQuotes) {
			$sb .= GERSHAYIM;
		}
		$sb .= $jOnes[7];
	} else {
		$tens = $year / 10;
		if($year % 10 == 0) { // if evenly divisable by 10
			if($singleDigitYear == false) {
				$sb .= $jTenEnds[$tens]; // use end letters so that for example 5750 will end with an end nun
			} else {
				$sb .= $jTens[$tens]; // use standard letters so that for example 5050 will end with a regular nun
			}
		} else {
			$sb .= $jTens[$tens];
			$year = $year % 10;
			$sb .= $jOnes[$year];
		}
	}
	if($singleDigitYear == true) {
			if ($useQuotes) {
				$sb .= GERSH;
			}
	} else { // append double quote before last digit
		$pos1 = strrpos($sb, "&");
			if ($useQuotes) {
				$sb = substr($sb, 0, $pos1) . GERSHAYIM . substr($sb, $pos1);
			}
	}

	return $sb;
}

function isSingleDigitHebrewYear($year) {
	$shortYear = $year %1000; //discard thousands
	//next check for all possible single Hebrew digit years
	if($shortYear < 11 || ($shortYear <100 && $shortYear % 10 == 0)  || ($shortYear <= 400 && $shortYear % 100 == 0) ) {
		return true;
	} else {
		return false;
	}
}

/*****************
 * Control Panel *
 *****************/

function hebrewDateMenu() {
	if (function_exists('add_options_page')) {
		add_options_page('Configure Hebrew Date Display', 'Hebrew Date', 6, basename(__FILE__), 'hebrewdate_subpanel');
	}
}

function hebrewdate_subpanel() {
	$updated = false;
	if (isset($_POST['update'])) {
		$latin_display = $_POST['latin_display'];
		$use_quotes = $_POST['use_quotes'];
		$spelling = $_POST['spelling'];
		$display_thousands = $_POST['display_thousands'];
		$display_full = $_POST['display_full'];
		$date_order = $_POST['date_order'];
		$correct_sunset = $_POST['correct_sunset'];
		$latitude = $_POST['latitude'];
		$longitude = $_POST['longitude'];
		update_option('hebrewdate_latin_display', $latin_display);
		update_option('hebrewdate_use_quotes', $use_quotes);
		update_option('hebrewdate_spelling', $spelling);
		update_option('hebrewdate_display_thousands', $display_thousands);
		update_option('hebrewdate_display_full', $display_full);
		update_option('hebrewdate_date_order', $date_order);
		update_option('hebrewdate_correct_sunset', $correct_sunset);
		update_option('hebrewdate_latitude', $latitude);
		update_option('hebrewdate_longitude', $longitude);
		$updated = true;
?><div id="message" class="updated fade"><p>
	<?php _e('Configuration Updated.')?>
	</p></div><?php
	}
	$latin_display = get_option('hebrewdate_latin_display');
	$use_quotes = get_option('hebrewdate_use_quotes');
	$spelling = get_option('hebrewdate_spelling');
	$display_thousands = get_option('hebrewdate_display_thousands');
	$display_full = get_option('hebrewdate_display_full');
	$date_order = get_option('hebrewdate_date_order');
	$correct_sunset = get_option('hebrewdate_correct_sunset');
	$latitude = get_option('hebrewdate_latitude');
	$longitude = get_option('hebrewdate_longitude');
?>

<div class=wrap>
  <form method="post">
	<h2>Hebrew Date Options</h2>
	 <fieldset class="options">
	<legend>Display Style</legend>
	<p>
	<input type="radio" name="date_order" value=" <?php echo SHOW_HEBREW ?>"
	<?php if ($date_order == SHOW_HEBREW) echo "checked=\"checked\"" ?> 
	id="show_hebrew" />
	<label for="show_hebrew">Show Hebrew date only</label><br />

	<input type="radio" name="date_order" value="<?php echo SHOW_HEBREW_THEN_GREGORIAN ?>"
	<?php if ($date_order == SHOW_HEBREW_THEN_GREGORIAN) echo "checked=\"checked\"" ?>
	id="show_hebrew_then_gregorian" />
	<label for="show_hebrew_then_gregorian">Show Hebrew date - Gregorian date</label><br />

	<input type="radio" name="date_order" value="<?php echo SHOW_GREGORIAN_THEN_HEBREW ?>"
	<?php if ($date_order == SHOW_GREGORIAN_THEN_HEBREW) echo "checked=\"checked\"" ?>
	id="show_gregorian_then_hebrew" />
	<label for="show_gregorian_then_hebrew">Show Gregorian date - Hebrew date</label>
	</p>
	 </fieldset>
	 <fieldset class="options">
	<legend>Character Set</legend>
	<p>
	<input type="radio" name="spelling" value="<?php echo HEBREW_SPELLING ?>"
	<?php if ($spelling == HEBREW_SPELLING) echo "checked=\"checked\"" ?>
	id="hebrew_spelling" />
	<label for="hebrew_spelling">Hebrew months</label><br />

	<input type="radio" name="spelling" value="<?php echo ASHKENAZIC_SPELLING ?>"
	<?php if ($spelling == ASHKENAZIC_SPELLING) echo "checked=\"checked\"" ?>
	id="ashkenazic_spelling" />
	<label for="ashkenazic_spelling">Ashkenazic Transliteration</label><br />

	<input type="radio" name="spelling" value="<?php echo SEFARDIC_SPELLING ?>"
	<?php if ($spelling == SEFARDIC_SPELLING) echo "checked=\"checked\"" ?>
	id="sefardic_spelling" />
	<label for="sefardic_spelling">Sefardic Transliteration</label>
	</p>

	<p>
	<input type="checkbox" <?php if ($display_full) echo "checked=\"checked\"" ?> 
	name="display_full" id="display_full" />
	<label for="display_full">Display Full names (e.g. Menachem Av)</label><br />
	<input type="checkbox" <?php if ($latin_display) echo "checked=\"checked\"" ?>
	name="latin_display" id="latin_display" />
	<label for="latin_display">Display dates as Arabic numbers instead of Hebrew letters (Hebrew months display only)</label><br />
	<input type="checkbox" <?php if ($use_quotes) echo "checked=\"checked\"" ?>
	name="use_quotes" id="use_quotes" />
	<label for="use_quotes">Insert quotes in Hebrew dates</label><br />
	<input type="checkbox" <?php if ($display_thousands) echo "checked=\"checked\"" ?> 
	name="display_thousands" id="display_thousands" />
	<label for="display_thousands">Display Thousands in the Hebrew Year (Hebrew numbered dates only)</label>
	</p>
	 </fieldset>
	 <fieldset class="options">
	<legend>Sunset Calculation</legend>
	<p><input type="checkbox" <?php if ($correct_sunset) echo "checked=\"checked\"" ?>
	name="correct_sunset" id="correct_sunset" />
	<label for="correct_sunset">Correct dates for local sunset (default is to 
assume that nighttime is still the previous Hebrew day)</label></p>
	<p>
	<label for="latitude">Latitude (N):</label>
	<input type="text" <?php if ($latitude) echo "value=\"$latitude\"" ?> 
	name="latitude" size="10" id="latitude" /> 
	<label for="longitude">Longitude (E):</label>
	<input type="text" <?php if ($longitude) echo "value=\"$longitude\"" ?> 
	name="longitude" size="10" id="longitude" /></p>
	 </fieldset>
  <div class="submit">
  <input type="submit" name="update" value="Update" />
  </div>
  </form>
  <h2>Help</h2>
<h3>hebrewDateCurrent API</h3>
<p>
HebrewDate provides an API, 
<code>hebrewDateCurrent($dateFormat,$location)</code> that can be used to 
display the current Hebrew Date in your favorite theme. If called with no 
parameters (or an illegal combination of parameters), it displays the 
current Hebrew Date according to the Character Set configuration above. By 
setting <code>$dateFormat</code> to a <a href="http://php.net/date">valid 
PHP date format</a>, and <code>$location</code> to either 
<code>"before"</code> or <code>"after"</code> (including the quotation 
marks), it will display the secular day as well 
(<code>$location</code> controls the placement of the <strong>Hebrew 
Date</strong>).</p> 
<p>Alternatively, <code>$dateFormat</code> can be set to the special 
value of <code>"date_format"</code>, in which case it will use the default wordpress 
formatting.</p>

</div><?php
}

/*****************
 * API Functions *
 *****************/
function hebrewDateCurrent($dateFormat="",$where=false) {
	/* Calculate the current timestamp: currentTime converted to GMT converted to Wordpress offset */
	//	$now = date('U') - date('Z') + 60*60*get_option('gmt_offset');

	/* Special cases for $dateFormat */
	if ($dateFormat == "date_format") {
		$dateFormat=get_option("date_format");
	}

	$hebrewDateString = GetHebrewDateString(date('U'), "U", null);

	if ($where == "before" && $dateFormat != "") {
		echo $hebrewDateString . " - " . date($dateFormat);
	} else if ($where == "after" && $dateFormat != "") {
		echo date($dateFormat) . " - " . $hebrewDateString;
	} else  {
		echo $hebrewDateString;
	}
}

/* Ideally, a theme should use these two functions, instead of us intercepting the_* and get_the_* */
function get_the_hebrew_date() {
	$hebrewDateString = GetHebrewDateString(the_date("U"), "U", null);
	return $hebrewDateString;
}

function the_hebrew_date() {
	echo get_the_hebrew_date();
}

/*************************
 * Plugin Initialization *
 *************************/
add_action('admin_menu','hebrewDateMenu');
add_filter('the_time','AddHebrewDateWrap_the_time', 10, 2);
add_filter('the_date','AddHebrewDateWrap_the_date', 10, 2);
add_filter('get_the_time','AddHebrewDateWrap_get_the_time', 10, 2);
add_filter('get_the_date','AddHebrewDateWrap_get_the_date', 10, 2);
add_filter('get_comment_date','AddHebrewDateWrap_get_comment_date', 10, 2);
add_filter('get_comment_time','AddHebrewDateWrap_get_comment_time', 10, 2);

?>
