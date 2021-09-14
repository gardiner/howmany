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


define("HM_VERSION", "0.1.0");
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
    protected $days_limit = 14;

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
        //only init resources on correct view
        $current_screen = function_exists('get_current_screen') ? get_current_screen() : false;
        if (!$current_screen || $current_screen->id != 'toplevel_page_hm_overview') {
            return;
        }
        wp_enqueue_style('howmany_css', HM_URL . '/css/howmany.css');
        wp_enqueue_script('howmany_js', HM_URL . '/js/howmany.all.js');
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
            "days_limit" => $this->days_limit,
        ));
        include('views/adminpage.html');
    }

    public function api() {
        $db = new HMDatabase();

        $endpoint = isset($_REQUEST['endpoint']) ? $_REQUEST['endpoint'] : false;
        $view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '%';
        $referer = isset($_REQUEST['referer']) ? $_REQUEST['referer'] : '%';
        $limit = time() - ($this->days_limit * 24 * 60 * 60); //backlog
        switch ($endpoint) {
            case 'views':
                $result = array(
                    "stats" => $db->load_all_extended('count(*) total', HM_LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s', array($limit, $view, $referer))[0],
                    "views" => $db->load_all_extended('l.url, count(l.id) count', HM_LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY l.url ORDER BY count DESC', array($limit, $view, $referer)),
                    "timeline" => $db->load_all_extended('min(l.time) starttime, floor(l.time / (60*60*24)) * (60*60*24) day, count(*) views', HM_LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY day', array($limit, $view, $referer)),
                );
                break;
            case 'visits':
                $result = array(
                    /*
                    "visits" => $db->load_all_extended('visit, count(id) views, min(time) starttime, max(time) endtime, max(time)-min(time) duration, floor(time/(60*60*24))*(60*60*24) day', HM_LOGTABLENAME, 'TRUE GROUP BY visit'),
                    */
                    "stats" => $db->load_all_extended('count(distinct visit) total', HM_LOGTABLENAME . ' l', 'l.time > %d', array($limit))[0],
                    "timeline" => $db->load_all_extended('count(v.visit) count, v.day' ,'(SELECT l.visit, floor(l.time / (60*60*24)) * (60*60*24) day FROM ' . HM_LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) v', 'TRUE GROUP BY v.day', array($limit)),
                    "entryurls" => $db->load_all_extended('count(visit) count, entryurl', '(SELECT l.visit, substring_index(group_concat(l.url ORDER BY l.time ASC SEPARATOR \'\n\'), \'\n\', 1) entryurl FROM ' . HM_LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) entryurls', 'TRUE GROUP BY entryurl ORDER BY count DESC', array($limit)),
                    "exiturls" => $db->load_all_extended('count(visit) count, exiturl', '(SELECT l.visit, substring_index(group_concat(l.url ORDER BY l.time DESC SEPARATOR \'\n\'), \'\n\', 1) exiturl FROM ' . HM_LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) exiturls', 'TRUE GROUP BY exiturl ORDER BY count DESC', array($limit)),
                    "views" => $db->load_all_extended('viewcount, count(viewcount) count', '(SELECT l.visit, count(l.url) viewcount FROM ' . HM_LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) viewcounts', 'TRUE GROUP BY viewcount LIMIT 15', array($limit)),
                    "durations" => $db->load_all_extended('duration, count(duration) count', '(SELECT l.visit, (max(l.time)-min(l.time)) duration FROM ' . HM_LOGTABLENAME . ' l WHERE l.time > %d GROUP BY visit) durations', 'TRUE GROUP BY duration LIMIT 15', array($limit)),
                );
                break;
            case 'useragents':
                $result = array(
                    "stats" => $db->load_all_extended('count(*) total', HM_LOGTABLENAME . ' l', 'l.time > %d AND url LIKE %s AND referer LIKE %s', array($limit, $view, $referer))[0],
                    "useragents" => $db->load_all_extended('l.useragent, count(l.id) count', HM_LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY l.useragent ORDER BY count DESC', array($limit, $view, $referer)),
                );
                break;
            case 'referers':
                $result = array(
                    "referers" => $db->load_all_extended('l.referer, count(l.id) count', HM_LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY l.referer ORDER BY count DESC', array($limit, $view, $referer)),
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

        $url = $_SERVER['REQUEST_URI'];

        if (preg_match("/^\/robots\.txt/i", $url) ||
            preg_match("/^\/sitemap\.xml/i",  $url) ||
            preg_match("/^\/wp-sitemap/i",  $url) ||
            preg_match("/^\/wp-admin/i", $url) ||
            preg_match("/^\/wp-json/i", $url)) {
            return;
        }

        $now = time();
        $fingerprint = $this->generate_fingerprint($_SERVER);
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
                $db->query('CREATE TABLE ' . HM_LOGTABLENAME . ' (id bigint(20) PRIMARY KEY AUTO_INCREMENT, time int, fingerprint varchar(10), url varchar(4096), referer varchar(4096), useragent varchar(4096), visit int)');
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

