<?php
use Phalcon\Mvc\Model;

class Recovery extends Model {

    public $id;
    public $userid;
    public $secret_key;
    public $expiration;
}

?>