<?php
/**
 * Created by PhpStorm.
 * User: foxtr
 * Date: 7/22/18
 * Time: 10:12 PM
 */

class Areas extends \Phalcon\Mvc\Model {

    final public function getSource() {
        return 'beast_' . parent::getSource();
    }

    public $area_name;

}