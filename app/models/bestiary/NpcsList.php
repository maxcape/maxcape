<?php
/**
 * Created by PhpStorm.
 * User: foxtr
 * Date: 7/23/18
 * Time: 12:38 AM
 */

class NpcsList extends \Phalcon\Mvc\Model {

    final public function getSource() {
        return 'beast_' . parent::getSource();
    }

    public $id;
    public $name;

}