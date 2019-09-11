<?php
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;

class UserController extends Controller {

    public function viewAction($name) {

        $user = Users::findFirstByUsername($name);
        $follow = ProfileFollowing::findFirstByFollowingId($user->userid);

        if (!$user) {
            return $this->response->redirect('');
        }

       $this->view->username = $user->username;
       $this->view->rsn = $user->rsn;
       $this->view->rankColor = $this->getRankFormatted($user->privilege_level);
       $this->view->following = $follow ? 1 : 0;
       $this->view->profile_status = $user->profile_status;
       $this->view->twitch_profile = $user->twitch_profile;
       $this->view->youtube_profile = $user->youtube_profile;
       $this->view->twitter_profile = $user->twitter_profile;
       $this->view->instagram_profile = $user->instagram_profile;
       $this->view->follower_count = ProfileFollowing::count([
            'conditions' => 'following_id = :user_id:',
            'bind' => [ 'user_id' => $user->userid ]
        ]);
        $this->view->following_count = ProfileFollowing::count([
            'conditions' => 'user_id = :user_id:',
            'bind' => [ 'user_id' => $user->userid ]
        ]);

        $this->view->online = $this->isOnline($user);

        if ($this->request->has("follow")) {

            if ($name == $this->session->get("user_auth")['username']) {
                $this->flash->error("You can't follow yourself.");
                return true;
            }
            if ($follow) {
                $this->flash->error("You are already following this user.");
            } else {
                $follow = new ProfileFollowing();
                $follow->user_id = $this->session->get("user_auth")['id'];
                $follow->following_id = $user->userid;
                $follow->save();
                $this->flash->success("You are now following this user.");
                $this->response->redirect("user/view/".$name);
            }
        }

        if ($this->request->has("unfollow")) {
            if (!$follow) {
                $this->flash->error("You are not following this user.");
            } else {
                $follow->delete();
                $this->flash->success("You have unfollowed this user.");
                $this->response->redirect("user/view/".$name);
            }
        }

        $this->view->activity_data = $this->getUserActivity($user);
        $this->view->donator = $this->getDonatorIcon($user->donator);
        $this->view->donTitle = $this->getDonationTitle($user->donator);
        //echo "<pre class='text-white'>".json_encode($this->getUserActivity($user), JSON_PRETTY_PRINT)."</pre>";
        return true;
    }

    public function getRankFormatted($rank) {
        switch ($rank) {
            case "Developer":
                return '<span class="text-danger"><i class="fas fa-wrench mr-1"></i>'.$rank.'</span>';
        }
        return '<span class="text-muted">'.$rank.'</span>';
    }

    public function isOnline($user) {
        return UserSessions::query()
            ->columns(['last_active'])
            ->conditions("user_id = :id: AND UNIX_TIMESTAMP() - last_active < 600")
            ->bind([
                'id' => $user->userid,
            ])->execute()->getFirst();
    }

    public function getUserActivity($user)
    {
        $results = [];
        $follow_data = (new QueryBuilder())
            ->columns(
                [
                    'date'       => 'pf.date',
                    'user_name'  => 'us.username',
                    'author'     => 'uf.username',
                    'user_rank'  => 'us.privilege_level',
                    'other_rank' => 'uf.privilege_level',

                ]
            )
            ->where("user_id = $user->userid")
            ->addFrom(ProfileFollowing::class, 'pf')
            ->innerJoin(Users::class, 'pf.user_id = us.userid', 'us')
            ->innerJoin(Users::class, 'pf.following_id = uf.userid', 'uf')
            ->orderBy('pf.date DESC')
            ->limit(5)
            ->getQuery()
            ->execute();

        foreach ($follow_data as $record) {
            $key = $record->date . '-' . uniqid();
            $results[$key] = [
                'type'       => 'follow',
                'date'       => $record->date,
                'user_name'  => $record->user_name,
                'author'     => $record->author,
                'user_rank'  => $record->user_rank,
                'other_rank' => $record->other_rank,
            ];
        }

        $post_data = (new QueryBuilder())
            ->columns(
                [
                    'postid' => 'ps.postid',
                    'date'   => 'ps.date',
                    'title'  => 'ps.title',
                    'author' => 'us.username',
                    'rank' => 'us.privilege_level',
                ]
            )
            ->where("author_id = $user->userid")
            ->addFrom(Posts::class, 'ps')
            ->innerJoin(Users::class, 'ps.author_id = us.userid', 'us')
            ->orderBy('ps.date DESC')
            ->limit(5)
            ->getQuery()
            ->execute();

        foreach ($post_data as $record) {
            $key = $record->date . '-' . uniqid();
            $results[$key] = [
                'type'   => 'post',
                'postid' => $record->postid,
                'date'   => $record->date,
                'title'  => $record->title,
                'author' => $record->author,
                'rank' => $record->rank,
            ];

        }

        krsort($results);

        return $results;
    }

    public function getDonatorIcon($rank) {

        switch ($rank) {
            case 1:
                return "sapphire-icon.png";
            case 2:
                return "emerald-icon.png";
            case 3:
                return "ruby-icon.png";
            case 4:
                return "diamond-icon.png";
            case 5:
                return "dragonstone-icon.png";
            case 6:
                return "hydrix-icon.png";
        }
        return null;
    }

    public function getDonationTitle($rank) {

        switch ($rank) {
            case 1:
                return "Sapphire Supporter";
            case 2:
                return "Emerald Supporter";
            case 3:
                return "Ruby Supporter";
            case 4:
                return "Diamond Supporter";
            case 5:
                return "Dragonstone Supporter";
            case 6:
                return "Hydrix Supporter";
        }
        return null;
    }
}