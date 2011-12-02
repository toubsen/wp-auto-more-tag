<?php
/*
Plugin Name: Auto More Tag
Plugin URI: http://travisweston.com/portfolio/wordpress-plugins/auto-tag-wordpress-plugin/
Description: Automatically add a More tag to your posts upon publication. No longer are you required to spend your time figuring out the perfect position for the more tag, you can now set a rule and this plugin will--to the best of it's abilities--add a proper more tag at or at the nearest non-destructive location.
Author: Travis Weston
Author URI: http://travisweston.com/
Version: 2.1.1
*/

if(!defined('TW_AUTO_MORE_TAG')){
	
	class tw_auto_more_tag {
		
		private static $_instance;

		public $length;
		public $options;
		public $data;

		public function __construct() {
			global $wpdb;

			$this->_db = &$wpdb;

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

			$options = get_option('tw_auto_more_tag');
					
			$length = $options['quantity'];
			$breakOn = $options['break'];

			$moreTag = strpos($data, '[amt_override]');

			if($moreTag !== false && $options['ignore_man_tag'] != true){
				
				return self::$_instance->manual($data);

			}

			switch($options['units']){
				case 1:
					
					return self::$_instance->byCharacter($data, $length, $breakOn);
					break;
				case 2:
				default:

					return self::$_instance->byWord($data, $length, $breakOn);
					break;

				case 3:
					
					return self::$_instance->byPercent($data, $length, $breakOn);
					break;
			}

		}

		public function manual($data) {

			$data = str_replace('<!--more-->', '', $data);
			$data = str_replace('[amt_override]','[amt_override]<!--more-->', $data);

			return $data;

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
				if(empty($temp_end) || trim($temp_end) == null || strlen($temp_end) <= 0)
					return $data;

				$data = $temp.'<!--more-->'.$temp_end;

			}
		
			return $data;

		}

		public function byPercent($data, $length, $breakon) {

			$lengthOfPost = strlen($data);
			$start = $lengthOfPost * ($length / 100);

			$data = str_replace('<!--more-->', '', $data);
			$break = ($breakOn === 2) ? PHP_EOL : ' ';
			$pos = strpos($data, $break, $start);

			if($pos === false){

				$pos = strpos($data, '>', $start);

				if($pos === false){

					$pos = $start;

				}

			}

			$temp = substr($data, 0, $pos);
			$temp_end = substr($data, $pos);
			
			if(empty($temp_end) || trim($temp_end) == null)
				return $data;

			$data = $temp.'<!--more-->'.$temp_end;

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

			if($input['units'] == 2){
				$input['messages']['errors'][] = 'This version does not include capabilities for Word seperation. Units has been defaulted to Characters.';
			}

			$input['credit_me'] = (isset($input['credit_me']) && ((bool)$input['credit_me'] == true)) ? true : false;

			if($input['credit_me'] === false){
				$input['messages']['notices'][] = 'Hard work and determination was put into this plugin. Please be kind, and credit me!';
			}

			$input['ignore_man_tag'] = (isset($input['ignore_man_tag']) && ((bool)$input['ignore_man_tag'] == true)) ? true : false;

			$input['units'] = ((int)$input['units'] == 1) ? 1 : (((int)$input['units'] == 2) ? 2 : 3);

			/*********************************
			* THIS IS TEMPORARY
			* ONLY HERE UNTIL WORD COUNT IS IMPLEMENTED
			**********************************/
			if($input['units'] == 2){
				$input['units'] = 1;
			}

			if($input['units'] == 3 && $input['quantity'] > 100){
				$input['messages']['notices'][] = 'While using Percentage breaking, you cannot us a number larger than 100%. This field has been reset to 50%.';
				$input['quantity'] = 50;
			}

			if($input['units'] == 1){
				$input['messages']['warnings'][] = 'Using characters is not suggested. The more tag is added to the unfiltered HTML of the post, which means that this tag could cause your HTML to unvalidate.';
			}			
			
			$input['break'] = (isset($input['break']) && (int)$input['break'] == 2) ? 2 : 1;
		
			return $input;

		}

		public function buildOptionsPage() {
			require_once('auto-more-options-page.php');
		}

		public function addPage() {

			$this->option_page = add_options_page('Auto More Tag', 'Auto More Tag', 'manage_options', 'tw_auto_more_tag', array($this, 'buildOptionsPage'));

		}

		private function updateAll() {
		
			$posts = get_posts(array(
				'numberposts' => '-1',
				'post_status' => 'publish',
				'post_type' => 'post'
			));

			if(count($posts) > 0){
				global $post;
				foreach($posts as $post){
					setup_postdata($post);
					$post->post_content = self::addTag($post->post_content);
					wp_update_post($post);			
				}
			}
	
		}

		public function manualOverride($atts, $content = null, $code = null){
			// We just want to make this tag disappear. Let's just make it go away now...
			return null;
		}

	}
	$tw_auto_more_tag = new tw_auto_more_tag();

	add_action('admin_init', array($tw_auto_more_tag, 'initOptionsPage'));
	add_action('admin_menu', array($tw_auto_more_tag, 'addPage'));
	add_action('wp_footer', 'tw_auto_more_tag::doFooter');
	add_filter('content_save_pre', 'tw_auto_more_tag::addTag', '1', 2);
	add_shortcode('amt_override', array($tw_auto_more_tag, 'manualOverride'));
	
	define('TW_AUTO_MORE_TAG', '<div style="text-align: center;"><a href="http://travisweston.com" target="_blank" style="font-size: 7pt;">PHP/MySQL Components, WordPress Plugins, and Technology Opinions at TravisWeston.com</a></div>');
}
