<?php 
/*
 * Plugin Name: Twitter Widget
 * Plugin URI: https://github.com/mmainulhasan/wordpress
 * Description: Display and cache tweets
 * Version: 1.0
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/mmainulhasan
 */

class Twitter_Widget extends WP_Widget {

	function __construct() {
		$options = array(
			'description' => 'Display and cache tweets',
			'name'        => 'Display Tweets'
		);
		parent::__construct('Twitter_Widget', '', $options);
	}

	public function form($instance) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$username = isset($instance['username']) ? esc_attr($instance['username']) : '';
		$tweet_count = isset($instance['tweet_count']) ? intval($instance['tweet_count']) : 5;
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input class="widefat" 
			       type="text" 
			       id="<?php echo $this->get_field_id('title'); ?>" 
			       name="<?php echo $this->get_field_name('title'); ?>" 
			       value="<?php echo $title; ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('username'); ?>">Twitter Username:</label>
			<input class="widefat" 
			       type="text" 
			       id="<?php echo $this->get_field_id('username'); ?>" 
			       name="<?php echo $this->get_field_name('username'); ?>" 
			       value="<?php echo $username; ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('tweet_count'); ?>">Number of Tweets to Retrieve:</label>
			<input class="widefat"
			       style="width: 40px;" 
			       type="number" 
			       id="<?php echo $this->get_field_id('tweet_count'); ?>" 
			       name="<?php echo $this->get_field_name('tweet_count'); ?>" 
			       min="1"
			       max="10"
			       value="<?php echo $tweet_count; ?>">
		</p>
		<?php
	}

	public function widget($args, $instance) {
		$title = isset($instance['title']) ? $instance['title'] : 'Recent Tweets';
		$username = isset($instance['username']) ? $instance['username'] : '';
		$tweet_count = isset($instance['tweet_count']) ? intval($instance['tweet_count']) : 5;

		if (empty($username)) {
			return; // Don't proceed if username is empty
		}

		$this->twitter($tweet_count, $username);
	}

	private function twitter($tweet_count, $username) {
		$this->fetch_tweets($tweet_count, $username);
	}

	private function fetch_tweets($tweet_count, $username) {
		$token = ''; // Add your token here
		$url = "https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=$username&access_token=$token";

		// Make the API request and process the tweets
		$tweets = wp_remote_get($url);

		// Process and display the tweets
		// ...
	}
}

add_action('widgets_init', 'register_twitter_widget');
function register_twitter_widget() {
	register_widget('Twitter_Widget');
}