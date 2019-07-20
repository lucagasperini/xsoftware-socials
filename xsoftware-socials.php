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

class xs_socials_plugin
{

        public function __construct()
        {
                $this->options = get_option('xs_options_socials');

                add_action('init', [$this, 'setup']);
                add_shortcode('xs_socials_facebook_posts', [$this,'shortcode_facebook_posts']);
                add_shortcode('xs_socials_twitter_posts', [$this,'shortcode_twitter_posts']);
                add_shortcode('xs_socials_icons', [$this,'shortcode_icons']);
        }

        function setup()
        {
                xs_framework::register_plugin(
                        'xs_socials',
                        'xs_options_socials'
                );
        }

        function shortcode_icons($attr)
        {
                return apply_filters('xs_socials_icons_show', null);
        }

        function shortcode_twitter_posts($attr)
        {
                $a = shortcode_atts(
                        [
                                'limit' => '20',
                        ],
                        $attr
                );

                if(
                        empty($this->options['twr']['api_key']) ||
                        empty($this->options['twr']['api_key_secret']) ||
                        empty($this->options['twr']['access_token']) ||
                        empty($this->options['twr']['access_token_secret'])
                )
                        return '';

                $output = '';


                $twitter = new DG\Twitter\Twitter(
                        $this->options['twr']['api_key'],
                        $this->options['twr']['api_key_secret'],
                        $this->options['twr']['access_token'],
                        $this->options['twr']['access_token_secret']
                );

                $post_list = $twitter->load(Twitter::ME);

                foreach($post_list as $single) {
                        $data = [
                                'id' => $single->id,
                                'description' => $single->text,
                                'user_link' => 'https://twitter.com/'.$single->user->screen_name,
                                'user_image' => htmlspecialchars($single->user->profile_image_url_https),
                                'user_name' => htmlspecialchars($single->user->name),
                                'date' => date('j.n.Y H:i', strtotime($single->created_at))
                        ];
                        if(!empty($single->entities->urls)){
                                foreach($single->entities->urls as $urls) {
                                        $tmp = array();
                                        $tmp['url'] = $urls->url;
                                        $tmp['expanded_url'] = $urls->expanded_url;
                                        $tmp['display_url'] = $urls->display_url;

                                        $data['urls'][] = $tmp;
                                }
                        }
                        if(!empty($single->entities->media)) {
                                foreach($single->entities->media as $media) {
                                        $tmp = array();
                                        $tmp['url'] = $media->media_url_https;
                                        $tmp['type'] = $media->type;

                                        $data['media'][] = $tmp;
                                }
                        }
                        $output .= apply_filters('xs_socials_twitter_post', $data);
                }

                return $output;
        }

        function shortcode_facebook_posts($attr)
        {
                $a = shortcode_atts(
                        [
                                'page_id' => 'me',
                                'limit' => '20',
                        ],
                        $attr
                );

                if(
                        empty($this->options['fb']['appid']) ||
                        empty($this->options['fb']['secret']) ||
                        empty($this->options['fb']['secret'])
                )
                        return '';

                $output = '';

                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $resp = $fb->get(
                        '/'.$a['page_id'].'?fields=link',
                        $this->options['fb']['token']
                );

                $user = $resp->getGraphNode()->asArray();

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
                        $output .= apply_filters('xs_socials_facebook_post', $single->asArray(), $user);
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
