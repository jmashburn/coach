<?php

namespace Stager\Launch\Handler;

use Stager\Handler\AbstractStagerHandler;
use Stager\Port;

class LaunchHandler extends AbstractStagerHandler{

	public function post() {
		try {
			$params = $this->getParameters(array( 
				'image_name'=> null, 'container_name' => null));
			$this->validateMandatoryParameters($params, array('image_name', 'container_name'));
			$container = $this->launch($params);
			return $this->display('launch/launch.json.phtml', compact('container'));
		} catch (\Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	public function launch($result = array()) {
		return $this->processLaunch($result);
	}

	public function postLaunch(\Docker\Container $container, \Stager\Router\AbstractRouter $router) {

	}

}