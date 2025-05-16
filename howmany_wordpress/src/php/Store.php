<?php

namespace OleTrenner\HowMany;


class Store {
    const DBVERSION = 3;
    const LOGTABLENAME = 'howmany_log';
    const CACHETABLENAME = 'howmany_cache';

    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getValue(string $key, string $slot): mixed
    {
        $result = $this->db->load(static::CACHETABLENAME, 'measurement=%s AND slot=%s', [$key, $slot]);
        if (!$result) {
            return null;
        }
        return json_decode($result->value, true);
    }

    public function storeValue(string $key, string $slot, mixed $value)
    {
        $this->db->query('DELETE FROM ' . static::CACHETABLENAME .
            ' WHERE measurement=%s AND slot=%s', [$key, $slot]);
        $this->db->insert(static::CACHETABLENAME, [
            'measurement' => $key,
            'slot' => $slot,
            'value' => json_encode($value),
        ]);
    }

    public function check_schema() {
        $dbversion = get_option('hm_dbversion', 0);
        $currentversion = self::DBVERSION;

        if ($dbversion != $currentversion) {
            if ($dbversion < 3) {
                //add cache table
                $this->db->query('CREATE TABLE ' . self::CACHETABLENAME . ' (' .
                                'id bigint(20) PRIMARY KEY AUTO_INCREMENT,' .
                                'measurement varchar(1000),' .
                                'slot varchar(1000),' .
                                'value JSON' .
                           ')');
            }
            if ($dbversion < 2) {
                //add missing visit column and populate it with values
                $this->db->query('ALTER TABLE ' . self::LOGTABLENAME . ' ADD visit int');
                $this->regenerate_visits();
            }
            if ($dbversion < 1) {
                $this->db->query('CREATE TABLE ' . self::LOGTABLENAME . ' (' .
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
        $views = $this->db->load_all(self::LOGTABLENAME . ' l', 'l.visit IS NULL ORDER BY l.time');
        foreach ($views as $view) {
            $result = $this->db->query('SELECT COALESCE(' .
                                    '(SELECT MAX(l.visit) FROM ' . self::LOGTABLENAME . ' l WHERE l.fingerprint=%s AND %d-l.time < %d),' .
                                    '(SELECT MAX(ll.visit)+1 FROM ' . self::LOGTABLENAME . ' ll),
                                    1) visit',
                                array($view->fingerprint, $view->time, self::MAXVISITLENGTH));
            $visit = $result[0]->visit;
            $this->db->query('UPDATE ' . self::LOGTABLENAME . ' l SET l.visit=%d WHERE l.id=%d', array($visit, $view->id));
        }
    }
}