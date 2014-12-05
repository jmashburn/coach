<?php

namespace Stager\Event\Handler;

use Stager\Handler\AbstractStagerHandler;

class EventHandler extends AbstractStagerHandler {

	private $map = array(
		'opened' 		=> 'launch',
		'reopened' 		=> 'launch',
		'synchronize' 	=> 'launch',
		'closed'		=> 'kill',
		'kill'			=> 'kill',
		'launch'		=> 'launch',
	);

	public function __construct() {
		parent::__construct();
		\ToroHook::add('before_handler', function($this) {
			try {
				// $port = new Port();
				// for($i=0;$i<=10;$i++) {
				// 	echo $port->nextPort();
				// }
				// die();
				// $headers = $this->getRequest()->getHeaders();
				// print_r($this->getRequest());
				// print_r($headers);
				// die();

			} catch (\Exception $e) {
				throw new Exception($apiIdentity->getMessage(), Exception::AUTH_ERROR);
			}
		});
	}

	public function get() {
		try {
			$params = $this->getParameters(array('action' => "unknown", 
				'image_name'=> null, 'container_name' => null, 
				'repo_owner' => null, 'pull_request_number' => null));
			$result = $this->event($params['action'], $params);
			return $this->display('event/event.json.phtml', compact('result'));
		} catch (\Exception $e) {
			return $this->display('exception/execption.json.phtml');
		} 
	}

	public function post() {
		try {
			if ($this->getRequest()->getHeaders('Content-Type') == 'application/json') {
				$params = \Docker\Util\Json::decode($this->getRequest()->getContent());
				$result = array(
					'action' => (!empty($this->map[$params['action']]))?$this->map[$params['action']]:'unknown',
					'image_name'=> $params['pull_request']['head']['repo']['name'],
					'container_name' => $params['pull_request']['head']['ref'],
					'repo_owner' => $params['pull_request']['base']['repo']['owner']['login'],
					'pull_request_number' => $params['pull_request']['number'],
					'repo_url' => $params['pull_request']['base']['repo']['clone_url'],
				);
				$result = $this->event($result['action'], $result);
				return $this->display('event/event.json.phtml', compact('result'));
			} else {
				throw new \Exception(sprintf('Content Type must be type: %s', 'application/json'));
			}
		} catch (\Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	public function event($action, $result = array()) {
		return $this->processEvent($action, $result);	
	}
}