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
        
        private $default = array('facebook' => 
                                        array(
                                                'token' => '',
                                                'enabled' => false
                                        ),
                                'twitter' =>
                                        array(
                                                'token' => '',
                                                'token_secret' => '',
                                                'enabled' => false
                                        )
                                
                                );
        
        
        public function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                add_action('save_post', array($this, 'action_publish_post'));
                
                $this->socials = get_option('socials_accounts', $this->default);
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
                register_setting( 'xsoftware_socials', 'socials_accounts', array($this, 'facebook_input') );
                add_settings_section( 'facebook_settings', 'Facebook configuration', array($this, 'facebook_show'), 'xsoftware_socials' );
                register_setting( 'xsoftware_socials_twitter', 'socials_accounts', array($this, 'twitter_input') );
                add_settings_section( 'twitter_settings', 'Twitter configuration', array($this, 'twitter_show'), 'xsoftware_socials_twitter' );
        }
        
        function twitter_input($input)
        {
                return $input + $this->socials;
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
                                $this->socials['twitter']['token'] = $new_token["oauth_token"];
                                $this->socials['twitter']["token_secret"] = $new_token["oauth_token_secret"];
                                $twitter = new xs_socials_twitter($new_token);
                        }
                }
                        
                if(empty($this->socials['twitter']['token']) && empty($this->socials['twitter']['token_secret'])) {
                
                        $twitter = new xs_socials_twitter(array());

                        if(WP_DEBUG == false)
                                $callback_url = "https://localhost/wp-admin/admin.php?page=xsoftware_socials&twitter=true";
                        else
                                $callback_url = "http://localhost/wp-admin/admin.php?page=xsoftware_socials&twitter=true";
                                
                        $callback = $twitter->callback_url($callback_url);
                        $_SESSION['oauth_token'] = $callback['oauth_token'];
                        $_SESSION['oauth_token_secret'] = $callback['oauth_token_secret'];
                        echo "<a class=\"button-primary\" href=\"".$callback['url']."\">Generate new token</a>";
                }
                
                $page = 'xsoftware_socials_twitter';
                $section = 'twitter_settings';
                
                $settings = array( 'options' => $this->socials['twitter'], 'defaults' => $this->default['twitter']);
                
                $settings_field = $settings + array('name' => 'enabled', 'field_name' => 'socials_accounts[twitter][enabled]', 'compare' => true);
                add_settings_field($settings_field['field_name'], 
                'Enabled:',
                'xs_framework::create_checkbox_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = $settings + array('name' => 'token', 'field_name' => 'socials_accounts[twitter][token]');
                add_settings_field($settings_field['field_name'], 
                'User token:',
                'xs_framework::create_text_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = $settings + array( 'name' => 'token_secret', 'field_name' => 'socials_accounts[twitter][token_secret]');
                add_settings_field($settings_field['field_name'], 
                'User token secret:',
                'xs_framework::create_text_input',
                $page,
                $section,
                $settings_field);
        }
        
        function facebook_input($input)
        {
                if(!$input['facebook']['enabled']) {
                        return $input;
                }
                
                $fb = new xs_socials_facebook();
                $result = $fb->login($input['facebook']['mail'], $input['facebook']['pass']);
                
                if($result !== true) {
                        echo $result;
                        exit;
                }
                
                unset($input['facebook']['mail']);
                unset($input['facebook']['pass']);
                unset($input['facebook']['token']);
                
                $new_token = $fb->get_token();
                $input['facebook']['token'] = $new_token;
                $input['facebook']['enabled'] = $input['facebook']['enabled'] ? true : false;
                
                
                return $input + $this->socials;
        }
        
        function facebook_show()
        {
                $page = 'xsoftware_socials';
                $section = 'facebook_settings';
                
                $settings = array( 'options' => $this->socials['facebook'], 'defaults' => $this->default['facebook']);
                
                $settings_field = $settings + array('name' => 'enabled', 'field_name' => 'socials_accounts[facebook][enabled]', 'compare' => true);
                add_settings_field($settings_field['field_name'], 
                'Enabled:',
                'xs_framework::create_checkbox_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = array('name' => 'mail', 'type' => 'email', 'field_name' => 'socials_accounts[facebook][mail]');
                add_settings_field($settings_field['field_name'], 
                'User email:',
                'xs_framework::create_text_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = array('name' => 'pass', 'type' => 'password', 'field_name' => 'socials_accounts[facebook][pass]');
                add_settings_field($settings_field['field_name'], 
                'User password:',
                'xs_framework::create_text_input',
                $page,
                $section,
                $settings_field);
                
                $settings_field = $settings + array('readonly' => true, 'name' => 'token', 'field_name' => 'socials_accounts[facebook][token]');
                add_settings_field($settings_field['field_name'], 
                'User token:',
                'xs_framework::create_text_input',
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
