<?php

namespace ABSCore\DataAccess\Paginator\Adapter;

use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Db\Sql;
use ABSCore\DataAccess\DBQuery as Query;

class DBQuery implements AdapterInterface
{
    protected $query;

    /**
     * Class Constructor
     *
     * @param Query $query
     * @access public
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Get current items
     *
     * @param int $offset
     * @param int $itemCountPerPage
     * @access public
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $query = $this->query;
        $table = $query->getFromTable();

        // clone Select to get IDS to filter
        $select = clone $query->getSelect();

        // remove JOINS COLUMNS
        $joins = $select->getRawState($select::JOINS);
        $select->reset($select::JOINS);
        $ids = $table->getPrimaryKey();
        foreach ($joins as $join) {
            $select->join($join['name'], $join['on'], array(), $join['type']);
        }

        // get IDS
        $select
            ->limit($itemCountPerPage)
            ->offset($offset)
            ->columns($ids)
            ->quantifier(Sql\Select::QUANTIFIER_DISTINCT);
        $result = $table->getTableGateway()->selectWith($select);

        // make conditions
        $tableName = $table->getTableName();
        $conditions = array();
        foreach ($result->toArray() as $row) {
            foreach ($row as $col => $value) {
                $conditions[$tableName.'.'.$col][] = $value;
            }
        }
        // fetch curret page
        $query->getSelect()->where($conditions);
        $result = $query->fetch();

        return $result;
    }

    /**
     * Get total of items
     *
     * @access public
     * @return int
     */
    public function count()
    {
        $select = clone $this->query->getSelect();
        $countExpression = new Sql\Expression('COUNT(DISTINCT '.implode(', ', $this->getPrimaryKeys()).')');
        $select->columns(array('c' => $countExpression));
        $table = $this->query->getFromTable();
        $result = $table->getTableGateway()->selectWith($select);
        $count = (int)$result->current()['c'];
        return $count;
    }

    /**
     * Get Primary Key columns with prefix
     *
     * @access protected
     * @return array
     */
    protected function getPrimaryKeys()
    {
        $table = $this->query->getFromTable();
        $tableName = $table->getTableName();
        $ids = $table->getPrimaryKey();
        foreach ($ids as &$id) {
            $id = $tableName . '.' . $id;
        }
        return $ids;
    }
}
