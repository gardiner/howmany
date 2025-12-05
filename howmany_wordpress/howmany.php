<?php
/*
Plugin Name: HowMany
Plugin URI: http://www.3dbits.de
Description: Simple Website Statistics
Version: 1.3.1
Author: Ole Trenner
Author URI: http://www.3dbits.de
License: custom
*/

namespace OleTrenner\HowMany;

use OleTrenner\HowMany\Measurements\ExternalReferers;
use OleTrenner\HowMany\Measurements\Urls;
use OleTrenner\HowMany\Measurements\UserAgents;
use OleTrenner\HowMany\Measurements\UserPlatforms;
use OleTrenner\HowMany\Measurements\Views;
use OleTrenner\HowMany\Measurements\VisitCounts;
use OleTrenner\HowMany\Measurements\VisitDurations;
use OleTrenner\HowMany\Measurements\Visits;

require_once __DIR__ . '/vendor/autoload.php';


class HowMany {
    const MAXVISITLENGTH = 60 * 60; //1 hour

    protected $BASE;
    protected $ROOT;

    protected $db;
    protected $store;
    protected $measurementService;
    protected $api;

    public function __construct() {
        $this->ROOT = dirname(__FILE__) . '/';
        $this->BASE = get_bloginfo('url') . '/wp-content/plugins/howmany_wordpress/';

        $this->db = new Database();
        $this->store = new Store($this->db);

        $measurements = [
            'views' => Views::class,
            'urls' => Urls::class,
            'visits' => Visits::class,
            'visitcounts' => VisitCounts::class,
            'visitdurations' => VisitDurations::class,
            'useragents' => UserAgents::class,
            'userplatforms' => UserPlatforms::class,
            'externalreferers' => ExternalReferers::class,
        ];

        $this->measurementService = new MeasurementService($measurements, $this->store, $this->db);
        $this->api = new Api($this->measurementService, $this->db);

        if (function_exists('add_action')) {
            //backend functionality
            add_action('admin_enqueue_scripts', array($this, 'init_admin_resources'));
            add_action('admin_menu', array($this, 'init_menus'));
            add_action('wp_ajax_hm_api', array($this->api, 'handle'));

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
        add_menu_page('HowMany', 'HowMany', 'manage_options', 'hm_overview', array($this, 'render_adminpage'), 'dashicons-chart-bar');
    }

    public function render_adminpage() {
        $info = get_plugin_data(__FILE__);
        $version = $info['Version'];

        $this->store->check_schema();
        $options = json_encode(array(
            "servername" => $_SERVER['SERVER_NAME'],    //will be used to determine external and internal referers
            "api" => array(
                "base" => add_query_arg(["action" => "hm_api"], admin_url("admin-ajax.php")),  //api request base url
            ),
            "days_limit" => $this->api->days_limit,
        ));
        include('views/adminpage.html');
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
            preg_match("/^\/feed/i", $url) ||
            preg_match("/^\/comments/i", $url) ||
            preg_match("/wp-json/i", $url) ||
            preg_match("/wp-includes/i", $url) ||
            preg_match("/xmlrpc.php/i", $url) ||
            preg_match("/^\/howmany_wordpress/i", $url)) {
            return;
        }

        $now = time();
        $fingerprint = $this->generate_fingerprint($_SERVER);
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        try {
            $ua = json_encode(parse_user_agent());
        } catch (\Exception $e) {
            $ua = '{"_status": "unknown user agent"}';
        }

        $db = new Database();
        try {
            $db->query('INSERT INTO ' . Store::LOGTABLENAME . ' ' .
                        '(time, fingerprint, url, referer, useragent, visit) ' .
                        'VALUES (' .
                            '%d, %s, %s, %s, %s,' .
                            '(SELECT COALESCE(' .
                                '(SELECT MAX(l.visit) FROM ' . Store::LOGTABLENAME . ' l WHERE l.fingerprint=%s AND %d-l.time < %d),' .
                                '(SELECT MAX(ll.visit)+1 FROM ' . Store::LOGTABLENAME . ' ll),
                                1)' .
                            ')' .
                        ')',
                        array($now, $fingerprint, $url, $referer, $ua, $fingerprint, $now, self::MAXVISITLENGTH));
        } catch (\Exception $e) {
            //ignored. hopefully does not happen too often.
        }
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
}


$howmany = new HowMany();

