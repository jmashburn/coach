<?php

namespace Stager\Kill\Handler;

use Stager\Handler\AbstractStagerHandler;

class KillHandler extends AbstractStagerHandler {

	public function post() {
		try{
			$params = $this->getParameters(array('container_name' => null));
			$this->validateMandatoryParameters($params, array('container_name'));
			$result = $this->kill($params);
			return $this->display('kill/kill.json.phtml', compact('result'));
		} catch (\Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	public function kill($result = array()) {
		return $this->processKill($result);	
	}

	public function preKill(\Docker\Container $container) {

	}
}