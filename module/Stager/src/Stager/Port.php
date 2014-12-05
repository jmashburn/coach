<?php

namespace Stager;


class Port {

	private $min = 3200;

	private $max = 3500;

	public $available;

	public function __construct($options = array()) {
		if (!empty($options['min'])) {
			$this->min = (int)$options['min'];
		} elseif (getenv('MIN_PORT')) {
			$this->min = getenv('MIN_PORT');
		}

		if (!empty($options['max'])) {
			$this->max = (int)$options['max'];
		} elseif (getenv('MAX_PORT')) {
			$this->max = getenv('MAX_PORT');
		}	
	}

	public function nextPort() {
		return array_shift(array_values($this->available()));
	}

	public function used() {
		$containers = \Docker\Container::find();
		$ports = array();
		foreach ($containers as $container) {
			foreach ($container->Ports as $port) {
				$ports[] = $port->PublicPort;
			}
		}
		return $ports;
	}

	public function range() {
		return range($this->min, $this->max);
	}

	public function available() {
		return (array_diff($this->range(), $this->used()));
	}

	public function atCapacity() {
		return ((count($this->available())>0)?false:true);
	}
}