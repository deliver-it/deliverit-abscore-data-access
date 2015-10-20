<?php

namespace ABSCore\DataAccessTest;

require 'Connection.php';
require 'Statement.php';

use Zend\Db\Adapter\Driver\DriverInterface;

class Driver implements DriverInterface
{

    protected $connection;

    public function __construct()
    {
        $this->connection = new Connection;
        $this->statement = new Statement;
    }

    /**
     * Get database platform name
     *
     * @param string $nameFormat
     * @return string
     */
    public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE)
    {
        return 'Mocked';
    }

    /**
     * Check environment
     *
     * @return bool
     */
    public function checkEnvironment()
    {
        return true;
    }

    /**
     * Get connection
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Create statement
     *
     * @param string|resource $sqlOrResource
     * @return StatementInterface
     */
    public function createStatement($sqlOrResource = null)
    {
        return $this->statement;
    }

    /**
     * Create result
     *
     * @param resource $resource
     * @return ResultInterface
     */
    public function createResult($resource)
    {
        return null;
    }

    /**
     * Get prepare type
     *
     * @return array
     */
    public function getPrepareType()
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    /**
     * Format parameter name
     *
     * @param string $name
     * @param mixed  $type
     * @return string
     */
    public function formatParameterName($name, $type = null)
    {
        return '?';
    }

    /**
     * Get last generated value
     *
     * @return mixed
     */
    public function getLastGeneratedValue()
    {
        return $this->getConnection()->getLastGeneratedValue();
    }

    public function setStatement(Statement $statement)
    {
        $this->statement = $statement;

        return $this;
    }
}
