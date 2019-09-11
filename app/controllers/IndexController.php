<?php
use Phalcon\Mvc\Controller;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;

class IndexController extends Controller {

    public function indexAction() {
        if ($this->request->has("like") && $this->request->has("type")) {
            $postId = $this->request->get("like");
            $like_type = $this->request->get("type");
            $ip_addr = Functions::getUserIP();

            $post = Posts::findFirstByPostid($postId);

            if (!$post) {
                $this->session->set("message", [
                    'type' => 'error',
                    'message' => 'Could not record reaction. Invalid post id.'
                ]);
                return $this->response->redirect("");
            }

            $reaction = PostReactions::findFirst([
                'conditions' => 'user_ip = ?1 AND postId = ?2',
                'bind' => [
                    1 => $ip_addr,
                    2 => $postId
                ]
            ]);

            if ($reaction) {
                $this->session->set("message", [
                    'type' => 'error',
                    'message' => 'You have already reacted to this post!'
                ]);
                return $this->response->redirect("");
            }

            $new          = new PostReactions;
            $new->user_ip = $ip_addr;
            $new->type    = $like_type;
            $new->postId  = $postId;
            $new->save();

            $this->session->set("message", [
                'type' => 'success',
                'message' => 'Thank you! Your reaction has been recorded.'
            ]);
            return $this->response->redirect("");
        }

        if ($this->session->has('message')) {
            $msgType = $this->session->get("message")['type'];
            $msg     = $this->session->get("message")['message'];
            $this->flash->message($msgType, $msg);
            $this->session->remove("message");
        }

        $discord  = $this->getDiscordData();
        $members  = $discord->getMembers();

        $this->view->discord = $discord;
        $this->view->discord_members = $members;

        $recentSearches = Searches::query()
            ->columns(['username', 'UNIX_TIMESTAMP(date_searched) AS date_searched'])
            ->orderBy("date_searched DESC")
            ->limit(500)
            ->execute();

        $searches = [];

        foreach ($recentSearches as $search) {
            if (count($searches) == 10) {
                break;
            }

            $username = strtolower($search->username);

            if (in_array($username, array_keys($searches))) {
                continue;
            }

            $time = $search->date_searched;

            $searches[$username] = [
                'elapsed' => Functions::elapsed($time)
            ];
        }

        $this->view->recent10 = $searches;

        $searchCount = Searches::count();
        $userCount   = Users::count();

        $totalNames = Searches::query()
            ->columns("COUNT(DISTINCT(username)) AS total")
            ->execute()
            ->getFirst();

        $searchesToday = Searches::query()
            ->columns("COUNT(*) AS total")
            ->conditions("UNIX_TIMESTAMP(date_searched) >= :start: AND UNIX_TIMESTAMP(date_searched) <= :end:")
            ->bind([
                'start' => strtotime(date('Y-m-d 00:00:00')),
                'end'   => strtotime(date('Y-m-d 23:59:59'))
            ])->execute()->getFirst();

        $this->view->members       = $userCount;
        $this->view->totalNames    = $totalNames->total;
        $this->view->totalUsers    = $userCount;
        $this->view->searchCount   = $searchCount;
        $this->view->searchesToday = $searchesToday->total;

        $posts = Posts::find([
            'conditions' => 'visible = 1',
            'columns' => [
                'Posts.postid',
                'Posts.date',
                'Posts.author_id',
                'Posts.title',
                'Posts.content',
                '(SELECT username FROM Users WHERE Posts.author_id = Users.userid) AS author',
                '(SELECT privilege_level FROM Users WHERE Posts.author_id = Users.userid) AS rank',
                '(SELECT rsn FROM Users WHERE Posts.author_id = Users.userid) AS rsn',
                '(SELECT COUNT(*) FROM PostReactions WHERE PostReactions.postid = Posts.postid AND type = 0) AS likes',
                '(SELECT COUNT(*) FROM PostReactions WHERE PostReactions.postid = Posts.postid AND type = 1) AS dislikes',
            ],
            'order' => 'date DESC',
            'limit' => 3
        ]);

        $this->view->posts = $posts;
        $this->view->activeUsers = UserSessions::getActiveUsers();
        $this->view->activeGuests = UserSessions::getGuests();
        $this->view->dailyUsers = UserSessions::getActiveUsers(86400);
        $this->view->dailyGuests = UserSessions::getGuests(86400);
    }

    public function viewpostAction($title) {
        $parts = explode("-", $title);

        if (count($parts) < 1) {
            return $this->response->redirect("");
        }

        $post = Posts::findFirstByPostid($parts[0]);

        if (!$post) {
            $this->dispatcher->forward([
                'controller' => 'errors',
                'action' => 'show404'
            ]);
            return true;
        }

        $author = Users::findFirstByUserid($post->author_id);

        $this->view->post     = $post;
        $this->view->author   = $author;
        $this->view->postBody = Functions::cleanupHtml($post->content);
        return true;
    }

    public function themeAction($theme) {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

        if (!array_key_exists($theme, theme_colors)) {
            return $this->response->redirect($_SERVER['HTTP_REFERER']);
        }

        $this->cookies->set("theme_color", $theme);
        return $this->response->redirect($_SERVER['HTTP_REFERER']);
    }

    public function getDiscordData() {
        $fCache   = new FrontData(['lifetime' => 10800]);
        $cache    = new BackFile($fCache, ['cacheDir' => "../app/compiled/cache/"]);
        $discord  = $cache->get("discord.cache");

        if (!$discord) {
            $discord = new Discord("297860060284059650");
            $discord->fetch();
            $cache->save("discord.cache", $discord);
        }

        return $discord;
    }

    public function logoutAction() {
        $this->session->destroy();
        return $this->response->redirect("");
    }

}