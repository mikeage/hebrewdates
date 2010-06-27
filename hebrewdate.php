<?php
/*
Plugin Name: Hebrew Date
Plugin URI: http://mikeage.net/content/software/hebrew-dates-in-wordpress/
Description: A plugin that provides Hebrew dates in Wordpress. Based on the <a href="http://www.kosherjava.com/wordpress/hebrew-date-plugin/">Hebrew Date</a> plugin by <a href="http://kosherjava.com">KosherJava</a>.
Version: 1.0.4
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
$ashkenazMonths = array("Tishrei", "Cheshvan", "Kislev", "Teves", "Shevat", "Adar I",
			"Adar II", "Nisan", "Iyar", "Sivan", "Tamuz", "Av", "Elul",
			"Adar", "Marcheshvan", "Menachem Av");
$sefardMonths = array(	"Tishre", "Heshwan", "Kislev", "Tevet", "Shevat", "Adar I",
			"Adar II", "Nisan", "Iyar", "Sivan", "Tamuz", "Av", "Elul",
			"Adar", "Marheshwan", "Menahem Av");
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

function jewishDateCalculate($content, $hour="", $min="", $day="") {
	$isArchiveFormat = false;
	$pdate = -1;
	return jewishDateCalculateCheckFormat($content,$hour,$min,$day,$isArchiveFormat,$pdate);
}

function jewishDateCalculateCheckFormat($content, $hour="", $min="", $day="", &$isArchiveFormat, &$pdate) {
	$sunset_correction = get_option('hebrewdate_correct_sunset') ? true : false;
	$colPos = strrpos($content, ":");
	$comPos = strrpos($content, ",");
	$slashPos = strrpos($content, "/");

	$content = str_replace("<br />", "", $content);
	$date_format = get_settings('date_format');

	$pdate = strtotime($content); //WP-Admin adds a break tag for display purposes which we have to strip out.
//	printf("PDATE is |$pdate| for |$content|");
	if (($content > strtotime("Jan 1, 1990")) && ($content < strtotime("Dec 31, 2020")))
		$pdate = $content;
	if ($pdate == -1){ // non valid parsable date such as Month, Year (archives)
		$dateParts = explode(" ", trim(str_replace(",","",$content))); //try to extract Month, Year format
		$month=$dateParts[0];

		$months = array("january"=>"1", "february"=>"2", "march"=>"3", "april"=>"4", "may"=>"5", "june"=>"6", "july"=>"7", "august"=>"8", "september"=>"9", "october"=>"10", "november"=>"11", "december"=>"12");
		if (isset($months[strtolower($month)])) { //found archive month in array
			$month = $months[strtolower($month)];
		} else { //FIXME might be caused by localized non english month
			//printf("returning early");
			$isArchiveFormat = true;
			return $content;
		}
	
		if (empty($year))
			$year=$dateParts[1];
		if(! is_numeric($year)){ // funny date format for archive
			return $content;
		}
		$isArchiveFormat = true;
	} else 	if (($content > 1990) && ($content < 2020)) { // Special case for a year
		//printf("returning early");
		$isArchiveFormat = true;
		return $content;
	} else { 
		if ($sunset_correction) {
			if ($hour != "" && $min != "" && $day!= "") {
				; // We have our H:M
			} else if ($comPos == false && $slashPos == false) {
				// Probably a timestamp
				$hour = date('H',$content);
				$min =  date('i',$content);
				$day = date('z', $content);
			} else if (get_comment_time('z')) {
				// If it's a comment, we don't want the post time
				$hour = get_comment_time('H');
				$min = get_comment_time('i');
				$day = get_comment_time('z');
			} else {
				// Get the time from the post
				$hour = get_post_time('H');
				$min = get_post_time('i');
				$day = get_post_time('z');
//				print "GTT returned " . get_the_time('H'). " and " . get_the_time('G');
			}
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
		$month = date('m',$adj_pdate);
		$day = date('j',$adj_pdate);
		$year = date('Y',$adj_pdate);
	}
	$altDay=30;
	if (empty($day)) {
		$day = 1;
		if (!empty($month)){
			if (function_exists("cal_days_in_month")) $altDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			else $altDay = 30;
		}
	}

	$jd = gregoriantojd($month, $day, $year);
	$hebrewDate = jdtojewish($jd);
	list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', $hebrewDate);

	$altJd = gregoriantojd($month, $altDay, $year);
	$altHebrewDate = jdtojewish($altJd);
	list ($altHebrewMonth, $altHebrewDay, $altHebrewYear) = split ('/', $altHebrewDate);

	if ($isArchiveFormat) $hebrewDay = "";

	$convertedDate = getHebrewDate($spelling, $hebrewYear, $hebrewMonth, 
		$hebrewDay, $altHebrewYear, $altHebrewMonth);

	return $convertedDate;
}

//function jewishDate($content, $hour="",$min="", $day="") {
function jewishDate($content) {
	$spelling = get_option('hebrewdate_spelling');
	//FIXME use a better way to detect time.
	$colPos = strrpos($content, ":");
	$comPos = strrpos($content, ",");
	$slashPos = strrpos($content, "/");
	$day="";
	$month="";
	$year="";
	$isArchiveFormat = false;
   // printf("|now working on -|$content|-  ");
	if (($colPos != false) && ($comPos == false)) {// contains a colon and no comma, assuming a time not date.
	   return $content;
	}
	$doneAlready = strrpos($content, "<!-- HD -->");
	if(false !== $doneAlready) { return $content;} // Already processed
//	if(false !== $doneAlready) { printf("|$content$| was done already at $doneAlready!"); ; return $content;} // Already processed

	$convertedDate = jewishDateCalculateCheckFormat($content,$hour,$min,$day,$isArchiveFormat,$pdate);

	$time_format = get_settings('time_format');
	$date_format = get_settings('date_format');

	if($isArchiveFormat){
		$gregorianDate = $content;
	} else {
		$gregorianDate = date($date_format, $pdate);
	}
	$date_order = get_option('hebrewdate_date_order');
	if ($date_order == SHOW_HEBREW) {
		$outputDate = $convertedDate;
	} else if ($date_order == SHOW_HEBREW_THEN_GREGORIAN) {
		$outputDate = $convertedDate . ' - ' . $gregorianDate;
	} else if ($date_order == SHOW_GREGORIAN_THEN_HEBREW) {
		$outputDate = $gregorianDate . ' - ' . $convertedDate;
	}

	return "<!-- HD -->" . $outputDate; 
}
function getHebrewDate($spelling, $year, $month="", $day="", $altYear="", $altMonth="") {
	$charset = get_option('hebrewdate_latin_display') ? LATIN_CHARSET : HEBREW_CHARSET;
	if ($spelling == SEFARDIC_SPELLING || $spelling == ASHKENAZIC_SPELLING) {

		$charset = LATIN_CHARSET;
	}
	if ($day) {
		if ($charset == HEBREW_CHARSET) {
			$hebrewDay = getHebrewDay($day);
		} else {
			$hebrewDay = $day;
		}
	}
	$hebrewMonth = getHebrewMonth($spelling, $month, $year);
	if (empty($day) && !empty($altMonth) && ($altMonth != $month)) {
		$altHebrewMonth = getHebrewMonth($spelling, $altMonth, $altYear);
	}
	if ($charset == HEBREW_CHARSET) {
		$hebrewYear = getHebrewYear($year);
	} else {
		$hebrewYear = $year;
	}
	if (empty($month))
		$altYear = $year + 1; // Fall through in the next case
	if (!empty($altYear) && $altYear != $year) {
		if ($charset == HEBREW_CHARSET) {
			$altHebrewYear = getHebrewYear($altYear);
		} else {
			$altHebrewYear = $altYear;
		}
	}
/*
	print "Results for D:$day M:$month AM:$altMonth Y:$year AY:$altYear are:";
	print "D:$hebrewDay M:$hebrewMonth AM:$altHebrewMonth Y:$hebrewYear AY:$altHebrewYear";
*/
	if ($charset != HEBREW_CHARSET && $spelling == HEBREW_SPELLING) {
		$convertedDate = "<span dir=\"rtl\">";
	}
	if ($hebrewDay)
		$convertedDate .= $hebrewDay;
	if ($hebrewMonth)
		$convertedDate .= " $hebrewMonth";
	if (!empty($altHebrewMonth) && empty($altHebrewYear))
		$convertedDate .= " / $altHebrewMonth";
	if ($hebrewYear)
		$convertedDate .= " $hebrewYear";
	if (!empty($altHebrewMonth) && !empty($altHebrewYear))
		$convertedDate .= " / $altHebrewMonth $altHebrewYear"; 
	if ($charset != HEBREW_CHARSET && $spelling == HEBREW_SPELLING) {
		$convertedDate .= "</span>";
	}
	return $convertedDate;
}
function getHebrewDay($day) {
	$jTens = array("", "&#1497;", "&#1499;", "&#1500;", "&#1502;",
		"&#1504;", "&#1505;", "&#1506;", "&#1508;", "&#1510;");
	$jTenEnds = array("", "&#1497;", "&#1498;", "&#1500;", "&#1501;",
		   "&#1503;", "&#1505;", "&#1506;", "&#1507;", "&#1509;");
	$tavTaz = array("&#1496;&quot;&#1493;", "&#1496;&quot;&#1494;");
	$jOnes = array("", "&#1488;", "&#1489;", "&#1490;", "&#1491;",
		"&#1492;", "&#1493;", "&#1494;", "&#1495;", "&#1496;");

	if($day < 10) { //single digit days get single quote appended
		$sb .= $jOnes[$day];
		$sb .= "'";
	} else if($day == 15) { //special case 15
		$sb .= $tavTaz[0];
	} else if($day == 16) { //special case 16
		$sb .= $tavTaz[1];
	} else {
		$tens = $day / 10;
		$sb .= $jTens[$tens];
		if($day % 10 == 0) { // 10 or 20 single digit append single quote
			$sb .= "'";
		} else if($day > 10) { // >10 display " between 10s and 1s
			$sb .= "&quot;";
		}
		$day = $day % 10; //discard 10s
		$sb .= $jOnes[$day];
	}
	return $sb;
}
function getHebrewMonth($spelling, $month, $year) {

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

function getHebrewYear($year) {
	$display_thousands = get_option('hebrewdate_display_thousands');
	$jAlafim = "&#1488;&#1500;&#1508;&#1497;&#1501;"; //word ALAFIM in Hebrew for display on years evenly divisable by 1000
	$jHundreds = array("", "&#1511;","&#1512;","&#1513;","&#1514;",	"&#1514;&#1511;","&#1514;&#1512;","&#1514;&#1513;", "&#1514;&#1514;", "&#1514;&#1514;&#1511;");
	$jTens = array("", "&#1497;", "&#1499;", "&#1500;", "&#1502;", "&#1504;", "&#1505;", "&#1506;", "&#1508;", "&#1510;");
	$jTenEnds = array("", "&#1497;", "&#1498;", "&#1500;", "&#1501;", "&#1503;", "&#1505;", "&#1506;", "&#1507;", "&#1509;");
	$tavTaz = array("&#1496;&quot;&#1493;", "&#1496;&quot;&#1494;");
	$jOnes = array("", "&#1488;", "&#1489;", "&#1490;", "&#1491;", "&#1492;", "&#1493;", "&#1494;", "&#1495;", "&#1496;");

	$singleDigitYear = isSingleDigitHebrewYear($year);
	$thousands = $year / 1000; //get # thousands

	$sb = "";

	//append thousands to String
	if($year % 1000 == 0) { // in year is 5000, 4000 etc
		$sb .= $jOnes[$thousands];
		$sb .= "'";
		$sb .= "&#160;";
		$sb .= $jAlafim; //add # of thousands plus word thousand (overide alafim boolean)
	} else if($display_thousands) { // if alafim boolean display thousands
		$sb .= $jOnes[$thousands];
		$sb .= "'"; //append thousands quote
		$sb .= "&#160;";
	}
	$year = $year % 1000;//remove 1000s
	$hundreds = $year / 100; // # of hundreds
	$sb .= $jHundreds[$hundreds]; //add hundreds to String
	$year = $year % 100; //remove 100s
	if($year == 15) { //special case 15
		$sb .= $tavTaz[0];
	} else if($year == 16) { //special case 16
		$sb .= $tavTaz[1];
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
		$sb .= "'"; //append single quote
	} else { // append double quote before last digit
		$pos1 = strrpos($sb, "&");
		$sb = substr($sb, 0, $pos1) . "&quot;" . substr($sb, $pos1);
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

function calcSunset($latitude, $longitude, $zenith, $yday, $offset) {
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
	if($year%19 == 0 || $year%19 == 3 || $year%19 ==6 || $year%19 == 8
	 ||$year%19 == 11|| $year%19 == 14|| $year%19 == 17) {
		return true;
	} else {
		return false;
	}
}

function hebrewDateMenu() {
    if (function_exists('add_options_page')) {
	add_options_page('Configure Hebrew Date Display', 'Hebrew Date', 6, basename(__FILE__), 'hebrewdate_subpanel');
    }
 }

function hebrewdate_subpanel() {
    $updated = false;
    if (isset($_POST['update'])) {
	$latin_display = $_POST['latin_display'];
	$spelling = $_POST['spelling'];
	$display_thousands = $_POST['display_thousands'];
	$display_full = $_POST['display_full'];
	$date_order = $_POST['date_order'];
	$correct_sunset = $_POST['correct_sunset'];
	$latitude = $_POST['latitude'];
	$longitude = $_POST['longitude'];
	update_option('hebrewdate_latin_display', $latin_display);
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
<p>Finally, <code>$dateFormat</code> can be set to the special value of 
<code>"default"</code>. In this case, the <code>$location</code> 
parameter is ignored, and the function produces the same value that 
<code>the_time()</code> produces within The Loop.</p>

 </div><?php
}

function hebrewDateCurrent($dateFormat="",$where=false) {
	/* Calculate the current timestamp: currentTime converted to GMT converted to Wordpress offset */
	$display = date('U') - date('Z') + 60*60*get_option('gmt_offset');
	if ($dateFormat == "date_format") 
		$dateFormat=get_option("date_format");
	if ($dateFormat == "default") {
		echo jewishDate(date("F j, Y",$display),date("G",$display),date("i",$display),date("z",$display));	
	}
	else if ($where == "before" && $dateFormat != "")
		echo jewishDateCalculate($display) . "-" . date($dateFormat);
	else if ($where =="after" && $dateFormat != "")
		echo date($dateFormat) . "-" . jewishDateCalculate($display);
	else 
		echo jewishDateCalculate($display);

}
/*
function hebrewDateHoliday($year="",$month="",$day="",$hour="",$min="")
{
if (!$year || !$month || !$day)

		$month = date('m',$adj_pdate);
		$day = date('j',$adj_pdate);
		$year = date('Y',$adj_pdate);

				$hour = date('G',$content);
				$min =  date('i',$content);


}
*/

add_action('admin_menu','hebrewDateMenu');
add_filter('the_time','jewishDate');
add_filter('the_date','jewishDate');
add_filter('get_the_time','jewishDate');
add_filter('get_the_date','jewishDate');
add_filter('get_comment_date','jewishDate');
?>
