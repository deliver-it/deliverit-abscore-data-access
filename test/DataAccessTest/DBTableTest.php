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

        $driver = new Driver;
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
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
        $driver = new Driver;
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
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
        $driver = new Driver;
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
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
        $driver = new Driver;
        $result = [['id' => 1, 'col' => 'value']];
        $statement = new Statement($result);
        $driver->setStatement($statement);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
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
        $driver = new Driver;
        $result = [['id' => 1, 'id2' => 2, 'col' => 'value']];
        $statement = new Statement($result);
        $driver->setStatement($statement);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
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

        $service = $this->getServiceManager();
        $dbTable = new DBTable('teste','id', $service);

        $driver = new Driver;
        $result = [['id' => 1, 'col' => 'value']];
        $statement = new Statement($result);
        $driver->setStatement($statement);
        $adapter = new Adapter($driver);

        $dbTable->setAdapter($adapter)->setPrototype(new \Zend\Stdlib\ArrayObject);

        $this->assertInstanceOf('Zend\Stdlib\ArrayObject', $dbTable->find('1'));
    }

    public function testInvalidPrototype()
    {
        $this->setExpectedException('Exception','Prototype must be an object');
        $service = $this->getServiceManager();

        $dbTable = new DBTable('teste', 'id', $service);
        $dbTable->setPrototype(null);
    }

    public function testInvalidPrototypeMethod()
    {
        $this->setExpectedException('Exception','Prototype must implement exchangeArray method');
        $service = $this->getServiceManager();

        $dbTable = new DBTable('teste', 'id', $service);
        $dbTable->setPrototype(new \StdClass);
    }

    /**
     * Teste de busca de registros
     *
     * @access public
     * @return null
     */
    public function testFetchAll()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $driver = new Driver;
        $result = [['id' => 1, 'col' => 'value'], ['id' => 2, 'col' => 'value2']];
        $statement = new Statement($result);
        $driver->setStatement($statement);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);

        $data = $dbTable->fetchAll(array('id' => 1), array('page' => 2, 'perPage' => 1));
        $this->assertInstanceOf('Zend\Paginator\Paginator',$data);

        $this->assertEquals(2, $data->getPages()->pageCount);
        $this->assertEquals(2, $data->getPages()->current);
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

    public function testeBeginTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['beginTransaction'])
            ->getMock();
        $connection->expects($this->once())->method('beginTransaction');

        $driver = new Driver;
        $driver->setConnection($connection);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable->beginTransaction();
        $this->assertTrue($dbTable->inTransaction());
    }

    public function testeCommitTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['commit'])
            ->getMock();
        $connection->expects($this->once())->method('commit');

        $driver = new Driver;
        $driver->setConnection($connection);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable->beginTransaction();
        $this->assertTrue($dbTable->inTransaction());
        $dbTable->commit();
        $this->assertFalse($dbTable->inTransaction());
    }

    public function testeRollbackTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['rollback'])
            ->getMock();
        $connection->expects($this->once())->method('rollback');

        $driver = new Driver;
        $driver->setConnection($connection);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable->beginTransaction();
        $this->assertTrue($dbTable->inTransaction());
        $dbTable->rollback();
        $this->assertFalse($dbTable->inTransaction());
    }

    public function testTwiceBeginTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());
        $dbTable2 = new DBTable('table2','id', $this->getServiceManager());

        $driver = new Driver;
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable2->setAdapter($adapter);

        $dbTable->beginTransaction();
        $this->assertTrue($dbTable->inTransaction());

        $dbTable2->beginTransaction();
        $this->assertFalse($dbTable2->inTransaction());
    }

    public function testTwiceBeginTransactionSameDbTable()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $driver = new Driver;
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);

        $dbTable->beginTransaction();
        $this->assertTrue($dbTable->inTransaction());

        $dbTable->beginTransaction();
        $this->assertTrue($dbTable->inTransaction());
    }

    public function testeCommitWhenNotInTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['commit'])
            ->getMock();
        $connection->expects($this->exactly(0))->method('commit');

        $driver = new Driver;
        $driver->setConnection($connection);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable->commit();
    }

    public function testeRollbackWhenNotInTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['rollback'])
            ->getMock();
        $connection->expects($this->exactly(0))->method('rollback');

        $driver = new Driver;
        $driver->setConnection($connection);
        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable->rollback();
    }

    public function testeChainRollbackTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());
        $dbTable2 = new DBTable('table2','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['rollback'])
            ->getMock();
        $connection->expects($this->exactly(0))->method('rollback');

        $driver = new Driver;
        $driver->setConnection($connection);

        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable2->setAdapter($adapter);

        $dbTable->beginTransaction();
        $dbTable2->beginTransaction();
        $dbTable2->rollback();
    }

    public function testeChainCommitTransaction()
    {
        $dbTable = new DBTable('table','id', $this->getServiceManager());
        $dbTable2 = new DBTable('table2','id', $this->getServiceManager());

        $connection = $this->getMockBuilder('ABSCore\DataAccessTest\Connection')
            ->setMethods(['commit'])
            ->getMock();
        $connection->expects($this->exactly(0))->method('commit');

        $driver = new Driver;
        $driver->setConnection($connection);

        $adapter = new Adapter($driver);
        $dbTable->setAdapter($adapter);
        $dbTable2->setAdapter($adapter);

        $dbTable->beginTransaction();
        $dbTable2->beginTransaction();
        $dbTable2->commit();
    }

    public function testGetServiceLocator()
    {
        $service = $this->getServiceManager();
        $dbTable = new DBTable('table','id', $service);
        $this->assertEquals($service, $dbTable->getServiceLocator());
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
