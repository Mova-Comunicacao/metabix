<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Cocur\Slugify\RuleProvider\DefaultRuleProvider as DefaultSlugRuleProvider;
use Cocur\Slugify\Slugify;

/*
* Function to Encrypt user sensitive data for storing in the database
*
* @param string	$value		The text to be encrypted
* @param 			$encodeKey	The Key to use in the encrytion
* @return						The encrypted text
*/
function encryptIt($value) 
{
	// The encodeKey MUST match the decodeKey
	$encodeKey = 'DvHtl3CGp4QLuuOEtBQ2AS';
	$encoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($encodeKey), $value, MCRYPT_MODE_CBC, md5(md5($encodeKey))));
	return($encoded);
}

/*
* Function to decrypt user sensitive data for displaying to the user
*
* @param string	$value		The text to be decrypted
* @param 			$decodeKey	The Key to use for decryption
* @return						The decrypted text
*/
function decryptIt($value) {
	// The decodeKey MUST match the encodeKey
	$decodeKey = 'DvHtl3CGp4QLuuOEtBQ2AS';
	$decoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($decodeKey), base64_decode($value), MCRYPT_MODE_CBC, md5(md5($decodeKey))), "\0");
	return($decoded);
}

function analytics_date($str)
{
	// Array com os meses do ano em português;
	$arrMonthsOfYear = array(1 => 'Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez');
	// Descobre o mês
	$intMonthOfYear = date('n',strtotime($str));
	// Formato a ser retornado
	return  $intMonthOfYear;
}	

/**
 * Function to calculate the estimated reading time of the given text.
 * 
 * @param string $text The text to calculate the reading time for.
 * @param string $wpm The rate of words per minute to use.
 * @return Array
 */
function estimateReadingTime($text, $wpm = 200) {
    $totalWords = str_word_count(strip_tags($text));
    $minutes = floor($totalWords / $wpm);
    $seconds = floor($totalWords % $wpm / ($wpm / 60));
    
    return array(
        'minutes' => $minutes,
        'seconds' => $seconds
    );
}

/**
 * Gets the thumbnail url associated with an url from either:
 *
 *      - youtube
 *      - daily motion
 *      - vimeo
 *
 * Returns false if the url couldn't be identified.
 *
 * In the case of you tube, we can use the second parameter (format), which
 * takes one of the following values:
 *      - small         (returns the url for a small thumbnail)
 *      - medium        (returns the url for a medium thumbnail)
 *
 *
 *
*/
function video_image($url) {
	$image_url = parse_url($url);
	if($image_url['host'] == 'www.youtube.com' || $image_url['host'] == 'youtube.com'){
		$array = explode("&", $image_url['query']);
		return "https://img.youtube.com/vi/".substr($array[0], 2)."/maxresdefault.jpg"; 		// Large Default
		return "https://img.youtube.com/vi/".substr($array[0], 2)."/default.jpg"; 		// Large Default
		return "https://img.youtube.com/vi/".substr($array[0], 2)."/0.jpg";
		return "https://img.youtube.com/vi/".substr($array[0], 2)."/1.jpg";
		return "https://img.youtube.com/vi/".substr($array[0], 2)."/2.jpg";
		return "https://img.youtube.com/vi/".substr($array[0], 2)."/3.jpg";			
	} else if($image_url['host'] == 'www.vimeo.com' || $image_url['host'] == 'vimeo.com'){
		$hash = unserialize(file_get_contents("https://vimeo.com/api/v2/video/".substr($image_url['path'], 1).".php"));
		return $hash[0]["thumbnail_medium"];
	}
	
}

/**
 * Returns the location of the actual video for a given url which belongs to either:
 *
 *      - youtube
 *      - daily motion
 *      - vimeo
 *
 * Or returns false in case of failure.
 * This function can be used for creating video sitemaps.
 */
function getVideoLocation($url)
{
	if (false !== ($id = getVimeoId($url))) {
		return /*'https://player.vimeo.com/video/' .*/ $id;
	}
	elseif (false !== ($id = getYoutubeId($url))) {
		return /*'https://www.youtube.com/embed/' .*/ $id;
	}
	return false;
}

/**
 * Extracts the youtube id from a youtube url.
 * Returns false if the url is not recognized as a youtube url.
 */
function getYoutubeId($url) {
	$parts = parse_url($url);
	if (isset($parts['host'])) {
		$host = $parts['host'];
		if (
			false === strpos($host, 'youtube') &&
			false === strpos($host, 'youtu.be')
		) {
			return false;
		}
	}
	if (isset($parts['query'])) {
		parse_str($parts['query'], $qs);
		if (isset($qs['v'])) {
			return $qs['v'];
		}
		else if (isset($qs['vi'])) {
			return $qs['vi'];
		}
	}
	if (isset($parts['path'])) {
		$path = explode('/', trim($parts['path'], '/'));
		return $path[count($path) - 1];
	}
	return false;
}

/**
 * Extracts the vimeo id from a vimeo url.
 * Returns false if the url is not recognized as a vimeo url.
 */
function getVimeoId($url) {
	if (preg_match('#(?:https?://)?(?:www.)?(?:player.)?vimeo.com/(?:[a-z]*/)*([0-9]{6,11})[?]?.*#', $url, $m)) {
		return $m[1];
	}
	return false;
}

if (!function_exists('in_array_multidimensional')) {
    /**
     * Check if in array multidimensional
     * @param  array $array  array to perform the checks
     * @param  mixed $key    array key
     * @param  mixed $val    the value to check
     * @return boolean
     */
    function in_array_multidimensional($array, $key, $val)
    {
        return inMultidimensional($array, $key, $val);
    }
}

function inMultidimensional($array, $key, $val) {
	foreach ($array as $item) {
		if (isset($item[$key]) && $item[$key] == $val) {
			return true;
		}
	}

	return false;
}
if (!function_exists('get_string_between')) {
    /**
     * Get string bettween words
     * @param  string $string the string to get from
     * @param  string $start  where to start
     * @param  string $end    where to end
     * @return string formatted string
     */
    function get_string_between($string, $start, $end)
    {
		$string = ' ' . $string;
		$ini    = strpos($string, $start);
		if ($ini == 0) {
			return '';
		}
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
	
		return substr($string, $ini, $len);
    }
}

if (!function_exists('seconds_to_time_format')) {
    /**
     * Format seconds to H:I:S
     * @param  integer $seconds         mixed
     * @param  boolean $include_seconds
     * @return string
     */
    function seconds_to_time_format($seconds = 0, $include_seconds = false)
    {
		$hours = floor($seconds / 3600);
		$mins  = floor(($seconds - ($hours * 3600)) / 60);
		$secs  = floor($seconds % 60);
	
		$hours   = ($hours < 10) ? '0' . $hours : $hours;
		$mins    = ($mins < 10) ? '0' . $mins : $mins;
		$secs    = ($secs < 10) ? '0' . $secs : $secs;
		$sprintF = $include_seconds == true ? '%02d:%02d:%02d' : '%02d:%02d';
	
		return sprintf($sprintF, $hours, $mins, $secs);    }
}

if (!function_exists('slug_it')) {
    /**
     * Slug function
     * @param  string $str
     * @param  array  $options Additional Options
     * @return mixed
     */
    function slug_it($str, $options = [])
    {
        $defaults = ['lang' => get_option('active_language')];
        $settings = array_merge($defaults, $options);

        return slug($str, $settings);
    }
}
function slug($str, $options = [])
{
	$defaults = [];

	// Deprecated
	if (isset($options['delimiter'])) {
		$defaults['separator'] = $options['delimiter'];
		unset($options['delimiter']);
	}

	$m = new DefaultSlugRuleProvider();

	$lang = isset($options['lang']) ? $options['lang'] : 'english';
	$set  = $lang == 'english' ? 'default' : $lang;

	$default_active_rule_sets = [
		'default',
		'azerbaijani',
		'burmese',
		'hindi',
		'georgian',
		'norwegian',
		'vietnamese',
		'ukrainian',
		'latvian',
		'finnish',
		'greek',
		'czech',
		'arabic',
		'turkish',
		'spanish',
		'polish',
		'german',
		'russian',
		'romanian',
	];

	// Set for portuguese in Slugify is named portuguese-brazil
	if ($set == 'portuguese_br' || $set == 'portuguese') {
		$set = 'portuguese-brazil';
	}

	if (!in_array($set, $default_active_rule_sets)) {
		$r = @$m->getRules($set);
		// Check if set exist
		if ($r) {
			$defaults['rulesets'] = [$set];
		}
	}

	$options = array_merge($defaults, $options);

	$slugify = new Slugify($options);

	return $slugify->slugify($str);
}
/*
 * ip_in_range.php - Function to determine if an IP is located in a
 *                   specific range as specified via several alternative
 *                   formats.
 *
 * Network ranges can be specified as:
 * 1. Wildcard format:     1.2.3.*
 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
 *
 * Return value BOOLEAN : ip_in_range($ip, $range);
 *
 * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
 * 10 January 2008
 * Version: 1.2
 *
 * Source website: http://www.pgregg.com/projects/php/ip_in_range/
 * Version 1.2
 *
 * This software is Donationware - if you feel you have benefited from
 * the use of this tool then please consider a donation. The value of
 * which is entirely left up to your discretion.
 * http://www.pgregg.com/donate/
 *
 * Please do not remove this header, or source attibution from this file.
 */

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
    list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
            // $netmask is a 255.255.0.0 format
      $netmask = str_replace('*', '0', $netmask);
            $netmask_dec = ip2long($netmask);

            return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
        } else {
            // $netmask is a CIDR size block
      // fix the range argument
      $x = explode('.', $range);
            while (count($x)<4) {
                $x[] = '0';
            }
            list($a, $b, $c, $d) = $x;
            $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b, empty($c)?'0':$c, empty($d)?'0':$d);
            $range_dec = ip2long($range);
            $ip_dec = ip2long($ip);

      # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
      #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

      # Strategy 2 - Use math to create it
      $wildcard_dec = pow(2, (32-$netmask)) - 1;
            $netmask_dec = ~ $wildcard_dec;

            return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
    } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
    if (strpos($range, '*') !==false) { // a.b.*.* format
      // Just convert to A-B format by setting * to 0 for A and 255 for B
      $lower = str_replace('*', '0', $range);
        $upper = str_replace('*', '255', $range);
        $range = "$lower-$upper";
    }

        if (strpos($range, '-')!==false) { // A-B format
      list($lower, $upper) = explode('-', $range, 2);
            $lower_dec = (float) sprintf("%u", ip2long($lower));
            $upper_dec = (float) sprintf("%u", ip2long($upper));
            $ip_dec = (float) sprintf("%u", ip2long($ip));

            return (($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec));
        }

        echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';

        return false;
    }
}