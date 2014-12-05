<?php

namespace Account\Handler;

use Application\Exception as Exception,
	Application\Handler\HtmlHandler;


class LoginHtmlHandler extends HtmlHandler {

	protected $layout = 'views/layout/login.phtml';

	public function get() {
        try {
            if ($this->hasIdentity()) {
                $this->getAuthAdapter()->clearIdentity();
            }
            $params = $this->getParameters(array('redirectUrl' => '/'));
        	return $this->display('account/login.phtml', compact('params'));
        } catch (Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        }
    }


    function post() {
        try {
            $params = $this->getParameters(array('username' => '', 'password' => ''));
            $this->validateMandatoryParameters($params, array('username', 'password'));
            $identity = $this->getAuthAdapter()->authenticate($params); 

            if (!$identity->isValid()) {
                $this->getAuthAdapter()->clearIdentity();
                throw new Exception($identity->getMessage(), Exception::AUTH_ERROR);
            }
            $loginResult = array(
                'status' => 'OK',
                'name' => $identity->getUsername(),
                'role' => $identity->getRole(),
            );
            \Event::fire('account.login', compact('loginResult'));
            $hash = md5(serialize($loginResult));   
            return $this->display('account/login.phtml', compact('loginResult'));
        } catch (Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        }
    }
}