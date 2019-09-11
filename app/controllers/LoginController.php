<?php
use \Phalcon\Mvc\Controller;

class LoginController extends Controller {

    public function indexAction() {
		if ($this->request->isPost() && $this->security->checkToken()) {
			$username = $this->request->getPost("username", "string");
			$password = $this->request->getPost("password");

			$user = Users::findFirstByUsername($username);

			if (!$user) {
				$this->security->hash(rand());
				$this->flash->error("User could not be found!");
			} else  {
				if (!$this->security->checkHash($password, $user->password)) {
					$this->flash->error("Invalid username or password");
				} else {
					$this->session->set("user_auth", [
			            "id"   => $user->userid,
			            "username" => $user->username,
			            'rank'	=> $user->privilege_level
			        ]);
					return $this->response->redirect("");
				}
			}
		}

		return true;
	}

}