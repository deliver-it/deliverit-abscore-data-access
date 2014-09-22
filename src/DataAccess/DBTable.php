<?php

namespace ABSCore\DataAccess;

use Zend\Di\ServiceLocatorInterface;
use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Filter\Word\SeparatorToCamelCase;
use Exception;
use ArrayObject;

class DBTable implements DataAccessInterface
{

    const PROTOTYPE_SUFFIX = 'Prototype';

    protected $serviceLocator;
    protected $primaryKey;
    protected $tableName;
    protected $tableGateway;
    protected $adapter;

    public function __construct($resource, $primaryKey, ServiceLocatorInterface $service)
    {
        $this->serviceLocator = $service;
        $this->setPrimaryKey($primaryKey);
        $this->setTableName($resource);
    }

    public function find($primaryKey)
    {
        $condition = $this->makeFindCondition($primaryKey);
        $rowset = $this->getTableGateway()->select($condition);
        $row = $rowset->current();
        if (!$row) {
            //@TODO change exception type
            throw new Exception('registry not found');
        }
        return $row;
    }

    public function fetchAll($conditions, array $options)
    {
    }
    public function save($data)
    {
    }
    public function delete($conditions)
    {
    }


    public function setTableGateway(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
        return $this;
    }

    public function getTableGateway()
    {
        if (is_null($this->tableGateway)) {
            $this->createTableGateway();
        }
        return $this->tableGateway;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    public function getAdapter()
    {
        if (is_null($this->adapter)) {
            throw new Exception('An adapter is required!');
        }
        return $this->adapter;
    }

    protected function createTableGateway()
    {
        $resultSet = new ResultSet($this->getPrototype());
        $tableGateway = new TableGateway($this->getAdapter(), null, $resultSet);
        $this->setTableGateway($TableGateway);

        return $this;
    }

    protected function getPrototype()
    {
        $table = $this->getTableName();
        $filter = new SeparatorToCamelCase();
        $prototypeName = $filter->filter($table).self::PROTOTYPE_SUFFIX;
        $serviceLocator = $this->getServiceLocator();
        if ($serviceLocator->has($prototypeName)) {
            $prototype = $serviceLocator->get($prototypeName);
        } else {
            $prototype = new ArrayObject();
        }
        return $prototype;
    }

    protected function makeFindCondition($primaryKey) {
        $this->verifyPrimaryKey($primaryKey);
        if (!is_array($primaryKey)) {
            $primaryKey = array((string)$primaryKey);
        }

        $keys = $this->getPrimaryKey();

        $conditions = array();
        foreach ($primaryKey as $key => $value) {
            if (is_string($key)) {
                if (!in_array($key, $keys)) {
                    $message = sprintf('The key "%s" is not a valid primary key (%s)',$key,implode(',',$keys));
                    throw new Exception($message);
                }
                $conditions[$key] = $value;
            } else {
                $conditions[$keys[$key]] = $value;
            }
        }
        return $conditions;
    }

    protected function verifyPrimaryKey($primaryKey)
    {
        $primaryCount = count($this->getPrimaryKey());
        if (!is_array($primaryKey)) {
            $passed = 1;
        } else {
            $passed = count($primaryKey);
        }
        if ($passed != $primaryCount) {
            $message = sprintf('%d keys are expected but %d was passed', $primaryCount, $passed);
            throw new Exception($message);
        }

        return $this;
    }

    protected function setPrimaryKey($primaryKey)
    {
        if (empty($primaryKey)) {
            throw new Exception('At last one primary key must be passed!');
        }

        if (!is_array($primaryKey)) {
            $primaryKey = array((string)$primaryKey);
        }
        $this->primaryKey = $primaryKey;
        return $this;
    }

    protected function setTableName($name)
    {
        $name = (string)$name;
        if (empty($name)) {
            throw new Exception('The table name cannot be blank!');
        }
        $this->tableName = $name;
    }
}
