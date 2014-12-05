<?php

namespace Stager\Router;


class HaProxyRouter extends AbstractRouter {

	public $reload = 'haproxy -f /etc/haproxy/haproxy.cfg -p /var/run/haproxy.pid -sf $(cat /var/run/haproxy.pid)';

	public $template_path = 'haproxy/haproxy.conf.phtml';

	public $bind = "*:8080";

	public function getBind() {
		if (!empty($this->settings['haproxy']['bind'])) {
			$this->bind = $this->settings['haproxy']['bind'];
		}
		return $this->bind;
	}

	public function getTemplatePath() {
		if (!empty($this->settings['haproxy']['template_path'])) {
			$this->template_path = $this->settings['haproxy']['template_path'];
		}
		return $this->template_path;
	}

	public function getTargetPath() {
		if (!empty($this->settings['haproxy']['target_path'])) {
			$this->target_path = $this->settings['haproxy']['target_path'];
		}
		return $this->target_path;
	}

	public function reload() {
		if (!empty($this->settings['haproxy']['reload'])) {
			$this->reload = $this->settings['haproxy']['reload'];
		}
		@exec($this->reload);
	}

	public function clean() {}
}