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
                add_shortcode('xs_socials_posts', [$this,'shortcode_posts']);
        }

        function setup()
        {
                xs_framework::register_plugin(
                        'xs_socials',
                        'xs_options_socials'
                );
        }

        function shortcode_posts($attr)
        {
                $a = shortcode_atts(
                        [
                                'page_id' => 'me',
                                'limit' => '20',
                        ],
                        $attr
                );

                $output = '';

                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $resp = $fb->get(
                        $this->fb_feed_fields(
                                $a['page_id'],
                                [
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
                                ],
                                $a['limit']
                        ),
                        $this->options['fb']['token']
                );
                $post_list = $resp->getGraphEdge();
                foreach($post_list as $single) {
                        $output .= apply_filters('xs_socials_facebook_post', $single->asArray());
                }

                return $output;
        }

        function fb_feed_fields($page_id, $fields, $limit)
        {
                $output = '/'.$page_id.'/feed?fields=';
                foreach($fields as $single)
                        $output .= $single . ',';

                $output = rtrim($output, ',');
                $output .= '&limit='.$limit;
                return $output;
        }
}

endif;

$xs_socials_plugin = new xs_socials_plugin;
?>
