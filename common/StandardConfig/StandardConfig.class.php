<?php
/**
 *  @package Cumula
 *  @subpackage Core
 *  @version    $Id$
 */

require_once dirname(__FILE__) . '/../../common/YAMLDataStore/YAMLDataStore.class.php';

/**
 * The StandardConfig represents the standard configuration storage and access mechanism for components.
 * 
 * @author mike
 *
 */
class StandardConfig implements CumulaConfig {
	private $_dataStore;
	
	/**
	 * Constructor
	 * 
	 * @param $source_directory
	 * @param $source_file
	 * @return unknown_type
	 */
	public function __construct($source_directory, $source_file) {
		global $App;
		$this->_dataStore = new YAMLDataStore(array('source_directory' => $source_directory, 'filename' => $source_file));
		$schema = new BaseSchema();
		$schema->setFields(array('id' => 'integer',
					 'value' => 'string'));
		$this->_dataStore->setSchema($schema);
		$this->_dataStore->connect();
	}
	
	/**
	 * Ensures that the configuration info is saved
	 * 
	 * @return unknown_type
	 */
	public function __destruct() {
		$this->_dataStore->disconnect();
	}
	
	/* (non-PHPdoc)
	 * @see core/interfaces/CumulaConfig#getConfigValue($config)
	 */
	public function getConfigValue($config, $default = null) {
		//$obj = null;
		$obj = $this->_dataStore->query($config);
		if (isset($obj)) {
			return $obj;
		} else {
			return $default;
		}
	}
	
	/* (non-PHPdoc)
	 * @see core/interfaces/CumulaConfig#setConfigValue($config, $value)
	 */
	public function setConfigValue($config, $value) {
		$obj = array($config => $value);
		$this->_dataStore->createOrUpdate($obj);
	}
	
	/* (non-PHPdoc)
	 * @see core/interfaces/CumulaConfig#deleteConfigValue($config)
	 */
	public function deleteConfigValue($config) {
		$obj = new stdClass();
		$obj->id = $config;
		$this->_dataStore->delete($obj);
	}
	
	/* (non-PHPdoc)
	 * @see core/interfaces/CumulaConfig#toXml()
	 */
	public function toXml() {
		//TODO	
	}
	
	public function toYaml() {
		//TODO
	}
	
	public function toArray() {
		//TODO
	}
	
	public function toString() {
		//TODO
	}
	
	public function serialize() {
		//TODO
	}
	
	public function unserialize() {
		//TODO	
	}
}