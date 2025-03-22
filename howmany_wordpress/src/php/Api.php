<?php

namespace OleTrenner\Howmany;


class Api {
    public $days_limit = 14;

    public function __construct(
        protected Database $db,
    )
    {
    }

    public function handle_request()
    {
        $endpoint = $_REQUEST['endpoint'] ?? false;
        $method = 'handle_' . $endpoint;

        $view = $_REQUEST['view'] ?? '%';
        $referer = $_REQUEST['referer'] ?? '%';
        $limit = time() - ($this->days_limit * 24 * 60 * 60); //backlog
        try {
            if (method_exists($this, $method)) {
                $result = $this->$method($view, $referer, $limit);
            } else {
                throw new \Exception('endpoint not found');
            }
        } catch(\Exception $e) {
            http_response_code(500);
            $result = [
                'error' => $e->getMessage(),
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    protected function handle_views($view, $referer, $limit)
    {
        return [
            "stats" => $this->db->load_all_extended('count(*) total', Store::LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s', array($limit, $view, $referer))[0],
            "views" => $this->db->load_all_extended('l.url, count(l.id) count', Store::LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY l.url ORDER BY count DESC', array($limit, $view, $referer)),
            "timeline" => $this->db->load_all_extended('min(l.time) starttime, floor(l.time / (60*60*24)) * (60*60*24) day, count(*) views', Store::LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY day', array($limit, $view, $referer)),
        ];
    }

    protected function handle_visits($view, $referer, $limit)
    {
        return [
            /*
            "visits" => $this->db->load_all_extended('visit, count(id) views, min(time) starttime, max(time) endtime, max(time)-min(time) duration, floor(time/(60*60*24))*(60*60*24) day', Store::LOGTABLENAME, 'TRUE GROUP BY visit'),
            */
            "stats" => $this->db->load_all_extended('count(distinct visit) total', Store::LOGTABLENAME . ' l', 'l.time > %d', array($limit))[0],
            "timeline" => $this->db->load_all_extended('count(v.visit) count, v.day' ,'(SELECT l.visit, floor(l.time / (60*60*24)) * (60*60*24) day FROM ' . Store::LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) v', 'TRUE GROUP BY v.day', array($limit)),
            "entryurls" => $this->db->load_all_extended('count(visit) count, entryurl', '(SELECT l.visit, substring_index(group_concat(l.url ORDER BY l.time ASC SEPARATOR \'\n\'), \'\n\', 1) entryurl FROM ' . Store::LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) entryurls', 'TRUE GROUP BY entryurl ORDER BY count DESC', array($limit)),
            "exiturls" => $this->db->load_all_extended('count(visit) count, exiturl', '(SELECT l.visit, substring_index(group_concat(l.url ORDER BY l.time DESC SEPARATOR \'\n\'), \'\n\', 1) exiturl FROM ' . Store::LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) exiturls', 'TRUE GROUP BY exiturl ORDER BY count DESC', array($limit)),
            "views" => $this->db->load_all_extended('viewcount, count(viewcount) count', '(SELECT l.visit, count(l.url) viewcount FROM ' . Store::LOGTABLENAME . ' l WHERE l.time > %d GROUP BY l.visit) viewcounts', 'TRUE GROUP BY viewcount ORDER BY viewcount LIMIT 15', array($limit)),
            "durations" => $this->db->load_all_extended('duration, count(duration) count', '(SELECT l.visit, (max(l.time)-min(l.time)) duration FROM ' . Store::LOGTABLENAME . ' l WHERE l.time > %d GROUP BY visit) durations', 'TRUE GROUP BY duration ORDER BY duration LIMIT 15', array($limit)),
        ];
    }

    protected function handle_useragents($view, $referer, $limit)
    {
        return [
            "stats" => $this->db->load_all_extended('count(*) total', Store::LOGTABLENAME . ' l', 'l.time > %d AND url LIKE %s AND referer LIKE %s', array($limit, $view, $referer))[0],
            "useragents" => $this->db->load_all_extended('l.useragent, count(l.id) count', Store::LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY l.useragent ORDER BY count DESC', array($limit, $view, $referer)),
        ];
    }

    protected function handle_referers($view, $referer, $limit)
    {
        return [
            "referers" => $this->db->load_all_extended('l.referer, count(l.id) count', Store::LOGTABLENAME . ' l', 'l.time > %d AND l.url LIKE %s AND l.referer LIKE %s GROUP BY l.referer ORDER BY count DESC', array($limit, $view, $referer)),
        ];
    }
}