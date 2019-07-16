<?php

if(!defined('ABSPATH')) die;

if (!class_exists('xs_socials_options')) :

class xs_socials_options
{
        private $default = [
                'fb' => [
                        'appid' => '',
                        'secret' => '',
                        'token' => ''
                ],
                'twr' => [
                        'api_key' => '',
                        'api_key_secret' => '',
                        'access_token' => '',
                        'access_token_secret' => ''
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
                $tab = xs_framework::create_tabs([
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

                if(isset($input['fb']) && !empty($input['fb']))
                        foreach($input['fb'] as $key => $value)
                                $current['fb'][$key] = $value;

                if(isset($input['twr']) && !empty($input['twr']))
                        foreach($input['twr'] as $key => $value)
                                $current['twr'][$key] = $value;

                return $current;
        }


        function show_twitter()
        {
                $settings_field = [
                        'value' => $this->options['twr']['api_key'],
                        'name' => 'xs_options_socials[twr][api_key]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Api Key:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                $settings_field = [
                        'value' => $this->options['twr']['api_key_secret'],
                        'name' => 'xs_options_socials[twr][api_key_secret]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Api Key Secret:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                $settings_field = [
                        'value' => $this->options['twr']['access_token'],
                        'name' => 'xs_options_socials[twr][access_token]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Access Token:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                $settings_field = [
                        'value' => $this->options['twr']['access_token_secret'],
                        'name' => 'xs_options_socials[twr][access_token_secret]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Access Token Secret:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );
        }

        function show_facebook()
        {
                $settings_field = [
                        'value' => $this->options['fb']['appid'],
                        'name' => 'xs_options_socials[fb][appid]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'App ID:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                $settings_field = [
                        'value' => $this->options['fb']['secret'],
                        'name' => 'xs_options_socials[fb][secret]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'App Secret:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                if(empty($this->options['fb']['secret']) && empty($this->options['fb']['appid']))
                        return;

                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $helper = $fb->getRedirectLoginHelper();

                if(!empty($this->options['fb']['token'])) {
                        $accessToken = $this->options['fb']['token'];
                } else if (isset($_GET['code']) && !empty($_GET['code'])) {
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
                }


                if(isset($accessToken)) {
                        $settings_field = [
                                'value' => $accessToken,
                                'readonly' => true,
                                'name' => 'xs_options_socials[fb][token]',
                                'echo' => TRUE
                        ];

                        add_settings_field($settings_field['name'],
                        'User token:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field);

                } else {
                        $url = xs_framework::get_browser_url();
                        $permissions = ['email'];
                        $loginUrl = $helper->getLoginUrl(
                                $url,
                                $permissions
                        );

                        $settings_field = [
                                'name' => 'link_facebook',
                                'href' => htmlspecialchars($loginUrl),
                                'text' => 'Log in with Facebook!',
                                'echo' => TRUE
                        ];

                        add_settings_field($settings_field['name'],
                        'Login facebook:',
                        'xs_framework::create_link',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field);

                }
        }
}

endif;

$xs_socials_options = new xs_socials_options;

?>
