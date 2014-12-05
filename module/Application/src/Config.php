<?php

use \Spyc;

class Config {

	private static $instance;

	public $config = array();

    public function __construct() {
        foreach (glob("{../,}{module/*/config,config}/{{autoload/,autoload/*/{global,local}},}{*}.{ini,php,yaml,yml}", GLOB_BRACE) as $file) {
            $fileinfo = pathinfo($file);
            $basename = basename($fileinfo['dirname']);
            $filename = $fileinfo['filename'];
            if ($fileinfo['extension'] == 'php') {
                $tmp = include $file;
                if (empty($tmp[$basename]) && $filename !== 'config') {
                   $config[$basename] = $tmp;
                } else {
                    $config = $tmp;
                }
            } elseif (in_array($fileinfo['extension'], array('ini', 'yaml', 'yml'))) {
                $tmp = Spyc::YAMLLoad($file);
                if (empty($tmp[$filename])) {
                    $config[$filename] = $tmp;
                } else {
                    $config = $tmp;
                }
            }
            if (is_array($config)) {
                $this->config = merge($this->config, $config);
            }
        }
	}

	public static function get_instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public static function getConfig($key = null, $default = null) {
		$instance = self::get_instance();
		if (!empty($key)) {
			return (!empty($instance->config[$key])?$instance->config[$key]:$default);   
		}
		return $instance->config;
    }

}
