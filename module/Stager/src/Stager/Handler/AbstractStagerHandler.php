<?php

namespace Stager\Handler;

use \Application\Api\Handler\JsonHandler;
use \ToroHook;
use \Stager\Port;

class AbstractStagerHandler extends JsonHandler {

	public $route_prefix = '/api/stager/';
	
	public $ports;

	public $config;
	
	public $image;

	public function __construct() {
		parent::__construct();
		$this->ports = new Port;
		$this->config = \Config::getConfig('images');
	}

	public function processLaunch($result = array()) {
		if ($this->ports->atCapacity() && !$this->__containerExits($result['container_name'])) {
			$this->__rotateContainer();
		}

		$image_config = $this->__imageConfig($result['image_name']);
		if ($this->__containerExits($result['container_name'])) {
			$this->processEvent('kill', $result);
		}
		$container = $this->__startContainer($result, $image_config);
		if ($container->Id) {
			$container = $this->__containerByName($result['container_name']);
		}

		$this->__updateRoutes('launch', $container);
		return $container;
	}

	public function processEvent($action, $result = array()) {
		if (!is_array($result)) {
			throw new \Exception(sprintf('Excpected array got: "%s"', gettype($result)));
		}
		$routes = \Config::getConfig('routes');
		if (isset($routes[$this->route_prefix.$action])) {
			$actionHandler = new $routes[$this->route_prefix.$action];
			if (method_exists($actionHandler, $action)) {
				return $actionHandler->$action($result);
			} else {
				throw new \Exception(sprintf('Method "%s" does not exist', $action));
			}
		} else {
			throw new \Exception(sprintf('No Handler found for action: %s', $action));
		}
	}

	public function processKill($result = array()) {
		if (!is_array($result)) {
			throw new \Exception(sprintf('Expected array got: "%s"', gettype($result)));
		}
		if (empty($result['container_name'])) {
			throw new \Exception(sprintf('No name specified for container'));
		}
		try {
			$container = $this->__containerByName($result['container_name']);
			if (!empty($container)) {
				$this->preKill($container);
				$kill = \Docker\Container::kill($container->Id);
			} else {
				throw new \Exception(sprintf('Container "%s" not found', $result['container_name']));
			}
		} catch (\Docker\DockerException $e) {
			throw new \Exception($e->getMessage(), $e->code());
		}

		$containers = \Docker\Container::find(array('all' => true));
		foreach ($containers as $_container) {
			if ((strpos($_container->Status, 'Up')) !== 0) {
				\Docker\Container::remove($_container->Id);
			}
		}

		$this->__updateRoutes('kill', $container);
		// Remove all non-running instances
		return array('ok');
	}

	private function __startContainer($result, $config) {
		$port = $config['port'];
		$env = $this->__containerEnv($result, $config);
		$container_params = array(
			"Cmd" => explode(" ", $config['command']),	"Image" => $this->image['Id'], "Env" => $env, "ExposedPorts" => array($port."/tcp" => array()) 
		);	
		$container_params = merge($container_params, (!empty($config['container_create_params'])?$config['container_create_params']:array()));
		$container_start_params = array("PortBindings" => array($port."/tcp" => array(array('HostIp' => '0.0.0.0', 'HostPort' => (string)$this->ports->nextPort()))));
		$container_start_params = merge($container_start_params, (!empty($config['container_start_params'])?$config['container_start_params']:array()));
		$params['name'] = $result['container_name'];
		$params['body'] = \Docker\Util\Json::encode($container_params);
		$start_params['body'] = \Docker\Util\Json::encode($container_start_params);
		try {
			$container = \Docker\Container::create($params);
			$result = \Docker\Container::start($result['container_name'], $start_params);
			return $container;
		} catch (\Docker\DockerException $e) {

		}
	}

	private function __containerEnv($result, $config) {
		$envs = $config;
		if (!empty($envs['container_create_params'])) {
			unset($envs['container_create_params']);
		}
		if (!empty($envs['container_start_params'])) {
			unset($envs['container_start_params']);
		}
		$envs = merge($envs, $result);
		foreach ($envs as $key => $value) {
			$env[] = strtoupper($key) . "=" . $value;
		}
		return $env;
	}

	private function __containerExits($name) {
		try {
			$container = \Docker\Container::inspect($name);
			return true;
		} catch (\Docker\DockerException $e) {
			return false;
		}
	}

	private function __rotateContainer() {
		try{
			$containers = \Docker\Container::find(array('all' => true));
			if ($containers) {
				$containers = $containers->toArray();
				$vcontainer = array();
				foreach ($containers as $container) {
					foreach ($container['Ports'] as $port) {
						if (in_array($port['PublicPort'], $this->ports->range())) {
							$vcontainer[] = $container;
						}
					}
				}
				usort($vcontainer, function ($a, $b) {
					$a1 = $a['Created'];
					$b1 = $b['Created'];
					return $a1 - $b1;
				});
			}
			if (!empty($vcontainer)) {
				$container = array_shift($vcontainer);
				$oldest_container = new \Docker\Container($container);
				if (!empty($oldest_container)) {
					$this->processEvent('kill', array('image_name' => $oldest_container->Image, 
						'container_name' => str_replace('/', '', $oldest_container->Names[0])));
				}
			}

		} catch (\Docker\DockerException $e) {
			die();
		}

	}

	private function __imageConfig($image_name) {
		if (empty($this->config)) {
			throw new \Exception(sprintf('No image configuration found'));
		}
		if (empty($this->config[$image_name])) {
			throw new \Exception(sprintf('No Image config found for "%s"', $image_name));
		}
		if (empty($this->config[$image_name]['port']) || empty($this->config[$image_name]['command'])) {
			throw new \Exception(sprintf('"%s" is not a valid image configuration', $image_name));
		}
		try {
			$this->image = \Docker\Image::inspect($image_name);
		} catch (\Docker\DockerException $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
		return $this->config[$image_name];
	}

	private function __containerByName($name) {
		try {
			$container = \Docker\Container::inspect($name);
			if (!empty($container['Config']['Env'])) {
				if (!in_array('CONTAINER_NAME='.$name, $container['Config']['Env']->toArray())) {
					throw new \Exception(sprintf('No Container with ENV: %s', 'CONTAINER_NAME='.$name));
				}
			}
			return $container;
		} catch (\Docker\DockerException $e) {

		}
	}

	private function __updateRoutes($action, \Docker\Container $container) {
		$config = \Config::getConfig('stager');
		if (!empty($config['router'])) {
			$router = new $config['router']($action, $container, $config);
			$router->route();

			#die('here');
		} else {
			#die('there');
		}
	}

	public function preKill(\Docker\Container $container) {}
	
	public function postLaunch(\Docker\Container $container) {}
}