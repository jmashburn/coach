<?php

namespace Application\Db;

use PDO;
use Spyc;


use Application\Exception as Exception;

class WebPDO {

  private static $instance = NULL;

  private static $guiName = 'gui';

  private static $env = 'database';

  private function __construct() { }
  private function __clone() { }

  public static function getInstance($context = 'gui') {
  	try {
		$dbConfig = \Config::getConfig(self::$env, array('gui' => array('type' => 'SQLITE', 'gui.db')));
		if (empty($dbConfig)) {
			throw new Exception(__('No database config found'), 100);
		}

		if (empty($dbConfig[$context])) {
			throw new Exception(__('Could not create connection to database "%s"', array($context)), 100);
		}

		$config = $dbConfig[$context];
		try {
			if (empty(self::$instance[$context])) {
				if (strtolower($config['type']) === 'sqlite') {
					if (strpos($config['name'], ":memory:") !== 0) {
						$dsn = 'sqlite:' . \Config::getConfig('db_dir', 'var/db/') . '/' . $config['name'];
					} else {
						$dsn = 'sqlite:' . $config['name'];
					}	
					self::$instance[$context] = new PDO($dsn, '','');
				} else {
					$dsn = strtolower($config['type']) . ":host={$config['host']};port={$config['port']};dbname={$config['name']}";
					$driver_options = (!empty($config['driver_options'])?$config['driver_options']:array());
					self::$instance[$context] = new PDO($dsn, $config['username'], $config['password'], $driver_options);
					self::$instance[$context]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
			}
		} catch(\PDOException $e) {
			print_r($e);
			die();
			throw new Exception($e);
		}

		if ($context == self::$guiName) {
			try {
				if (! self::$instance[$context]->query('SELECT * FROM `gui_metadata` WHERE `name` = \'gui_schema_version\'')) {
					throw new \PDOException();
				}
			} catch (\PDOException $e) {
				$creator = new SqlDbCreator(self::$instance[$context], self::$guiName);
				$creator->createDb('gui', strtolower($config['type']), 'create');	
			}
		} else {
			$pdoPrep = self::getInstance(self::$guiName)->prepare('SELECT * FROM `gui_metadata` WHERE `name` = \''.$context.'_schema_version\'');
			$pdoPrep->execute();
			$row = $pdoPrep->fetch(PDO::FETCH_ASSOC);
			if (! $row ) {
				$creator = new SqlDbCreator(self::$instance[$context], self::$guiName);
				$creator->createDb($context, strtolower($config['type']), 'create');
			}
		}
		return self::$instance[$context];
	} catch (\PDOException $e) {
		throw new Exception($e->getMessage(), $e->getCode());
	} catch (\Exception $e) {
		throw new Exception($e->getMessage(), $e->getCode());
	}
  }

  static function setGuiName($name) {
  	self::$guiName = $name;
  }

  static function setEnv($env) {
  	self::$env = $env;
  }
}
