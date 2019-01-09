<?php
/*
Plugin Name: XSoftware Socials
Description: Socials management on wordpress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.eu/
Text Domain: xsoftware_socials
*/

if (!class_exists('xs_socials_plugin')) {

include 'facebook/facebook.php';
include 'twitter/twitter.php';

if (!isset($_SESSION)) session_start();

class xs_socials_plugin 
{
        private $socials = array();
        
        private $default_facebook = array(
                                        'token' => '',
                                        );
                                        
        private $default_twitter = array(
                                        'token' => '',
                                        'token_secret' => '',
                                        );
                                        
        private $socials_facebook = NULL;
        private $socials_twitter = NULL;
        
        public function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                add_action('save_post', array($this, 'action_publish_post'));
                
                $this->socials_facebook = get_option('xs_facebook', $this->default_facebook);
                $this->socials_twitter = get_option('xs_twitter', $this->default_twitter);
                
        }
        
        function admin_menu()
        {
                global $menu;
                $menuExist = false;
                foreach($menu as $item) {
                        if(strtolower($item[0]) == strtolower('XSoftware')) {
                                $menuExist = true;
                        }
                }
                
                if(!$menuExist)
                        add_menu_page( 'XSoftware', 'XSoftware', 'manage_options', 'xsoftware', array($this, 'menu_page') );
                        
                add_submenu_page( 'xsoftware', 'XSoftware Socials', 'Socials', 'manage_options', 'xsoftware_socials', array($this, 'menu_page') );
        }
        
        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                xs_framework::init_admin_style();
                
                echo '<div class="wrap">';
                echo '<h2>Socials configuration</h2>';
                
                echo "<form action=\"options.php\" method=\"post\">";
                
                settings_fields('xsoftware_socials');
                do_settings_sections('xsoftware_socials');
                
                submit_button( '', 'primary', 'globals', true, NULL );
                
                echo "</form>";
                
                echo "<form action=\"options.php\" method=\"post\">";
                
                settings_fields('xsoftware_socials_twitter');
                do_settings_sections('xsoftware_socials_twitter');
                
                submit_button( '', 'primary', 'globals', true, NULL );
                
                echo "</form>";
                echo '</div>';
        }
        
        function section_menu()
        {
                register_setting( 'xsoftware_socials', 'xs_facebook', array($this, 'facebook_input') );
                add_settings_section( 'facebook_settings', 'Facebook configuration', array($this, 'facebook_show'), 'xsoftware_socials' );
                register_setting( 'xsoftware_socials_twitter', 'xs_twitter', array($this, 'twitter_input') );
                add_settings_section( 'twitter_settings', 'Twitter configuration', array($this, 'twitter_show'), 'xsoftware_socials_twitter' );
        }
        
        function twitter_input($input)
        {
                return $input;
        }
        
        function twitter_show()
        {
                if(isset($_SESSION["oauth_token"]) && isset($_SESSION["oauth_token_secret"]) && isset($_GET["twitter"]) && isset($_GET["oauth_verifier"])) {
                
                        $token["oauth_token"] = $_SESSION["oauth_token"];
                        $token["oauth_token_secret"] = $_SESSION["oauth_token_secret"];
                        unset($_SESSION["oauth_token"]);
                        unset($_SESSION["oauth_token_secret"]);
                
                        
                        $twitter = new xs_socials_twitter($token);
                                
                        $oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier');
                        if(!empty($oauth_verifier)) {
                                $new_token = $twitter->verify($oauth_verifier);
                                $this->socials_twitter['token'] = $new_token["oauth_token"];
                                $this->socials_twitter["token_secret"] = $new_token["oauth_token_secret"];
                                $twitter = new xs_socials_twitter($new_token);
                        }
                }
                        
                if(empty($this->socials_twitter['token']) && empty($this->socials_twitter['token_secret'])) {
                
                        $twitter = new xs_socials_twitter(array());

                        $callback_url = "https://localhost/wp-admin/admin.php?page=xsoftware_socials&twitter=true";
                        
                        $callback = $twitter->callback_url($callback_url);
                        $_SESSION['oauth_token'] = $callback['oauth_token'];
                        $_SESSION['oauth_token_secret'] = $callback['oauth_token_secret'];
                        xs_framework::create_link(array( 'class' => 'button-primary', 'href' => $callback['url'], 'text' => 'Generate new token'));
                }
                
                $page = 'xsoftware_socials_twitter';
                $section = 'twitter_settings';
                
                $settings_field = array('value' => $this->socials_twitter["token"], 'name' => 'xs_twitter[token]');
                add_settings_field($settings_field['name'], 
                'User token:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = array('value' => $this->socials_twitter["token_secret"], 'name' => 'xs_twitter[token_secret]');
                add_settings_field($settings_field['name'], 
                'User token secret:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);
        }
        
        function facebook_input($input)
        {
                $fb = new xs_socials_facebook(); 
                $result = $fb->login($input['mail'], $input['pass']); // Try to login
                
                if($result !== true) { // Abort if can't login
                        echo $result;
                        exit;
                }
                
                unset($input['mail']); //clear all input
                unset($input['pass']);
                unset($input['token']);
                
                $input['token'] = $fb->get_token(); // get new token
                
                return $input;
        }
        
        function facebook_show()
        {
                $page = 'xsoftware_socials';
                $section = 'facebook_settings';
                
                $settings_field = array('type' => 'email', 'name' => 'xs_facebook[mail]');
                add_settings_field($settings_field['name'], 
                'User email:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = array('type' => 'password', 'name' => 'xs_facebook[pass]');
                add_settings_field($settings_field['name'], 
                'User password:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = array('value' => $this->socials_facebook["token"], 'readonly' => true, 'name' => 'xs_facebook[token]');
                add_settings_field($settings_field['name'], 
                'User token:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);
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

}

$socials_plugin = new xs_socials_plugin;

}
?>
