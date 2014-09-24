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

    /**
     * Teste para quando o nome do recurso é inválido
     *
     * @access public
     * @return null
     */
    public function testInvalidResourceName()
    {
        $this->setExpectedException('Exception','The table name cannot be blank!');
        $dbTable = new DBTable('',array('id'), $this->getServiceManager());
    }

    /**
     * Teste para quando as chaves primárias são inválidas
     *
     * @access public
     * @return null
     */
    public function testInvalidPrimaryKey()
    {
        $this->setExpectedException('Exception','At last one primary key must be passed');
        $dbTable = new DBTable('table',null, $this->getServiceManager());
    }


    /**
     * Teste para quando não é definido um adaptador
     *
     * @access public
     * @return null
     */
    public function testNonSetedAdapter()
    {
        $this->setExpectedException('Exception','An adapter is required');
        $dbTable = new DBTable('teste',array('id'), $this->getServiceManager());
        $dbTable->getAdapter();
    }

    /**
     * Teste de busca sem resultado
     *
     * @access public
     * @return null
     */
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

    /**
     * Teste de chaves primárias inválidas
     *
     * @access public
     * @return null
     */
    public function testFindWithInvalidPrimaryKeys()
    {
        $this->setExpectedException('Exception', '2 keys are expected but 1 was passed');
        $dbTable = new DBTable('teste',array('id','id2'), $this->getServiceManager());
        $tableMock = $this->getTableGatewayMock();
        $dbTable->setTableGateway($tableMock);
        $this->assertNotNull($dbTable->find('1'));
    }

    /**
     * Teste de busca passando uma chave inválida
     *
     * @access public
     * @return null
     */
    public function testFindWithNonPrimaryKeys()
    {
        $this->setExpectedException('Exception', 'The key "teste" is not a valid primary key (id)');
        $dbTable = new DBTable('teste','id', $this->getServiceManager());
        $tableMock = $this->getTableGatewayMock();
        $dbTable->setTableGateway($tableMock);
        $this->assertNotNull($dbTable->find(array('teste' => 1)));
    }

    /**
     * Teste de busca com sucesso
     *
     * @access public
     * @return null
     */
    public function testFind()
    {
        $dbTable = new DBTable('teste','id', $this->getServiceManager());
        $dbTable->setAdapter($this->getAdapterMock());
        $this->assertNotNull($dbTable->find('1'));
    }

    /**
     * Teste de busca com múltiplas chaves
     *
     * @access public
     * @return null
     */
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

    /**
     * Teste para quando um protótipo customizado é definido
     *
     * @access public
     * @return null
     */
    public function testCustomPrototype()
    {
        $result = $this->getMockBuilder('ArrayObject')->getMock();
        $result->expects($this->once())->method('exchangeArray');

        $service = $this->getServiceManager();
        $service->setService('TestePrototype',$result);

        $dbTable = new DBTable('teste','id', $service);
        $dbTable->setAdapter($this->getAdapterMock());

        $dbTable->find('1');
    }

    /**
     * Método auxiliar para obter um adaptador Mocado
     *
     * @access protected
     * @return null
     */
    protected function getAdapterMock()
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

        return $adapter;
    }

    /**
     * Obtenção de gerenciador de serviços
     *
     * @access protected
     * @return null
     */
    protected function getServiceManager()
    {
        return new ServiceManager(new ServiceConfig());
    }


    /**
     * Obtenção do mock de TableGateway
     *
     * @access protected
     * @return null
     */
    protected function getTableGatewayMock()
    {
        return $this->getMockBuilder('Zend\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();
    }

}
