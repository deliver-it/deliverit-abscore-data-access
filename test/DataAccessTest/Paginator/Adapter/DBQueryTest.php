<?php

namespace ABSCore\DataAccessTest\Paginator\Adapter;

require_once __DIR__ . '/../../Driver.php';

use Zend\ServiceManager;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\TableIdentifier;

use PHPUnit_Framework_TestCase;

use ABSCore\DataAccess;
use ABSCore\DataAccessTest;

class DBQueryTest extends PHPUnit_Framework_TestCase
{
    public function testPagination()
    {
        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());
        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $driver = new DataAccessTest\Driver;
        $data = [
            ['t0_id' => 1, 't0_col1' => 'value1', 't0_col2' => 'value2'],
            ['t0_id' => 2, 't0_col1' => 'value3', 't0_col2' => 'value4']
        ];
        $statement = new DataAccessTest\Statement($data);
        $driver->setStatement($statement);
        $dbTable->setAdapter(new Adapter($driver));

        $dbQuery = new DataAccess\DBQuery($dbTable, ['alias' => 'col1', 'col2'], $service);
        $paginatorAdapter = new DataAccess\Paginator\Adapter\DBQuery($dbQuery);
        $this->assertEquals(count($data), $paginatorAdapter->count());
        $result = $paginatorAdapter->getItems(0, 10);
        $this->assertInstanceOf('ArrayIterator', $result);
        $expected = [
            ['alias' => 'value1', 'col2' => 'value2'],
            ['alias' => 'value3', 'col2' => 'value4'],
        ];
        $this->assertEquals($expected, $result->getArrayCopy());
    }

    public function testPaginationWithJoins()
    {
        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());
        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable2 = new DataAccess\DBTable(new TableIdentifier('table2', 'schema'), 'id', $service);
        $driver = new DataAccessTest\Driver;
        $data = [
            ['t0_id' => 1, 't0_col1' => 'value1', 't1_id' => 1, 't1_col2' => 'value2'],
            ['t0_id' => 2, 't0_col1' => 'value3', 't1_id' => 2, 't1_col2' => 'value4']
        ];
        $statement = new DataAccessTest\Statement($data);
        $driver->setStatement($statement);
        $dbTable->setAdapter(new Adapter($driver));

        $dbQuery = new DataAccess\DBQuery($dbTable, ['alias' => 'col1'], $service);
        $dbQuery->join(['child' => $dbTable2 ], $dbTable, '$1.id = $2.table_id', ['col2']);
        $paginatorAdapter = new DataAccess\Paginator\Adapter\DBQuery($dbQuery);
        $this->assertEquals(count($data), $paginatorAdapter->count());
        $result = $paginatorAdapter->getItems(0, 10);
        $this->assertInstanceOf('ArrayIterator', $result);
        $expected = [
            [
                'alias' => 'value1',
                'child' => [['col2' => 'value2']],
            ],
            [
                'alias' => 'value3',
                'child' => [['col2' => 'value4']],
            ],
        ];
        $this->assertEquals($expected, $result->getArrayCopy());
    }

}
