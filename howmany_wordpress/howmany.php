<?php
/*
Plugin Name: HowMany
Plugin URI: ...
Description: Simple Website Statistics
Version: 0.0.4
Author: Ole Trenner
Author URI: http://www.3dbits.de
License: custom
*/


define("HM_VERSION", "0.0.4");
define("HM_DBVERSION", 2);

define("HM_BASE", basename(dirname(__FILE__)));
define("HM_URL", WP_PLUGIN_URL . "/" . HM_BASE);
define("HM_PATH", WP_PLUGIN_DIR . "/" . HM_BASE);

define('HM_LOGTABLENAME', 'howmany_log');
define('MAXVISITLENGTH', 60 * 60);          //1 hour


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
            "api" => array(
                "base" => admin_url("admin-ajax.php"),  //api request base url
                "default_data" => array(                //will be send with each api request
                    "action" => "hm_api",
                ),
            ),
        ));
        include('views/adminpage.html');
    }

    public function api() {
        $db = new HMDatabase();

        $endpoint = isset($_REQUEST['endpoint']) ? $_REQUEST['endpoint'] : false;
        $view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '%';
        $referer = isset($_REQUEST['referer']) ? $_REQUEST['referer'] : '%';
        switch ($endpoint) {
            case 'views':
                $result = array(
                    "views" => $db->load_all_extended('l.url, count(l.id) count', HM_LOGTABLENAME . ' l', 'l.url LIKE %s AND l.referer LIKE %s GROUP BY l.url ORDER BY count DESC', array($view, $referer)),
                    "timeline" => $db->load_all_extended('min(l.time) starttime, floor(l.time / (60*60*24)) * (60*60*24) day, count(*) views', HM_LOGTABLENAME . ' l', 'l.url LIKE %s AND l.referer LIKE %s GROUP BY day', array($view, $referer)),
                );
                break;
            case 'visits':
                $result = array(
                    /*
                    "visits" => $db->load_all_extended('visit, count(id) views, min(time) starttime, max(time) endtime, max(time)-min(time) duration, floor(time/(60*60*24))*(60*60*24) day', HM_LOGTABLENAME, 'TRUE GROUP BY visit'),
                    */
                    "timeline" => $db->load_all_extended('count(v.visit) count, v.day' ,'(SELECT l.visit, floor(l.time / (60*60*24)) * (60*60*24) day FROM ' . HM_LOGTABLENAME . ' l GROUP BY l.visit) v', 'TRUE GROUP BY v.day'),
                    "entryurls" => $db->load_all_extended('count(visit) count, entryurl', '(SELECT l.visit, substring_index(group_concat(l.url ORDER BY l.time ASC SEPARATOR \'\n\'), \'\n\', 1) entryurl FROM ' . HM_LOGTABLENAME . ' l GROUP BY l.visit) entryurls', 'TRUE GROUP BY entryurl ORDER BY count DESC'),
                    "exiturls" => $db->load_all_extended('count(visit) count, exiturl', '(SELECT l.visit, substring_index(group_concat(l.url ORDER BY l.time DESC SEPARATOR \'\n\'), \'\n\', 1) exiturl FROM ' . HM_LOGTABLENAME . ' l GROUP BY l.visit) exiturls', 'TRUE GROUP BY exiturl ORDER BY count DESC'),
                    "views" => $db->load_all_extended('viewcount, count(viewcount) count', '(SELECT l.visit, count(l.url) viewcount FROM ' . HM_LOGTABLENAME . ' l GROUP BY l.visit) viewcounts', 'TRUE GROUP BY viewcount'),
                    "durations" => $db->load_all_extended('duration, count(duration) count', '(SELECT l.visit, (max(l.time)-min(l.time)) duration FROM ' . HM_LOGTABLENAME . ' l GROUP BY visit) durations', 'TRUE GROUP BY duration'),
                );
                break;
            case 'useragents':
                $result = array(
                    "useragents" => $db->load_all_extended('l.useragent, count(l.id) count', HM_LOGTABLENAME . ' l', 'l.url LIKE %s AND l.referer LIKE %s GROUP BY l.useragent ORDER BY count DESC', array($view, $referer)),
                );
                break;
            case 'referers':
                $result = array(
                    "referers" => $db->load_all_extended('l.referer, count(l.id) count', HM_LOGTABLENAME . ' l', 'l.url LIKE %s AND l.referer LIKE %s AND l.referer IS NOT NULL AND l.referer != "" GROUP BY l.referer ORDER BY count DESC', array($view, $referer)),
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
        $db->query('INSERT INTO ' . HM_LOGTABLENAME . ' ' .
                    '(time, fingerprint, url, referer, useragent, visit) ' .
                    'VALUES (' .
                        '%d, %s, %s, %s, %s,' .
                        '(SELECT COALESCE(' .
                            '(SELECT MAX(l.visit) FROM ' . HM_LOGTABLENAME . ' l WHERE l.fingerprint=%s AND %d-l.time < %d),' .
                            '(SELECT MAX(ll.visit)+1 FROM ' . HM_LOGTABLENAME . ' ll),
                            1)' .
                        ')' .
                    ')',
                    array($now, $fingerprint, $url, $referer, $ua, $fingerprint, $now, MAXVISITLENGTH));
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
            if ($dbversion == 1) {
                //add missing visit column and populate it with values
                $db->query('ALTER TABLE ' . HM_LOGTABLENAME . ' ADD visit int');
                $this->regenerate_visits();
            } else if ($dbversion != $currentversion) {
                $db->query('CREATE TABLE ' . HM_LOGTABLENAME . ' (id bigint(20) PRIMARY KEY AUTO_INCREMENT, time int, fingerprint varchar(10), url varchar(4096), referer varchar(4096), useragent varchar(4096))');
            }
            update_option('hm_dbversion', $currentversion);
        }
    }

    protected function regenerate_visits() {
        $db = new HMDatabase();
        $views = $db->load_all(HM_LOGTABLENAME . ' l', 'l.visit IS NULL ORDER BY l.time');
        foreach ($views as $view) {
            $result = $db->query('SELECT COALESCE(' .
                                    '(SELECT MAX(l.visit) FROM ' . HM_LOGTABLENAME . ' l WHERE l.fingerprint=%s AND %d-l.time < %d),' .
                                    '(SELECT MAX(ll.visit)+1 FROM ' . HM_LOGTABLENAME . ' ll),
                                    1) visit',
                                array($view->fingerprint, $view->time, MAXVISITLENGTH));
            $visit = $result[0]->visit;
            $db->query('UPDATE ' . HM_LOGTABLENAME . ' l SET l.visit=%d WHERE l.id=%d', array($visit, $view->id));
        }
    }
}


$howmany = new HowMany();

