<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://themesgrove.com/
 * @since      1.0.0
 *
 * @package    MailScout
 * @subpackage MailScout/admin
 */

use MS\Services\GMailService;
use MS\Services\URLService;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MailScout
 * @subpackage MailScout/admin
 * @author     https://themesgrove.com/
 */
class MailScout_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * The slug for main menu
     *
     * @since   1.0.0
     * @access private
     * @var string $main_menu_slug The slug for the main menu item
     */

    private $messages = [];

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function mailscout_google_fonts_url()
    {
        $font_url = '';
        $font_url = add_query_arg('family', urlencode("Montserrat:600,700|Open+Sans:400,600"), "//fonts.googleapis.com/css");
        return $font_url;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook)
    {
        if (URLService::isMSMenuPage()) {
            wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css', array(),
                $this->version, 'all');

            wp_enqueue_style('glyphyicon', plugin_dir_url(__FILE__) . 'css/glyphyicon.css', array(),
                $this->version, 'all');

            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ms-admin.css', array(),
                $this->version, 'all');

            wp_enqueue_style('ms-google-fonts', $this->mailscout_google_fonts_url(), array(), '1.0');

            wp_enqueue_style($this->plugin_name . '_datetimepicker', plugin_dir_url(__FILE__) . 'css/bootstrap-datetimepicker.min.css', array(),
                $this->version, 'all');

            if (!function_exists('mailscout_hide_all_plugin_notification')) {
                function mailscout_hide_all_plugin_notification()
                {
                    if (is_super_admin()) {
                        remove_all_actions('admin_notices');
                    }
                }
            }
            add_action('admin_head', 'mailscout_hide_all_plugin_notification', 1);
            add_filter('admin_footer_text', '__return_empty_string', 11);
            add_filter('update_footer', '__return_empty_string', 11);

        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        if(file_exists(GMailService::AuthFile())){
            $read_file = json_decode( file_get_contents( GMailService::AuthFile() ), true );
            if ( $read_file ) {

                $client_secret          = array_key_exists( 'client_secret', $read_file['web'] );
                $client_redirect_uris   = array_key_exists( 'redirect_uris', $read_file['web'] );
                $site_main_redirect_url = GMailService::getRedirectUri();
                if($client_redirect_uris){
                    $redirectUriMatched     = in_array( $site_main_redirect_url, $read_file['web']['redirect_uris'] );
                }else{
                    $redirectUriMatched = false;
                }
                if ($client_secret && $redirectUriMatched){
                    remove_submenu_page( 'mailscout_plugin', 'ms_installation' );
                }
            }
        }

        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nextNonce' => wp_create_nonce('myajax-next-nonce'),
            'pluginUrl' => plugin_dir_url(__DIR__),
            'redirectUrl' => GMailService::getRedirectUri(),
            'pages' => URLService::AllMenuPageURL(),
            'gmail_auth_url' => \MS\Listeners\URLRewriteListener::getOAuthRedirectURL(),
        );

        // region common
        /**
         * No need to load jquery
         * wp_enqueue_script('_jquery', plugin_dir_url(__FILE__) . 'js/jquery.min.js', array(), $this->version, false);
         */

        // manifest for loader
        wp_enqueue_script($this->plugin_name . '_manifest', plugin_dir_url(__FILE__) . 'js/manifest.js',
            array('jquery'),
            $this->version, false);
        // vendor package
        wp_enqueue_script($this->plugin_name . '_vendor', plugin_dir_url(__FILE__) . 'js/vendor.js',
            array('jquery'),
            $this->version, false);

        // popperjs
        wp_enqueue_script('popperjs', plugin_dir_url(__FILE__) . 'js/popper.min.js', array('jquery'),
            $this->version, false);
        // bootstrap
        wp_enqueue_script('bootstrap', plugin_dir_url(__FILE__) . 'js/bootstrap.min.js', array('jquery'),
            $this->version, false);

        wp_enqueue_script($this->plugin_name . '_moment',
            plugin_dir_url(__FILE__) . 'js/moment.min.js', array('jquery'),
            $this->version, false);

        // endregion

        // dashboard
        if (URLService::isDashboard()) {
            wp_enqueue_script($this->plugin_name . '_dashboard', plugin_dir_url(__FILE__) . 'js/ms-dashboard.js', array('jquery'),
                $this->version, false);
        }

        // installation
        if (URLService::isPage(URLService::PAGE_INSTALLATION)) {
            wp_enqueue_script($this->plugin_name . '_installation', plugin_dir_url(__FILE__) . 'js/ms-installation.js', array('jquery'),
                $this->version, false);
        }

        // campaign form
        if (URLService::isPage(URLService::PAGE_CAMPAIGN_FORM)) {
            wp_enqueue_editor();

            wp_enqueue_script($this->plugin_name . '_datetimepicker',
                plugin_dir_url(__FILE__) . 'js/bootstrap-datetimepicker.min.js', array('jquery'),
                $this->version, false);

            wp_enqueue_script($this->plugin_name . '_campaign_form',
                plugin_dir_url(__FILE__) . 'js/ms-campaign-form.js', array('jquery'),
                $this->version, false);
        }

        // campaigns
        if (URLService::isPage(URLService::PAGE_CAMPAIGNS)) {
            wp_enqueue_script($this->plugin_name . '_campaigns', plugin_dir_url(__FILE__) . 'js/ms-campaigns.js',
                array('jquery'),
                $this->version, false);
            $params['campaign_form'] = URLService::GetURL(URLService::PAGE_CAMPAIGN_FORM) . '&campaign_id=';
        }

        // mail accounts
        if (URLService::isPage(URLService::PAGE_MAIL_ACCOUNTS)) {
            wp_enqueue_script($this->plugin_name . '_mail_accounts',
                plugin_dir_url(__FILE__) . 'js/ms-mail-accounts.js', array('jquery'),
                $this->version, false);
        }

        // subscribers
        if (URLService::isPage(URLService::PAGE_SUBSCRIBERS)) {
            wp_enqueue_script($this->plugin_name . '_subscribers', plugin_dir_url(__FILE__) . 'js/ms-subscribers.js',
                array('jquery'),
                $this->version, false);
        }

        // Settings
        if (URLService::isPage(URLService::PAGE_SETTINGS)) {
            wp_enqueue_script($this->plugin_name . '_settings', plugin_dir_url(__FILE__) . 'js/ms-settings.js',
                array('jquery'),
                $this->version, false);

            if (file_exists(GMailService::AuthFile())) {
                $this->messages[] = 'A google credentials file already exists.';
            }
        }

        // Help
        if (URLService::isPage(URLService::PAGE_HELP)) {
            wp_enqueue_script($this->plugin_name . '_help', plugin_dir_url(__FILE__) . 'js/ms-help.js',
                array('jquery'),
                $this->version, false);

            if (file_exists(GMailService::AuthFile())) {
                $this->messages[] = 'A google credentials file already exists.';
            }
        }

        // About
        if (URLService::isPage(URLService::PAGE_ABOUT)) {
            wp_enqueue_script($this->plugin_name . '_about', plugin_dir_url(__FILE__) . 'js/ms-about.js',
                array('jquery'),
                $this->version, false);
        }

        // if ( ! file_exists( GMailService::AuthFile() ) && ! URLService::isPage( URLService::PAGE_SETTINGS ) ) {
        // 	$this->messages[] = 'Google Authentication credentials file is not uploaded. Please upload it from settings page.';
        // }

        $messages = array_merge(
            array('messages' => $this->messages),
            $params
        );

        wp_enqueue_script($this->plugin_name . '_manifest');
        wp_localize_script(
            $this->plugin_name . '_manifest',
            'MS_Ajax',
            $messages
        );
    }

    /**
     * Register Admin Menu
     *
     * @since   1.0.0
     */
    public function admin_menu()
    {
        add_menu_page('MailScout', 'MailScout', 'manage_options', URLService::MAIN_MENU_SLUG, array(
            $this,
            'admin_index'
        ),
            'dashicons-email-alt', 9999);
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Dashboard', 'Dashboard', 'manage_options', URLService::MAIN_MENU_SLUG,
            array($this, 'admin_index'));

        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Installation', 'Installation', 'manage_options', URLService::PAGE_INSTALLATION,
            array($this, 'admin_installation'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Campaigns', 'Campaigns', 'manage_options', URLService::PAGE_CAMPAIGNS,
            array($this, 'admin_campaigns'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'New Campaign', 'New Campaign', 'manage_options', URLService::PAGE_CAMPAIGN_FORM,
            array($this, 'admin_new_campaign'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Mail Accounts', 'Mail Accounts', 'manage_options', URLService::PAGE_MAIL_ACCOUNTS,
            array($this, 'admin_mail_accounts'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Subscribers', 'Subscribers', 'manage_options', URLService::PAGE_SUBSCRIBERS,
            array($this, 'admin_subscribers'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Settings', 'Settings', 'manage_options', URLService::PAGE_SETTINGS,
            array($this, 'admin_settings'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'Help', 'Help', 'manage_options', URLService::PAGE_HELP,
            array($this, 'admin_help'));
        add_submenu_page(URLService::MAIN_MENU_SLUG, 'About', 'About', 'manage_options', URLService::PAGE_ABOUT,
            array($this, 'admin_about'));
    }

    /**
     * Show admin index page
     *
     * @since   1.0.0
     */
    public function admin_index()
    {
        echo "<div id=\"ms-dashboard\"></div>";
    }

    /**
     * Show admin index page
     *
     * @since   1.0.0
     */
    public function admin_installation()
    {
        echo "<div id=\"ms-installation\"></div>";
    }

    /**
     * Mail Accounts Page
     *
     * @since   1.0.0
     */
    public function admin_mail_accounts()
    {
        echo "<div id=\"ms-mail-accounts\"></div>";
    }

    /**
     * Campaign Form
     *
     * @since   1.0.0
     */
    public function admin_new_campaign()
    {
        echo "<div id=\"ms-campaign-form\"</div>";
    }

    /**
     * Show admin campaigns list
     *
     * @since   1.0.0
     */
    public function admin_campaigns()
    {
        echo "<div id=\"ms-campaigns\"></div>";
    }

    /**
     * Show admin subscribers page
     *
     * @since   1.0.0
     */
    public function admin_subscribers()
    {
        echo "<div id=\"ms-subscribers\"></div>";
    }

    /**
     * Show admin settings page
     *
     * @since   1.0.0
     */
    public function admin_settings()
    {
        echo "<div id=\"ms-settings\"></div>";
    }

    /**
     * Show admin help page
     *
     * @since   1.0.0
     */
    public function admin_help()
    {
        echo "<div id=\"ms-help\"></div>";
    }

    /**
     * Show admin about page
     *
     * @since   1.0.0
     */
    public function admin_about()
    {
        echo "<div id=\"ms-about\"></div>";
    }

    /**
     * Handles ajax requests
     *
     * @since   1.0.0
     * @throws Exception
     */
    public function request_handler()
    {
        (new \MS\MailScout())->run();
        wp_die();
    }
}
