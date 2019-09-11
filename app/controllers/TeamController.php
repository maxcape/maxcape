<?php
use \Phalcon\Mvc\Controller;

class TeamController extends Controller {

    public function indexAction() {

        $list = [];

        $userList = Users::find([
            'conditions' => 'privilege_level = "Developer"',
            'columns' => [
                'username',
                'privilege_level',
                'rsn',
                'twitch_profile',
                'youtube_profile',
                'twitter_profile',
                'instagram_profile'
            ]
        ]);

        foreach ($userList as $ul) {
            $list[$ul->privilege_level][] = $ul;
        }

        $this->view->team = $list;
    }
}