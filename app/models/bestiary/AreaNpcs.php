<?php
/**
 * Created by PhpStorm.
 * User: foxtr
 * Date: 7/22/18
 * Time: 10:19 PM
 */

class AreaNpcs extends \Phalcon\Mvc\Model {

    final public function getSource() {
        return 'beast_' . parent::getSource();
    }

    public $npc_id;
    public $npc_name;
    public $area;


}