<?php
    /**
     * Class DatabaseObject
     * Abstract class used to easily manipulate data in a database table
     * via simple load/save/delete methods.
     *
     *
     * @author Quentin Zervas
     * @package Application_Model
     */
    abstract class Voices_Model_DatabaseObject
    {
    	/**
    	 * @var const int
    	 */
        const TYPE_TIMESTAMP = 1;
        
        /**
         * @var const int
         */
        const TYPE_BOOLEAN   = 2;

        /**
         * @var array
         * @access protected
         */
        protected static $types = array(self::TYPE_TIMESTAMP, self::TYPE_BOOLEAN);

        /**
         * @var int
         * @access private
         */
        private $_id = null;
        
        /**
         * @var array
         * @access private
         */
        private $_properties = array();

		/**
		 * @var string
		 * @access protected
		 */
        protected $_db = null;
        
        /**
         * @var string
         * @access protected
         */
        protected $_table = '';
        
        /**
         * @var string
         * @access protected
         */
        protected $_idField = '';

        /**
         * Constructor
         *
         * @param Zend_Db_Adapter_Abstract $db
         * @param string $table
         * @param string $idField
         */
        public function __construct(Zend_Db_Adapter_Abstract $db, $table, $idField)
        {
            $this->_db = $db;
            $this->_table = $table;
            $this->_idField = $idField;
        }

        /**
         * Loads a record by performing a select query. Returns true if the record is
         * loaded.
         *
         * @param int $id
         * @param string $field
         */
        public function load($id, $field = null)
        {
            if (strlen($field) == 0)
                $field = $this->_idField;

            if ($field == $this->_idField) {
                $id = (int) $id;
                if ($id <= 0)
                    return false;
            }

            $query = sprintf('select %s from %s where %s = ?',
                             join(', ', $this->getSelectFields()),
                             $this->_table,
                             $field);

            $query = $this->_db->quoteInto($query, $id);

            return $this->_load($query);
        }

        /**
         * Helper function to select fields from table
         *
         * @param string $prefix
         * @return string
         */
        protected function getSelectFields($prefix = '')
        {
            $fields = array($prefix . $this->_idField);
            foreach ($this->_properties as $k => $v)
                $fields[] = $prefix . $k;

            return $fields;
        }

        /**
         * Helper function to load query from database
         *
         * @param string $query
         * @return bool
         */
        protected function _load($query)
        {
            $result = $this->_db->query($query);
            $row = $result->fetch();
            if (!$row)
                return false;

            $this->_init($row);

            $this->postLoad();

            return true;
        }

        /**
         * Helper function
         *
         * @param string $row
         */
        public function _init($row)
        {
            foreach ($this->_properties as $k => $v) {
                $val = $row[$k];

                switch ($v['type']) {
                    case self::TYPE_TIMESTAMP:
                        if (!is_null($val))
                            $val = strtotime($val);
                        break;
                    case self::TYPE_BOOLEAN:
                        $val = (bool) $val;
                        break;
                }

                $this->_properties[$k]['value'] = $val;
            }
            $this->_id = (int) $row[$this->_idField];
        }

		/**
		 * Save record into database
		 *
		 * @param bool $useTransactions
		 */
        public function save($useTransactions = true)
        {
            $update = $this->isSaved();

            if ($useTransactions)
                $this->_db->beginTransaction();

            if ($update)
                $commit = $this->preUpdate();
            else
                $commit = $this->preInsert();

            if (!$commit) {
                if ($useTransactions)
                    $this->_db->rollback();
                return false;
            }

            $row = array();

            foreach ($this->_properties as $k => $v) {
                if ($update && !$v['updated'])
                    continue;

                switch ($v['type']) {
                    case self::TYPE_TIMESTAMP:
                        if (!is_null($v['value'])) {
                            if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql)
                                $v['value'] = date('Y-m-d H:i:sO', $v['value']);
                            else
                                $v['value'] = date('Y-m-d H:i:s', $v['value']);
                        }
                        break;

                    case self::TYPE_BOOLEAN:
                        $v['value'] = (int) ((bool) $v['value']);
                        break;
                }

                $row[$k] = $v['value'];
            }

            if (count($row) > 0) {
                // perform insert/update
                if ($update) {
                    $this->_db->update($this->_table, $row, sprintf('%s = %d', $this->_idField, $this->getId()));
                }
                else {
                    $this->_db->insert($this->_table, $row);
                    $this->_id = $this->_db->lastInsertId($this->_table, $this->_idField);
                }
            }

            // update internal id

            if ($commit) {
                if ($update)
                    $commit = $this->postUpdate();
                else
                    $commit = $this->postInsert();
            }

            if ($useTransactions) {
                if ($commit)
                    $this->_db->commit();
                else
                    $this->_db->rollback();
            }

            return $commit;
        }

        /**
         * If a record has been loaded, this function performs an SQL delete query.
         *
         * @param bool $useTransactions
         * @return bool
         */
        public function delete($useTransactions = true)
        {
            if (!$this->isSaved())
                return false;

            if ($useTransactions)
                $this->_db->beginTransaction();

            $commit = $this->preDelete();

            if ($commit) {
                $this->_db->delete($this->_table, sprintf('%s = %d', $this->_idField, $this->getId()));
            }
            else {
                if ($useTransactions)
                    $this->_db->rollback();
                return false;
            }

            $commit = $this->postDelete();

            $this->_id = null;

            if ($useTransactions) {
                if ($commit)
                    $this->_db->commit();
                else
                    $this->_db->rollback();
            }

            return $commit;
        }

        /**
         * Returns true if a record has previously been loaded with load().
         *
         * @return int
         */
        public function isSaved()
        {
            return $this->getId() > 0;
        }

        /**
         * Retrieves the database ID of a saved record.
         */
        public function getId()
        {
            return (int) $this->_id;
        }

        /**
         * Get the database
         *
         * @return string
         */
        public function getDb()
        {
            return $this->_db;
        }
		
        /**
         * Magic set
         *
         * @param string $name
         * @param string $value
         * @return bool
         */
        public function __set($name, $value)
        {
            if (array_key_exists($name, $this->_properties)) {
                $this->_properties[$name]['value'] = $value;
                $this->_properties[$name]['updated'] = true;
                return true;
            }

            return false;
        }

        /**
         * Magic get
         *
         * @param string $name
         * @return string
         */
        public function __get($name)
        {
            return array_key_exists($name, $this->_properties) ? $this->_properties[$name]['value'] : null;
        }

        /**
         * Add a record a table
         *
         * @param string $field
         * @param string $default
         * @param string $type
         */
        protected function add($field, $default = null, $type = null)
        {
            $this->_properties[$field] = array('value'   => $default,
                                               'type'    => in_array($type, self::$types) ? $type : null,
                                               'updated' => false);
        }

        /**
         * Called prior to inserting a new record
         *
         * @return bool
         */
        protected function preInsert()
        {
            return true;
        }

        /**
         * Called after a new record is saved.
         *
         * @return bool
         */
        protected function postInsert()
        {
            return true;
        }

        /**
         * Called prior to an existing record being updated.
         *
         * @return bool
         */
        protected function preUpdate()
        {
            return true;
        }

        /**
         * Called after an existing record is updated.
         *
         * @return bool
         */
        protected function postUpdate()
        {
            return true;
        }

        /**
         * Called prior to an existing record being deleted.
         *
         * @return bool
         */
        
        protected function preDelete()
        {
            return true;
        }

        /**
         * Called after a record has been deleted.
         *
         * @return bool
         */
        protected function postDelete()
        {
            return true;
        }

        /**
         * Called after a record is successfully loaded.
         *
         * @return bool
         */
        protected function postLoad()
        {
            return true;
        }

        public static function BuildMultiple($db, $class, $data)
        {
            $ret = array();

            if (!class_exists($class))
                throw new Exception('Undefined class specified: ' . $class);

            $testObj = new $class($db);

            if (!$testObj instanceof DatabaseObject)
                throw new Exception('Class does not extend from DatabaseObject');

            foreach ($data as $row) {
                $obj = new $class($db);
                $obj->_init($row);

                $ret[$obj->getId()] = $obj;
            }

            return $ret;
        }
    }
?>