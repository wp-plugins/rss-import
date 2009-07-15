<?php
/**
 * @package WP-RSSImport
 * @author Frank B&uuml;ltge
 * @version 4.4
 */
 
/*
Plugin Name: WP-RSSImport
Plugin URI: http://bueltge.de/wp-rss-import-plugin/55/
Description: Import and display Feeds in your blog, use the function RSSImport() or Shortcode [RSSImport]. Please see the new <a href="http://wordpress.org/extend/plugins/rss-import/">possibilities</a>.
Author: Frank B&uuml;ltge
Version: 4.4
License: GPL
Author URI: http://bueltge.de/
Last change: 18.06.2009 11:23:25
*/ 

/*
------------------------------------------------------------
 ACKNOWLEDGEMENTS
------------------------------------------------------------
Original and Idea: Dave Wolf, http://www.davewolf.net
Thx to Thomas Fischer, http://www.securityfocus.de and
Gunnar Tillmann http://www.gunnart.de for a better code

Paging: Ilya Shindyapin, http://skookum.com

------------------------------------------------------------
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
										$view = true,
										$before_noitems = '<p>', $noitems = 'No items, feed is empty.', $after_noitems = '</p>',
										$before_error = '<p>', $error = 'Error: Feed has a error or is not valid', $after_error = '</p>',
										$paging = false, $prev_paging_link = '&laquo; Previous', $next_paging_link = 'Next &raquo;', $prev_paging_title = 'more items', $next_paging_title = 'more items'
									) {
	
	$display = intval($display);
	$page = ( ( !empty( $_GET['rsspage'] ) && intval($_GET['rsspage']) > 0 ) ? intval($_GET['rsspage']) : 1 );
	$truncatedescchar = intval($truncatedescchar);
	$truncatetitlechar = intval($truncatetitlechar);
	$echo = '';
	
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
		
		if ( isset($target) && $target != '' )
			$target = ' target="_' . $target . '"';
		
		$displaylimit = ($page * $display);
		$display = (($page-1) * $display);
		$nextitems = TRUE;
		$previousitems = FALSE;
		if ( $page > 1 )
			$previousitems = TRUE;
		
		while($display < $displaylimit) {
		
			if ( array_key_exists( $display, $rss->items ) ) {
			
				$item = $rss->items[$display];
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
					$desc = wp_specialchars(strip_tags($item['description'])); // For import without HTML
			
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
					//$title = substr($title, 0, $truncatetitlechar);
					//$title = RSSImport_end_on_word($title) . $truncatetitlestring;
					$title = wp_html_excerpt($title, $truncatetitlechar) . $truncatetitlestring;
				}
			
				if ( isset($desc) && $truncatedescchar && (strlen($desc) > $truncatedescchar) ) {
					//$desc = substr($desc, 0, $truncatedescchar);
					//$desc = RSSImport_end_on_word($desc) . $truncatedescstring;
					$desc = wp_html_excerpt($desc, $truncatedescchar) . $truncatedescstring;
				}
							
				// Moved the target outside the loop
			
				$echo .= '<a' . $target . ' href="' . $href . '" title="'. ereg_replace("[^A-Za-z0-9 ]", "", $item['title']) . '">' . $title . '</a>';
				if ( isset($pubDate) && $date && $pubDate != '' )
					$echo .= $before_date . $pubDate . $after_date;
				if ( isset($creator) && $creator && $creator != '' )
					$echo .= $before_creator . $creator . $after_creator;
				if ( isset($desc) && $displaydescriptions && $desc != '' )
					$echo .= $before_desc . $desc . $after_desc;
				$echo .= $end_item;		
			} else {
				$nextitems = FALSE;
			}
			
			$display++;
		}
		
		if ($echo)
			$echo = wptexturize($start_items . $echo . $end_items);
		else
			$echo = wptexturize($before_noitems . $noitems . $after_noitems);
		
	} else {
		$echo = wptexturize($before_error . $error . $rss->ERROR . $after_error);
	}
	
	if ($paging) {
		$echo .= '<div class="rsspaging">';
		if ($previousitems)
			$echo .= '<a href="' . add_query_arg( 'rsspage', ($page-1) ) . '" class="rsspaging_prev" title="' . $prev_paging_title . '">' . $prev_paging_link . '</a>';
		if ($nextitems)
			$echo .= '<a href="' . add_query_arg( 'rsspage', ($page+1) ) . '" class="rsspaging_next" title="' . $next_paging_title . '">' . $next_paging_link .'</a>';
		$echo .= '<br style="clear: both" />';
		$echo .= '</div>';
	}
	
	if ($view)
		echo $echo;
	else
		return $echo;
}

function utf8dec($s_String) {
	if ( version_compare(phpversion(), '5.0.0', '>=') )
		$s_String = html_entity_decode(htmlentities( $s_String." ", ENT_COMPAT, 'UTF-8') );
	else
		$s_String = RSSImport_html_entity_decode_php4( htmlentities($s_String." ") );
	return substr($s_String, 0, strlen($s_String)-1);
}

function isodec($s_String) {
	if ( version_compare(phpversion(), '5.0.0', '>=') )
		$s_String = html_entity_decode(htmlentities($s_String." ", ENT_COMPAT, 'ISO-8859-1'));
	else
		$s_String = RSSImport_html_entity_decode_php4( htmlentities($s_String." ") );
	return substr($s_String, 0, strlen($s_String)-1);
}

function all_convert($s_String) {

	// Array for entities
	$umlaute  = array('â€ž','â€œ','â€“',' \&#34;','&#8211;','&#8212;','&#8216;','&#8217;','&#8220;','&#8221;','&#8222;','&#8226;','&#8230;' ,'€'     ,'‚'      ,'ƒ'     ,'„'      ,'…'       ,'†'       ,'‡'       ,'ˆ'     ,'‰'       ,'Š'       ,'‹'       ,'Œ'      ,'Ž'       ,'‘'      ,'’'      ,'“'      ,'”'      ,'•'     ,'–'      ,'—'      ,'˜'      ,'™'      ,'š'       ,'›','œ','ž','Ÿ','¡','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾','¿','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','×','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','÷','ø','ù','ú','û','ü','ý','þ','ÿ',utf8_encode('€'),utf8_encode('‚'),utf8_encode('ƒ'),utf8_encode('„'),utf8_encode('…'),utf8_encode('†'),utf8_encode('‡'),utf8_encode('ˆ'),utf8_encode('‰'),utf8_encode('Š'),utf8_encode('‹'),utf8_encode('Œ'),utf8_encode('Ž'),utf8_encode('‘'),utf8_encode('’'),utf8_encode('“'),utf8_encode('”'),utf8_encode('•'),utf8_encode('–'),utf8_encode('—'),utf8_encode('˜'),utf8_encode('™'),utf8_encode('š'),utf8_encode('›'),utf8_encode('œ'),utf8_encode('ž'),utf8_encode('Ÿ'),utf8_encode('¡'),utf8_encode('¢'),utf8_encode('£'),utf8_encode('¤'),utf8_encode('¥'),utf8_encode('¦'),utf8_encode('§'),utf8_encode('¨'),utf8_encode('©'),utf8_encode('ª'),utf8_encode('«'),utf8_encode('¬'),utf8_encode('®'),utf8_encode('¯'),utf8_encode('°'),utf8_encode('±'),utf8_encode('²'),utf8_encode('³'),utf8_encode('´'),utf8_encode('µ'),utf8_encode('¶'),utf8_encode('·'),utf8_encode('¸'),utf8_encode('¹'),utf8_encode('º'),utf8_encode('»'),utf8_encode('¼'),utf8_encode('½'),utf8_encode('¾'),utf8_encode('¿'),utf8_encode('À'),utf8_encode('Á'),utf8_encode('Â'),utf8_encode('Ã'),utf8_encode('Ä'),utf8_encode('Å'),utf8_encode('Æ'),utf8_encode('Ç'),utf8_encode('È'),utf8_encode('É'),utf8_encode('Ê'),utf8_encode('Ë'),utf8_encode('Ì'),utf8_encode('Í'),utf8_encode('Î'),utf8_encode('Ï'),utf8_encode('Ð'),utf8_encode('Ñ'),utf8_encode('Ò'),utf8_encode('Ó'),utf8_encode('Ô'),utf8_encode('Õ'),utf8_encode('Ö'),utf8_encode('×'),utf8_encode('Ø'),utf8_encode('Ù'),utf8_encode('Ú'),utf8_encode('Û'),utf8_encode('Ü'),utf8_encode('Ý'),utf8_encode('Þ'),utf8_encode('ß'),utf8_encode('à'),utf8_encode('á'),utf8_encode('â'),utf8_encode('ã'),utf8_encode('ä'),utf8_encode('å'),utf8_encode('æ'),utf8_encode('ç'),utf8_encode('è'),utf8_encode('é'),utf8_encode('ê'),utf8_encode('ë'),utf8_encode('ì'),utf8_encode('í'),utf8_encode('î'),utf8_encode('ï'),utf8_encode('ð'),utf8_encode('ñ'),utf8_encode('ò'),utf8_encode('ó'),utf8_encode('ô'),utf8_encode('õ'),utf8_encode('ö'),utf8_encode('÷'),utf8_encode('ø'),utf8_encode('ù'),utf8_encode('ú'),utf8_encode('û'),utf8_encode('ü'),utf8_encode('ý'),utf8_encode('þ'),utf8_encode('ÿ'),chr(128),chr(129),chr(130),chr(131),chr(132),chr(133),chr(134),chr(135),chr(136),chr(137),chr(138),chr(139),chr(140),chr(141),chr(142),chr(143),chr(144),chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151),chr(152),chr(153),chr(154),chr(155),chr(156),chr(157),chr(158),chr(159),chr(160),chr(161),chr(162),chr(163),chr(164),chr(165),chr(166),chr(167),chr(168),chr(169),chr(170),chr(171),chr(172),chr(173),chr(174),chr(175),chr(176),chr(177),chr(178),chr(179),chr(180),chr(181),chr(182),chr(183),chr(184),chr(185),chr(186),chr(187),chr(188),chr(189),chr(190),chr(191),chr(192),chr(193),chr(194),chr(195),chr(196),chr(197),chr(198),chr(199),chr(200),chr(201),chr(202),chr(203),chr(204),chr(205),chr(206),chr(207),chr(208),chr(209),chr(210),chr(211),chr(212),chr(213),chr(214),chr(215),chr(216),chr(217),chr(218),chr(219),chr(220),chr(221),chr(222),chr(223),chr(224),chr(225),chr(226),chr(227),chr(228),chr(229),chr(230),chr(231),chr(232),chr(233),chr(234),chr(235),chr(236),chr(237),chr(238),chr(239),chr(240),chr(241),chr(242),chr(243),chr(244),chr(245),chr(246),chr(247),chr(248),chr(249),chr(250),chr(251),chr(252),chr(253),chr(254),chr(255),chr(256));
	$htmlcode = array('&bdquo;','&ldquo;','&ndash;',' &#34;','&ndash;','&mdash;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bdquo;','&bull;' ,'&hellip;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','','&#x017D;','','','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','','&#x017E;','&Yuml;','&nbsp;','&iexcl;','&iexcl;','&iexcl;','&iexcl;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','­&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
	//$s_String = str_replace($umlaute, $htmlcode, $s_String);
	if ( version_compare(phpversion(), '5.0.0', '>=') )
		$s_String = utf8_encode( html_entity_decode( str_replace($umlaute, $htmlcode, $s_String) ) );
	else
		$s_String = utf8_encode( RSSImport_html_entity_decode_php4( str_replace($umlaute, $htmlcode, $s_String) ) );
	
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
																'debug' => false,
																'before_noitems' => '<p>',
																'noitems' => __('No items, feed is empty.', FB_RSSI_TEXTDOMAIN ),
																'after_noitems' => '</p>',
																'before_error' => '<p>',
																'error' => __('Error: Feed has a error or is not valid', FB_RSSI_TEXTDOMAIN ),
																'after_error' => '</p>',
																'paging' => false,
																'prev_paging_link' => __( '&laquo; Previous', FB_RSSI_TEXTDOMAIN ),
																'next_paging_link' => __( 'Next &raquo;', FB_RSSI_TEXTDOMAIN ),
																'prev_paging_title' => __( 'more items', FB_RSSI_TEXTDOMAIN ),
																'next_paging_title'=> __( 'more items', FB_RSSI_TEXTDOMAIN )
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
											$view = false,
											$before_noitems, $noitems, $after_noitems,
											$before_error, $error, $after_error,
											$paging, $prev_paging_link, $next_paging_link, $prev_paging_title, $next_paging_title
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
		edButtons[length] = new edButton(\'RSSImport\', \'$context\', \'[RSSImport display="5" feedurl="http://feedurl.com/" before_desc="<br />" displaydescriptions="true" after_desc=" " html="false" truncatedescchar="200" truncatedescstring=" ... " truncatetitlechar=" " truncatetitlestring=" ... " before_date=" <small>" date="false" after_date=" </small>" before_creator=" <small>" creator="false" after_creator=" </small>" start_items=" <ul>" end_items=" </ul>" start_item=" <li>" end_item=" </li>" target="" charsetscan="false" debug="false" before_noitems="<p>" noitems="No items, feed is empty." after_noitems="</p>" before_error="<p>" error="Error: Feed has a error or is not valid" after_error="</p>" paging="false"]\', \'\', \'\');
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

/**
 * code to utf-8 in PHP 4
 *
 * @package WP-RSSImport
 */
function RSSImport_code_to_utf8($num) {
	
	if ($num <= 0x7F) {
		return chr($num);
	} elseif ($num <= 0x7FF) {
		return chr(($num >> 0x06) + 0xC0) . chr(($num & 0x3F) + 128);
	} elseif ($num <= 0xFFFF) {
		return chr(($num >> 0x0C) + 0xE0) . chr((($num >> 0x06) & 0x3F) + 0x80) . chr(($num & 0x3F) + 0x80);
	} elseif ($num <= 0x1FFFFF) {
		return chr(($num >> 0x12) + 0xF0) . chr((($num >> 0x0C) & 0x3F) + 0x80) . chr((($num >> 0x06) & 0x3F) + 0x80) . chr(($num & 0x3F) + 0x80);
	}

	return '';
}


/**
 * html_entity_decode for PHP 4
 *
 * @package WP-RSSImport
 */
function RSSImport_html_entity_decode_php4($str) {
	$htmlentities = array (
		"&Aacute;" => chr(195).chr(129),
		"&aacute;" => chr(195).chr(161),
		"&Acirc;" => chr(195).chr(130),
		"&acirc;" => chr(195).chr(162),
		"&acute;" => chr(194).chr(180),
		"&AElig;" => chr(195).chr(134),
		"&aelig;" => chr(195).chr(166),
		"&Agrave;" => chr(195).chr(128),
		"&agrave;" => chr(195).chr(160),
		"&alefsym;" => chr(226).chr(132).chr(181),
		"&Alpha;" => chr(206).chr(145),
		"&alpha;" => chr(206).chr(177),
		"&amp;" => chr(38),
		"&and;" => chr(226).chr(136).chr(167),
		"&ang;" => chr(226).chr(136).chr(160),
		"&Aring;" => chr(195).chr(133),
		"&aring;" => chr(195).chr(165),
		"&asymp;" => chr(226).chr(137).chr(136),
		"&Atilde;" => chr(195).chr(131),
		"&atilde;" => chr(195).chr(163),
		"&Auml;" => chr(195).chr(132),
		"&auml;" => chr(195).chr(164),
		"&bdquo;" => chr(226).chr(128).chr(158),
		"&Beta;" => chr(206).chr(146),
		"&beta;" => chr(206).chr(178),
		"&brvbar;" => chr(194).chr(166),
		"&bull;" => chr(226).chr(128).chr(162),
		"&cap;" => chr(226).chr(136).chr(169),
		"&Ccedil;" => chr(195).chr(135),
		"&ccedil;" => chr(195).chr(167),
		"&cedil;" => chr(194).chr(184),
		"&cent;" => chr(194).chr(162),
		"&Chi;" => chr(206).chr(167),
		"&chi;" => chr(207).chr(135),
		"&circ;" => chr(203).chr(134),
		"&clubs;" => chr(226).chr(153).chr(163),
		"&cong;" => chr(226).chr(137).chr(133),
		"&copy;" => chr(194).chr(169),
		"&crarr;" => chr(226).chr(134).chr(181),
		"&cup;" => chr(226).chr(136).chr(170),
		"&curren;" => chr(194).chr(164),
		"&dagger;" => chr(226).chr(128).chr(160),
		"&Dagger;" => chr(226).chr(128).chr(161),
		"&darr;" => chr(226).chr(134).chr(147),
		"&dArr;" => chr(226).chr(135).chr(147),
		"&deg;" => chr(194).chr(176),
		"&Delta;" => chr(206).chr(148),
		"&delta;" => chr(206).chr(180),
		"&diams;" => chr(226).chr(153).chr(166),
		"&divide;" => chr(195).chr(183),
		"&Eacute;" => chr(195).chr(137),
		"&eacute;" => chr(195).chr(169),
		"&Ecirc;" => chr(195).chr(138),
		"&ecirc;" => chr(195).chr(170),
		"&Egrave;" => chr(195).chr(136),
		"&egrave;" => chr(195).chr(168),
		"&empty;" => chr(226).chr(136).chr(133),
		"&emsp;" => chr(226).chr(128).chr(131),
		"&ensp;" => chr(226).chr(128).chr(130),
		"&Epsilon;" => chr(206).chr(149),
		"&epsilon;" => chr(206).chr(181),
		"&equiv;" => chr(226).chr(137).chr(161),
		"&Eta;" => chr(206).chr(151),
		"&eta;" => chr(206).chr(183),
		"&ETH;" => chr(195).chr(144),
		"&eth;" => chr(195).chr(176),
		"&Euml;" => chr(195).chr(139),
		"&euml;" => chr(195).chr(171),
		"&euro;" => chr(226).chr(130).chr(172),
		"&exist;" => chr(226).chr(136).chr(131),
		"&fnof;" => chr(198).chr(146),
		"&forall;" => chr(226).chr(136).chr(128),
		"&frac12;" => chr(194).chr(189),
		"&frac14;" => chr(194).chr(188),
		"&frac34;" => chr(194).chr(190),
		"&frasl;" => chr(226).chr(129).chr(132),
		"&Gamma;" => chr(206).chr(147),
		"&gamma;" => chr(206).chr(179),
		"&ge;" => chr(226).chr(137).chr(165),
		"&harr;" => chr(226).chr(134).chr(148),
		"&hArr;" => chr(226).chr(135).chr(148),
		"&hearts;" => chr(226).chr(153).chr(165),
		"&hellip;" => chr(226).chr(128).chr(166),
		"&Iacute;" => chr(195).chr(141),
		"&iacute;" => chr(195).chr(173),
		"&Icirc;" => chr(195).chr(142),
		"&icirc;" => chr(195).chr(174),
		"&iexcl;" => chr(194).chr(161),
		"&Igrave;" => chr(195).chr(140),
		"&igrave;" => chr(195).chr(172),
		"&image;" => chr(226).chr(132).chr(145),
		"&infin;" => chr(226).chr(136).chr(158),
		"&int;" => chr(226).chr(136).chr(171),
		"&Iota;" => chr(206).chr(153),
		"&iota;" => chr(206).chr(185),
		"&iquest;" => chr(194).chr(191),
		"&isin;" => chr(226).chr(136).chr(136),
		"&Iuml;" => chr(195).chr(143),
		"&iuml;" => chr(195).chr(175),
		"&Kappa;" => chr(206).chr(154),
		"&kappa;" => chr(206).chr(186),
		"&Lambda;" => chr(206).chr(155),
		"&lambda;" => chr(206).chr(187),
		"&lang;" => chr(226).chr(140).chr(169),
		"&laquo;" => chr(194).chr(171),
		"&larr;" => chr(226).chr(134).chr(144),
		"&lArr;" => chr(226).chr(135).chr(144),
		"&lceil;" => chr(226).chr(140).chr(136),
		"&ldquo;" => chr(226).chr(128).chr(156),
		"&le;" => chr(226).chr(137).chr(164),
		"&lfloor;" => chr(226).chr(140).chr(138),
		"&lowast;" => chr(226).chr(136).chr(151),
		"&loz;" => chr(226).chr(151).chr(138),
		"&lrm;" => chr(226).chr(128).chr(142),
		"&lsaquo;" => chr(226).chr(128).chr(185),
		"&lsquo;" => chr(226).chr(128).chr(152),
		"&macr;" => chr(194).chr(175),
		"&mdash;" => chr(226).chr(128).chr(148),
		"&micro;" => chr(194).chr(181),
		"&middot;" => chr(194).chr(183),
		"&minus;" => chr(226).chr(136).chr(146),
		"&Mu;" => chr(206).chr(156),
		"&mu;" => chr(206).chr(188),
		"&nabla;" => chr(226).chr(136).chr(135),
		"&nbsp;" => chr(194).chr(160),
		"&ndash;" => chr(226).chr(128).chr(147),
		"&ne;" => chr(226).chr(137).chr(160),
		"&ni;" => chr(226).chr(136).chr(139),
		"&not;" => chr(194).chr(172),
		"&notin;" => chr(226).chr(136).chr(137),
		"&nsub;" => chr(226).chr(138).chr(132),
		"&Ntilde;" => chr(195).chr(145),
		"&ntilde;" => chr(195).chr(177),
		"&Nu;" => chr(206).chr(157),
		"&nu;" => chr(206).chr(189),
		"&Oacute;" => chr(195).chr(147),
		"&oacute;" => chr(195).chr(179),
		"&Ocirc;" => chr(195).chr(148),
		"&ocirc;" => chr(195).chr(180),
		"&OElig;" => chr(197).chr(146),
		"&oelig;" => chr(197).chr(147),
		"&Ograve;" => chr(195).chr(146),
		"&ograve;" => chr(195).chr(178),
		"&oline;" => chr(226).chr(128).chr(190),
		"&Omega;" => chr(206).chr(169),
		"&omega;" => chr(207).chr(137),
		"&Omicron;" => chr(206).chr(159),
		"&omicron;" => chr(206).chr(191),
		"&oplus;" => chr(226).chr(138).chr(149),
		"&or;" => chr(226).chr(136).chr(168),
		"&ordf;" => chr(194).chr(170),
		"&ordm;" => chr(194).chr(186),
		"&Oslash;" => chr(195).chr(152),
		"&oslash;" => chr(195).chr(184),
		"&Otilde;" => chr(195).chr(149),
		"&otilde;" => chr(195).chr(181),
		"&otimes;" => chr(226).chr(138).chr(151),
		"&Ouml;" => chr(195).chr(150),
		"&ouml;" => chr(195).chr(182),
		"&para;" => chr(194).chr(182),
		"&part;" => chr(226).chr(136).chr(130),
		"&permil;" => chr(226).chr(128).chr(176),
		"&perp;" => chr(226).chr(138).chr(165),
		"&Phi;" => chr(206).chr(166),
		"&phi;" => chr(207).chr(134),
		"&Pi;" => chr(206).chr(160),
		"&pi;" => chr(207).chr(128),
		"&piv;" => chr(207).chr(150),
		"&plusmn;" => chr(194).chr(177),
		"&pound;" => chr(194).chr(163),
		"&prime;" => chr(226).chr(128).chr(178),
		"&Prime;" => chr(226).chr(128).chr(179),
		"&prod;" => chr(226).chr(136).chr(143),
		"&prop;" => chr(226).chr(136).chr(157),
		"&Psi;" => chr(206).chr(168),
		"&psi;" => chr(207).chr(136),
		"&radic;" => chr(226).chr(136).chr(154),
		"&rang;" => chr(226).chr(140).chr(170),
		"&raquo;" => chr(194).chr(187),
		"&rarr;" => chr(226).chr(134).chr(146),
		"&rArr;" => chr(226).chr(135).chr(146),
		"&rceil;" => chr(226).chr(140).chr(137),
		"&rdquo;" => chr(226).chr(128).chr(157),
		"&real;" => chr(226).chr(132).chr(156),
		"&reg;" => chr(194).chr(174),
		"&rfloor;" => chr(226).chr(140).chr(139),
		"&Rho;" => chr(206).chr(161),
		"&rho;" => chr(207).chr(129),
		"&rlm;" => chr(226).chr(128).chr(143),
		"&rsaquo;" => chr(226).chr(128).chr(186),
		"&rsquo;" => chr(226).chr(128).chr(153),
		"&sbquo;" => chr(226).chr(128).chr(154),
		"&Scaron;" => chr(197).chr(160),
		"&scaron;" => chr(197).chr(161),
		"&sdot;" => chr(226).chr(139).chr(133),
		"&sect;" => chr(194).chr(167),
		"&shy;" => chr(194).chr(173),
		"&Sigma;" => chr(206).chr(163),
		"&sigma;" => chr(207).chr(131),
		"&sigmaf;" => chr(207).chr(130),
		"&sim;" => chr(226).chr(136).chr(188),
		"&spades;" => chr(226).chr(153).chr(160),
		"&sub;" => chr(226).chr(138).chr(130),
		"&sube;" => chr(226).chr(138).chr(134),
		"&sum;" => chr(226).chr(136).chr(145),
		"&sup1;" => chr(194).chr(185),
		"&sup2;" => chr(194).chr(178),
		"&sup3;" => chr(194).chr(179),
		"&sup;" => chr(226).chr(138).chr(131),
		"&supe;" => chr(226).chr(138).chr(135),
		"&szlig;" => chr(195).chr(159),
		"&Tau;" => chr(206).chr(164),
		"&tau;" => chr(207).chr(132),
		"&there4;" => chr(226).chr(136).chr(180),
		"&Theta;" => chr(206).chr(152),
		"&theta;" => chr(206).chr(184),
		"&thetasym;" => chr(207).chr(145),
		"&thinsp;" => chr(226).chr(128).chr(137),
		"&THORN;" => chr(195).chr(158),
		"&thorn;" => chr(195).chr(190),
		"&tilde;" => chr(203).chr(156),
		"&times;" => chr(195).chr(151),
		"&trade;" => chr(226).chr(132).chr(162),
		"&Uacute;" => chr(195).chr(154),
		"&uacute;" => chr(195).chr(186),
		"&uarr;" => chr(226).chr(134).chr(145),
		"&uArr;" => chr(226).chr(135).chr(145),
		"&Ucirc;" => chr(195).chr(155),
		"&ucirc;" => chr(195).chr(187),
		"&Ugrave;" => chr(195).chr(153),
		"&ugrave;" => chr(195).chr(185),
		"&uml;" => chr(194).chr(168),
		"&upsih;" => chr(207).chr(146),
		"&Upsilon;" => chr(206).chr(165),
		"&upsilon;" => chr(207).chr(133),
		"&Uuml;" => chr(195).chr(156),
		"&uuml;" => chr(195).chr(188),
		"&weierp;" => chr(226).chr(132).chr(152),
		"&Xi;" => chr(206).chr(158),
		"&xi;" => chr(206).chr(190),
		"&Yacute;" => chr(195).chr(157),
		"&yacute;" => chr(195).chr(189),
		"&yen;" => chr(194).chr(165),
		"&yuml;" => chr(195).chr(191),
		"&Yuml;" => chr(197).chr(184),
		"&Zeta;" => chr(206).chr(150),
		"&zeta;" => chr(206).chr(182),
		"&zwj;" => chr(226).chr(128).chr(141),
		"&zwnj;" => chr(226).chr(128).chr(140),
		"&gt;" => ">",
		"&lt;" => "<"
	);

	$return = strtr($str, $htmlentities);
	$return = preg_replace('~&#x([0-9a-f]+);~ei', 'RSSImport_code_to_utf8(hexdec("\\1"))', $return);
	$return = preg_replace('~&#([0-9]+);~e', 'RSSImport_code_to_utf8(\\1)', $return);

	return $return;
}
?>