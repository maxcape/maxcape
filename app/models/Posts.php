<?php
use Phalcon\Mvc\Model;

class Posts extends Model {

    public $postid;
    public $author;
    public $author_id;
    public $title;
    public $content;
    public $visible;
    public $sticky;

}

?>