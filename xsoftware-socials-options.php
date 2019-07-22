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

                echo '<form action="options.php" method="post">';

                settings_fields('xs_socials_setting');
                do_settings_sections('xs_socials');

                submit_button( '', 'primary', 'globals', true, NULL );

                echo '</form>';

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
                                'twr' => 'Twitter',
                        //        'ig' => 'Instagram'
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
                        /*
                        case 'ig':
                                $this->show_instagram();
                                return;
                        */
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

                /*
                if(isset($input['ig']) && !empty($input['ig']))
                        foreach($input['ig'] as $key => $value)
                                $current['ig'][$key] = $value;
                */
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
                /* Create a html select with wordpress pages URL using {get_wp_pages_link} */
                $options = [
                        'name' => 'xs_options_socials[fb][call]',
                        'selected' => $this->options['fb']['call'],
                        'data' => xs_framework::get_wp_pages_link(),
                        'default' => 'Select a facebook page',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $options['name'],
                        'Set facebook page',
                        'xs_framework::create_select',
                        'xs_socials',
                        'xs_socials_section',
                        $options
                );

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

                if(isset($this->options['fb']['token']) && !empty($this->options['fb']['token']))
                {
                        $settings_field = [
                                'value' => $this->options['fb']['token'],
                                'name' => 'xs_options_socials[fb][token]',
                                'readonly' => TRUE,
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
                        return;
                }

                if(
                        empty($this->options['fb']['secret']) ||
                        empty($this->options['fb']['appid']) ||
                        empty($this->options['fb']['call'])
                )
                        return;

                $fb = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $helper = $fb->getRedirectLoginHelper();

                $permissions = ['email'];
                $loginUrl = $helper->getLoginUrl(
                        $this->options['fb']['call'],
                        $permissions
                );

                $settings_field = [
                        'name' => 'link_facebook',
                        'href' => htmlspecialchars($loginUrl),
                        'text' => 'Log in with Facebook!',
                        'echo' => TRUE
                ];
                add_settings_field(
                        $settings_field['name'],
                        'Login facebook:',
                        'xs_framework::create_link',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );
        }
/*
        function show_instagram()
        {
                $options = [
                        'name' => 'xs_options_socials[ig][call]',
                        'selected' => $this->options['ig']['call'],
                        'data' => xs_framework::get_wp_pages_link(),
                        'default' => 'Select a instagram page',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $options['name'],
                        'Set instagram page',
                        'xs_framework::create_select',
                        'xs_socials',
                        'xs_socials_section',
                        $options
                );

                $settings_field = [
                        'value' => $this->options['ig']['appid'],
                        'name' => 'xs_options_socials[ig][appid]',
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
                        'value' => $this->options['ig']['secret'],
                        'name' => 'xs_options_socials[ig][secret]',
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

                if(
                        empty($this->options['ig']['secret']) ||
                        empty($this->options['ig']['appid']) ||
                        empty($this->options['ig']['call'])
                )
                        return;

                $url = 'https://api.instagram.com/oauth/authorize/?client_id='.$this->options['ig']['appid'].
                '&redirect_uri='.$this->options['ig']['call'].'&response_type=code';

                $settings_field = [
                        'name' => 'link_instagram',
                        'href' => htmlspecialchars($url),
                        'text' => 'Log in with Instagram!',
                        'echo' => TRUE
                ];
                add_settings_field(
                        $settings_field['name'],
                        'Login instagram:',
                        'xs_framework::create_link',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

        }
*/
}

endif;

$xs_socials_options = new xs_socials_options;

?>
