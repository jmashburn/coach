<?php


namespace Stager\Router;


abstract class AbstractRouter {

	public $container;

	public $settings;

	public $ports = array();

	public $event_type;

	public $reload;

	public $target_path;

	public $host = 'localhost';
	public $port = 0;
	public $slug;

	public function __construct($action, \Docker\Container $container, $settings) {
		$this->event_type = $action;
		$this->container = $container;
		$this->settings = $settings;	
	}

	public function setPorts($ports = array()) {
		$this->ports = $ports;
		return $this;
	}

	public function getPorts() {
		return $this->ports;
	}

	public function clean() {
		if (file_exists($this->getTargetPath())) {
			@unlink($this->getTargetPath());
		}
	}

	public function getName() {
		return str_replace('/', '', $this->container->Name);
	}

	public function getSlug() {
		$this->slug = $this->getName();
		return $this->slug;
	}

	public function getPort() {
		if (!empty($this->container['NetworkSettings'])) {
			$this->port = array_shift($this->container['NetworkSettings']['Ports']->toArray())[0]['HostPort'];
		}
		return $this->port;
	}

	public function getHost() {
		if (!empty($this->settings['host'])) {
			$this->host = $this->settings['host'];
		}
		return $this->host;
	}

	public function write() {
		try {
			foreach (\Config::getConfig('view_paths') as $path) {
                if (is_file($path . $this->getTemplatePath())) {
                    $file = $path . $this->getTemplatePath();
                }
            }

			if ($file) {
				ob_start();
				include $file;
				$rendered = ob_get_clean();
				$fh = fopen($this->getTargetPath() , 'w');
				fwrite($fh, $rendered);
				fclose($fh);
			} else {
				throw new \Exception(sprintf('No template file found for %s', $view));
			}
		} catch (\Exception $e) {
			print_r($e);
			die();
		}
	}

	public function route() {
		$this->clean();
		if ($this->event_type !== 'kill') {
			$this->write();
		}
		$this->reload();
	}

	abstract public function getTargetPath();

	abstract public function reload();
}
