<?php

namespace Stager\Status\Handler;

use Stager\Handler\AbstractStagerHandler;

class StatusHandler extends AbstractStagerHandler {

	public function get() {
		$containers = \Docker\Container::find(array('all' => true));
		return $this->display('status/list.json.phtml', compact('containers'));
	}

}