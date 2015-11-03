<?php

namespace ABSCore\DataAccessTest;

use Zend\Db\Adapter\Driver\ConnectionInterface;

class Connection implements ConnectionInterface
{
    protected $inTransaction = false;

    /**
     * Get current schema
     *
     * @return string
     */
    public function getCurrentSchema()
    {
        return 'database';
    }

    /**
     * Get resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return null;
    }

    /**
     * Connect
     *
     * @return ConnectionInterface
     */
    public function connect()
    {
        return $this;
    }

    /**
     * Is connected
     *
     * @return bool
     */
    public function isConnected()
    {
        return true;
    }

    /**
     * Disconnect
     *
     * @return ConnectionInterface
     */
    public function disconnect()
    {
        return $this;
    }

    /**
     * Begin transaction
     *
     * @return ConnectionInterface
     */
    public function beginTransaction()
    {
        $this->inTransaction = true;
        return $this;
    }

    public function inTransaction()
    {
        return (bool)$this->inTransaction;
    }

    public function setInTransaction($flag)
    {
        $this->inTransaction = $flag;
    }

    /**
     * Commit
     *
     * @return ConnectionInterface
     */
    public function commit()
    {
        $this->inTransaction = false;
        return $this;
    }

    /**
     * Rollback
     *
     * @return ConnectionInterface
     */
    public function rollback()
    {
        $this->inTransaction = false;
        return $this;
    }

    /**
     * Execute
     *
     * @param  string $sql
     * @return ResultInterface
     */
    public function execute($sql)
    {
        return null;
    }

    /**
     * Get last generated id
     *
     * @param  null $name Ignored
     * @return int
     */
    public function getLastGeneratedValue($name = null)
    {
        return 1;
    }
}
