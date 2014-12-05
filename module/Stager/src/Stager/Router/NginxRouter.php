<?php

namespace Stager\Router;


class NginxRouter extends AbstractRouter {

	public $reload = 'sudo nginx -s reload';

	public $template_path = 'nginx/nginx.conf.phtml';

	public $target_path = '/etc/nginx/conf.d/';

	public function getTemplatePath() {
		if (!empty($this->settings['nginx']['template_path'])) {
			$this->template_path = $this->settings['nginx']['template_path'];
		}
		return $this->template_path;
	}

	public function getTargetPath() {
		if (!empty($this->settings['nginx']['target_path'])) {
			$this->target_path = $this->settings['nginx']['target_path'];
		}
		return $this->target_path . $this->getSlug().".conf";
	}

	public function reload() {
		if (!empty($this->settings['nginx']['reload'])) {
			$this->reload = $this->settings['nginx']['reload'];
		}
		exec($this->reload);
	}
	
}