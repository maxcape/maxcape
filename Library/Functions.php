<?php

class Functions {

    public static $type = array(
        "rank"  => 0,
        "level" => 1,
        "exp"   => 2
    );

    public static $skills = array(
        "Overall" => 0,
        "Attack" => 1,
        "Defence" => 2,
        "Strength" => 3,
        "Constitution" => 4,
        "Ranged" => 5,
        "Prayer" => 6,
        "Magic" => 7,
        "Cooking" => 8,
        "Woodcutting" => 9,
        "Fletching" => 10,
        "Fishing" => 11,
        "Firemaking" => 12,
        "Crafting" => 13,
        "Smithing" => 14,
        "Mining" => 15,
        "Herblore" => 16,
        "Agility" => 17,
        "Thieving" => 18,
        "Slayer" => 19,
        "Farming" => 20,
        "Runecrafting" => 21,
        "Hunter" => 22,
        "Construction" => 23,
        "Summoning" => 24,
        "Dungeoneering" => 25,
        "Divination" => 26,
        "Invention" => 27
    );

    public static function debug($data) {
        echo "<pre class='text-white'>".json_encode($data, JSON_PRETTY_PRINT)."</pre>";
    }

    /**
     * @param $msg array
     */
    public static function println($msg) {
        echo json_encode($msg);
    }

    public static function getLastNDays($days, $format = 'n j'){
        date_default_timezone_set(timezone);

        $m  = date("m");
        $de = date("d");
        $y  = date("Y");

        $dateArray = array();

        for($i = 0; $i <= $days - 1; $i++){
            $dateArray[] = date($format, mktime(0,0,0,$m,($de-$i),$y));
        }

        return array_reverse($dateArray);
    }

    public static function getUserIP() {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } else if(filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

    public static function replace($search, $replace, $string) {
        return str_replace($search, $replace, $string);
    }

    public static function filter($item_list, $key, $value) {
        foreach ($item_list as $item) {
            if ($item[$key] == $value) {
                return $item;
            }
        }
        return null;
    }

    public static function mapArrayAsString($array, $key) {
        return implode(',', array_map(function ($item) use ($key) {
            return $item[$key];
        }, $array));
    }

    public static function startsWith($haystack, $needle) {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle) {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public function timeLeft($time) {
        $future = new DateTime(date('Y-m-d H:i:s', $time));
        $differ = $future->diff(new DateTime());
        $remain = $differ->format("%hh %im %ss");
        return $remain;
    }

    public static function elapsed( $ptime ) {
        $etime = time() - $ptime;

        if ( $etime < 1 ) {
            return '0 seconds - '.$etime;
        }

        $a = array(
            12 * 30 * 24 * 60 * 60 => 'year', 30 * 24 * 60 * 60 => 'month', 24 * 60 * 60 => 'day', 60 * 60 => 'hour', 60 => 'minute', 1 => 'second'
        );

        foreach ( $a as $secs => $str ) {
            $d = $etime / $secs;
            if ( $d >= 1 ) {
                $r = round( $d );
                return $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
            }
        }
    }

    public function modern_number_format($number){
        if ($number >= 1000) {
            return floor($number / 100) / 10 . "K";
        }
        return $number;
    }

    public function getFormattedName($rank, $name) {
        switch ($rank) {
            case "Developer":
                return '<span class="text-danger"><i class="fas fa-wrench mr-1"></i>'.$name.'</span>';
        }
        return $name;
    }

    public function getRankFormatted($rank) {
        switch ($rank) {
            case "Developer":
                return '<span class="text-danger"><i class="fas fa-wrench mr-1"></i>'.$rank.'</span>';
        }
        return '<span class="text-muted">'.$rank.'</span>';
    }

    public static function cleanupHtml($string) {
        $config = array(
            'indent'         => true,
            'output-xhtml'   => true,
            'wrap'           => 400
        );

       $tidy = new tidy;
      $tidy->parseString($string, $config, 'utf8');
       $tidy->cleanRepair();
        return $string;
    }

    public static function limit_string($string, $limit) {
        if (strlen($string) > $limit) {
            $string = substr($string, 0, $limit) . '...';
        }
        return self::cleanupHtml($string);
    }

    public static function getData($skill, $type, $userData) {
        $dataType = self::$type[$type];
        $skillId = self::$skills[$skill];
        $data = explode("\n", $userData);
        $skillData = explode(",", $data[$skillId]);
        return $skillData[$dataType];
    }

    public static function getVirtualLevel($skill, $xp) {
        $exp_table = $skill != "Invention" ? levels : inventionXPTable;

        if ($xp > $exp_table[count($exp_table) - 1]) {
            return 120;
        }

        $level = 1;

        for ($i = 0; $i < count($exp_table); $i++) {
            if ($xp < $exp_table[$i]) {
                $level = $i;
                break;
            }
        }

        return $level < 1 ? 1 : $level;
    }


    public static function getNextLevel($skill, $xp) {
        $exp = $skill != "Invention" ? levels : inventionXPTable;
        for ($i = 0; $i < count($exp); $i++) {
            if ($xp < $exp[$i]) {
                return $exp[$i];
            }
        }
        return 0;
    }

    public static function array_sort($array, $on, $order=SORT_ASC, $limit = null){

        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $limit == null ? $new_array : array_slice($array, 0, $limit);
    }

    public static function array_msort($array, $cols) {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_'.$k] = strtolower($row[$col]);
            }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\''.$col.'\'],'.$order.',';
        }
        $eval = substr($eval,0,-1).');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k,1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;
    }

    public static function getNeededFor($type, $user) {
        $totalLeft = 0;
        $max = 0;

        foreach(skills as $skill) {
            $skillData = $user[$skill];

            if ($skill == "Overall")
                continue;

            if ($type == "max") {
                $max = $skill == 'Invention' ? 36073511 : 13034431;
            } else if ($type == "120") {
                $max = $skill == 'Invention' ? 166253312 : 104273167;
            } else if ($type == "comp") {
                $max = $skill == "Overall" ? 5400000000 : 200000000;
            }

            $needed = min($skillData['exp'], $max);

            if ($needed < 0)
                continue;

            $totalLeft += $needed;
        }
        return $totalLeft;
    }

     public static function withinPercentage($first, $second, $percent)
    {
        $decimalPercent = ($percent / 100.0);
        $highRange = ($second * (1.0 + $decimalPercent));
        $lowRange = ($second * (1.0 - $decimalPercent));

        return $lowRange <= $first && $first <= $highRange;
    }

    public static function isSurvivalist($data) {
        
        if ($data['Agility']['level'] == 99 && $data['Hunter']['level'] == 99 && $data['Thieving']['level'] == 99 && $data['Slayer']['level'] >= 99) {
            return true;
        }
        return false;
    }

    public static function isNaturalist($data) {

        if ($data['Cooking']['level'] == 99 && $data['Farming']['level'] == 99 && $data['Herblore']['level'] == 99 && $data['Runecrafting']['level'] == 99) {
            return true;
        }
        return false;

    }

    public static function isArtisan($data) {

        if ($data['Smithing']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Fletching']['level'] == 99 && $data['Construction']['level'] == 99 && $data['Firemaking']['level'] == 99) {
            return true;
        }
        return false;
    }

    public static function isGatherer($data) {

        if ($data['Woodcutting']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Divination']['level'] == 99) {
            return true;
        }
        return false;
    }

    public static function isMasterful($data) {

        if ($data['Attack']['exp'] == 200000000 || $data['Defence']['exp'] == 200000000 || $data['Strength']['exp'] == 200000000 || $data['Constitution']['exp'] == 200000000 || $data['Ranged']['exp'] == 200000000 || $data['Prayer']['exp'] == 200000000 || $data['Magic']['exp'] == 200000000 || $data['Cooking']['exp'] == 200000000 || $data['Woodcutting']['exp'] == 200000000 || $data['Fletching']['exp'] == 200000000 || $data['Fishing']['exp'] == 200000000 || $data['Firemaking']['exp'] == 200000000 || $data['Crafting']['exp'] == 200000000 || $data['Smithing']['exp'] == 200000000 || $data['Mining']['exp'] == 200000000 || $data['Herblore']['exp'] == 200000000 || $data['Agility']['exp'] == 200000000 || $data['Thieving']['exp'] == 200000000 || $data['Slayer']['exp'] == 200000000 || $data['Farming']['exp'] == 200000000 || $data['Runecrafting']['exp'] == 200000000 || $data['Hunter']['exp'] == 200000000 || $data['Construction']['exp'] == 200000000 || $data['Summoning']['exp'] == 200000000 || $data['Dungeoneering']['exp'] == 200000000 || $data['Divination']['exp'] == 200000000 || $data['Invention']['exp'] == 200000000) {
            return true;
        }

        return false;
    }

    public static function isClassic($data) {

        if ($data['Attack']['level'] == 99 && $data['Defence']['level'] == 99 && $data['Strength']['level'] == 99 && $data['Constitution']['level'] == 99 && $data['Ranged']['level'] == 99 && $data['Prayer']['level'] == 99 && $data['Magic']['level'] == 99 && $data['Cooking']['level'] == 99 && $data['Woodcutting']['level'] == 99 && $data['Fletching']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Firemaking']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Smithing']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Herblore']['level'] == 99 && $data['Agility']['level'] == 99 && $data['Thieving']['level'] == 99) {
            return true;
        }
        return false;
    }

    public static function isf2pCompletionist($data) {

        if ($data['Dungeoneering']['level'] != 120) {
            return false;
        }

        if ($data['Attack']['level'] == 99 && $data['Strength']['level'] == 99 && $data['Defence']['level'] == 99 && $data['Ranged']['level'] == 99 && $data['Prayer']['level'] == 99 && $data['Magic']['level'] == 99 && $data['Constitution']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Smithing']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Cooking']['level'] == 99 && $data['Firemaking']['level'] == 99 && $data['Woodcutting']['level'] == 99 && $data['Runecrafting']['level'] == 99 && $data['Fletching']['level'] == 99) {
            return true;
        }
        return false;
    }

    public static function isBillionaire($data) {

        if ($data['Overall']['exp'] >= 1000000000) {
            return true;
        }
        return false;
    }

    public static function isCombat138($data) {

        if (self::calculateCombatLevel($data) == 138) {
            return true;
        }
        return false;
    }

    public static function isPortMaster($data) {

        if ($data['Agility']['level'] >= 90 && $data['Construction']['level'] >= 90 && $data['Cooking']['level'] >= 90 && $data['Divination']['level'] >= 90 && $data['Dungeoneering']['level'] >= 90 && $data['Fishing']['level'] >= 90 && $data['Herblore']['level'] >= 90 && $data['Hunter']['level'] >= 90 && $data['Prayer']['level'] >= 90 && $data['Runecrafting']['level'] >= 90 && $data['Slayer']['level'] >= 90 && $data['Thieving']['level'] >= 90) {
            return true;
        }
        return false;
    }

    public static function isBalanced($data) {

        $cmbavg = (($data['Attack']['level'] + $data['Strength']['level'] + $data['Ranged']['level'] + $data['Magic']['level'] + $data['Summoning']['level'] + $data['Defence']['level'] + $data['Prayer']['level'] + $data['Constitution']['level']) / 8);

        $skillavg = (($data['Crafting']['level'] + $data['Mining']['level'] + $data['Smithing']['level'] + $data['Fishing']['level'] + $data['Cooking']['level'] + $data['Firemaking']['level'] + $data['Woodcutting']['level']
                + $data['Runecrafting']['level'] + $data['Dungeoneering']['level'] + $data['Agility']['level'] + $data['Herblore']['level'] + $data['Thieving']['level'] + $data['Fletching']['level'] + $data['Slayer']['level']
                + $data['Farming']['level'] + $data['Construction']['level'] + $data['Hunter']['level'] + $data['Divination']['level'] + $data['Invention']['level']) / 19);

        if (self::withinPercentage($cmbavg, $skillavg, 5)) {

            return true;
        }
            return false;
    }

    public static function isSkillMastery($data) {
        if ($data['Attack']['exp'] >= 104273167 || $data['Defence']['exp'] >= 104273167 || $data['Strength']['exp'] >= 104273167 || $data['Constitution']['exp'] >= 104273167 || $data['Ranged']['exp'] >= 104273167 || $data['Prayer']['exp'] >= 104273167 || $data['Magic']['exp'] >= 104273167 || $data['Cooking']['exp'] >= 104273167 || $data['Woodcutting']['exp'] >= 104273167 || $data['Fletching']['exp'] >= 104273167 || $data['Fishing']['exp'] >= 104273167 || $data['Firemaking']['exp'] >= 104273167 || $data['Crafting']['exp'] >= 104273167 || $data['Smithing']['exp'] >= 104273167 || $data['Mining']['exp'] >= 104273167 || $data['Herblore']['exp'] >= 104273167 || $data['Agility']['exp'] >= 104273167 || $data['Thieving']['exp'] >= 104273167 || $data['Slayer']['exp'] >= 104273167 || $data['Farming']['exp'] >= 104273167 || $data['Runecrafting']['exp'] >= 104273167 || $data['Hunter']['exp'] >= 104273167 || $data['Construction']['exp'] >= 104273167 || $data['Summoning']['exp'] >= 104273167 || $data['Dungeoneering']['exp'] >= 104273167 || $data['Divination']['exp'] >= 104273167 || $data['Invention']['exp'] >= 80618654) {
            return true;
        }
        return false;
    }

    public static function isCompletionist($data) {
        if ($data['Dungeoneering']['level'] != 120) {
            return false;
        }
        foreach ($data as $user) {
            if ($user['level'] < 99) {
                return false;
            }
        }
        return true;
    }

    public static function calculateCombatLevel($data) {
        $attack    = $data["Attack"]['level'];
        $defence   = $data["Defence"]['level'];
        $strength  = $data["Strength"]['level'];
        $hitpoints = $data["Constitution"]['level'];
        $prayer    = $data["Prayer"]['level'];
        $ranged    = $data["Ranged"]['level'];
        $magic     = $data["Magic"]['level'];
        $summoning = $data["Summoning"]['level'];

        $baseCalc = max($attack + $strength, 2 * $magic, 2 * $ranged);
        $level = floor(0.25 * (1.3 * $baseCalc + $defence + $hitpoints + (0.5 * $prayer) + (0.5 * $summoning)));
        return $level;
    }

}