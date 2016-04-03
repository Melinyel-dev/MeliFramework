<?php

namespace System\Helpers;

class Lang {
	protected static $instance;

	protected $lang = [];
	protected $currentLangId = null;


	public function __construct() {

	}

	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function load($langid) {

		$this->lang[$langid] = [];

		foreach (glob(ROOT . DS . 'Lang' . DS . $langid . DS . '*') as $lngfile) {
			require $lngfile;
			$this->lang[$langid] = array_merge($lang, $this->lang[$langid]);
		}
	}

	public function setLangId($langId) {
		$this->currentLangId = $langId;
	}

	public function get($id, $key, $default=null) {
		if(isset($this->lang[$id][$key])) {
			return $this->lang[$id][$key];
		}

		if(!$default) {
			return 'Missing phrase (' . $id . ') : ' . $key;
		}
		return $default;
	}

	public function p($key) {
		if($this->currentLangId) {
			return $this->get($this->currentLangId, $key);
		}

		return 'Default language not set';
	}
}