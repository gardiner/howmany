<?php
/*
Plugin Name: HowMany
Plugin URI: ...
Description: Simple Website Statistics
Version: 0.0.1
Author: Ole Trenner
Author URI: http://www.3dbits.de
License: custom
*/


define("HM_VERSION", "0.0.1");
define("HM_DBVERSION", 1);

define("HM_BASE", basename(dirname(__FILE__)));
define("HM_URL", WP_PLUGIN_URL . "/" . HM_BASE);
define("HM_PATH", WP_PLUGIN_DIR . "/" . HM_BASE);

define('HM_LOGTABLENAME', 'howmany_log');


//autoloading all includes
foreach (glob(HM_PATH . "/inc/*.php") as $filename) {
    require_once($filename);
}


class HowMany {
    public function __construct() {
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
        wp_enqueue_style('howmany-css', HM_URL . '/css/howmany.css');
        wp_enqueue_script('howmany-js', HM_URL . '/js/howmany.js', array('jquery'));
    }

    public function init_menus() {
        add_menu_page('HowMany', 'HowMany', 'manage_options', 'hm_overview', array($this, 'render_adminpage'));
    }

    public function render_adminpage() {
        $this->check_schema();
        include('partials/adminpage.php');
    }

    public function api() {
        $db = new HMDatabase();
        $log = $db->load_all_extended('l.url, count(l.id) count', HM_LOGTABLENAME . ' l group by l.url order by count desc');
        header('Content-Type: application/json');
        echo json_encode(array("result" => $log));
        exit;
    }

    /**
     * Track request.
     */
    public function track_request() {
        $now = time();
        $fingerprint = $this->generate_fingerprint($_SERVER);
        $url = $_SERVER['REQUEST_URI'];
        $referer = $_SERVER['HTTP_REFERER'];
        $ua = $_SERVER['HTTP_USER_AGENT'];

        $db = new HMDatabase();
        $db->insert(HM_LOGTABLENAME, array(
            "time" => $now,
            "fingerprint" => $fingerprint,
            "url" => $url,
            "referer" => $referer,
            "useragent" => $ua,
        ), array('%d', '%s', '%s', '%s', '%s'));
    }


    /**
     * generates a more or less unique fingerprint from request data
     */
    protected function generate_fingerprint($data) {
        $parts = array(
            $data['REMOTE_ADDR'],
            $data['HTTP_USER_AGENT'],
        );
        return sha1(implode("", $parts), false);
    }

    protected function format_date($timestamp, $include_time=false) {
        return date($include_time ? 'd.m.y G:i:s' : 'd.m.Y', $timestamp);
    }

    protected function check_schema() {
        $db = new HMDatabase();
        $dbversion = get_option('hm_dbversion', 0);
        $currentversion = HM_DBVERSION;
        if ($dbversion != $currentversion) {
            $db->query('CREATE TABLE ' . HM_LOGTABLENAME . ' (id bigint(20) PRIMARY KEY AUTO_INCREMENT, time int, fingerprint varchar(40), url varchar(4096), referer varchar(4096), useragent varchar(4096))');
            update_option('hm_dbversion', $currentversion);
        }
    }
}


$howmany = new HowMany();

