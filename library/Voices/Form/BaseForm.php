<?php
/**
 * Base class for creating forms and form elements.
 *
 * @see Zend_Form
 * @package Form
 */
class Voices_Form_BaseForm extends Zend_Form
{
	/**
	 * Form decorators
	 * @var array
	 */
	protected $formDecorators = array(
								'FormElements',
								array('HtmlTag', array('tag' => 'table', 'class' => 'form-table')),
            					array('Fieldset', /*array('legend' => 'Please fill in the following')*/),
								'Form',
							  );
	
	/**
	 * Element decorators
	 * @var array
	 */
	protected $elementDecorators = array(
        							'ViewHelper',
        							'Errors',
        							array(array('data' => 'HtmlTag'), array('tag' => 'td')),
       								array('Label', array('tag' => 'td')),
       								array(array('row' => 'HtmlTag'), array('tag' => 'tr'))
      							);

    /**
     * Captche decorator
     * @var array
     */
    protected $captchaDecorator = array(
        							'Errors',
        							array(array('data' => 'HtmlTag'), array('tag' => 'td')),
       								array('Label', array('tag' => 'td')),
       								array(array('row' => 'HtmlTag'), array('tag' => 'tr'))
      							);
     
	/**
	 * Button decorator
	 * @var array
	 */
    protected $buttonDecorators = array(
        							'ViewHelper',
        							array(array('data' => 'HtmlTag'), array('tag' => 'td')),
        							array(array('label' => 'HtmlTag'), array('tag' => 'td', 'placement' => 'prepend')),
        							array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        						);
    
    /**
     * Translator object
     * @var object
     */
	protected  $translator = null;
	
	/**
	 * @see Zend_Form::init()
	 */
    public function init()
	{
		$this->translator = Zend_Registry::get('Zend_Translate');
		
		$this->clearDecorators();
		$this->setMethod('post')
			 ->setDecorators($this->formDecorators);
				
		/** USER PERSONAL INFORMATION INPUT ELEMENTS **/
			 
		// create a hidden field to store user type
		// for new users
		$usertype = new Zend_Form_Element_Hidden('usertype');
		$usertype->setValue('guest');
		
		// create a select for admin to select the type
		// of user during registration
		$usertypes = new Zend_Form_Element_Select('usertypes');
		$usertypes->setLabel('User Type:')
				  ->addMultiOption('', $this->translator->translate('form-select'))
				  ->addMultiOptions(array(
				  		'rad'	=> 'Radio',
				  		'ngo'	=> 'Sahel Eco',
				  		'admin' => 'Administrator'
				  ));
		$usertypes->setDecorators($this->elementDecorators);
							  				
		// create text input for name
		$username = new Zend_Form_Element_Text('username:');
		$username->setLabel('form-contact-username')
				 ->setOptions(array('size' => '30'))
				 ->setRequired(true)
				 ->addValidator('Alnum',false)
		//		 ->addErrorMessage('Username invalid')
				 ->addValidator('StringLength', false, array(3,10))
		//		 ->addErrorMessage('Username should have a minimum of 3 characters')
				 ->addFilter('HtmlEntities')
				 ->addFilter('StringTrim')
				 ->setDecorators($this->elementDecorators);
		
		// create password input text
		$password = new Zend_Form_Element_Password('password');
		$password->setLabel('form-contact-password')
				 ->setOptions(array('size' => '30'))
				 ->setRequired(true)
				 ->addFilter('HtmlEntities')
				 ->addFilter('StringTrim')
				 ->setDecorators($this->elementDecorators);
				 
		// re-enter password
		$confirm_pass = new Zend_Form_Element_Password('confirm_pass');
		$confirm_pass->setLabel('form-contact-confirm-password')
				 ->setOptions(array('size' => '30'))
				 ->setRequired(true)
				 ->addValidator('Identical', false, array('token' => 'password'))
		//		 ->addErrorMessage('The passwords do not match')
				 ->addValidator('StringLength', false, array(5,9))
		//		 ->addErrorMessage('Please choose a password between 5-9 characters')
				 ->addFilter('HtmlEntities')
				 ->addFilter('StringTrim')
				 ->setDecorators($this->elementDecorators);
		
		// create text input for first name
		$firstname = new Zend_Form_Element('firstname');
		$firstname->setLabel('form-contact-firstname')
				  ->setOptions(array('size' => '30'))
				  ->setRequired(true)
				  ->addValidator('Alpha', false, array('allowWhiteSpace' => 'true'))
		//		  ->addValidator('NotEmpty', true)
				  ->addFilter('HtmlEntities')
				  ->addFilter('StringTrim')
				  ->setDecorators($this->elementDecorators);
		
		// create text input for last name
		$lastname = new Zend_Form_Element('lastname');
		$lastname->setLabel('form-contact-lastname')
				  ->setOptions(array('size' => '30'))
				  ->setRequired(true)
				  ->addValidator('Alpha', false, array('allowWhiteSpace' => 'true'))
		//		  ->addValidator('NotEmpty', true)
				  ->addFilter('HtmlEntities')
				  ->addFilter('StringTrim')
				  ->setDecorators($this->elementDecorators);
				  
		// create text input for email address
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel('form-contact-email')
			  ->setOptions(array('size' => '30'))
			  ->setRequired(true)
			  ->addValidator('NotEmpty', true)
			  ->addValidator('EmailAddress', true)
			  ->addFilter('HtmlEntities')
			  ->addFilter('StringToLower')
			  ->addFilter('StringTrim')
			  ->setDecorators($this->elementDecorators);
			  
		// create text input for phone
		$phone = new Zend_Form_Element_Text('phone');
		$phone->setLabel('form-contact-phone')
			  ->setOptions(array('size' => '20'))
			  ->addValidator('Alnum')
			  ->addFilter('HtmlEntities')
			  ->addFilter('StringTrim')
			  ->setDecorators($this->elementDecorators);
			  
		// create text input for address
		$address = new Zend_Form_Element_Textarea('address');
		$address->setLabel('form-contact-address')
			    ->setOptions(array('rows' => '6','cols' => '40'))
			//	->addFilter('HtmlEntities')
				->addFilter('StringTrim')
				->setDecorators($this->elementDecorators);
			  
		/** CAPTCHA **/
		// create a captcha form
		$captcha = new Voices_Form_Element_Captcha(
						'captcha',
							array(
									'label'	  => 'form-captcha',
									'captcha' => 'Figlet',
									'captchaOptions' =>
										array(
									 			'captcha' => 'Figlet',
			  									'wordLen' => 6,
												'timeout' => 600,
							         			'helper' => null,
									 			'font' => APPLICATION_PATH . '/../data/fonts/standard.flf',
										)
							));
		
		$captcha->setDecorators($this->captchaDecorator);
				                  
		/** DEFINING PRODUCT INFORMATION **/
			// create text input for produce name
		$prod_file_path = APPLICATION_PATH . '/../data/txt-files/products.txt';
		$prodSelectArray = $this->getTextContents($prod_file_path);
			
		// create text input for zone of farmer
		$zone = new Zend_Form_Element_Text('zone');
		$zone->setLabel('form-farmer-zone')
				->setOptions(array('size' => '30'))
				->setRequired(true)
				->addFilter('StringToLower')
				->addFilter('StringTrim')
				->setDecorators($this->elementDecorators);
				
		// create text input for village name
		$village = new Zend_Form_Element_Text('village');
		$village->setLabel('form-village-name')
				->setOptions(array('size' => '30'))
				->setRequired(true)
				->addFilter('StringToLower')
				->addFilter('StringTrim')
				->setDecorators($this->elementDecorators);
				
		$prod = new Zend_Form_Element_Select('prod');
		$prod->setLabel('form-product-name')
				  ->setRequired(true)
				  ->addMultiOption('',  $this->translator->translate('form-select'))
				  ->addMultiOptions($prodSelectArray)
				  ->addFilter('StringToLower')
				  ->setDecorators($this->elementDecorators);
				
		// create text input for quantity
		$quant = new Zend_Form_Element_Text('quant');
		$quant->setLabel('form-product-quantity')
			  ->setOptions(array('size' => '20'))
			  ->setRequired(true)
			  ->addValidator('NotEmpty', true)
			  ->addValidator('Digits')
			  ->addFilter('StringTrim')
			  ->setDecorators($this->elementDecorators);
					  
		$metric_file_path = APPLICATION_PATH . '/../data/txt-files/metrics.txt';
		$metricSelectArray = $this->getTextContents($metric_file_path);
		
		$quant_metric = new Zend_Form_Element_Select('quant_metric');
		$quant_metric->setLabel('form-quantity-metric')
					 ->setRequired(true)
					 ->addMultiOption('', $this->translator->translate('form-select'))
					 ->addMultiOptions($metricSelectArray)
					 ->setDecorators($this->elementDecorators);
		
		// create text input for price
		$validator = new Zend_Validate_Float(array('locale' => Zend_Registry::get('Zend_Locale')));
		
		$price = new Zend_Form_Element_Text('price');
		$price->setLabel('form-price')
			  ->setRequired(true)
			  ->setOptions(array('size' => '20'))
			  ->addValidator('NotEmpty', true)
			  ->addValidator('Digits')
			  ->setDecorators($this->elementDecorators);
			  	
		$currency_file_path = APPLICATION_PATH . '/../data/txt-files/currency.txt';
		$currencySelectArray = $this->getTextContents($currency_file_path);
		
		$currency = new Zend_Form_Element_Select('currency');
		$currency->setLabel('form-currency')
				 ->setRequired(true)
				 ->addMultiOption('', $this->translator->translate('form-select'))
				 ->addMultiOptions($currencySelectArray)
				 ->setDecorators($this->elementDecorators);
			  
		$qual = new Zend_Form_Element_Text('qual');
		$qual->setLabel('form-product-quality')
		//	  ->setRequired(true)
			  ->setOptions(array('size' => '30'))
			  ->addValidator('NotEmpty', true)
		//	  ->addValidator('Alpha', false, array('allowWhiteSpace' => 'true'))
		//	  ->addFilter('HtmlEntities')
			  ->addFilter('StringTrim')
			  ->setDecorators($this->elementDecorators);
			  
		/** COMMUNIQUE FORM ELEMENTS **/
		// radio station
		$rad_file_path = APPLICATION_PATH . '/../data/txt-files/radio-stations.txt';
		$radSelectArray = $this->getTextContents($rad_file_path);
		
		$radio = new Zend_Form_Element_Select('radio');
		$radio->setLabel('Radio Station:')
			  ->addMultiOption('', $this->translator->translate('form-select'))
			  ->addMultiOptions($radSelectArray)
			  ->setDecorators($this->elementDecorators);

		// the language
		$lang_file_path = APPLICATION_PATH . '/../data/txt-files/languages.txt';
		$langSelectArray = $this->getTextContents($lang_file_path);
		
		$language = new Zend_Form_Element_Select('language');
		$language->setLabel('Language:')
				 ->setRequired(true)
				 ->addMultiOption('', $this->translator->translate('form-select'))
				 ->addMultiOptions($langSelectArray)
				 ->setDecorators($this->elementDecorators);
		
		// the contact
		$contact_file_path = APPLICATION_PATH . '/../data/txt-files/contacts.txt';
		$contactSelectArray = $this->getTextContents($contact_file_path);
		
		$contact = new Zend_Form_Element_Select('contact');
		$contact->setLabel('form-contact')
				->setRequired(true)
				->addMultiOption('', $this->translator->translate('form-select'))
			    ->addMultiOptions($contactSelectArray)
			    ->setDecorators($this->elementDecorators);
			  
		/** BUTTONS **/
		// create submit button
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('form-send-info')
		       ->setDecorators($this->buttonDecorators)
			   ->setOptions(array('class' => 'submit'));
			   
		$reset = new Zend_Form_Element_Reset('reset');
		$reset->setLabel('Reset')
			  ->setDecorators($this->buttonDecorators)
			  ->setOptions(array('class' => 'submit'));
			  
		/** ADD ELEMENTS TO FORM **/
		$this->addElement($zone)
			 ->addElement($village)
			 ->addElement($prod)
		//	 ->addElement($prod_com_id)
			 ->addElement($qual)
			 ->addElement($quant)
			 ->addElement($quant_metric)
			 ->addElement($currency)
			 ->addElement($price);
			   
		// attach elements to form
		$this->addElement($usertypes)
			 ->addElement($username)
			 ->addElement($password)
			 ->addElement($confirm_pass)
			 ->addElement($firstname)
			 ->addElement($lastname)
			 ->addElement($email)
			 ->addElement($contact)
			 ->addElement($phone)
		//	 ->addElement($contact_com_id)
			 ->addElement($address)
             ->addElement($radio)
             ->addElement($language)
			 ->addElement($usertype)
			 ->addElement($captcha);
			 			 
		// add submit and reset
		$this->addElement($submit);
	//	$this->addElement($reset);
			
	}
	
	/**
	 * Utility method for reading content of a text file
	 * and storing the content in an array
	 *
	 * @param string $file_path
	 * @return array
	 */
	public function getTextContents($file_path)
	{
		$fh = @fopen($file_path, 'rb');
		$Array = array();
		
		$i = 0;
		while ($line = fgets($fh))
		{
			$i++;
		//	$sep_lines = explode("\n", rtrim($line, '\n'));
			$Array[$i] = rtrim($line);
		}
		fclose($fh);
		
		return $Array;
	}
	
	/**
	 * Utility function to get the index of text file data
	 * by comparing strings inpur
	 *
	 * @param string $str
	 * @param string $file_path
	 * @return int
	 */
	public function getTextIndex($str, $file_path)
	{
		$content = $this->getTextContents($file_path);
		
		for ($i=1;$i<=count($content);$i++)
		{
			if (strcmp($str, $content[$i]) == 0)
				$index = $i;
		}
		
		return isset($index) ? $index : '';
	}
	
	/**
	 * Utility method to help with character
	 * encoding in forms
	 *
	 * @param string $info
	 * @return string
	 */
	public function decode_characters($info)
	{
    	$info = mb_convert_encoding($info,"UTF-8","HTML-ENTITIES");
    //	$info = mb_convert_encoding($info, "HTML-ENTITIES", "UTF-8");
    //	$info = preg_replace('~^(&([a-zA-Z0-9]);)~',htmlentities('${1}'),$info);
    	return($info);
	}
}