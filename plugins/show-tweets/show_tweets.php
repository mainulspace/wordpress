<?php
/**
 * Plugin Name: Show Tweets
 * Plugin URI: https://github.com/mmainulhasan/wordpress
 * Description: Simple shortcode
 * Version: 1.0
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/mmainulhasan
 */

class ShowTweetsPlugin {
    private $consumer_key;
    private $consumer_secret;
    private $token;
    private $token_secret;

    public function __construct() {
        // Load API credentials from secure storage (e.g., environment variables)
        $this->consumer_key = getenv('TWITTER_CONSUMER_KEY');
        $this->consumer_secret = getenv('TWITTER_CONSUMER_SECRET');
        $this->token = getenv('TWITTER_TOKEN');
        $this->token_secret = getenv('TWITTER_TOKEN_SECRET');

        add_shortcode('twitter', [$this, 'twitter_shortcode']);
    }

    public function twitter_shortcode($atts, $content) {
        $atts = shortcode_atts(
            [
                'username' => 'envatowebdev',
                'content' => !empty($content) ? $content : 'Follow me on Twitter!',
                'show_tweets' => false,
                'tweet_reset_time' => 10,
                'num_tweets' => 5
            ],
            $atts
        );

        extract($atts);

        if ($show_tweets) {
            $tweets = $this->fetch_tweets($num_tweets, $username, $tweet_reset_time);
        }

        return "$tweets <p><a href='http://twitter.com/$username'>$content</a></p>";
    }

    private function fetch_tweets($num_tweets, $username, $tweet_reset_time) {
        $recent_tweets = get_transient('mh_recent_tweets');

        if (!$recent_tweets) {
            $tweets = $this->fetch_tweets_from_api($num_tweets, $username);
            $data = [];

            foreach ($tweets as $tweet) {
                if ($num_tweets-- === 0) {
                    break;
                }

                $data[] = $tweet->text;
            }

            $recent_tweets = [(int)date('i', time())];
            $recent_tweets[] = '<ul class="mh_tweets"><li>' . implode('</li><li>', $data) . '</li><ul>';

            $this->cache($recent_tweets, $tweet_reset_time);
        }

        return isset($recent_tweets[0][1]) ? $recent_tweets[0][1] : $recent_tweets[1];
    }

    private function fetch_tweets_from_api($num_tweets, $username) {
        $host = 'api.twitter.com';
        $method = 'GET';
        $path = '/1.1/statuses/user_timeline.json'; // API call path

        $query = [
            'screen_name' => $username,
            'count' => $num_tweets
        ];

        $oauth = [
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_token' => $this->token,
            'oauth_nonce' => (string)mt_rand(), // a stronger nonce is recommended
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0'
        ];

        $oauth = array_map('rawurlencode', $oauth); // must be encoded before sorting
        $query = array_map('rawurlencode', $query);

        $arr = array_merge($oauth, $query); // combine the values THEN sort

        asort($arr); // secondary sort (value)
        ksort($arr); // primary sort (key)

        $querystring = urldecode(http_build_query($arr, '', '&'));

        $url = "https://{$host}{$path}?{$querystring}";

        // mash everything together for the text to hash
        $base_string = "{$method}&" . rawurlencode($url);

        // same with the key
        $key = rawurlencode($this->consumer_secret) . '&' . rawurlencode($this->token_secret);

        // generate the hash
        $signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));

        $oauth['oauth_signature'] = $signature; // don't want to abandon all that work!
        ksort($oauth); // probably not necessary, but Twitter's demo does it

        // this is the full value of the Authorization line
        $auth = 'OAuth ' . urldecode(http_build_query($oauth, '', ', '));

        $options = [
            CURLOPT_HTTPHEADER => ["Authorization: $auth"],
            CURLOPT_HEADER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5
        ];

        // do our business
        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        curl_close($feed);

        $twitter_data = json_decode($json);

        return $twitter_data;
    }

    private function cache($recent_tweets, $tweet_reset_time) {
        set_transient('mh_recent_tweets', $recent_tweets, $tweet_reset_time * 60);
    }

    private function reset_data($recent_tweets, $tweet_reset_time) {
        if (isset($recent_tweets[0][0])) {
            $delay = $recent_tweets[0][0] + (int)$tweet_reset_time;
            if ($delay >= 60) {
                $delay -= 60;
            }
            if ($delay <= (int)date('i', time())) {
                delete_transient('mh_recent_tweets');
            }
        }
    }
}

// Instantiate the plugin class
$show_tweets_plugin = new ShowTweetsPlugin();