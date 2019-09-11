<?php
use \Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class RegisterController extends Controller {

    public function indexAction() {
		if ($this->request->isPost() && $this->security->checkToken()) {
			$username 	= $this->request->getPost("username", "string");
			$email 		= $this->request->getPost("email", "email");
			$password 	= $this->request->getPost("password");

			if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->flash->error("Please provide a valid email address.");
				return true;
			}

			if (empty($password) || strlen($password) < 6 || strlen($password) > 20) {
				$this->flash->error("Password must be between 6 and 20 characters in length.");
				return true;
			}

			$user = Users::findFirst([
				"conditions"=> "username = ?1 OR email = ?2",
				"bind"		=> array(
					1 => $username,
					2 => $email
				)
			]);

			if ($user) {
				$this->flash->error("Username or Email already registered.");
			} else {
				$user = new Users();
				$user->username = $username;
				$user->password = $this->security->hash($password);
				$user->email 	= $email;

				if (!$user->save()) {
					$this->flash->error($user->getMessages());
				} else {
				    $this->flash->success("Your account has been created!");
                }
			}
		}
	}

}