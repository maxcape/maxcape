<?php
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group as RouterGroup;

class CustomRouter extends RouterGroup {

	public function initialize() {
		$routes = array(

            array(
                "route" => "/post/{id:[0-9\-]+}-{title:[A-Za-z0-9\-]+}",
                "params" => [
                    "controller" 	=> "index",
                    "action"     	=> "viewpost"
                ]
            ),
		);

		foreach ($routes as $route) {
			$this->add($route['route'], $route['params']);
		}
	}

}
?>
