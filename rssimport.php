<?php
/*
Plugin Name: WP-RSSImport
Plugin URI: http://bueltge.de/wp-rss-import-plugin/55/
Description: Import and display Feeds in your blog, use the function RSSImport() or Shortcode [RSSImport]. Please see the new <a href="http://wordpress.org/extend/plugins/rss-import/">possibilities</a>.
Author: Frank B&uuml;ltge
Version: 4.2.9
License: GPL
Author URI: http://bueltge.de/
Last change: 23.05.2009 23:03:28
*/ 

/*
------------------------------------------------------------
 ACKNOWLEDGEMENTS
------------------------------------------------------------
Original and Idea: Dave Wolf, http://www.davewolf.net
Thx to Thomas Fischer, http://www.securityfocus.de and
Gunnar Tillmann http://www.gunnart.de for a better code

USAGE: Use following code with a PHP-Plugin for WordPress:
Example: <?php RSSImport(10, "http://bueltge.de/feed/"); ?>
------------------------------------------------------------
*/

//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( function_exists('add_action') ) {
	//WordPress definitions
	if ( !defined('WP_CONTENT_URL') )
		define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
	if ( !defined('WP_CONTENT_DIR') )
		define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
	if ( !defined('WP_PLUGIN_URL') )
		define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
	if ( !defined('WP_PLUGIN_DIR') )
		define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
	if ( !defined('PLUGINDIR') )
		define( 'PLUGINDIR', 'wp-content/plugins' ); // Relative to ABSPATH.  For back compat.
	if ( !defined('WP_LANG_DIR') )
		define('WP_LANG_DIR', WP_CONTENT_DIR . '/languages');

	// plugin definitions
	define( 'FB_RSSI_BASENAME', plugin_basename(__FILE__) );
	define( 'FB_RSSI_BASEFOLDER', plugin_basename( dirname( __FILE__ ) ) );
	define( 'FB_RSSI_TEXTDOMAIN', 'rssimport' );
	define( 'FB_RSSI_QUICKTAG', true );
}

// For function fetch_rss from wp-core
if ( file_exists(ABSPATH . WPINC . '/rss.php') ) {
	@require_once (ABSPATH . WPINC . '/rss.php');
	// It's Wordpress 2.x. since it has been loaded successfully
} elseif (file_exists(ABSPATH . WPINC . '/rss-functions.php')) {
	@require_once (ABSPATH . WPINC . '/rss-functions.php');
	// In Wordpress < 2.1
} else {
	die (__('Error in file: ' . __FILE__ . ' on line: ' . __LINE__ . '.<br />The Wordpress file "rss-functions.php" or "rss.php" could not be included.'));
}


function RSSImport_textdomain() {

	if ( function_exists('load_plugin_textdomain') )
	load_plugin_textdomain( FB_RSSI_TEXTDOMAIN, false, dirname( FB_RSSI_BASENAME ) . '/languages');
}


// cache and error report
//define('MAGPIE_CACHE_ON', false); // Cache off
define('MAGPIE_CACHE_AGE', '60*60'); // in sec, one hour
// error reporting
//error_reporting(E_ALL);

function RSSImport(
										$display = 5, $feedurl = 'http://bueltge.de/feed/',
										$before_desc = '', $displaydescriptions = false, $after_desc = '', $html = false, $truncatedescchar = 200, $truncatedescstring = ' ... ',
										$truncatetitlechar = '', $truncatetitlestring = ' ... ',
										$before_date = ' <small>', $date = false, $after_date = '</small>',
										$before_creator = ' <small>', $creator = false, $after_creator = '</small>',
										$start_items = '<ul>', $end_items = '</ul>',
										$start_item = '<li>', $end_item = '</li>',
										$target = '',
										$charsetscan = false, $debug = false,
										$view = true
									) {
	
	$display = intval($display);
	$truncatedescchar = intval($truncatedescchar);
	$truncatetitlechar = intval($truncatetitlechar);
	$echo  = '';
	
	if ( $charsetscan && function_exists('file_get_contents') ) {
		// read in file for search charset
		ini_set('default_socket_timeout', 120);
		$a = file_get_contents($feedurl);
		// for better performance, if the server accepts the method 
		// $a = file_get_contents($feedurl, FALSE, NULL, 0, 50);
	}
	
	$rss = fetch_rss($feedurl);

	if ($rss) {
		
		// the follow print_r list all items in array, for debug purpose
		if ($debug) {
			print('<pre>');
			print_r($rss);
			print('</pre>');
			define('MAGPIE_CACHE_ON', false);
		}
		
		foreach ($rss->items as $item) {
			if ($display == 0)
				break;
			
			$echo .= $start_item;
			// import title
			if ( isset($item['title']) )
				$title = wp_specialchars( $item['title'] );
			// import link
			if ( isset($item['link']) )
				$href  = wp_filter_kses( $item['link'] );
			// import date
			if ($date && isset($item['pubdate']) )
				$pubDate = date_i18n( get_option('date_format'), strtotime( $item['pubdate'] ) );
			// import creator
			if ($creator && isset($item['dc']['creator']) )
				$creator = wp_specialchars( $item['dc']['creator'] );
			elseif ($creator && isset($item['creator']) )
				$creator = wp_specialchars( $item['creator'] );
			// import desc
			if ( $displaydescriptions && $html && isset($item['content']['encoded']) && $item['content']['encoded'] != 'A' )
				$desc = $item['content']['encoded']; // For import with HTML
			elseif ( $displaydescriptions && $html && isset($item['content']['atom_content']) && $item['content']['atom_content'] != 'A' )
				$desc = $item['content']['atom_content']; // For import with HTML
			elseif ( $displaydescriptions && $html && isset($item['content']) && !is_array($item['content']) )
				$desc = $item['content'];
			elseif ( $displaydescriptions && $html && isset($item['description']) )
				$desc = $item['description'];
			elseif ( $displaydescriptions && !$html && isset($item['description']) )
				$desc = wp_specialchars($item['description']); // For import without HTML
			
			if ( isset($a) && eregi('ISO', $a) ) {
				if ($debug)
					$echo .= 'ISO Feed' . "\n";
				if ( isset($title) )
					isodec($title);
				if ( isset($creator) )
					isodec($creator);
				if ( isset($desc) )
					isodec($desc);
			} else {
				if ($debug)
					$echo .= 'NonISO Feed' . "\n";
				if ( isset($title) )
					utf8dec($title);
				if ( isset($creator) )
					utf8dec($creator);
				if ( isset($desc) )
					utf8dec($desc);
			}
			
			if ( isset($title) )
				all_convert($title);
			if ( isset($creator) )
				all_convert($creator);
			if ( isset($desc) )
				all_convert($desc);
			
			if ( isset($title) && $truncatetitlechar && (strlen($title) > $truncatetitlechar) ) {
				$title = substr($title, 0, $truncatetitlechar);
				$title = RSSImport_end_on_word($title) . $truncatetitlestring;
			}
			
			if ( isset($desc) && $truncatedescchar && (strlen($desc) > $truncatedescchar) ) {
				$desc = substr($desc, 0, $truncatedescchar);
				$desc = RSSImport_end_on_word($desc) . $truncatedescstring;
			}
			
			if ( isset($target) && $target != '' )
				$target = ' target="_' . $target . '"';
			
			$echo .= '<a' . $target . ' href="' . $href . '" title="'. ereg_replace("[^A-Za-z0-9 ]", "", $item['title']) . '">' . $title . '</a>';
			if ( isset($pubDate) && $date && $pubDate != '' )
				$echo .= $before_date . $pubDate . $after_date;
			if ( isset($creator) && $creator && $creator != '' )
				$echo .= $before_creator . $creator . $after_creator;
			if ( isset($desc) && $displaydescriptions && $desc != '' )
				$echo .= $before_desc . $desc . $after_desc;
			$echo .= $end_item;
			
			$display--;
		}
		
		$echo = wptexturize($start_items . $echo . $end_items);
		
	} else {
		$echo = '<p>' . __('Error: Feed has a error or is not valid', FB_RSSI_TEXTDOMAIN) . $rss->ERROR . '</p>';
	}
	
	if ($view)
		echo $echo;
	else
		return $echo;
}

function utf8dec($s_String) {
	$s_String = html_entity_decode(htmlentities($s_String." ", ENT_COMPAT, 'UTF-8'));
	return substr($s_String, 0, strlen($s_String)-1);
}

function isodec($s_String) {
	$s_String = html_entity_decode(htmlentities($s_String." ", ENT_COMPAT, 'ISO-8859-1'));
	return substr($s_String, 0, strlen($s_String)-1);
}

function all_convert($s_String) {

	// Array for entities
	$umlaute  = array('â€ž','â€œ','â€“',' \&#34;','&#8211;','&#8212;','&#8216;','&#8217;','&#8220;','&#8221;','&#8222;','&#8226;','&#8230;' ,'€'     ,'‚'      ,'ƒ'     ,'„'      ,'…'       ,'†'       ,'‡'       ,'ˆ'     ,'‰'       ,'Š'       ,'‹'       ,'Œ'      ,'Ž'       ,'‘'      ,'’'      ,'“'      ,'”'      ,'•'     ,'–'      ,'—'      ,'˜'      ,'™'      ,'š'       ,'›','œ','ž','Ÿ','¡','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾','¿','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','×','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','÷','ø','ù','ú','û','ü','ý','þ','ÿ',utf8_encode('€'),utf8_encode('‚'),utf8_encode('ƒ'),utf8_encode('„'),utf8_encode('…'),utf8_encode('†'),utf8_encode('‡'),utf8_encode('ˆ'),utf8_encode('‰'),utf8_encode('Š'),utf8_encode('‹'),utf8_encode('Œ'),utf8_encode('Ž'),utf8_encode('‘'),utf8_encode('’'),utf8_encode('“'),utf8_encode('”'),utf8_encode('•'),utf8_encode('–'),utf8_encode('—'),utf8_encode('˜'),utf8_encode('™'),utf8_encode('š'),utf8_encode('›'),utf8_encode('œ'),utf8_encode('ž'),utf8_encode('Ÿ'),utf8_encode('¡'),utf8_encode('¢'),utf8_encode('£'),utf8_encode('¤'),utf8_encode('¥'),utf8_encode('¦'),utf8_encode('§'),utf8_encode('¨'),utf8_encode('©'),utf8_encode('ª'),utf8_encode('«'),utf8_encode('¬'),utf8_encode('®'),utf8_encode('¯'),utf8_encode('°'),utf8_encode('±'),utf8_encode('²'),utf8_encode('³'),utf8_encode('´'),utf8_encode('µ'),utf8_encode('¶'),utf8_encode('·'),utf8_encode('¸'),utf8_encode('¹'),utf8_encode('º'),utf8_encode('»'),utf8_encode('¼'),utf8_encode('½'),utf8_encode('¾'),utf8_encode('¿'),utf8_encode('À'),utf8_encode('Á'),utf8_encode('Â'),utf8_encode('Ã'),utf8_encode('Ä'),utf8_encode('Å'),utf8_encode('Æ'),utf8_encode('Ç'),utf8_encode('È'),utf8_encode('É'),utf8_encode('Ê'),utf8_encode('Ë'),utf8_encode('Ì'),utf8_encode('Í'),utf8_encode('Î'),utf8_encode('Ï'),utf8_encode('Ð'),utf8_encode('Ñ'),utf8_encode('Ò'),utf8_encode('Ó'),utf8_encode('Ô'),utf8_encode('Õ'),utf8_encode('Ö'),utf8_encode('×'),utf8_encode('Ø'),utf8_encode('Ù'),utf8_encode('Ú'),utf8_encode('Û'),utf8_encode('Ü'),utf8_encode('Ý'),utf8_encode('Þ'),utf8_encode('ß'),utf8_encode('à'),utf8_encode('á'),utf8_encode('â'),utf8_encode('ã'),utf8_encode('ä'),utf8_encode('å'),utf8_encode('æ'),utf8_encode('ç'),utf8_encode('è'),utf8_encode('é'),utf8_encode('ê'),utf8_encode('ë'),utf8_encode('ì'),utf8_encode('í'),utf8_encode('î'),utf8_encode('ï'),utf8_encode('ð'),utf8_encode('ñ'),utf8_encode('ò'),utf8_encode('ó'),utf8_encode('ô'),utf8_encode('õ'),utf8_encode('ö'),utf8_encode('÷'),utf8_encode('ø'),utf8_encode('ù'),utf8_encode('ú'),utf8_encode('û'),utf8_encode('ü'),utf8_encode('ý'),utf8_encode('þ'),utf8_encode('ÿ'),chr(128),chr(129),chr(130),chr(131),chr(132),chr(133),chr(134),chr(135),chr(136),chr(137),chr(138),chr(139),chr(140),chr(141),chr(142),chr(143),chr(144),chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151),chr(152),chr(153),chr(154),chr(155),chr(156),chr(157),chr(158),chr(159),chr(160),chr(161),chr(162),chr(163),chr(164),chr(165),chr(166),chr(167),chr(168),chr(169),chr(170),chr(171),chr(172),chr(173),chr(174),chr(175),chr(176),chr(177),chr(178),chr(179),chr(180),chr(181),chr(182),chr(183),chr(184),chr(185),chr(186),chr(187),chr(188),chr(189),chr(190),chr(191),chr(192),chr(193),chr(194),chr(195),chr(196),chr(197),chr(198),chr(199),chr(200),chr(201),chr(202),chr(203),chr(204),chr(205),chr(206),chr(207),chr(208),chr(209),chr(210),chr(211),chr(212),chr(213),chr(214),chr(215),chr(216),chr(217),chr(218),chr(219),chr(220),chr(221),chr(222),chr(223),chr(224),chr(225),chr(226),chr(227),chr(228),chr(229),chr(230),chr(231),chr(232),chr(233),chr(234),chr(235),chr(236),chr(237),chr(238),chr(239),chr(240),chr(241),chr(242),chr(243),chr(244),chr(245),chr(246),chr(247),chr(248),chr(249),chr(250),chr(251),chr(252),chr(253),chr(254),chr(255),chr(256));
	$htmlcode = array('&bdquo;','&ldquo;','&ndash;',' &#34;','&ndash;','&mdash;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bdquo;','&bull;' ,'&hellip;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','','&#x017D;','','','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','','&#x017E;','&Yuml;','&nbsp;','&iexcl;','&iexcl;','&iexcl;','&iexcl;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','­&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
	//$s_String = str_replace($umlaute, $htmlcode, $s_String);
	$s_String = utf8_encode( html_entity_decode( str_replace($umlaute, $htmlcode, $s_String) ) );
				
	// &hellip; , &#8230;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xC2\xA6~', '&hellip;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\xA6~', '&hellip;', $s_String);
	$s_String = preg_replace('~\xD0\xB2\xD0\x82\xC2\xA6~', '&hellip;', $s_String);
	
	// &mdash; , &#8212;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D~', '&mdash;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC2\x9D~', '&mdash;', $s_String);
	$s_String = preg_replace('~\xD0\xB2\xD0\x82\xE2\x80\x9D~', '&mdash;', $s_String);
	
	// &ndash; , &#8211;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C~', '&ndash;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC5\x93~', '&ndash;', $s_String);
	$s_String = preg_replace('~\xD0\xB2\xD0\x82\xE2\x80\x9C~', '&ndash;', $s_String);
	
	// &rsquo; , &#8217;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2~', '&rsquo;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x80\x9E\xC2\xA2~', '&rsquo;', $s_String);
	$s_String = preg_replace('~\xD0\xB2\xD0\x82\xE2\x84\xA2~', '&rsquo;', $s_String);
	$s_String = preg_replace('~\xD0\xBF\xD1\x97\xD0\x85~', '&rsquo;', $s_String);
	
	// &lsquo; , &#8216;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xCB\x9C~', '&lsquo;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x8B\xC5\x93~', '&lsquo;', $s_String);
	
	// &rdquo; , &#8221;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xC2\x9D~', '&rdquo;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\x9D~', '&rdquo;', $s_String);
	$s_String = preg_replace('~\xD0\xB2\xD0\x82\xD1\x9C~', '&rdquo;', $s_String);
	
	// &ldquo; , &#8220;
	$s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xC5\x93~', '&ldquo;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x85\xE2\x80\x9C~', '&ldquo;', $s_String);
	$s_String = preg_replace('~\xD0\xB2\xD0\x82\xD1\x9A~', '&ldquo;', $s_String);
	
	// &trade; , &#8482;
	$s_String = preg_replace('~\xC3\xA2\xE2\x80\x9E\xC2\xA2~', '&trade;', $s_String);
	$s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x82\xAC\xC5\xBE\xC3\x82\xC2\xA2~', '&trade;', $s_String);
	
	// th
	$s_String = preg_replace('~t\xC3\x82\xC2\xADh~', 'th', $s_String);
	
	// .
	$s_String = preg_replace('~.\xD0\x92+~', '.', $s_String);
	$s_String = preg_replace('~.\xD0\x92~', '.', $s_String);
	
	// ,
	$s_String = preg_replace('~\x2C\xD0\x92~', ',', $s_String);

	return $s_String;
}

/**
 * Entfernt unvollstaendige Worte am Ende eines Strings.
 * @author Thomas Scholz <http://toscho.de>
 * @param $str Zeichenkette
 * @return string
 */
function RSSImport_end_on_word($str) {
	
	$arr = explode( ' ', trim($str) );
	array_pop($arr);
	
	return rtrim( implode(' ', $arr), ',;');
}

function RSSImport_Shortcode($atts) {
	extract( shortcode_atts( array(
																'display' => '5',
																'feedurl' => 'http://bueltge.de/feed/',
																'before_desc' => '<br />',
																'displaydescriptions' => false,
																'after_desc' => '',
																'html' => false,
																'truncatedescchar' => 200,
																'truncatedescstring' => ' ... ',
																'truncatetitlechar' => '',
																'truncatetitlestring' => ' ... ',
																'before_date' => ' <small>',
																'date' => false,
																'after_date' => '</small>',
																'before_creator' => ' <small>',
																'creator' => false,
																'after_creator' => '</small>',
																'start_items' => '<ul>',
																'end_items' => '</ul>',
																'start_item' => '<li>',
																'end_item' => '</li>',
																'target' => '',
																'charsetscan' => false,
																'debug' => false
																), $atts) );

	$return = RSSImport(
											$display, $feedurl,
											$before_desc, $displaydescriptions, $after_desc, $html, $truncatedescchar, $truncatedescstring,
											$truncatetitlechar, $truncatetitlestring,
											$before_date, $date, $after_date,
											$before_creator, $creator, $after_creator,
											$start_items, $end_items,
											$start_item, $end_item,
											$target,
											$charsetscan, $debug,
											$view = false
										 );

	return $return;
}

/**
 * add quicktag-button to editor
 */
function RSSImport_insert_button() {
	global $pagenow;
	
	$post_page_pages = array('post-new.php', 'post.php', 'page-new.php', 'page.php');
	if ( !in_array( $pagenow, $post_page_pages ) )
		return;
	
	echo '
	<script type="text/javascript">
		//<![CDATA[
		var length = edButtons.length;
		edButtons[length] = new edButton(\'RSSImport\', \'$context\', \'[RSSImport display="5" feedurl="http://feedurl.com/" before_desc="<br />" displaydescriptions="true" after_desc=" " html="false" truncatedescchar="200" truncatedescstring=" ... " truncatetitlechar=" " truncatetitlestring=" ... " before_date=" <small>" date="false" after_date=" </small>" before_creator=" <small>" creator="false" after_creator=" </small>" start_items=" <ul>" end_items=" </ul>" start_item=" <li>" end_item=" </li>" target="" charsetscan="false" debug="false"]\', \'\', \'\');
		function RSSImport_tag(id) {
			id = id.replace(/RSSImport_/, \'\');
			edInsertTag(edCanvas, id);
		}
		jQuery(document).ready(function() {
			content = \'<input id="RSSImport_\'+length+\'" class="ed_button" type="button" value="' . __( 'RSSImport', FB_RSSI_TEXTDOMAIN ) . '" title="' . __( 'Import a feed with RSSImport', FB_RSSI_TEXTDOMAIN ) . '" onclick="RSSImport_tag(this.id);" />\';
			jQuery("#ed_toolbar").append(content);
		});
		//]]>
	</script>';
}

function RSSImport_update_notice() {
	$plugin_data = get_plugin_data( __FILE__ );
	if ($plugin_data['Version'] > '4.2.4')
		return;
	else
		echo '<tr><td class="plugin-update" colspan="5">' . __('New version, however the plugin has been modified with many new parameters. Please see the new <a target="_blank" href="http://wordpress.org/extend/plugins/rss-import/">possibilities</a>.', FB_RSSI_TEXTDOMAIN) . '</td></tr>';
}

if ( function_exists('add_shortcode') )
	add_shortcode('RSSImport', 'RSSImport_Shortcode');

add_action( 'init', 'RSSImport_textdomain' );
if ( is_admin() ) {
	add_action( 'after_plugin_row_'.FB_RSSI_BASENAME, 'RSSImport_update_notice' );
	if (FB_RSSI_QUICKTAG)
		add_filter( 'admin_footer', 'RSSImport_insert_button' );
}
?>