<?php

namespace ABSCore\DataAccessTest;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use PHPUnit_Framework_TestCase;

use ABSCore\DataAccess\DBTable;

class DBTableTest extends PHPUnit_Framework_TestCase
{

    public function testInvalidResourceName()
    {
        $this->setExpectedException('Exception','The table name cannot be blank!');
        $dbTable = new DBTable('',array('id'), $this->getServiceManager());
    }

    public function testInvalidPrimaryKey()
    {
        $this->setExpectedException('Exception','At last one primary key must be passed');
        $dbTable = new DBTable('table',null, $this->getServiceManager());
    }


    public function testNonSetedAdapter()
    {
        $this->setExpectedException('Exception','An adapter is required');
        $dbTable = new DBTable('teste',array('id'), $this->getServiceManager());
        $dbTable->getAdapter();
    }

    public function testFindWithoutResult()
    {
        $this->setExpectedException('Exception','registry not found');
        $dbTable = new DBTable('teste','id', $this->getServiceManager());
        $result = new ResultSet();
        $result->initialize(array());
        $tableMock = $this->getTableGatewayMock();
        $tableMock->expects($this->once())->method('select')->will($this->returnValue($result));


        $dbTable->setTableGateway($tableMock);
        $dbTable->find('1');
    }

    public function testFindWithInvalidPrimaryKeys()
    {
        $this->setExpectedException('Exception', '2 keys are expected but 1 was passed');
        $dbTable = new DBTable('teste',array('id','id2'), $this->getServiceManager());
        $tableMock = $this->getTableGatewayMock();
        $dbTable->setTableGateway($tableMock);
        $this->assertNotNull($dbTable->find('1'));
    }

    public function testFindWithNonPrimaryKeys()
    {
        $this->setExpectedException('Exception', 'The key "teste" is not a valid primary key (id)');
        $dbTable = new DBTable('teste','id', $this->getServiceManager());
        $tableMock = $this->getTableGatewayMock();
        $dbTable->setTableGateway($tableMock);
        $this->assertNotNull($dbTable->find(array('teste' => 1)));
    }

    public function testFind()
    {
        $dbTable = new DBTable('teste','id', $this->getServiceManager());
        $resultSet = new ResultSet();
        $resultSet->initialize(array(array('id' => 1)));
        $tableMock = $this->getTableGatewayMock();
        $tableMock->expects($this->once())->method('select')->will($this->returnValue($resultSet));
        $dbTable->setTableGateway($tableMock);
        $this->assertNotNull($dbTable->find('1'));
    }

    public function testFindWithMultipleKeys()
    {
        $dbTable = new DBTable('teste',array('id','id2'), $this->getServiceManager());
        $resultSet = new ResultSet();
        $resultSet->initialize(array(array('id' => 1,'id2' => 2)));
        $tableMock = $this->getTableGatewayMock();
        $tableMock->expects($this->once())->method('select')->will($this->returnValue($resultSet));
        $dbTable->setTableGateway($tableMock);
        $this->assertNotNull($dbTable->find(array('id' => 1, 'id2' => 2)));
    }

    public function testCustomPrototype()
    {
        $adapter = $this->getMockBuilder('Zend\Db\Adapter\Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $platform = $this->getMockBuilder('Zend\Db\Adapter\Platform\Mysql')
            ->disableOriginalConstructor()
            ->getMock();
        $platform->method('getName')->will($this->returnValue(null));


        $statement = $this->getMock('Zend\Db\Adapter\Driver\Mysqli\Statement');
        $statement->method('execute')->will($this->returnValue(array(array('id' => 1))));

        $driver = $this->getMockBuilder('Zend\Db\Adapter\Driver\Mysqli\Mysqli')
            ->disableOriginalConstructor()
            ->getMock();

        $driver->method('createStatement')->will($this->returnValue($statement));

        $adapter->method('getPlatform')->will($this->returnValue($platform));
        $adapter->method('getDriver')->will($this->returnValue($driver));

        $result = $this->getMockBuilder('ArrayObject')->getMock();
        $result->expects($this->once())->method('exchangeArray');

        $service = $this->getServiceManager();
        $service->setService('TestePrototype',$result);

        $dbTable = new DBTable('teste','id', $service);
        $dbTable->setAdapter($adapter);

        $dbTable->find('1');
    }

    protected function getServiceManager()
    {
        return new ServiceManager(new ServiceConfig());
    }


    protected function getTableGatewayMock()
    {
        return $this->getMockBuilder('Zend\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();
    }

}
