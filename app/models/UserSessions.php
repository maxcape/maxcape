<?php
use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Callback;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Uniqueness;

class UserSessions extends Model {

    public $id;
    public $user_id;
    public $username;
    public $rank;
    public $ip_address;
    public $last_active;

    public function logAccess($user) {
        $session = $this->getSession($user);

        if (!$session) {
            $this->setUserId($user != null ? $user->userid : -1);
            $this->setUsername($user != null ? $user->username : null);
            $this->setRank($user != null ? $user->privilege_level : null);
            $this->setIpAddress(Functions::getUserIP());
            $this->setLastActive(time());
            return $this->save();
        }

        $session->setUserId($user != null ? $user->userid : -1);
        $session->setUsername($user != null ? $user->username : null);
        $session->setRank($user != null ? $user->privilege_level : null);
        $session->setIpAddress(Functions::getUserIP());
        $session->setLastActive(time());
        return $session->update();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
    /**
     * @return mixed
     */
    public function getRank() {
        return $this->rank;
    }
    /**
     * @param mixed $rank
     */
    public function setRank($rank) {
        $this->rank = $rank;
    }
    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param mixed $ip_address
     */
    public function setIpAddress($ip_address)
    {
        $this->ip_address = $ip_address;
    }

    /**
     * @return mixed
     */
    public function getLastActive()
    {
        return $this->last_active;
    }

    /**
     * @param mixed $last_active
     */
    public function setLastActive($last_active)
    {
        $this->last_active = $last_active;
    }

    public function getSession($member) {

        if ($member) {
            return UserSessions::query()
                ->conditions('user_id = :id:')
                ->bind([
                    'id' => $member->userid
                ])
                ->execute()->getFirst();
        }

        return UserSessions::query()
            ->conditions('ip_address = :ip:')
            ->bind([
                'ip' => Functions::getUserIP()
            ])
            ->execute()->getFirst();
    }

    /**
     * @param int $limit Default 600 seconds (10 minutes)
     * @return \Phalcon\Mvc\Model\ResultsetInterface|UserSessions
     */
    public static function getActiveUsers($limit = 600) {
        return UserSessions::query()
            ->columns(['user_id', 'username', 'rank', 'last_active'])
            ->conditions("user_id != -1 AND UNIX_TIMESTAMP() - last_active < :limit:")
            ->bind([
                'limit' => $limit
            ])->execute();
    }

    /**
     * @param int $limit
     * @return bool|\Phalcon\Mvc\ModelInterface|UserSessions
     */
    public static function getGuests($limit = 600) {
        return UserSessions::query()
            ->columns(['COUNT(*) as count'])
            ->conditions("user_id = -1 AND UNIX_TIMESTAMP() - last_active < :limit:")
            ->limit(1)
            ->bind([
                'limit' => $limit
            ])->execute()->getFirst();
    }
}

?>