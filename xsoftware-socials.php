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
                add_shortcode('xs_socials_instagram_posts', [$this,'shortcode_instagram_posts']);
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
                ) {
                        apply_filters('xs_socials_twitter_call', $_GET);
                        return '';
                }
                $output = '';


                $twitter = new DG\Twitter\Twitter(
                        $this->options['twr']['api_key'],
                        $this->options['twr']['api_key_secret'],
                        $this->options['twr']['access_token'],
                        $this->options['twr']['access_token_secret']
                );

                $post_list = $twitter->load(Twitter::ME);

                foreach($post_list as $single) {
                        $post = [
                                'description' => $single->text,
                                'user_link' => 'https://twitter.com/'.$single->user->screen_name,
                                'user_image' => htmlspecialchars($single->user->profile_image_url_https),
                                'user_name' => htmlspecialchars($single->user->name),
                                'date' => new DateTime($single->created_at),
                        ];
                        $post['permalink'] = $post['user_link'].'/status/'.$single->id;
                        if(!empty($single->entities->urls)){
                                foreach($single->entities->urls as $urls) {
                                        $tmp = array();
                                        $tmp['url'] = $urls->url;
                                        $tmp['expanded_url'] = $urls->expanded_url;
                                        $tmp['display_url'] = $urls->display_url;

                                        $post['urls'][] = $tmp;
                                }
                        }
                        if(!empty($single->entities->media)) {
                                foreach($single->entities->media as $media) {
                                        $tmp = array();
                                        $tmp['url'] = $media->media_url_https;
                                        $tmp['type'] = $media->type;

                                        $post['media'][] = $tmp;
                                }
                                $post['media'] = $post['media'][0]['url'];
                        }
                        $output .= apply_filters('xs_socials_twitter_post', $post);
                }

                return $output;
        }

        function shortcode_instagram_posts($attr)
        {
                $a = shortcode_atts(
                        [
                                'page_id' => 'me',
                                'limit' => '20',
                        ],
                        $attr
                );

                if(
                        ! empty($this->options['fb']['appid']) &&
                        ! empty($this->options['fb']['secret']) &&
                        empty($this->options['fb']['token'])
                ) {
                        return '';
                }
                $output = '';

                $ig = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $resp = $ig->get(
                        '/'.$a['page_id'].'?fields=instagram_business_account',
                        $this->options['fb']['token']
                );

                $id_user = $resp->getGraphNode()->asArray();
                $id_user = $id_user['instagram_business_account']['id'];

                $resp = $ig->get(
                      '/'.$id_user.'/?fields=profile_picture_url,name,username',
                        $this->options['fb']['token']
                );

                $userinfo = $resp->getGraphNode();


                $resp = $ig->get(
                      '/'.$id_user.'/media?fields=media_url,permalink,media_type,caption,timestamp'.
                      '&limit='.$a['limit'],
                        $this->options['fb']['token']
                );
                $post_list = $resp->getGraphEdge();

                foreach($post_list as $single) {
                        $post = [
                                'user_link' => 'https://www.instagram.com/'.$userinfo->getField('username'),
                                'user_image' => $userinfo->getField('profile_picture_url'),
                                'user_name' => $userinfo->getField('name'),
                                'date' => new DateTime($single->getField('timestamp')),
                                'permalink' => $single->getField('permalink'),
                                'description' => $single->getField('caption'),
                                'media' => $single->getField('media_url')
                        ];
                        $output .= apply_filters('xs_socials_instagram_post', $post);
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
                        ! empty($this->options['fb']['appid']) &&
                        ! empty($this->options['fb']['secret']) &&
                        empty($this->options['fb']['token'])
                ) {
                        $this->facebook_call();
                        return '';
                }
                $output = '';

                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $resp = $fb->get(
                        '/'.$a['page_id'].'?fields=link,picture,name',
                        $this->options['fb']['token']
                );

                $user = $resp->getGraphNode();

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
                        $post = [
                                'user_link' => $user->getField('link'),
                                'user_image' => $user->getField('picture')['url'],
                                'user_name' => $user->getField('name'),
                                'date' => $single->getField('created_time'),
                                'permalink' => $single->getField('permalink_url'),
                                'description' => $single->getField('description'),
                                'media' => $single->getField('full_picture')
                        ];

                        $output .= apply_filters('xs_socials_facebook_post', $post);
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

        function facebook_call()
        {
                if (!isset($_GET['code']) || empty($_GET['code']))
                        return;

                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $helper = $fb->getRedirectLoginHelper();
                $accessToken = $helper->getAccessToken();

                // The OAuth 2.0 client handler helps us manage access tokens
                $oAuth2Client = $fb->getOAuth2Client();

                // Get the access token metadata from /debug_token
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);

                // Validation (these will throw FacebookSDKException's when they fail)
                $tokenMetadata->validateAppId($this->options['fb']['appid']);

                $tokenMetadata->validateExpiration();

                if (! $accessToken->isLongLived()) {
                        // Exchanges a short-lived access token for a long-lived one
                        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                }
                /* Get the option using wordpress API */
                $options = get_option('xs_options_socials', array());

                /* Replace the value with access token */
                $options['fb']['token'] = (string) $accessToken;
                /* Refresh the option deleting the cache */
                wp_cache_delete ( 'alloptions', 'options' );
                /* Update the option on framework and return the value */
                $result = update_option('xs_options_socials', $options);
                var_dump($result, $accessToken);
        }


}

endif;

$xs_socials_plugin = new xs_socials_plugin;
?>
