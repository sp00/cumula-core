<?php

namespace Cumula\Application;

class HTMLStream extends \Cumula\Base\Stream {
	public function __construct() {
		parent::__construct();
		A('Request')->unbind('ProcessRequest', array($this, 'processRequest'));
		A('Request')->bind('BeforeProcessRequest', array($this, 'processRequest'));
	}
	
	public function processRequest() {
		parent::processRequest();
		$request = A('Request');
		$request->path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
		if(isset($_SERVER['REMOTE_ADDR']))
			$request->requestIp = $_SERVER['REMOTE_ADDR'];
		if(isset($_SERVER['REQUEST_URI']))
			$request->fullPath = $_SERVER['REQUEST_URI'];
		if(isset($_SERVER['REQUEST_METHOD']))
			$request->method = $_SERVER['REQUEST_METHOD'];
		$request->params = array_merge($_GET, $_POST);
		array_walk_recursive($request->params, function(&$ele, $key) {$ele = str_replace("\\\\", "\\", $ele);});
		A('Response')->data['headers'] = array();
		A('Response')->data['code'] = 200;
		A('Application')->stream = $this->getStreamName();
	}
	
	public function processResponse() {
		parent::processResponse();
		$response = A('Response');
		$content = '';
		if(A('Response')->data['code'] == 404) {
			$content = A('Renderer')->buffer['404'];
		} else if(A('Renderer')->useTemplate)
			$content = A('Template')->renderTemplate(
				$this->_processBuffer(A('Renderer')->buffer)
			);
		else {
			foreach(A('Renderer')->buffer as $block) {
				$content .= $block['data'];
			}
		}
		$response->content = ($response->content == '' || !$response->content) ? $content : $response->content;
		A('Response')->bind('ResponseSend', array($this, 'sendHeaders'));
	}
	
	public function startup() {
		A('Renderer')->bind('GatherRenderers', array(
			"renderHTML" => array($this, 'renderHTML'),
			"renderDefault" => array($this, 'renderHTML'),
			"renderPlain" => array($this, 'renderPlain'),
			"renderNotFound" => array($this, 'renderNotFound'),
			"renderRedirect" => array($this, 'renderRedirect'),
			"renderNotAllowed" => array($this, 'renderNotAllowed')
		));
		
		A('Router')->bind('GatherRouteTypes', array(
			"/" => "/",
		));
	}	
	
	protected function _processBuffer($buffer) {
		$args = array();
		foreach($buffer as $key => $block) {
			$args[$key] = $block['data'];
		}
		return $args;
	}
	
	public function sendHeaders() {
		$code = isset(A('Response')->data['code']) ? A('Response')->data['code'] : 200;
		if ($code && $code == 404) {
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			return;
		}
		foreach(A('Response')->data['headers'] as $key => $value) {
			$this->_sendHeader($key, $value, $code);
		}
	}
	
	protected function _sendHeader($header, $value, $statusCode = null) {
		header("$header: $value", true, $statusCode);
	}
	
	public function renderHTML($fileName, $args = array(), $useTemplate = true) {
		global $cm;
		A('Renderer')->useTemplate = $useTemplate;
		extract($args, EXTR_OVERWRITE);
		ob_start();
		include $fileName;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	public function renderString($string, $args) {
		
	}
	
	public function renderView($fileName, $args = array()) {
		global $cm;
		return $cm->renderView($fileName, $args);
	}
	
	public function __get($name) {
		global $cm;
		return $cm->$name;
	}
	
	public function __call($name, $args) {
		global $cm;
		call_user_func_array(array($cm, $name), $args);
	}
	
	public function renderPlain($content, $block = 'content', $useTemplate = false) {
		A('Renderer')->useTemplate = $useTemplate;
		A('Renderer')->buffer[$block] =  array('block' => $block, 'data' => $content, 'config' => array());
	}
	
	public function renderRedirect($url) {
		if (FALSE === stripos($url, 'http')) {
	      $protocol = ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'))
	        ? 'https' : 'http';
	      if ($url{0} != '/') $url = '/'.$url;
	      $url = $protocol.'://'.$_SERVER['HTTP_HOST'].$url;
	    }
		A('Response')->data['headers']['Location'] = $url;
		A('Response')->data['code'] = 302;
	}
	
	public function renderNotAllowed() {
		A('Response')->data['code'] = 405; //Change Denied
		A('Response')->data['headers']['Pragma'] = 'no-cache';
		A('Response')->content = '';
		A('Renderer')->useTemplate = false;
	}
	
	public function renderNotFound() {
		$fileName = A('Template')->get404File();
		A('Renderer')->buffer['404'] = $this->renderHTML($fileName, array(), false);
		A('Response')->data['code'] = 404;
	}

}