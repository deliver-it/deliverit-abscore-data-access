<?php

namespace ABSCore\DataAccess;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Db\ResultSet\ResultSet;

/**
 * Class to make complex queries over DBTable objects
 *
 * @property array $from
 * @property mixed $select
 * @property array $joins
 * @property array $tree
 *
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class DBQuery
{
    use ServiceLocatorAwareTrait;

    /**
     * Separator of keys identifier
     */
    const IDENTIFIER_SEPARATOR = ':';

    /**
     * Attributes of from table
     *
     * @var array
     * @access protected
     */
    protected $from;

    /**
     * Select object
     *
     * @var mixed
     * @access protected
     */
    protected $select;

    /**
     * Set of joins to apply in query
     *
     * @var array
     * @access protected
     */
    protected $joins = array();

    /**
     * Set of conditions to filter
     *
     * @var array
     * @access protected
     */
    protected $where = array();

    /**
     * Tree of joins to parse information into result tree
     *
     * @var array
     * @access protected
     */
    protected $tree = array();

    /**
     * Array Object Prototype
     *
     * @var mixed
     * @access protected
     */
    protected $arrayObjectPrototype = null;


    protected $prefixes = [];

    /**
     * Class constructor
     *
     * @param DBTable $tableFrom Table from
     * @param array $columns     Set of columns to get of from table
     * @param mixed $service     Service locator
     * @access public
     */
    public function __construct(DBTable $tableFrom, $columns, $service)
    {
        $this->from = array('table' => $tableFrom, 'columns' => $columns);
        $this->addNode($tableFrom, $tableFrom->getTableName(), $columns);
        $this->setServiceLocator($service);
    }

    /**
     * Get the from table
     *
     * @access public
     * @return DBTable
     */
    public function getFromTable()
    {
        return $this->from['table'];
    }

    /**
     * Fetch results
     *
     * @access public
     * @return ResultSet
     */
    public function fetch()
    {
        $select = $this->getSelect();
        $data = $this->from['table']->getTableGateway()->selectWith($select);
        // reset select
        $this->select = null;
        $data = $this->parse($data->toArray());
        $result = new ResultSet();
        if (!is_null($this->arrayObjectPrototype)) {
            $result->setArrayObjectPrototype($this->arrayObjectPrototype);
        }
        $result->initialize($data);
        return $result;
    }


    /**
     * Set an array object prototype to use into result
     *
     * @param mixed $object
     * @access public
     * @return null
     */
    public function setArrayObjectPrototype($object)
    {
        $this->arrayObjectPrototype = $object;
        return $this;
    }

    /**
     * Get select object
     *
     * @access public
     * @return mixed
     */
    public function getSelect()
    {
        // is select available?
        if (is_null($this->select)) {
            // get initial select
            $select = $this->from['table']->getTableGateway()->getSql()->select();
            // normalize columns names of from table
            $cols = $this->from['columns'];
            foreach ($this->from['table']->getPrimaryKey() as $col) {
                if (!in_array($col, $cols)) {
                    $cols[] = $col;
                }
            }
            $columns = $this->normalizeColumns($this->from['table']->getTableName(), $cols);
            // define columns
            $select->columns($columns);
            // loop to add joins
            foreach ($this->joins as $join) {
                $this->createJoin($select, $join);
            }
            // define where conditions
            $select->where($this->where);

            $this->select = $select;
        }

        return $this->select;
    }

    /**
     * Add a where conditions
     *
     * @param array|string $conditions
     * @param string|DBTable|null $table
     * @access public
     * @return null
     */
    public function addWhereConditions($conditions, $table = null)
    {
        if (is_null($table)) {
            if (is_array($conditions)) {
                $this->where = array_merge($this->where, $conditions);
            } else {
                $this->where[] = (string)$conditions;
            }
        } else {
            if (!is_array($conditions)) {
                $conditions = array((string)$conditions);
            }
            $alias = $table;
            if ($table instanceof DBTable) {
                $alias = $table->getTableName();
            }
            foreach ($conditions as $condition) {
                $condition = str_replace('$1', $alias, $condition);
                $this->where[] = $condition;
            }
        }

        return $this;
    }

    /**
     * Make a join with new table
     *
     * This function adds to two structures:
     *
     *     1- Joins: This structure is responsible for keep all information to make joins into select object
     *     2- Tree:  This structure is responsible for keep all information to enable parser result information into
     *                a result tree
     *
     * For conditions those tokens are available:
     *
     *     1- $1: It will be replaced by new table name
     *     2- $2: It will be replaced by old table nam
     *
     * If old table not exists then a RuntimeException is thrown
     *
     * @param array|DBTable $a         New Table
     * @param string|DBTable $b         Old Table
     * @param string $conditions Conditions of join
     * @param array $columns     Columns of new table
     * @param string $type       Type of join (default is INNER)
     * @access public
     * @return DBquery
     */
    public function join($a, $b, $conditions, array $columns, $type = 'inner')
    {
        if (is_array($a)) {
            $alias = current(array_keys($a));
            $table = current($a);
        } else {
            $alias = null;
            $table = $a;
        }

        if (!($table instanceof DBTable)) {
            throw new \RuntimeException('Table must be a instance of DBTable');
        }

        if (is_null($alias)) {
            $alias = $table->getTableName();
        }

        if ($b instanceof DBTable) {
            $aliasOld = $b->getTableName();
        } else {
            $aliasOld = (string)$b;
        }

        if (!$this->addNode($table, $alias, $columns, $aliasOld)) {
            throw new \RuntimeException('The table '. $aliasOld.' was not identified');
        }

        $this->joins[] = array('table' => $table, 'alias' => $alias, 'related' => $b, 'columns' => $columns, 'conditions' => $conditions, 'type' => $type);


        return $this;
    }

    /**
     * Get prefix name of Table
     *
     * @param string $alias
     * @access public
     * @return string
     */
    public function getPrefix($alias)
    {
        $key = array_search($alias, $this->prefixes);
        if ($key === false) {
            $key = count($this->prefixes);
            $this->prefixes[] = $alias;
        }
        return "t{$key}_";
    }


    /**
     * Add a node to join tree as a child of old node, if "$old" is null then "$new" will be considered root node
     *
     * @param DBTable $new   New node to add into tree
     * @param string $alias  Alias of new node
     * @param array $columns Columns of node added
     * @param string $old   Node where new node must be inserted
     * @access protected
     * @return boolean True if is able to insert new node | False otherwise
     */
    protected function addNode(DBTable $new, $alias, array $columns, $old = null)
    {
        if ($old == null && empty($this->tree)) {
            $this->tree['root'][$alias] = array(
                'node' => $new,
                'alias' => $alias,
                'children' => array(),
                'columns' => $columns,
            );
            $result = true;
        } else {
            $result = $this->addSubNode($old, $new, $alias, $this->tree['root'], $columns);
        }
        return $result;
    }

    /**
     * This is called when the tree is not empty. The "$new" will be inserted as a child of searched node
     *
     * @param string $index   Index of searched node
     * @param DBTable $new    New table to be inserted
     * @param string $alias   Alias of new node
     * @param array $node     Actual node
     * @param array $columns  Set of collumns of new table
     * @access protected
     * @return boolean True if is able to insert new node | False otherwise
     */
    protected function addSubNode($index, DBTable $new, $alias, &$node, $columns)
    {
        foreach ($node as $key => &$child) {
            if ($key === $index) {
                $child['children'][$alias] = array(
                    'node' => $new,
                    'alias' => $alias,
                    'children' => array(),
                    'columns' => $columns,
                );
                return true;
            } else {
                if ($this->addSubNode($index, $new, $alias, $child['children'], $columns)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Create a join into select
     *
     * @param mixed $select Base select
     * @param array $join   Set of joins options
     * @access protected
     * @return DBQuery
     */
    protected function createJoin($select, $join)
    {
        $cols = $join['columns'];
        foreach ($join['table']->getPrimaryKey() as $col) {
            if (!in_array($col, $cols)) {
                $cols[] = $col;
            }
        }
        $cols = $this->normalizeColumns($join['alias'], $cols);
        $conditions = str_replace(
            array('$1', '$2'),
            array($join['alias'], $join['related']->getTableName()),
            $join['conditions']
        );
        $name = $join['table']->getTableName();
        $select->join(array($join['alias'] => $name), $conditions, $cols, $join['type']);
        return $this;
    }

    /**
     * Parse fetch results to make a tree as defined by joins
     *
     * @param array $data        Results to parse
     * @param array $node        Set of current node
     * @param string $name       Name of node
     * @param array $conditions  Set of conditions to identify entry
     * @access protected
     * @return array Parsed Result
     */
    protected function parse($data, $node = null, $name = null, $conditions = array())
    {
        // is first request?
        if (is_null($node)) {
            // get root node
            $aux = $this->tree['root'];
            $name = current(array_keys($aux));
            $node = current($aux);
        }

        // initialize result
        $result = array();

        // table prefix name
        $prefixName = $this->getPrefix($name);

        // set of all urrent conditions
        $allConditions = array();

        // loop to parse results
        foreach ($data as $i => $row) {
            // set of table primary keys
            $keys = $node['node']->getPrimaryKey();

            // initialize current information
            $info = array();

            // verify if conditions are corrects
            $isInConditions = true;
            if (!empty($conditions)) {
                foreach ($conditions as $key => $value) {
                    if ($row[$key] != $value) {
                        $isInConditions = false;
                        break;
                    }
                }
            }
            // entry is not from this set?
            if (!$isInConditions) {
                continue;
            }

            $localConditions = array();

            // loop to get only values of current table and which is in requested columns
            foreach ($row as $column_name => $value) {
                // is this column part of current table?
                if (($pos = strpos($column_name, $prefixName)) === 0) {
                    // get realname of column
                    $realName = substr($column_name, strlen($prefixName));
                    // is this column a primary key?
                    if (in_array($realName, $keys)) {
                        $localConditions[$column_name] = $value;
                    }
                    // is this column a requested column?
                    if (in_array($realName, $node['columns']) || in_array($realName, array_keys($node['columns']))) {
                        $info[$realName] = $value;
                    }
                }
            }
            // make identifier of this element
            $identifier = $this->makeIdentifier($localConditions);
            // was element inserted?
            if (empty($result[$identifier]) && !empty($identifier)) {
                $allConditions[] = $localConditions;
                $result[$identifier] = $info;
            }
        }

        // loop to parse children tables
        foreach ($node['children'] as $name => $child) {
            // loop to insert each child element at correct parent
            foreach ($allConditions as $localConditions) {
                $identifier = $this->makeIdentifier($localConditions);
                // new conditions was passed conditions plus current conditions
                $newConditions = array_merge($conditions, $localConditions);

                $result[$identifier][$name] = array_values($this->parse($data, $child, $name, $newConditions));
            }
        }

        return array_values($result);
    }

    /**
     * Make a key identifier
     *
     * @param array $keys Set of keys
     * @access protected
     * @return string
     */
    protected function makeIdentifier(array $keys)
    {
        return implode(self::IDENTIFIER_SEPARATOR, $keys);
    }


    /**
     * Normalize table columns name
     *
     * The passed columns name are perfixed by table prefix
     *
     * @param string $alias Table alias
     * @param array $columns Set of table columns
     * @access protected
     * @return array Normalized Columns
     */
    protected function normalizeColumns($alias, $columns)
    {
        $identifier = $this->getPrefix($alias);
        $result = array();
        foreach ($columns as $key => $value)
        {
            if (is_string($value)) {
                $key = $identifier . $value;
            } else {
                $key = $identifier . $key;
            }
            $result[$key] = $value;
        }
        return $result;
    }
}
