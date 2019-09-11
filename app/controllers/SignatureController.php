<?php

class SignatureController extends \Phalcon\Mvc\Controller {

    public function indexAction() {

        return true;
    }

    private static $backgrounds = [ 'default' ];
    private static $modes = [ 'regular', 'ironman', 'hc_ironman' ];

    public function viewAction($username = null, $mode = null, $virtual = null, $background = null) {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

        $username   = strtolower($this->filter->sanitize($username, "string"));
        $background = strtolower($this->filter->sanitize($background, 'string'));
        $game_mode  = strtolower($this->filter->sanitize($mode, 'string'));
        $virtual    = strtolower($this->filter->sanitize($virtual, "string"));

        if ($background == null || !in_array($background, self::$backgrounds))
            $background = "default";
        if ($game_mode == null || !in_array($game_mode, self::$modes))
            $game_mode = "regular";
        if ($virtual != "on" && $virtual != "off")
            $virtual = "off";

        $isVirtual  = $virtual != null && $virtual == "on";

        $image_name = str_replace(" ", "_", $username).".$game_mode.$background.$virtual.png";
        $image_path = "../public/img/signatures/".$image_name;

        if (!file_exists($image_path) || (time() - filemtime($image_path)) > 3600 * 12) {
            $this->getImage($username, $background, $game_mode, $isVirtual);
        }

        if (file_exists($image_path)) {
            header("Content-type: image/png");
            $image = imagecreatefrompng($image_path);
            imagepng($image);
            imagedestroy($image);
        }

    }

    public function getImage($rsn, $background, $mode, $virtual) {
        $image   = imagecreatefrompng("../public/img/backgrounds/$background.png");
        $black   = imagecolorallocate($image, 0, 0, 0);
        $white   = imagecolorallocate($image, 255, 255, 255);
        $blue    = imagecolorallocate($image, 2, 249, 255);
        $green   = imagecolorallocate($image, 0, 255, 128);
        $yellow  = imagecolorallocate($image, 236, 230, 56);
        $font    = "../public/fonts/Oswald-Light.ttf";
        $fsize   = 16;

        $stats  = $this->getStats($rsn, $mode);

        if (!$stats) {
            $image = imagecreatefrompng("../public/img/backgrounds/invalid.png");
            imagepng($image);
            return;
        }

        $startX = 20;
        $startY = 53;

        $groups = array_chunk($stats['skills'], 4);
        $index = 0;

        foreach ($groups as $group) {
            for($i = 0; $i < count($group); $i++) {
                $skill = $stats['skills'][$index]['skill'];
                $level = $stats['skills'][$index][$virtual ? 'vlevel' : 'level'];

                $icon = imagecreatefrompng("../public/img/skill_icons/".$skill."-icon.png");
                imagecopy($image, $icon, $startX, $startY, 0, 0, imagesx($icon), imagesy($icon));

                $textX = $startX + 30;
                $textY = $startY + 22;

                imagettftext($image, $fsize, 0, $textX + 1, $textY + 1, $black, $font, "".$level."");

                if ($level >= 99 && $level < 120) {
                    $color = $green;
                } else if ($level == 120) {
                    $color = $yellow;
                } else {
                    $color = $white;
                }

                imagettftext($image, $fsize, 0, $textX, $textY, $color, $font, "".$level."");

                $startY += 37;
                $index++;

                imagedestroy($icon);
            }

            $startX += 70;
            $startY = 53;
        }

        imagettftext($image, 20, 0, 20, 32, $blue, $font, strtoupper($rsn).' #'.number_format($stats['overall']['rank']).'');

        $exp_str    = "XP: ".number_format($stats['overall']['exp']);
        $exp_box   = imagettfbbox(10, 0, $font, $exp_str);
        $exp_width = abs($exp_box[7] - $exp_box[4]);

        $total_str   = "Total: ".number_format($stats['overall']['level']);
        $total_box   = imagettfbbox(10, 0, $font, $total_str);
        $total_width = abs($total_box[7] - $total_box[4]);

        imagettftext($image, 10, 0, imagesx($image) - $exp_width, 19, $white, $font, $exp_str);
        imagettftext($image, 10, 0, imagesx($image) - $total_width, 34, $white, $font, $total_str);

        //imagettftext($image, 12, 0, 150, 32, $white, $font, "XP: ".number_format($stats['overall']['exp'])."");
        //imagettftext($image, 12, 0, 360, 171, $white, $font, "Overall: ".number_format($stats['overall']['level'])."");

        $image_name = str_replace(" ", "_", $rsn).".$mode.$background.".($virtual ? "on" : "off").".png";
        imagepng($image, "../public/img/signatures/".$image_name);
    }

    public function getStats($rsn, $mode) {
        $ch = null;

        if ($mode == "regular") {
            $ch = curl_init("https://secure.runescape.com/m=hiscore/index_lite.ws?player=$rsn");
        } else if ($mode == "ironman") {
            $ch = curl_init( "https://secure.runescape.com/m=hiscore_ironman/index_lite.ws?player=$rsn");
        } else if ($mode == "hc_ironman") {
            $ch = curl_init( "https://secure.runescape.com/m=hiscore_hardcore_ironman/index_lite.ws?player=$rsn");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = [];

        if ($status != '404') {
            $stats = explode("\n", $result);
            for($i = 0; $i < count(skills); $i++) {
                $skill = skills[$i];
                $parts = explode(",", $stats[$i]);

                if ($skill == 'Overall') {
                    $data['overall'] = [
                        'skill' => $skill,
                        'id'    => $i,
                        'rank'  => $parts[0],
                        'level' => $parts[1],
                        'exp'   => $parts[2]
                    ];
                    continue;
                }

                $level = $parts[1];
                if ($level < 1) {
                    $level = 1;
                }

                $data['skills'][] = [
                    'skill'  => $skill,
                    'id'     => (int)$i,
                    'rank'   => (int)$parts[0],
                    'level'  => (int)$level,
                    'vlevel' => Functions::getVirtualLevel($skill, $parts[2]),
                    'exp'    => (int)$parts[2]
                ];
            }
        }

        //echo "<pre>".json_encode($data, JSON_PRETTY_PRINT)."</pre>";

        return $status == "404" ? null : $data;
    }

}