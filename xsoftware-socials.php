<?php
/*
Plugin Name: XSoftware Socials
Description: Socials management on wordpress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.eu/
Text Domain: xsoftware_socials
*/

if(!defined('ABSPATH')) die;

if (!class_exists('xs_socials_plugin')) :

include 'xsoftware-socials-options.php';

include 'facebook/autoload.php';
include 'twitter/twitter.class.php';

if (!isset($_SESSION)) session_start();

class xs_socials_plugin
{
        private $socials = array();

        public function __construct()
        {
                $this->options = get_option('xs_options_socials');

                add_action('init', [$this, 'setup']);
                add_action('save_post', [$this, 'action_publish_post']);
                add_shortcode('xs_socials_posts', [$this,'shortcode_posts']);
        }

        function setup()
        {
                xs_framework::register_plugin(
                        'xs_socials',
                        'xs_options_socials'
                );
        }

        function shortcode_posts()
        {
                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.3',
                ]);

                $resp = $fb->get(
                        $this->fb_feed_fields([
                                'description',
                                'caption',
                                'created_time',
                                'full_picture',
                                'is_published',
                                'permalink_url',
                                'width',
                                'height',
                                'event',
                                'is_hidden',
                                'from',
                                'link',
                                'message_tags',
                                'status_type',
                                'privacy'
                        ]),
                        $this->options['fb']['token']
                );
                $post_list = $resp->getGraphEdge();
                foreach($post_list as $single) {
                        echo apply_filters('xs_socials_facebook_post', $single->asArray());
                }
        }

        function action_publish_post( $postid )
        {
                if(!isset($postid))
                        return;
                // check if post status is 'publish'
                if ( get_post_status( $postid ) != 'publish')
                        return;
                $post_type = get_post_type($postid);
                if($post_type != 'post' && $post_type != 'page')
                        return;

                $post = get_post($postid);
                if ($post->post_excerpt) {
                        $text = apply_filters('the_excerpt', $the_post->post_excerpt);
                } else {
                        setup_postdata( $post );
                        $text = get_the_excerpt();
                        wp_reset_postdata();
                }

                $link = get_bloginfo('url');

                if($link == 'http://localhost')
                        $link = 'xsoftware.eu';
                else
                        $link = get_post_permalink($postid);

                if($this->socials['facebook']['enabled'] && $this->socials['facebook']['enabled'] !== false) {
                        $fb = new xs_socials_facebook($this->socials['facebook']['token']);
                        $fb->post_add($text, $link);
                }

                if($this->socials['twitter']['enabled'] && $this->socials['twitter']['enabled'] !== false) {
                        $token['oauth_token'] = $this->socials['twitter']['token'];
                        $token['oauth_token_secret'] =  $this->socials['twitter']['token_secret'];
                        $twitter = new xs_socials_twitter($token);
                        $twitter->post_add($text);
                }
        }

        function fb_feed_fields($fields)
        {
                $output = '/me/feed?fields=';
                foreach($fields as $single)
                        $output .= $single . ',';

                $output = rtrim($output, ',');
                return $output;
        }
}

endif;

$xs_socials_plugin = new xs_socials_plugin;
?>
