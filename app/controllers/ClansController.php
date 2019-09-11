<?php
use Phalcon\Cache\Backend\File  as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;

class ClansController extends \Phalcon\Mvc\Controller {

    public function indexAction() {
        if ($this->request->isPost()) {
            $clan_name = $this->request->getPost("search", "string");
            $clan_name = htmlspecialchars(str_replace(" ", "+", $clan_name));
            return $this->response->redirect("clans/info/".$clan_name);
        }
        return true;
    }

    public function infoAction($clanName) {
        $csv_data = $this->fetchCsv($this->filter->sanitize($clanName, "string"));

        if (!$csv_data) {
            $this->dispatcher->forward([
                'controller' => 'errors',
                'action' => 'show404'
            ]);
            return false;
        }

        $cache = new BackFile(new FrontData(['lifetime' => 3600]), [ 'cacheDir' => '../app/compiled/clans/' ]);

        $cache_key = str_replace(" ", "_", $clanName).'.cache';
        $clanData = $cache->get($cache_key);

        if (!$clanData) {
            $clanData = $this->parse_csv($csv_data);
            $cache->save($cache_key, $clanData);
        }

        $total_xp = 0;
        $total_kills = 0;
        $ranks = [];

        for ($i = 1; $i < count($clanData) - 1; $i++) {
            $total_xp += $clanData[$i][2];
            $total_kills += $clanData[$i][3];
            if (!array_key_exists($clanData[$i][1], $ranks)) {
                $ranks[$clanData[$i][1]] = 0;
            }
            $ranks[$clanData[$i][1]] += 1;
        }

        $this->view->total_xp = $total_xp;
        $this->view->ranks = $ranks;
        $this->view->totalKills = $total_kills;
        $this->view->clan = $clanData;
        $this->view->name = $clanName;
        $this->view->totalMembers = count($clanData) - 2;
        return true;
    }

    public function fetchCsv($clan) {
        $url = "http://services.runescape.com/m=clan-hiscores/members_lite.ws?clanName=$clan";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_NOBODY, FALSE); // remove body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CONNECTION_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode != '200' ? null : $response;
    }

    function parse_csv ($csv_string, $delimiter = ",", $skip_empty_lines = true, $trim_fields = true) {
        $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
        $enc = preg_replace_callback(
            '/"(.*?)"/s',
            function ($field) {
                return urlencode(utf8_encode($field[1]));
            },
            $enc
        );
        $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
        return array_map(
            function ($line) use ($delimiter, $trim_fields) {
                $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
                return array_map(
                    function ($field) {
                        return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
                    },
                    $fields
                );
            },
            $lines
        );
    }


}