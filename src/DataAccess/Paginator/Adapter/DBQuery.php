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
        $internalSelect = clone $query->getSelect();

        // remove JOINS COLUMNS
        $joins = $internalSelect->getRawState($internalSelect::JOINS);
        $internalSelect->reset($internalSelect::JOINS);
        $ids = $table->getPrimaryKey();
        foreach ($joins as $join) {
            $internalSelect->join($join['name'], $join['on'], array(), $join['type']);
        }

        // get IDS
        $internalSelect->columns($ids);

        $joinConditions = [];
        foreach ($ids as $id) {
            $joinConditions[] = "t.$id = s.$id";
        }

        $distinctSelect = new Sql\Select();
        $distinctSelect
            ->from(['internalSelect' => $internalSelect])
            ->quantifier(Sql\Select::QUANTIFIER_DISTINCT);

        $adapter = $table->getTableGateway()->getAdapter();
        $select = new Sql\Select();
        $select
            ->columns($ids)
            ->from(['distinctSelect' => $distinctSelect])
            ->limit($itemCountPerPage)
            ->offset($offset);

        $sql = new Sql\Sql($adapter);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        // make conditions
        $tableName = $table->getTableAlias();
        $conditions = array();
        foreach ($result as $row) {
            foreach ($row as $col => $value) {
                $conditions[$tableName.'.'.$col][] = $value;
            }
        }
        // fetch curret page
        $query->getSelect()->where($conditions);
        $result = $query->fetch();
        $result = new \ArrayIterator($result->toArray());
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
        $query = $this->query;
        $table = $query->getFromTable();
        $joins = $select->getRawState($select::JOINS);
        $select->reset($select::JOINS);
        $ids = $table->getPrimaryKey();
        foreach ($joins as $join) {
            $select->join($join['name'], $join['on'], array(), $join['type']);
        }
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
        $tableName = $table->getTableAlias();
        $ids = $table->getPrimaryKey();
        foreach ($ids as &$id) {
            $id = $tableName . '.' . $id;
        }
        return $ids;
    }
}
