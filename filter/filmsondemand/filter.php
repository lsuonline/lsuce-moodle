<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Films On Demand Video filter plugin.
 * @author    Pramod Ubbala (AGS -> Infobase)
 * @package    filter_filmsondemand
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2019 Infobase
 * @copyright based on work by Pramod Ubbala
 */

defined('MOODLE_INTERNAL') || die();

class filter_filmsondemand extends moodle_text_filter {
  
  //private $embedmarkers;

  public function filter($text, array $options = array()) {
    global $CFG, $PAGE;

	if (!is_string($text) or empty($text)) {
		// non string data can not be filtered anyway
		return $text;
	}

	if (stripos($text, '</a>') === false) {
		// Performance shortcut - if not </a> tag, nothing can match.
		return $text;
	}

	$dom = new DOMDocument();
	$dom->loadHTML($text);
	$el = $dom->getElementsByTagName('a');

	foreach($el as $node)
	{
		$a = $dom->saveHTML($node);

		preg_match("/<a\s+(?:[^>]*?\s+)?href=\"([^\"]*)\"/", $a, $b64text);

		try
		{
			$obj = json_encode(base64_decode($b64text[1]),true);
			 $results = print_r($obj, true);
			$temp=json_decode($results, true);

			$newtext = json_decode($temp,true);
		}
		catch(Exception $e)
		{
			continue;
		}

		
		//if our url doesn't need to be encoded, set it to the OG
		
		// ===================    UofL Start    ===================
		if (isset($newtext['url']) && $newtext['url'] == '') {
		// if($newtext['url'] == '')
		// ===================    UofL End      ===================
			$newtext['url'] = $b64text[1];
		}

		//only do this if it's our url
		if(stripos($newtext['url'], '.infobase.com') !== false && stripos($newtext['url'], '/OnDemandEmbed') !== false)
		{
			$text=str_replace($b64text[1],$newtext['url'],$text);

			$a = str_replace($b64text[1],$newtext['url'],$a);

			//get the substring of the url with moodle/ so we can decode it to match the actual text
			if(stripos($newtext['url'], 'moodle/') !== false)
			{
				$moodleText = substr($newtext['url'], stripos($newtext['url'], 'moodle/') + 7, strlen($newtext['url']) - stripos($newtext['url'], 'moodle/') + 7);

				$a = str_replace("moodle/". $moodleText, "moodle/". urldecode($moodleText), $a);
			}

			//$this->embedmarkers = 'lti\.films\.com|localhost|\.mp4';

			//$this->trusted = !empty($options['noclean']) or !empty($CFG->allowobjectembed);

			$width =  435;
			$height = 382;

			if(strpos($newtext['url'], '&w=') !== false)
			{
				$dims = substr($newtext['url'], strpos($newtext['url'], '&w='), strlen($newtext['url']) - strpos($newtext['url'], '&w='));

				$width = substr($dims, strpos($dims, '&w=') + 3, strpos($dims, '&h')-3) + 20;
				$height = substr($dims, strpos($dims, '&h=') + 3, strlen($dims) -strpos($dims, '&h=') + 3) + 50;
			}

			$iframeText = '<iframe src="' . $newtext['url'] . '" frameborder="0" style="width: ' . $width . 'px;height:' . $height . 'px;" allowfullscreen></iframe>';

			$text = str_replace($a, $iframeText, $text);		
		}
	}

    
	return $text;	
  }

}
