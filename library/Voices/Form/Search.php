<?php
class Voices_Form_Search extends Zend_Form
{
	public function init()
	{
		// initialize form
		$this->setAction('/market-info/search')
			 ->setMethod('get');
		
		// set form decorators
		$this->setDecorators(array(
				array('FormErrors', array('markupListItemStart' => '',
										  'markupListItemEnd' => '')),
				array('FormElements'),
				array('Form')
				));
				
		// create text input for keywords
		$query = new Zend_Form_Element_Text('q');
		$query->setLabel('Keywords:')
			  ->setOptions(array('size' => '20'))
			  ->setAttrib('placeholder', 'please enter search query')
			  ->addFilter('HtmlEntities')
			  ->addFilter('StringTrim');
		$query->setDecorators(array(
								 array('ViewHelper'),
								 array('Errors'),
								 array('Label', array('tag' => '<span>'))));
								 
		// create a submit button
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Search')
			   ->setOptions(array('class' => 'submit'))
			   ->setDecorators(array(array('ViewHelper')));
			   
		$this->addElement($query)
			 ->addElement($submit);
			 
		// set element decorators
		$this->setElementDecorators(array(
									array('ViewHelper'),
									array('Label', array('tag' => '<span>'))
		));
		
		$submit->setDecorators(array(array('ViewHelper'),));
	}
}