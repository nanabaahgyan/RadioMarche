<?php
/**
 * Log writer that will integrate with Doctrine to write
 * log entries to database. Implemented for consistency
 *
 * @see Zend_Log_Writer_Abstract
 */
class Voices_Log_Writer_Doctrine extends Zend_Log_Writer_Abstract
{
	/**
	 * Constructor accepts model name and column map
	 *
	 * @param string $modelName
	 * @param string $columnMap
	 */
	public function __construct($modelName, $columnMap)
	{
		$this->_modelName = $modelName;
		$this->_columnMap = $columnMap;
	}
	
	/**
	 * Stub function to deny formatter coupling
	 *
	 * @see Zend_Log_Writer_Abstract::setFormatter()
	 */
	public function setFormatter($formatter)
	{
		require_once 'Zend/Log/Exception.php';
		throw new Zend_Log_Exception(get_class() . 'does not support formatting');
	}
	
	/**
	 * Main log write method that maps database fields to log message fields and
	 * saves log messages as database records using model methods
	 *
	 * @see Zend_Log_Writer_Abstract::_write()
	 * @param string $message
	 */
	protected function _write($message)
	{
		$data = array();
		
		foreach ($this->_columnMap as $messageField => $modelField){
			$data[$modelField] = $message[$messageField];
		}
		$model = new $this->_modelName();
		$model->fromArray($data);
		$model->save();
	}
	
	/**
	 *  Static factory method
	 *
	 * @param string $config
	 * @return object Zend_Log_Writer_Abstract
	 */
	static public function factory($config)
	{
		return new self(self::_parseConfig($config));
	}
}