<?php

if(!defined('ABSPATH')) die;

if (!class_exists('xs_socials_options')) :

class xs_socials_options
{
        private $default = [
                'fb' => [
                        'token' => ''
                ],
                'twr' => [
                        'token' => '',
                        'token_secret' => '',
                ]
        ];

        public function __construct()
        {
                $this->options = get_option('xs_options_socials', $this->default);

                add_action('admin_menu', [$this, 'admin_menu']);
                add_action('admin_init', [$this, 'section_menu']);
        }

        function admin_menu()
        {
                add_submenu_page(
                        'xsoftware',
                        'XSoftware Socials',
                        'Socials',
                        'manage_options',
                        'xsoftware_socials',
                        [$this, 'menu_page']
                );
        }

        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }

                echo '<div class="wrap">';

                echo "<form action=\"options.php\" method=\"post\">";

                settings_fields('xs_socials_setting');
                do_settings_sections('xs_socials');

                submit_button( '', 'primary', 'globals', true, NULL );

                echo "</form>";

                echo '</div>';
        }

        function section_menu()
        {
                register_setting(
                        'xs_socials_setting',
                        'xs_options_socials',
                        [$this, 'input']
                );
                add_settings_section(
                        'xs_socials_section',
                        'Settings',
                        [$this, 'show'],
                        'xs_socials'
                );
        }

        function show()
        {
                $tab = xs_framework::create_tabs( [
                        'href' => '?page=xsoftware_socials',
                        'tabs' => [
                                'fb' => 'Facebook',
                                'twr' => 'Twitter'
                        ],
                        'home' => 'fb',
                        'name' => 'main_tab'
                ]);

                switch($tab) {
                        case 'fb':
                                $this->show_facebook();
                                return;
                        case 'twr':
                                $this->show_twitter();
                                return;
                }
        }

        function input($input)
        {
                $current = $this->options;

                return $current;
        }


        function show_twitter()
        {

                if(isset($_SESSION["oauth_token"]) && isset($_SESSION["oauth_token_secret"]) &&
isset($_GET["twitter"]) && isset($_GET["oauth_verifier"])) {

                        $token["oauth_token"] = $_SESSION["oauth_token"];
                        $token["oauth_token_secret"] = $_SESSION["oauth_token_secret"];
                        unset($_SESSION["oauth_token"]);
                        unset($_SESSION["oauth_token_secret"]);


                        $twitter = new xs_socials_twitter($token);

                        $oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier');
                        if(!empty($oauth_verifier)) {
                                $new_token = $twitter->verify($oauth_verifier);
                                $this->options['twr']['token'] = $new_token["oauth_token"];
                                $this->options['twr']["token_secret"] =
$new_token["oauth_token_secret"];
                                $twitter = new xs_socials_twitter($new_token);
                        }
                }

                if(empty($this->options['twr']['token']) &&
empty($this->options['twr']['token_secret'])) {

                        $twitter = new xs_socials_twitter(array());

                        $callback_url =
"https://localhost/wp-admin/admin.php?page=xsoftware_socials&twitter=true";

                        $callback = $twitter->callback_url($callback_url);
                        $_SESSION['oauth_token'] = $callback['oauth_token'];
                        $_SESSION['oauth_token_secret'] = $callback['oauth_token_secret'];
                        xs_framework::create_link(array(
                                'class' => 'button-primary',
                                'href' => $callback['url'],
                                'text' => 'Generate new token',
                                'echo' => TRUE
                        ));
                }

                $page = 'xs_socials';
                $section = 'xs_socials_section';

                $settings_field = array(
                        'value' => $this->options['twr']["token"],
                        'name' => 'xs_twitter[token]',
                        'echo' => TRUE
                );

                add_settings_field($settings_field['name'],
                'User token:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);

                $settings_field = array(
                        'value' => $this->options['twr']["token_secret"],
                        'name' => 'xs_twitter[token_secret]',
                        'echo' => TRUE
                );

                add_settings_field($settings_field['name'],
                'User token secret:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);

        }
/*
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
*/
        function show_facebook()
        {
                $page = 'xs_socials';
                $section = 'xs_socials_section';

                $settings_field = array(
                        'type' => 'email',
                        'name' => 'xs_facebook[mail]',
                        'echo' => TRUE
                );

                add_settings_field($settings_field['name'],
                'User email:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);

                $settings_field = [
                        'type' => 'password',
                        'name' => 'xs_facebook[pass]',
                        'echo'=> TRUE
                ];

                add_settings_field($settings_field['name'],
                'User password:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);

                $settings_field = array(
                        'value' => $this->options['fb']["token"],
                        'readonly' => true,
                        'name' => 'xs_facebook[token]',
                        'echo' => TRUE
                );

                add_settings_field($settings_field['name'],
                'User token:',
                'xs_framework::create_input',
                $page,
                $section,
                $settings_field);
        }
}

endif;

$xs_socials_options = new xs_socials_options;

?>
