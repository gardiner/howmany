<?php
/*
Plugin Name: HowMany
Plugin URI: ...
Description: Simple Website Statistics
Version: 0.0.3
Author: Ole Trenner
Author URI: http://www.3dbits.de
License: custom
*/


define("HM_VERSION", "0.0.3");
define("HM_DBVERSION", 1);

define("HM_BASE", basename(dirname(__FILE__)));
define("HM_URL", WP_PLUGIN_URL . "/" . HM_BASE);
define("HM_PATH", WP_PLUGIN_DIR . "/" . HM_BASE);

define('HM_LOGTABLENAME', 'howmany_log');


require_once __DIR__ . '/vendor/autoload.php';


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
        wp_enqueue_style('howmany', HM_URL . '/css/howmany.css');
        wp_enqueue_script('requirejs', HM_URL . '/bower_components/requirejs/require.js', array('jquery'));
        wp_localize_script('requirejs', 'require', array(
            'baseUrl' => HM_URL,
            'deps'    => array(
                HM_URL . '/js/jquery.compat.js',
                HM_URL . '/js/howmany.js',
            )
        ));
    }

    public function init_menus() {
        add_menu_page('HowMany', 'HowMany', 'manage_options', 'hm_overview', array($this, 'render_adminpage'));
    }

    public function render_adminpage() {
        $this->check_schema();
        $options = json_encode(array(
            "servername" => $_SERVER['SERVER_NAME'],    //will be used to determine external and internal referers
            "apibase" => admin_url("admin-ajax.php"),   //api request base url
            "default_data" => array(                    //will be send with each api request
                "action" => "hm_api",
            ),
        ));
        include('views/adminpage.html');
    }

    public function api() {
        $db = new HMDatabase();

        $endpoint = isset($_REQUEST['endpoint']) ? $_REQUEST['endpoint'] : false;
        switch ($endpoint) {
            case 'views':
                $result = array(
                    "views" => $db->load_all_extended('l.url, count(l.id) count', HM_LOGTABLENAME . ' l group by l.url order by count desc'),
                    "timeline" => $db->load_all_extended('min(l.time) starttime, floor(l.time / (60 * 60 * 24)) day, count(*) views', HM_LOGTABLENAME . ' l group by day'),
                );
                break;
            case 'useragents':
                $result = array(
                    "useragents" => $db->load_all_extended('l.useragent, count(l.id) count', HM_LOGTABLENAME . ' l group by l.useragent order by count desc'),
                );
                break;
            case 'referers':
                $result = array(
                    "referers" => $db->load_all_extended('l.referer, count(l.id) count', HM_LOGTABLENAME . ' l where l.referer is not null AND l.referer != "" group by l.referer order by count desc'),
                );
                break;

            default:
                $result = array();
                break;
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    /**
     * Track request.
     */
    public function track_request() {
        if (is_admin()) {
            return;
        }

        $now = time();
        $fingerprint = $this->generate_fingerprint($_SERVER);
        $url = $_SERVER['REQUEST_URI'];
        $referer = $_SERVER['HTTP_REFERER'];
        $ua = json_encode(parse_user_agent());

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
        $db = new HMDatabase();
        $dbversion = get_option('hm_dbversion', 0);
        $currentversion = HM_DBVERSION;
        if ($dbversion != $currentversion) {
            $db->query('CREATE TABLE ' . HM_LOGTABLENAME . ' (id bigint(20) PRIMARY KEY AUTO_INCREMENT, time int, fingerprint varchar(10), url varchar(4096), referer varchar(4096), useragent varchar(4096))');
            update_option('hm_dbversion', $currentversion);
        }
    }
}


$howmany = new HowMany();

