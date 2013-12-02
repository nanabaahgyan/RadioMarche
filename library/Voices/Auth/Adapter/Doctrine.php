<?php
/**
 * Class to authentication through Doctrine
 *
 * @package Application
 */
class Voices_Auth_Adapter_Doctrine implements Zend_Auth_Adapter_Interface
{
	/**
	 * Array containing authenticated user record
	 *
	 * @var array
	 */
	protected $_resultArray;
	
	/**
	 * constructor
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * Main authentication method
	 * queries database for match to authentication credentials
	 * returns Zend_Auth_Result with success/failure code
	 *
	 * @see Zend_Auth_Adapter_Interface::authenticate()
	 */
	public function authenticate()
	{
		$q = Doctrine_Query::create()->from('Voices_Model_User u');
		$result = $q->fetchArray();
		if (count($result) == 1){
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->username, array());
		}
		else {
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array());
		}
    }
	
	/**
	 * Returns result array representing authenticated user record
	 * excludes specified user record fields as needed
	 * @param $excludeFields
	 */
	public function getResultArray($excludeFields = null)
	{
		if (!$this->_resultArray){
			return false;
		}
		
		if ($excludeFields != null){
			$excludeFields = (array)$excludeFields;
			foreach ($this->_resultArray as $key => $value){
				if (!in_array($key, $excludeFields)){
					$returnArray[$key] = $value;
				}
			}
			return $returnArray;
		}
		else {
			return $this->_resultArray;
		}
	}
}