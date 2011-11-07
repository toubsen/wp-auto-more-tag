<?php
/*
Plugin Name: Auto More Tag
Plugin URI: http://travisweston.com/auto-tag-wordpress-plugin
Description: Automatically add a More tag to your posts upon publication. No longer are you required to spend your time figuring out the perfect position for the more tag, you can now set a rule and this plugin will--to the best of it's abilities--add a proper more tag at or at the nearest non-destructive location.
Author: Travis Weston
Author URI: http://travisweston.com/
Version: 1.1
*/
file_put_contents('debug.log', 'test', FILE_APPEND);
if(!defined('TW_AUTO_MORE_TAG')){
	
	class tw_auto_more_tag {
		
		private static $_instance;

		public $length;
		public $options;
		public $data;

		public function __construct() {
			self::$_instance = $this;
		}

		public static function doFooter() {

			$options = get_option('tw_auto_more_tag');

			if(isset($options['credit_me']) && ((bool)$options['credit_me'] == true)){

				echo TW_AUTO_MORE_TAG;
	
			}

		}

		private static function doLog($message){
			file_put_contents('./debug.log', $message, FILE_APPEND);
		}

		public static function addTag($data, $arr = array()){
			self::doLog(__LINE__);
#			if($data['post_status'] != 'publish')
#				return $data;

			self::doLog(__LINE__);
			$options = get_option('tw_auto_more_tag');
			self::doLog(__LINE__);
					
			$length = $options['quantity'];
			self::doLog(__LINE__);
			
			$breakOn = $options['break'];
			self::doLog(__LINE__);

			self::doLog(__LINE__);
			
			switch($options['units']){
				case 1:
					self::doLog(__LINE__);
					return self::$_instance->byCharacter($data, $length, $breakOn);
					break;
				case 2:
				default:

					self::doLog(__LINE__);
					return self::$_instance->byWord($data, $length, $breakOn);
					break;
			}

		}

		public function byWord($data, $length, $breakOn) {
			// UNUSED IN CURRENT VERSION
			return $data;
		}

		public function byCharacter($data, $length, $breakOn) {

			if(strlen($data) > $length){

				//Remove any old more tags.

				$data = str_replace('<!--more-->', '', $data);

				$break = ($breakOn === 2) ? PHP_EOL : ' ';

				$pos = strpos($data, $break, $length);

				if($pos === false) {

					$pos = strpos($data, '>', $length);

					if($pos === false){

						$pos = $length;

					}

				}

				$temp = substr($data, 0, $pos);
				$temp_end = substr($data, $pos);
				$data = $temp.'<!--more-->'.$temp_end;

			}
		
			return $data;

		}

		public function initOptionsPage() {
	
			register_setting( 'tw_auto_more_tag', 'tw_auto_more_tag', array($this, 'validateOptions') );

		}

		public function validateOptions($input){

			$start = $input;
			
			$input['messages'] = array(
					'errors' => array(),
					'notices' => array(),
					'warnings' => array()
				);
			$input['quantity'] = (isset($input['quantity']) && (int)$input['quantity'] > 0) ? ((int)$input['quantity']) : 0;

			if($input['quantity'] != $start['quantity']){
				$input['messages']['notices'][] = 'Quantity cannot be less than 0, and has been set to 0.';
			}

			if($input['units'] != 1){
				$input['messages']['errors'][] = 'This version does not include capabilities for Word seperation. Units has been defaulted to Characters.';
			}

			$input['credit_me'] = (isset($input['credit_me']) && ((bool)$input['credit_me'] == true)) ? true : false;

			if($input['credit_me'] === false){
				$input['messages']['notices'][] = 'Hard work and determination was put into this plugin. Please be kind, and credit me!';
			}

			$input['units'] = ((int)$input['units'] == 1) ? 1 : 2;
			/*********************************
			* THIS IS TEMPORARY
			* ONLY HERE UNTIL WORD COUNT IS IMPLEMENTED
			**********************************/			
			$input['units'] = 1;

			if(false && $input['units'] == 1){
				$input['messages']['warnings'][] = 'Using characters is not suggested. The more tag is added to the unfiltered HTML of the post, which means that this tag could cause your HTML to unvalidate.';
			}			
			
			$input['break'] = (isset($input['break']) && (int)$input['break'] == 2) ? 2 : 1;

			$input['credit'] = true;

			return $input;

		}

		public function buildOptionsPage() {
			require_once('auto-more-options-page.php');
		}

		public function addPage() {

			$this->option_page = add_options_page('Auto More Tag', 'Auto More Tag', 'manage_options', 'tw_auto_more_tag', array($this, 'buildOptionsPage'));

		}


	}
	$tw_auto_more_tag = new tw_auto_more_tag();

	add_action('admin_init', array($tw_auto_more_tag, 'initOptionsPage'));
	add_action('admin_menu', array($tw_auto_more_tag, 'addPage'));
	add_action('wp_footer', 'tw_auto_more_tag::doFooter');
	add_filter('content_save_pre', 'tw_auto_more_tag::addTag', '1', 2);
	define('TW_AUTO_MORE_TAG', '<div style="text-align: center;"><a href="http://travisweston.com" target="_blank" style="font-size: 8pt;">Auto More Tag powered by TravisWeston.com</a></div>');
}
