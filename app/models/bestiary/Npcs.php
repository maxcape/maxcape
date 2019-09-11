<?php

class Npcs extends \Phalcon\Mvc\Model {

    final public function getSource() {
        return 'beast_' . parent::getSource();
    }

    public $id;
    public $name;
    public $members;
    public $weakness;
    public $level;
    public $lifepoints;
    public $defence;
    public $attack;
    public $magic;
    public $ranged;
    public $xp;
    public $slayerLevel;
    public $slayercat;
    public $size;
    public $attackable;
    public $aggressive;
    public $poisonous;
    public $description;
    public $areas;
    public $animations;

}