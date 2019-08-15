<?php

if(!defined('ABSPATH')) die;

if (!class_exists('xs_socials_options')) :

class xs_socials_options
{
        private $default = [
                'main' => [
                        'time_expired_cache' => 90
                ],
                'fb' => [
                        'appid' => '',
                        'secret' => '',
                        'token' => '',
                        'call' => '',
                ],
                'twr' => [
                        'api_key' => '',
                        'api_key_secret' => '',
                        'access_token' => '',
                        'access_token_secret' => ''
                ],
                'ig' => [
                        'id_business_account' => '',
                        'id_facebook_page' => '',
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
                                'main' => 'Common',
                                'fb' => 'Facebook',
                                'twr' => 'Twitter',
                                'ig' => 'Instagram'
                        ],
                        'home' => 'fb',
                        'name' => 'main_tab'
                ]);

                switch($tab) {
                        case 'main':
                                $this->show_main();
                                return;
                        case 'fb':
                                $this->show_facebook();
                                return;
                        case 'twr':
                                $this->show_twitter();
                                return;
                        case 'ig':
                                $this->show_instagram();
                                return;
                }
        }

        function input($input)
        {
                $current = $this->options;

                if(isset($input['fb']['remove_token']) && $input['fb']['remove_token'] === $current['fb']['token']){
                        $input['fb']['token'] = '';
                        unset($input['fb']['remove_token']);
                }


                if(isset($input['fb']) && !empty($input['fb']))
                        foreach($input['fb'] as $key => $value)
                                $current['fb'][$key] = $value;

                if(isset($input['twr']) && !empty($input['twr']))
                        foreach($input['twr'] as $key => $value)
                                $current['twr'][$key] = $value;

                if(isset($input['ig']) && !empty($input['ig']))
                        foreach($input['ig'] as $key => $value)
                                $current['ig'][$key] = $value;

                if(isset($input['main']) && !empty($input['main']))
                        foreach($input['main'] as $key => $value)
                                $current['main'][$key] = $value;

                return $current;
        }

        function show_main()
        {
                $settings_field = [
                        'value' => $this->options['main']['time_expired_cache'],
                        'name' => 'xs_options_socials[main][time_expired_cache]',
                        'max' => 9999999999,
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Cache time live (minutes):',
                        'xs_framework::create_input_number',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );
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
                        $settings_field = [
                                'value' => $this->options['fb']['token'],
                                'name' => 'xs_options_socials[fb][remove_token]',
                                'text' => 'Remove Access Token',
                                'echo' => TRUE
                        ];

                        add_settings_field(
                                $settings_field['name'],
                                'Remove Access Token:',
                                'xs_framework::create_button',
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

                global $xs_socials_plugin;
                $url = $xs_socials_plugin->facebook_url($this->options['fb']['call']);

                $settings_field = [
                        'name' => 'link_facebook',
                        'href' => $url,
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

        function show_instagram()
        {
                $settings_field = [
                        'value' => $this->options['ig']['id_facebook_page'],
                        'name' => 'xs_options_socials[ig][id_facebook_page]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Facebook Page ID:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );
                if(empty($this->options['ig']['id_facebook_page']))
                        return;

                $ig = new Facebook\Facebook([
                        'app_id' => $this->options['fb']['appid'],
                        'app_secret' => $this->options['fb']['secret'],
                        'default_graph_version' => 'v3.2',
                ]);

                $resp = $ig->get(
                        '/'.$this->options['ig']['id_facebook_page'].'?fields=instagram_business_account',
                        $this->options['fb']['token']
                );

                $id_user = $resp->getGraphNode()->asArray();
                $id_user = $id_user['instagram_business_account']['id'];

                $settings_field = [
                        'value' => $id_user,
                        'name' => 'xs_options_socials[ig][id_business_account]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'Instagram business account ID:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );
        }
}

$xs_socials_options = new xs_socials_options;

endif;

?>
