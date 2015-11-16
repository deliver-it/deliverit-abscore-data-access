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
        $this->setQuery($query);
    }

    protected function setQuery(Query $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get current items
     *
     * @param int $offset
     * @param int $itemCountPerPage
     * @access public
     * @return \ArrayIterator
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $query = $this->getQuery();
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
        $conditions = [];
        foreach ($result as $row) {
            foreach ($row as $col => $value) {
                $column = $this->getPlatform()->quoteIdentifierInFragment($tableName.'.'.$col);
                $conditions[$column][] = $value;
            }
        }
        // fetch curret page
        $query->getSelect()->where($conditions);
        $result = $query->fetch();
        $result = new \ArrayIterator($result->toArray());
        return $result;
    }

    /**
     * Get Database Platform
     *
     * @access protected
     * @return \Zend\Db\Adapter\Platform\PlatformInterface
     */
    protected function getPlatform()
    {
        return $this->getQuery()->getFromTable()->getTableGateway()->getAdapter()->getPlatform();
    }

    /**
     * Get total of items
     *
     * @access public
     * @return int
     */
    public function count()
    {
        $query = $this->getQuery();
        $select = clone $query->getSelect();

        $table = $query->getFromTable();

        $joins = $select->getRawState($select::JOINS);

        $select->reset($select::JOINS);

        foreach ($joins as $join) {
            $select->join($join['name'], $join['on'], [], $join['type']);
        }

        $countExpression = new Sql\Expression('COUNT(DISTINCT '.implode(', ', $this->getPrimaryKeys()).')');
        $select->columns(array('c' => $countExpression));

        $table = $query->getFromTable();
        $result = $table->getTableGateway()->selectWith($select);

        $count = (int)$result->current()['c'];
        return $count;
    }

    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get Primary Key columns with prefix and quoted
     *
     * @access protected
     * @return array
     */
    protected function getPrimaryKeys()
    {
        $table = $this->getQuery()->getFromTable();
        $tableName = $table->getTableAlias();
        $ids = $table->getPrimaryKey();
        foreach ($ids as &$id) {
            $id = $this->getPlatform()->quoteIdentifierInFragment($tableName . '.' . $id);

        }
        return $ids;
    }
}
