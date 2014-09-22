<?php

namespace ABSCore\DataAccessTest;

use Zend\Di\ServiceLocator;
use Zend\Db\ResultSet\ResultSet;
use PHPUnit_Framework_TestCase;

use ABSCore\DataAccess\DBTable;

class DBTableTest extends PHPUnit_Framework_TestCase
{
    public function testInvalidResourceName()
    {
        $this->setExpectedException('Exception','The table name cannot be blank!');
        $dbTable = new DBTable('',array('id'), new ServiceLocator());
    }

    public function testInvalidPrimaryKey()
    {
        $this->setExpectedException('Exception','At last one primary key must be passed');
        $dbTable = new DBTable('table',null, new ServiceLocator());
    }


    public function testNonSetedAdapter()
    {
        $this->setExpectedException('Exception','An adapter is required');
        $dbTable = new DBTable('teste',array('id'), new ServiceLocator());
        $dbTable->getAdapter();
    }

    public function testFindWithoutResult()
    {
        $this->setExpectedException('ABSCore\DataAccess\RegistryNotFound','registry not found');
        $dbTable = new DBTable('teste','id', new ServiceLocator());
        $result = new ResultSet();
        $result->initialize(array());
        $tableMock = $this->getMockBuilder('Zend\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->expects($this->once())->method('select')->will($this->returnValue($result));


        $dbTable->setTableGateway($tableMock);
        $dbTable->find('1');
    }

}
