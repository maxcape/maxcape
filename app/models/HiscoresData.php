<?php
/**
 * Created by PhpStorm.
 * User: Brian
 * Date: 6/7/2019
 * Time: 9:18 PM
 */

class HiscoresData extends \Phalcon\Mvc\Model {

    private $username;
    private $overall;
    private $attack;
    private $defence;
    private $strength;
    private $hitpoints;
    private $ranged;
    private $prayer;
    private $magic;
    private $cooking;
    private $woodcutting;
    private $fletching;
    private $fishing;
    private $firemaking;
    private $crafting;
    private $smithing;
    private $mining;
    private $herblore;
    private $agility;
    private $thieving;
    private $slayer;
    private $farming;
    private $runecraft;
    private $hunter;
    private $construction;
    private $summoning;
    private $dungeoneering;
    private $divination;
    private $invention;
    private $entry;

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return HiscoresData
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOverall()
    {
        return $this->overall;
    }

    /**
     * @param mixed $overall
     * @return HiscoresData
     */
    public function setOverall($overall)
    {
        $this->overall = $overall;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttack()
    {
        return $this->attack;
    }

    /**
     * @param mixed $attack
     * @return HiscoresData
     */
    public function setAttack($attack)
    {
        $this->attack = $attack;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefence()
    {
        return $this->defence;
    }

    /**
     * @param mixed $defence
     * @return HiscoresData
     */
    public function setDefence($defence)
    {
        $this->defence = $defence;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStrength()
    {
        return $this->strength;
    }

    /**
     * @param mixed $strength
     * @return HiscoresData
     */
    public function setStrength($strength)
    {
        $this->strength = $strength;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHitpoints()
    {
        return $this->hitpoints;
    }

    /**
     * @param mixed $hitpoints
     * @return HiscoresData
     */
    public function setHitpoints($hitpoints)
    {
        $this->hitpoints = $hitpoints;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRanged()
    {
        return $this->ranged;
    }

    /**
     * @param mixed $ranged
     * @return HiscoresData
     */
    public function setRanged($ranged)
    {
        $this->ranged = $ranged;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrayer()
    {
        return $this->prayer;
    }

    /**
     * @param mixed $prayer
     * @return HiscoresData
     */
    public function setPrayer($prayer)
    {
        $this->prayer = $prayer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMagic()
    {
        return $this->magic;
    }

    /**
     * @param mixed $magic
     * @return HiscoresData
     */
    public function setMagic($magic)
    {
        $this->magic = $magic;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCooking()
    {
        return $this->cooking;
    }

    /**
     * @param mixed $cooking
     * @return HiscoresData
     */
    public function setCooking($cooking)
    {
        $this->cooking = $cooking;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWoodcutting()
    {
        return $this->woodcutting;
    }

    /**
     * @param mixed $woodcutting
     * @return HiscoresData
     */
    public function setWoodcutting($woodcutting)
    {
        $this->woodcutting = $woodcutting;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFletching()
    {
        return $this->fletching;
    }

    /**
     * @param mixed $fletching
     * @return HiscoresData
     */
    public function setFletching($fletching)
    {
        $this->fletching = $fletching;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFishing()
    {
        return $this->fishing;
    }

    /**
     * @param mixed $fishing
     * @return HiscoresData
     */
    public function setFishing($fishing)
    {
        $this->fishing = $fishing;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFiremaking()
    {
        return $this->firemaking;
    }

    /**
     * @param mixed $firemaking
     * @return HiscoresData
     */
    public function setFiremaking($firemaking)
    {
        $this->firemaking = $firemaking;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCrafting()
    {
        return $this->crafting;
    }

    /**
     * @param mixed $crafting
     * @return HiscoresData
     */
    public function setCrafting($crafting)
    {
        $this->crafting = $crafting;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmithing()
    {
        return $this->smithing;
    }

    /**
     * @param mixed $smithing
     * @return HiscoresData
     */
    public function setSmithing($smithing)
    {
        $this->smithing = $smithing;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMining()
    {
        return $this->mining;
    }

    /**
     * @param mixed $mining
     * @return HiscoresData
     */
    public function setMining($mining)
    {
        $this->mining = $mining;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHerblore()
    {
        return $this->herblore;
    }

    /**
     * @param mixed $herblore
     * @return HiscoresData
     */
    public function setHerblore($herblore)
    {
        $this->herblore = $herblore;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAgility()
    {
        return $this->agility;
    }

    /**
     * @param mixed $agility
     * @return HiscoresData
     */
    public function setAgility($agility)
    {
        $this->agility = $agility;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getThieving()
    {
        return $this->thieving;
    }

    /**
     * @param mixed $thieving
     * @return HiscoresData
     */
    public function setThieving($thieving)
    {
        $this->thieving = $thieving;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSlayer()
    {
        return $this->slayer;
    }

    /**
     * @param mixed $slayer
     * @return HiscoresData
     */
    public function setSlayer($slayer)
    {
        $this->slayer = $slayer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFarming()
    {
        return $this->farming;
    }

    /**
     * @param mixed $farming
     * @return HiscoresData
     */
    public function setFarming($farming)
    {
        $this->farming = $farming;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRunecraft()
    {
        return $this->runecraft;
    }

    /**
     * @param mixed $runecraft
     * @return HiscoresData
     */
    public function setRunecraft($runecraft)
    {
        $this->runecraft = $runecraft;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHunter()
    {
        return $this->hunter;
    }

    /**
     * @param mixed $hunter
     * @return HiscoresData
     */
    public function setHunter($hunter)
    {
        $this->hunter = $hunter;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConstruction()
    {
        return $this->construction;
    }

    /**
     * @param mixed $construction
     * @return HiscoresData
     */
    public function setConstruction($construction)
    {
        $this->construction = $construction;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSummoning()
    {
        return $this->summoning;
    }

    /**
     * @param mixed $summoning
     * @return HiscoresData
     */
    public function setSummoning($summoning)
    {
        $this->summoning = $summoning;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDungeoneering()
    {
        return $this->dungeoneering;
    }

    /**
     * @param mixed $dungeoneering
     * @return HiscoresData
     */
    public function setDungeoneering($dungeoneering)
    {
        $this->dungeoneering = $dungeoneering;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDivination()
    {
        return $this->divination;
    }

    /**
     * @param mixed $divination
     * @return HiscoresData
     */
    public function setDivination($divination)
    {
        $this->divination = $divination;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInvention()
    {
        return $this->invention;
    }

    /**
     * @param mixed $invention
     * @return HiscoresData
     */
    public function setInvention($invention)
    {
        $this->invention = $invention;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * @param mixed $entry
     * @return HiscoresData
     */
    public function setEntry($entry)
    {
        $this->entry = $entry;
        return $this;
    }

    /**
     * @return string the source table
     */
    public function getSource() {
       return "rs_hiscore_data";
    }

}