<?php
use Phalcon\Mvc\Model;

class Comments extends Model {

    public $threadid;
    public $userid;
    public $username;
    public $rsn;
    public $content;
    public $privelege_level;
}

?>