<?php

namespace ABSCore\DataAccessTest;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

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
        $this->setExpectedException('ABSCore\DataAccess\Exception\UnknowRegistryException','registry not found');
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
     * Teste de busca de registros
     *
     * @access public
     * @return null
     */
    public function testFetchAll()
    {
        $adapter = $this->getAdapterMock();
        $dbTable = new DBTable('table','id', $this->getServiceManager());
        $dbTable->setAdapter($adapter);
        $data = $dbTable->fetchAll(array('id' => 1), array('page' => 1));
        $this->assertInstanceOf('Zend\Paginator\Paginator',$data);
    }

    /**
     * testOrdered
     *
     * @access public
     * @return null
     */
    public function testOrderedFetchAll()
    {
        $select = $this->getMockBuilder('Zend\Db\Sql\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $select->expects($this->exactly(2))
            ->method('order')
            ->with('id ASC');

        $sql = $this->getMockBuilder('Zend\Db\Sql\Sql')
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->exactly(2))
            ->method('select')
            ->will($this->returnValue($select));

        $tableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway', // className
            array('initialize','executeSelect'), // methods that will maintan behavior
            array(), // construct params
            'TableGateway', // className of mocked object
            false // enable real constructor
        );

        $reflectionClass = new ReflectionClass($tableGateway);
        $property = $reflectionClass->getProperty('sql');
        $property->setAccessible(true);
        $property->setValue($tableGateway,$sql);

        $dbTable = new DBTable('table','id', $this->getServiceManager());
        $dbTable->setTableGateway($tableGateway);
        $dbTable->fetchAll(null, array('order' => 'id ASC'));
        $dbTable->fetchAll(null, array('paginated' => false, 'order' => 'id ASC'));
    }

    /**
     * Teste para busca sem paginação
     *
     * @access public
     * @return null
     */
    public function testUnpaginatedFetchAll()
    {
        $adapter = $this->getAdapterMock();
        $dbTable = new DBTable('table','id', $this->getServiceManager());
        $dbTable->setAdapter($adapter);
        $data = $dbTable->fetchAll(array('id' => 1), array('paginated' => false));
        $this->assertInstanceOf('Zend\Db\ResultSet\ResultSet',$data);
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
     * Teste para salvamento de um novo resgistro
     *
     * @access public
     * @return null
     */
    public function testSaveNewEntry()
    {
        $tableGateway = $this->getTableGatewayMock();
        $tableGateway->expects($this->once())->method('insert')->will($this->returnValue(1));
        $dbTable = new DBTable('table','id',$this->getServiceManager());
        $dbTable->setTableGateway($tableGateway);
        $result = $dbTable->save(array('name' => 'test'));
        $this->assertEquals(1, $result);
    }

    /**
     * Teste de atualização de um registro
     *
     * @access public
     * @return null
     */
    public function testSaveUpdateEntry()
    {
        $tableGateway = $this->getTableGatewayMock();
        $tableGateway->expects($this->once())->method('update')->will($this->returnValue(1))->with(array('name' => 'test'),array('id' => 1));
        $dbTable = new DBTable('table','id',$this->getServiceManager());
        $dbTable->setTableGateway($tableGateway);
        $result = $dbTable->save(array('id' =>1, 'name' => 'test'));
        $this->assertEquals(1, $result);
    }


    public function testDelete()
    {
        $tableGateway = $this->getTableGatewayMock();
        $tableGateway->expects($this->once())->method('delete')->will($this->returnValue(1))->with(array('id' => 1));
        $dbTable = new DBTable('table','id',$this->getServiceManager());
        $dbTable->setTableGateway($tableGateway);
        $result = $dbTable->delete(array('id' => 1));
        $this->assertEquals(1, $result);
    }

    public function testUpdateOnlyIfExistsAndExists()
    {
        $tableGateway = $this->getTableGatewayMock();
        $tableGateway->expects($this->once())->method('update');
        $resultSet = new ResultSet();
        $resultSet->initialize(array(array('id' => 1)));
        $tableGateway->expects($this->once())->method('select')->will($this->returnValue($resultSet));
        $dbTable = new DBTable('table','id',$this->getServiceManager());
        $dbTable->updateOnlyIfExists(true);
        $dbTable->setTableGateway($tableGateway);
        $dbTable->save(array('id' => 1));
    }

    public function testUpdateOnlyIfExistsAndNotExists()
    {
        $tableGateway = $this->getTableGatewayMock();
        $tableGateway->expects($this->once())->method('insert')->will($this->returnValue(1))->with(array('id' => 1));
        $resultSet = new ResultSet();
        $resultSet->initialize(array());
        $tableGateway->expects($this->once())->method('select')->will($this->returnValue($resultSet));
        $dbTable = new DBTable('table','id',$this->getServiceManager());
        $dbTable->updateOnlyIfExists(true);
        $dbTable->setTableGateway($tableGateway);
        $dbTable->save(array('id' => 1));
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
