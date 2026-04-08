<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('is_html')) {
    /**
     * Check if there is html in string
     */
    function is_html($string)
    {
        return isHtml($string);
    }
}

if (!function_exists('strafter')) {
    /**
     * Get string after specific charcter/word
     * @param  string $string    string from where to get
     * @param  substring $substring search for
     * @return string
     */
    function strafter($string, $substring)
    {
        return after($string, $substring);
    }
}

if (!function_exists('strbefore')) {
    /**
     * Get string before specific charcter/word
     * @param  string $string    string from where to get
     * @param  substring $substring search for
     * @return string
     */
    function strbefore($string, $substring)
    {
        return before($string, $substring);
    }
}

if (!function_exists('check_for_links')) {
    /**
     * Check for links/emails/ftp in string to wrap in href
     * @param  string $ret
     * @return string      formatted string with href in any found
     */
    function check_for_links($ret)
    {
        return clickable($ret);
    }
}

/**
 * Check for links/emails/ftp in string to wrap in href
 * @param  string $ret
 * @return string      formatted string with href in any found
 */
function clickable($ret)
{
    $ret = ' ' . $ret;
    // in testing, using arrays here was found to be faster
    $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'self::make_url_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'self::make_web_ftp_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', 'self::make_email_clickable_cb', $ret);
    // this one is not in an array because we need it to run last, for cleanup of accidental links within links
    $ret = preg_replace('#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', '$1$3</a>', $ret);
    $ret = trim($ret);

    return $ret;
}

/**
 * Check for links/emails/ftp in string to wrap in href
 * @param  string $ret
 * @return string      formatted string with href in any found
 */
function check_for_links($ret)
{
    $ret = ' ' . $ret;
    // in testing, using arrays here was found to be faster
    $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_url_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_web_ftp_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret);
    // this one is not in an array because we need it to run last, for cleanup of accidental links within links
    $ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
    $ret = trim($ret);
    
    return $ret;
}

function isHtml($string)
{
    return preg_match('/<[^<]+>/', $string, $m) != 0;
}

function after($string, $substring)
{
    $pos = strpos($string, $substring);
    if ($pos === false) {
        return $string;
    }

    return (substr($string, $pos + strlen($substring)));
}

function before($string, $substring)
{
    $pos = strpos($string, $substring);
    if ($pos === false) {
        return $string;
    }

    return (substr($string, 0, $pos));
}
/**
 * Remove <br /> html tags from string to show in textarea with new linke
 * @param  string $text
 * @param  string $replace character to replace with
 * @return string formatted text
 */
function clear_textarea_breaks($text, $replace = '')
{
    if (empty($text)) {
        return $text;
    }

    $breaks = [
        '<br />',
        '<br>',
        '<br/>',
    ];

    $text = str_ireplace($breaks, $replace, $text);
    $text = trim($text);

    return $text;
}
/**
 * Equivalent function to nl2br php function but keeps the html if found and do not ruin the formatting
 * @param  string $string
 * @return string
 */
function nl2br_save_html($string)
{
    if(! preg_match("#</.*>#", $string)) // avoid looping if no tags in the string.
        return nl2br($string);

    $string = str_replace(array("\r\n", "\r", "\n"), "\n", $string);

    $lines=explode("\n", $string);
    $output='';
    foreach($lines as $line)
    {
        $line = rtrim($line);
        if(! preg_match("#</?[^/<>]*>$#", $line)) // See if the line finished with has an html opening or closing tag
            $line .= '<br />';
        $output .= $line . "\n";
    }

    return $output;
}
/**
 * Coma separated tags for input
 * @param  array $tag_names
 * @return string
 */
function prep_tags_input($tag_names){
    $tag_names = array_filter($tag_names, function($value) { return $value !== ''; });
    return implode(',',$tag_names);
}
/**
 * Strip tags
 * @param  string $html string to strip tags
 * @return string
 */
function _strip_tags($html)
{
    return strip_tags($html, '<br>,<em>,<p>,<ul>,<ol>,<li>,<h4><h3><h2><h1>,<pre>,<code>,<a>,<img>,<strong>,<b>,<blockquote>,<table>,<thead>,<th>,<tr>,<td>,<tbody>,<tfoot>');
}
/**
 * Convert hex color to rgb
 * @param  string $color color hex code
 * @return string
 */
function hex2rgb($color)
{
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6) {
        return array(
            0,
            0,
            0
        );
    }
    $rgb = array();
    for ($x = 0; $x < 3; $x++) {
        $rgb[$x] = hexdec(substr($color, (2 * $x), 2));
    }
    return $rgb;
}
/**
 * Normaliza espaços “não-quebráveis” no HTML e limpa parasitas comuns.
 * - Remove &nbsp;, &#160;, &#xA0; e o caractere Unicode U+00A0 (e afins).
 * - Remove <p> “vazios” (ex.: <p>&nbsp;</p>).
 * - (Opcional) compacta espaços múltiplos.
 *
 * Requer mbstring habilitado para a flag /u funcionar nas regex.
 */
if (!function_exists('sanitize_html_input')) {

    function sanitize_html_input($html, $compactSpaces = false): string {
        $normalized = normalize_html_spaces($html, $compactSpaces);
        // true => permite algum HTML conforme políticas do Perfex/CI
        return clean($normalized, true);
    }
}
function normalize_html_spaces(string $html, bool $compactSpaces = false): string
{
    if ($html === '') return $html;

    // Cobre: &nbsp; | &#160; | &#xA0; | U+00A0 (UTF-8 \xC2\xA0) | variantes “thin space”
    $html = preg_replace(
        [
            '/&(nbsp|#160|#xA0);/i',          // entidades HTML
            "/\xC2\xA0/u",                    // U+00A0 em bytes UTF-8
            '/\x{00A0}|\x{202F}|\x{2007}/u',  // No-break / narrow no-break / figure space
        ],
        ' ',
        $html
    );    

    // Ex.: <p>   </p>, <p><br></p>, <p>&nbsp;</p>
    $html = preg_replace('/<p>\s*(?:<br\s*\/?>\s*)*<\/p>/i', '', $html);  
    
    if ($compactSpaces) {
        // Evita crescer whitespace entre tags
        $html = preg_replace('/\s{2,}/u', ' ', $html);
        // Tira espaços excessivos entre fechamento/abertura de tags
        $html = preg_replace('/>\s+</u', '><', $html);
    }

    return trim($html);    
}
/**
 * Function that strip all html tags from string/text/html
 * @param  string $str
 * @param  string $allowed prevent specific tags to be stripped
 * @return string
 */
function strip_html_tags($str, $allowed = '')
{
    $str = preg_replace('/(<|>)\1{2}/is', '', $str);
    $str = preg_replace(array(
        // Remove invisible content
        '@<head[^>]*?>.*?</head>@siu',
        '@<style[^>]*?>.*?</style>@siu',
        '@<script[^>]*?.*?</script>@siu',
        '@<object[^>]*?.*?</object>@siu',
        '@<embed[^>]*?.*?</embed>@siu',
        '@<applet[^>]*?.*?</applet>@siu',
        '@<noframes[^>]*?.*?</noframes>@siu',
        '@<noscript[^>]*?.*?</noscript>@siu',
        '@<noembed[^>]*?.*?</noembed>@siu',
        // Add line breaks before and after blocks
        '@</?((address)|(blockquote)|(center)|(del))@iu',
        '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
        '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
        '@</?((table)|(th)|(td)|(caption))@iu',
        '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
        '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
        '@</?((frameset)|(frame)|(iframe))@iu'
    ), array(
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0"
    ), $str);

    $str = strip_tags($str, $allowed);

    // Remove on events from attributes
    $re = '/\bon[a-z]+\s*=\s*(?:([\'"]).+?\1|(?:\S+?\(.*?\)(?=[\s>])))/i';
    $str = preg_replace($re, '', $str);

    return $str;
}
/*
* Function to strip slashes for displaying content to the user
*
* @param string	$value		The string to be stripped
* @return						The stripped text
*/
function clean($value) {
	$str = str_replace('\\', '', $value);
	return $str;
}

function fulltrim( $str ) {
	return trim( preg_replace( '/\s+/', ' ', $str ) );
}

function cleantrim($value) {
	$str = str_replace('\r\n', '<br>', $value);
	return $str;		
}