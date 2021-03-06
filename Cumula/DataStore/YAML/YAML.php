<?php
namespace Cumula\DataStore\YAML;

require_once dirname(__FILE__) . '/lib/sfYamlDumper.php';
require_once dirname(__FILE__) . '/lib/sfYamlParser.php';

class YAML extends \Cumula\Base\FileDataStore {
	private $_storage;
	private $_cache;
	
	public function create($obj) {
		$this->_createOrUpdate($obj);
	}
	
	protected function _createOrUpdate($obj) {
		$obj = $this->prepareSave($obj);
		$idField = $this->_getIdField();
		$key = $this->_getIdValue($obj);
		//If object is a simple key/value (count == 2), set the value to be the remaining attribute, otherwise set the object as the value
		if(count((array)$obj) == 2) {
			foreach($obj as $k => $value) {
				if($k != $idField)
					$this->_storage[$key] = $value;
			}
		} else {
			$this->_storage[$key] = (array)$obj;
		}
		return $this->_save();
	}
	
	public function update($obj) {
		$this->_createOrUpdate($obj);
	}
	
	public function createOrUpdate($obj) {
		return $this->_createOrUpdate($obj);
	}
	
	public function destroy($obj) {
		if(is_string($obj)) {
			//if Obj is an ID (string), unset the entire record
			if ($this->recordExists($obj)) {
				unset($this->_storage[$obj]);
			}
		} else {
			//if obj is an object, unset the object based on the passed id
			$key = $this->_getIdValue($obj);
			unset($this->_storage[$key]);
			$this->_save();
		}
	}

	public function get($id) {
		$ret = false;
		if ($this->recordExists($id)) {
			$vals = $this->_storage[$id];
			$field = $this->_getNonIdFields();
			$idField = $this->_getIdField();
			$obj = $this->newObj(array($field[0] => $vals, $idField => $id));
			$obj = $this->prepareLoad($obj);
			$ret = $obj;
		}
		return $ret;
	}
	
	public function recordExists($id) {
		if(!isset($this->_storage))
			return false;

		$idField = $this->_getIdField();
		if (is_array($id) && isset($id[$idField])) {
			$id = $id[$idField];
		}
		return array_key_exists($id, $this->_storage);
	}
	
	/* (non-PHPdoc)
	 * @see core/interfaces/DataStore#connect()
	 */
	public function connect() {
		$this->_load();
		$this->_connected = true;
	}
	
	/* (non-PHPdoc)
	 * @see core/interfaces/DataStore#disconnect()
	 */
	public function disconnect() {
		$this->_save();
		$this->_connected = false;
	}	
	
	/**
	 * Saves the data in the internal storage variable to the YAML file.
	 * @return unknown_type
	 */
	protected function _save() {
		if(!empty($this->_storage) && $this->_storage != $this->_cache) {
			if (extension_loaded('yaml')) {
				$yaml = yaml_emit($this->_storage);
			} else {
				$dumper = new \sfYamlDumper();
				$yaml = $dumper->dump($this->_storage, 2);
			}
			return file_put_contents($this->_dataStoreFile(), $yaml);
		}
	}
	
	/**
	 * Loads the data in the external YAML file into the internal storage var.
	 * 
	 * @return boolean True if the information was loaded, false otherwise.
	 */
	protected function _load() {
		if (file_exists($this->_dataStoreFile())) {
			if (extension_loaded('yaml')) {
				if($contents = file_get_contents($this->_dataStoreFile()))
					$this->_storage = yaml_parse($contents);
			} else {
				$yaml = new \sfYamlParser();
				$this->_storage = $yaml->parse(file_get_contents($this->_dataStoreFile()));
			}
			$this->_cache = $this->_storage;
			return true;
		} else {
			return false;
		}
	}
}