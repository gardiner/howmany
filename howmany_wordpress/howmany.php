<?php
/*
Plugin Name: HowMany
Plugin URI: ...
Description: Simple Website Statistics
Version: 0.1.0
Author: Ole Trenner
Author URI: http://www.3dbits.de
License: custom
*/


namespace OleTrenner\HowMany;

require_once __DIR__ . '/vendor/autoload.php';


class HowMany {
    const DBVERSION = 2;
    const LOGTABLENAME = 'howmany_log';
    const MAXVISITLENGTH = 60 * 60; //1 hour

    protected $BASE;
    protected $ROOT;
    protected $db;
    protected $api;

    public function __construct() {
        $this->ROOT = dirname(__FILE__) . '/';
        $this->BASE = get_bloginfo('url') . '/wp-content/plugins/howmany_wordpress/';

        $this->db = new Database();
        $this->api = new Api($this->db);

        if (function_exists('add_action')) {
            //backend functionality
            add_action('admin_enqueue_scripts', array($this, 'init_admin_resources'));
            add_action('admin_menu', array($this, 'init_menus'));
            add_action('wp_ajax_hm_api', array($this, 'api'));

            //hooking into wordpress to track requests
            add_action('init', array($this, 'track_request'));
        }
    }

    public function init_admin_resources() {
        //only init resources on correct view
        $current_screen = function_exists('get_current_screen') ? get_current_screen() : false;
        if (!$current_screen || $current_screen->id != 'toplevel_page_hm_overview') {
            return;
        }
        wp_enqueue_style('howmany_css', $this->url('css/howmany.css'));
        wp_enqueue_script('howmany_js', $this->url('js/howmany.all.js'));
    }

    public function init_menus() {
        add_menu_page('HowMany', 'HowMany', 'manage_options', 'hm_overview', array($this, 'render_adminpage'));
    }

    public function render_adminpage() {
        $info = get_plugin_data(__FILE__);
        $version = $info['Version'];

        $this->check_schema();
        $options = json_encode(array(
            "servername" => $_SERVER['SERVER_NAME'],    //will be used to determine external and internal referers
            "api" => array(
                "base" => admin_url("admin-ajax.php"),  //api request base url
                "default_data" => array(                //will be send with each api request
                    "action" => "hm_api",
                ),
            ),
            "days_limit" => $this->api->days_limit,
        ));
        include('views/adminpage.html');
    }

    public function api() {
        return $this->api->handle_request();
    }

    /**
     * Track request.
     */
    public function track_request() {
        if (is_admin()) {
            return;
        }

        $url = $_SERVER['REQUEST_URI'];

        if (preg_match("/^\/robots\.txt/i", $url) ||
            preg_match("/^\/sitemap\.xml/i",  $url) ||
            preg_match("/^\/wp-sitemap/i",  $url) ||
            preg_match("/^\/wp-cron/i", $url) ||
            preg_match("/^\/wp-admin/i", $url) ||
            preg_match("/^\/wp-login/i", $url) ||
            preg_match("/^\/wp-json/i", $url)) {
            return;
        }

        $now = time();
        $fingerprint = $this->generate_fingerprint($_SERVER);
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        try {
            $ua = json_encode(parse_user_agent());
        } catch (Exception $e) {
            $ua = '{"_status": "unknown user agent"}';
        }

        $db = new Database();
        $db->query('INSERT INTO ' . self::LOGTABLENAME . ' ' .
                    '(time, fingerprint, url, referer, useragent, visit) ' .
                    'VALUES (' .
                        '%d, %s, %s, %s, %s,' .
                        '(SELECT COALESCE(' .
                            '(SELECT MAX(l.visit) FROM ' . self::LOGTABLENAME . ' l WHERE l.fingerprint=%s AND %d-l.time < %d),' .
                            '(SELECT MAX(ll.visit)+1 FROM ' . self::LOGTABLENAME . ' ll),
                            1)' .
                        ')' .
                    ')',
                    array($now, $fingerprint, $url, $referer, $ua, $fingerprint, $now, self::MAXVISITLENGTH));
    }

    public function url($path) {
        return $this->BASE . $path;
    }

    public function path($path) {
        return $this->ROOT . $path;
    }

    /**
     * Generates a more or less unique fingerprint from request data
     * (first three octets of ip address + user agent string).
     * A weak checksum is used to reduce uniqueness of fingerprints.
     */
    protected function generate_fingerprint($data) {
        $parts = array(
            implode(".", explode(".", $data['REMOTE_ADDR'], -1)),
            $data['HTTP_USER_AGENT'],
        );
        return hash('crc32', implode("", $parts), false);
    }

    protected function check_schema() {
        $db = new Database();
        $dbversion = get_option('hm_dbversion', 0);
        $currentversion = self::DBVERSION;

        if ($dbversion != $currentversion) {
            if ($dbversion == 1) {
                //add missing visit column and populate it with values
                $db->query('ALTER TABLE ' . self::LOGTABLENAME . ' ADD visit int');
                $this->regenerate_visits();
            } else if ($dbversion != $currentversion) {
                $db->query('CREATE TABLE ' . self::LOGTABLENAME . ' (' .
                                'id bigint(20) PRIMARY KEY AUTO_INCREMENT,' .
                                'time int,' .
                                'fingerprint varchar(10),' .
                                'url varchar(4096),' .
                                'referer varchar(4096),' .
                                'useragent varchar(4096),' .
                                'visit int' .
                           ')');
            }
            update_option('hm_dbversion', $currentversion);
        }
    }

    protected function regenerate_visits() {
        $db = new Database();
        $views = $db->load_all(self::LOGTABLENAME . ' l', 'l.visit IS NULL ORDER BY l.time');
        foreach ($views as $view) {
            $result = $db->query('SELECT COALESCE(' .
                                    '(SELECT MAX(l.visit) FROM ' . self::LOGTABLENAME . ' l WHERE l.fingerprint=%s AND %d-l.time < %d),' .
                                    '(SELECT MAX(ll.visit)+1 FROM ' . self::LOGTABLENAME . ' ll),
                                    1) visit',
                                array($view->fingerprint, $view->time, self::MAXVISITLENGTH));
            $visit = $result[0]->visit;
            $db->query('UPDATE ' . self::LOGTABLENAME . ' l SET l.visit=%d WHERE l.id=%d', array($visit, $view->id));
        }
    }
}


$howmany = new HowMany();

