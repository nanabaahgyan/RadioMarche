<?php
class Voices_Form_Decorator_SimpleLabel extends Zend_Form_Decorator_Abstract
{
	protected $_format = '<label for="%s">%s</label>';
	
	public function render($content)
	{
		$element = $this->getElement();
		$id = htmlentities($element->getId());
		$label = htmlentities($element->getLabel());
		
		$markup = sprintf($this->_format, $id, $label);
		
		$placement = $this->getPlacement();
		$separator = $this->getSeparator();
		switch ($placement) {
			case self::APPEND:
			   	return $markup . $separator . $content;
			case self::PREPEND:
			default:
				return $content . $separator . $markup;
			break;
		}
	}
}