<?php

/*
Plugin Name: WP-RSSImport
Plugin URI: http://bueltge.de/wp-rss-import-plugin/55/
Description: List a RSS-Feed in your WP-Blog, only headlines or with description.
Author: Frank Bueltge
Version: 4.1
License: GPL
Author URI: http://bueltge.de
*/ 

/*
------------------------------------------------------
 ACKNOWLEDGEMENTS
------------------------------------------------------
Original and Idea: Dave Wolf, http://www.davewolf.net
Thx to Thomas Fischer, http://www.securityfocus.de and Gunnar Tillmann http://www.gunnart.de for a better code

USAGE: Use following code with a PHP-Plugin for WordPress:
Example: <?php RSSImport(10, "http://bueltge.de/feed/", true, false); ?>
------------------------------------------------------
*/

// For function fetch_rss
if(file_exists(ABSPATH . WPINC . '/rss-functions.php')) {
	@require_once (ABSPATH . WPINC . '/rss-functions.php');
	// It's Wordpress 1.5.2 or 2.x. since it has been loaded successfully
} elseif (file_exists(ABSPATH . WPINC . '/rss.php')) {
	@require_once (ABSPATH . WPINC . '/rss.php');
	// In Wordpress 2.1, a new file name is being used
} else {
	die (__('Error in file: ' . __FILE__ . ' on line: ' . __LINE__ . '.<br />The Wordpress file "rss-functions.php" or "rss.php" could not be included.'));
}

// cache and error report
//define('MAGPIE_CACHE_ON', false); // Cache off
define('MAGPIE_CACHE_AGE', '60*60'); // in sec, one hour
//error_reporting(E_ERROR);

function RSSImport($display=0, $feedurl, $displaydescriptions=false, $truncatetitle=true) {

	// read in file for search charset
	ini_set('default_socket_timeout', 120);
	$a = file_get_contents($feedurl);
	// for better performance, if the server accepts the method 
	//$a = file_get_contents($feedurl,FALSE,NULL,0,50);
	
	$rss = fetch_rss($feedurl);

	if ($rss && !$rss->ERROR) {
		// the follow print_r list all items in array
		// print_r($rss);
		echo wptexturize('<ul>');
		foreach ($rss->items as $item) {
			if ($display == 0) {
				break;
			}
			
			$title   = $item['title'];
			$href    = $item['link'];
			// view date
			$pubDate = $item['pubdate'];
			// cut date
			$pubDate = substr($pubDate, 0, 25);
			
			// Edit here:
			// For import with pure text
			$desc    = $item['description'];
			// For import with HTML
			//$desc    = $item['content']['encoded'];
			
			if (eregi('encoding="ISO-8859-', $a)) {
				isodec($title);
				isodec($desc);
			} else {
				utf8dec($title);
				utf8dec($desc);
			}
			$umlaute  = array('â€ž','â€œ','â€“',' \&#34;','&#8211;','&#8212;','&#8216;','&#8217;','&#8220;','&#8221;','&#8222;','&#8226;','&#8230;' ,'€'     ,'‚'      ,'ƒ'     ,'„'      ,'…'       ,'†'       ,'‡'       ,'ˆ'     ,'‰'       ,'Š'       ,'‹'       ,'Œ'      ,'Ž'       ,'‘'      ,'’'      ,'“'      ,'”'      ,'•'     ,'–'      ,'—'      ,'˜'      ,'™'      ,'š'       ,'›','œ','ž','Ÿ','¡','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾','¿','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','×','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','÷','ø','ù','ú','û','ü','ý','þ','ÿ',utf8_encode('€'),utf8_encode('‚'),utf8_encode('ƒ'),utf8_encode('„'),utf8_encode('…'),utf8_encode('†'),utf8_encode('‡'),utf8_encode('ˆ'),utf8_encode('‰'),utf8_encode('Š'),utf8_encode('‹'),utf8_encode('Œ'),utf8_encode('Ž'),utf8_encode('‘'),utf8_encode('’'),utf8_encode('“'),utf8_encode('”'),utf8_encode('•'),utf8_encode('–'),utf8_encode('—'),utf8_encode('˜'),utf8_encode('™'),utf8_encode('š'),utf8_encode('›'),utf8_encode('œ'),utf8_encode('ž'),utf8_encode('Ÿ'),utf8_encode('¡'),utf8_encode('¢'),utf8_encode('£'),utf8_encode('¤'),utf8_encode('¥'),utf8_encode('¦'),utf8_encode('§'),utf8_encode('¨'),utf8_encode('©'),utf8_encode('ª'),utf8_encode('«'),utf8_encode('¬'),utf8_encode('®'),utf8_encode('¯'),utf8_encode('°'),utf8_encode('±'),utf8_encode('²'),utf8_encode('³'),utf8_encode('´'),utf8_encode('µ'),utf8_encode('¶'),utf8_encode('·'),utf8_encode('¸'),utf8_encode('¹'),utf8_encode('º'),utf8_encode('»'),utf8_encode('¼'),utf8_encode('½'),utf8_encode('¾'),utf8_encode('¿'),utf8_encode('À'),utf8_encode('Á'),utf8_encode('Â'),utf8_encode('Ã'),utf8_encode('Ä'),utf8_encode('Å'),utf8_encode('Æ'),utf8_encode('Ç'),utf8_encode('È'),utf8_encode('É'),utf8_encode('Ê'),utf8_encode('Ë'),utf8_encode('Ì'),utf8_encode('Í'),utf8_encode('Î'),utf8_encode('Ï'),utf8_encode('Ð'),utf8_encode('Ñ'),utf8_encode('Ò'),utf8_encode('Ó'),utf8_encode('Ô'),utf8_encode('Õ'),utf8_encode('Ö'),utf8_encode('×'),utf8_encode('Ø'),utf8_encode('Ù'),utf8_encode('Ú'),utf8_encode('Û'),utf8_encode('Ü'),utf8_encode('Ý'),utf8_encode('Þ'),utf8_encode('ß'),utf8_encode('à'),utf8_encode('á'),utf8_encode('â'),utf8_encode('ã'),utf8_encode('ä'),utf8_encode('å'),utf8_encode('æ'),utf8_encode('ç'),utf8_encode('è'),utf8_encode('é'),utf8_encode('ê'),utf8_encode('ë'),utf8_encode('ì'),utf8_encode('í'),utf8_encode('î'),utf8_encode('ï'),utf8_encode('ð'),utf8_encode('ñ'),utf8_encode('ò'),utf8_encode('ó'),utf8_encode('ô'),utf8_encode('õ'),utf8_encode('ö'),utf8_encode('÷'),utf8_encode('ø'),utf8_encode('ù'),utf8_encode('ú'),utf8_encode('û'),utf8_encode('ü'),utf8_encode('ý'),utf8_encode('þ'),utf8_encode('ÿ'),chr(128),chr(129),chr(130),chr(131),chr(132),chr(133),chr(134),chr(135),chr(136),chr(137),chr(138),chr(139),chr(140),chr(141),chr(142),chr(143),chr(144),chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151),chr(152),chr(153),chr(154),chr(155),chr(156),chr(157),chr(158),chr(159),chr(160),chr(161),chr(162),chr(163),chr(164),chr(165),chr(166),chr(167),chr(168),chr(169),chr(170),chr(171),chr(172),chr(173),chr(174),chr(175),chr(176),chr(177),chr(178),chr(179),chr(180),chr(181),chr(182),chr(183),chr(184),chr(185),chr(186),chr(187),chr(188),chr(189),chr(190),chr(191),chr(192),chr(193),chr(194),chr(195),chr(196),chr(197),chr(198),chr(199),chr(200),chr(201),chr(202),chr(203),chr(204),chr(205),chr(206),chr(207),chr(208),chr(209),chr(210),chr(211),chr(212),chr(213),chr(214),chr(215),chr(216),chr(217),chr(218),chr(219),chr(220),chr(221),chr(222),chr(223),chr(224),chr(225),chr(226),chr(227),chr(228),chr(229),chr(230),chr(231),chr(232),chr(233),chr(234),chr(235),chr(236),chr(237),chr(238),chr(239),chr(240),chr(241),chr(242),chr(243),chr(244),chr(245),chr(246),chr(247),chr(248),chr(249),chr(250),chr(251),chr(252),chr(253),chr(254),chr(255),chr(256));
			$htmlcode = array('&bdquo;','&ldquo;','&ndash;',' &#34;','&ndash;','&mdash;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bdquo;','&bull;' ,'&hellip;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','','&#x017D;','','','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','','&#x017E;','&Yuml;','&nbsp;','&iexcl;','&iexcl;','&iexcl;','&iexcl;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','­&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
			$title = str_replace($umlaute, $htmlcode, $title);
			$desc  = str_replace($umlaute, $htmlcode, $desc);
			
			if ($truncatetitle && (strlen($title)>30)) {
					$title = substr($title, 0, 30) . " ... ";
			}
			echo wptexturize('<li>');
			echo wptexturize('<a href="' . $href . '" title="'. ereg_replace("[^A-Za-z0-9 ]", "", $item['title']) . '">' . $title . '</a> <small>' . $pubDate . '</small>');
			if ($displaydescriptions && $desc <> "") { 
				echo wptexturize('<br />' . "\n" . $desc . "\n");
			}
			$display--;
			echo wptexturize('</li>');
		}
		echo wptexturize('</ul>');
	} else {
		echo '<p>' . __('Error: Feed has a error or is not valid') . $rss->ERROR . '</p>';
	}
}

function utf8dec($s_String) {
	$s_String = html_entity_decode(htmlentities($s_String." ", ENT_COMPAT, 'UTF-8'));
	return substr($s_String, 0, strlen($s_String)-1);
}

function isodec($s_String) {
	$s_String = html_entity_decode(htmlentities($s_String." ", ENT_COMPAT, 'ISO-8859-1'));
	return substr($s_String, 0, strlen($s_String)-1);
}
?>