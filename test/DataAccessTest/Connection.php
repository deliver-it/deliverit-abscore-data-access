<?php

namespace ABSCore\DataAccessTest;

use Zend\Db\Adapter\Driver\ConnectionInterface;

class Connection implements ConnectionInterface
{
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
        return $this;
    }

    /**
     * Commit
     *
     * @return ConnectionInterface
     */
    public function commit()
    {
        return $this;
    }

    /**
     * Rollback
     *
     * @return ConnectionInterface
     */
    public function rollback()
    {
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
