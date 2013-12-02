<?php
class Voices_Form_Decorator_SimpleInput extends Zend_Form_Decorator_Abstract
{
	protected $_format = '<input id="%s" name="%s" type="text" value="%s" size="30"/>';
	
	public function render($content)
	{
		$element = $this->getElement();
		$name = htmlentities($element->getFullyQualifiedName());
		$id = htmlentities($element->getId());
		$value = htmlentities($element->getValue());
		
		$markup = sprintf($this->_format, $id, $name, $value);
		
		$placement = $this->getPlacement();
		$separator = $this->getSeparator();
		switch ($placement) {
			case self::PREPEND:
			   	return $markup . $separator . $content;
				break;
			case self::APPEND:
			default:
				return $content . $separator . $markup;
			break;
		}
	}
}