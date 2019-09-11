<?php
use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Callback;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Uniqueness;

class Users extends Model {

	public $username;
	public $privilege_level;
	public $donator;
    public $password;
	public $email;
	public $rsn;
	public $join_date;
	public $hidersn;
	public $profile_status;
	public $twitch_profile;
    public $youtube_profile;
	public $twitter_profile;
	public $instagram_profile;
	public $profile_visible;
	public $profile_views;
	public $total_donated;

	public function validation() {
        $validator = new Validation();

		$validator->add("username", new Uniqueness([
			"message" => "The name is already registered.",
		]));

		$validator->add("email", new Uniqueness([
			"message" => "The email is already registered.",
		]));

        $validator->add("username", new Callback([
            "callback" => function() {
                return strlen($this->username) >= 3 && strlen($this->username <= 15);
            },
            "message" => "Name must be between 3 and 15 characters."
        ]));

        $validator->add("password", new PresenceOf([
            "message" => "You must provide a password"
        ]));

		$validator->add("password", new Callback([
            "callback" => function() {
                return strlen($this->password) >= 6 && strlen($this->password <= 25);
            },
            "message" => "Password must be between 6 and 25 characters."
        ]));

        /**$validator->add("profile_status", new Callback([
            "callback" => function() {
                return strlen($this->profile_status) >= 6 && strlen($this->profile_status) <= 100;
            },
            "message" => "Profile status must be between 6 and 100 characters."
        ]));**/
        //todo fix profile_status validation possibly null check it to ignore?


        $validator->add("username", new Regex([
            "message" => "Username must be alphanumeric and include only spaces",
            "pattern" => "/[A-Za-z0-9 ]+/",
        ]));

        $validator->add("email", new PresenceOf(["message" => "The e-mail is required"]));
        $validator->add("email", new Email(["message" => "The e-mail is not valid"]));
		$validator->add("email", new Uniqueness(["message" => "The email is already registered."]));

        return $this->validate($validator);
    }

}

?>