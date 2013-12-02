<?php
/**
 * Class for user management related functionality
 *
 * @see Voices_Model_DatabaseObject
 * @package Application_Model
 */
class Voices_Model_DatabaseObject_User extends Voices_Model_DatabaseObject
{
	/**
	 * Array of the various user types
	 *
	 * @var array $userTypes
	 */
	static $userTypes = array('ngo' => 'Member',
							  'rad' => 'Member',
							  'admin' => 'Administrator'
							);
							
	// for instantiating and managing profile db
	public $profile = null;
							
	/**
	 * Constructor
	 *
	 * @param $db
	 */
	public function __construct($db)
	{
		parent::__construct($db, 'users', 'user_id');
		
		$this->add('username');
		$this->add('password');
		$this->add('user_type');
		$this->add('ts_created', time(), self::TYPE_TIMESTAMP);
		$this->add('ts_last_login', null, self::TYPE_TIMESTAMP);
		
		// instantiating Profile db here
		$this->profile = new Voices_Model_Profile_User($db);
	}
	
	/**
	 * Callback function preInsert()
	 *
	 * @see DatabaseObject::preInsert()
	 */
	protected function preInsert()
	{
		return true;
	}
	
	/**
	 * Callback function postLoad()
	 *
	 * @see DatabaseObject::postLoad()
	 */
	protected function postLoad()
	{
		$this->profile->setUserId($this->getId());
		$this->profile->load();
	}
	
	/**
	 * Callback function postInsert()
	 *
	 * @see DatabaseObject::postInsert()
	 */
	protected function postInsert()
	{
		$this->profile->setUserId($this->getId());
		$this->profile->save(false);
		return true;
	}
	
	/**
	 * Callback function postUpdate()
	 *
	 * @see DatabaseObject::postUpdate()
	 */
	protected function postUpdate()
	{
		$this->profile->save(false);
		return true;
	}
	
	/**
	 * Callback function preDelete()
	 *
	 * @see DatabaseObject::preDelete()
	 */
	protected function preDelete()
	{
		$this->profile->delete();
		return true;
	}
	
	/**
	 * Function used to create identity of an
	 * authenticated user that can be retrieved
	 * later
	 */
	public function createAuthIdentity()
    {
        $identity = new stdClass;
        $identity->user_id = $this->getId();
        $identity->username = $this->username;
        $identity->user_type = $this->user_type;
        $identity->first_name = $this->profile->first_name;
        $identity->last_name = $this->profile->last_name;
        $identity->email = $this->profile->email;

        return $identity;
    }
    
    /**
     * Function to record last login time of user
     */
	public function loginSuccess()
	{
		$this->ts_last_login = time();
		$this->save();
		
		$message = sprintf('Successful login attempt from %s', $_SERVER['REMOTE_ADDR']);
		
		$logger = Zend_Registry::get('logger');
		
		$logger->setEventItem('stacktrace', 'With username: '. $this->username . ', ' .
											'UserType: ' . $this->user_type);
		$logger->setEventItem('request', $message);
		$logger->log('Successful User Login', Zend_Log::INFO);
	}
	
	/**
	 * Function log failed user login
	 *
	 * @param string $username
	 * @param int $code
	 */
	static public function LoginFailure($username, $code = '')
	{
		switch ($code) {
			case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
				$reason = 'Unknown username';
			break;
			case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
				$reason = 'Multiple users found with this username';
			break;
			case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
				$reason = 'Invalid password';
			break;
			default:
				$reason = '';
		}
		
		$message = sprintf('Failed login attempt from user %s', $username);
				
		if (strlen($reason) > 0)
			$message .= sprintf(' (%s)', $reason);
				
		$logger = Zend_Registry::get('logger');
		
		$logger->setEventItem('stacktrace', 'Attempted login from user '. $username);
		$logger->setEventItem('request', $message);
		$logger->log('Failed User Login', Zend_Log::WARN);
	}
	
	/**
	 * Using PHP's magic methods to set password
	 * and user type
	 *
	 * @param $name
	 * @param $value
	 * @return ($name, $value)
	 */
	public function __set($name, $value)
	{
		switch ($name){
			case 'password':
				$value = md5($value);
				break;
				
			case 'user_type':
				if (!array_key_exists($value, self::$userTypes))
					$value = 'guest';
				break;
		}
		
		return parent::__set($name, $value);
	}
	
	/**
	 * Function to determine whether a new username is already in use.
	 *
	 * @param string $username
	 * @return int
	 */
	public function usernameExists($username)
    {
     	$query = sprintf('select count(*) from %s where username = ?', $this->_table);

        $result = $this->_db->fetchOne($query, $username);

        return $result > 0;
    }
    
	static public function IsValidUsername($username)
    {
        $validator = new Zend_Validate_Alnum();
        return $validator->isValid($username);
    }

    /**
     * Function to send Email
     *
     * @param string $file file template to use
     * @param string $email email of recipient
     * @param string $fName first name of recipient
     * @param string $lName last name of recipient
     */
    public function sendEmail($file,$email,$fName,$lName)
    {
    	$tplFile = APPLICATION_PATH . '/../data/templates/email/' . $file;
    	
    	$fileContent = file_get_contents($tplFile);
    	    	
		// extract the subject from the first line
		list($subject, $body) = preg_split('/\r|\n/', $fileContent, 2);
		
		// now set up and send the e-mail
		$mail = new Zend_Mail();
		
		// set the to address and the user's full name in the 'to' line
		$mail->addTo($email, trim(ucfirst($fName) . ' ' . ucfirst($lName)));
		
		// get the admin 'from' details from the config
		$mail->setFrom(Zend_Registry::get('config')->email->from->email,
					   Zend_Registry::get('config')->email->from->name);
					   
		// set the subject and body and send the mail
		$mail->setSubject(trim($subject));
		$mail->setBodyHtml(trim($body), null, 'utf8');
	//	$mail->send();
    }
}