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
include 'twitter/twitter.php';

if (!isset($_SESSION)) session_start();

class xs_socials_plugin
{
        private $socials = array();

        public function __construct()
        {
                $this->options = get_option('xs_options_socials');

                add_action('init', [$this, 'setup']);
                add_action('save_post', [$this, 'action_publish_post']);
        }

        function setup()
        {
                xs_framework::register_plugin(
                        'xs_socials',
                        'xs_options_socials'
                );
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

        function login_facebook($data)
        {
                $fb = new Facebook\Facebook([
                'app_id' => '2196833727302325', // Replace {app-id} with your app id
                'app_secret' => '910e3b966829bce1ac9ddc7cefc32e90',
                'default_graph_version' => 'v3.2',
                ]);

                $helper = $fb->getRedirectLoginHelper();

                try {
                        $accessToken = $helper->getAccessToken();
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                        // When Graph returns an error
                        echo 'Graph returned an error: ' . $e->getMessage();
                        exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                        // When validation fails or other local issues
                        echo 'Facebook SDK returned an error: ' . $e->getMessage();
                        exit;
                }

                if (! isset($accessToken)) {
                        if ($helper->getError()) {
                                header('HTTP/1.0 401 Unauthorized');
                                echo "Error: " . $helper->getError() . "\n";
                                echo "Error Code: " . $helper->getErrorCode() . "\n";
                                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                                echo "Error Description: " . $helper->getErrorDescription() . "\n";
                        } else {
                                header('HTTP/1.0 400 Bad Request');
                                echo 'Bad request';
                        }
                        exit;
                }

                // The OAuth 2.0 client handler helps us manage access tokens
                $oAuth2Client = $fb->getOAuth2Client();

                // Get the access token metadata from /debug_token
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);

                // Validation (these will throw FacebookSDKException's when they fail)
                $tokenMetadata->validateAppId('2196833727302325');
                // If you know the user ID this access token belongs to, you can validate it here
                //$tokenMetadata->validateUserId('123');
                $tokenMetadata->validateExpiration();

                if (! $accessToken->isLongLived()) {
                // Exchanges a short-lived access token for a long-lived one
                try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                } catch (Facebook\Exceptions\FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
                exit;
                }

                echo '<h3>Long-lived</h3>';
                var_dump($accessToken->getValue());
                }

                $_SESSION['fb_access_token'] = (string) $accessToken;
        }

}

endif;

$xs_socials_plugin = new xs_socials_plugin;
?>
