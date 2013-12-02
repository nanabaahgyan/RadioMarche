<?php
/**
 * This class is used for adding field for search
 *
 */
class Voices_Search_Market extends Zend_Search_Lucene_Document
{
	/**
      * Constructor. Creates our indexable document and adds all
      * necessary fields to it using the passed in document
      */
    public function __construct($param)
    {
    	// data that is available for search and is stored in full
		$this->addField(Zend_Search_Lucene_Field::text('Product', $param['prod_name'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::text('Quantity', $param['quantity'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::text('Price', $param['price'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::text('Village', $param['Voices_Model_Contacts']['village'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::text('Zone', $param['Voices_Model_Contacts']['zone'], 'UTF-8'));
		
		// data that is available for search, but isn’t stored in the index in full
		$this->addField(Zend_Search_Lucene_Field::unIndexed('Quality', $param['quality'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::unIndexed('FirstName', $param['Voices_Model_Contacts']['first_name'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::unIndexed('LastName', $param['Voices_Model_Contacts']['last_name'], 'UTF-8'));
		$this->addField(Zend_Search_Lucene_Field::unIndexed('Phone', $param['Voices_Model_Contacts']['tel_number'], 'UTF-8'));
		
		// data that isn’t available for searching, but is stored with our document
		$this->addField(Zend_Search_Lucene_Field::unIndexed('RecordID', $param['prod_id'], 'UTF-8'));
    }
}