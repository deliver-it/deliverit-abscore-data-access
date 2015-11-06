<?php

namespace ABSCore\DataAccessTest;

require 'Driver.php';

use Zend\ServiceManager;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\TableIdentifier;

use PHPUnit_Framework_TestCase;

use ABSCore\DataAccess;

class DBQueryTest extends PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());
        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $driver = new Driver;
        $statement = new Statement([['t0_id' => 1, 't0_col1' => 'value1', 't0_col2' => 'value2']]);
        $driver->setStatement($statement);
        $dbTable->setAdapter(new Adapter($driver));

        $dbQuery = new DataAccess\DBQuery($dbTable, ['alias' => 'col1', 'col2'], $service);
        $result = $dbQuery->fetch();

        $this->assertArrayInto(['alias' => 'value1', 'col2' => 'value2'], $result->toArray()[0]);

    }

    public function testConstructWithTableIdentifier()
    {
        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());
        $dbTable = new DataAccess\DBTable(new TableIdentifier('table1', 'schema'), 'id', $service);
        $driver = new Driver;
        $statement = new Statement([['t0_id' => 1, 't0_col1' => 'value1', 't0_col2' => 'value2']]);
        $driver->setStatement($statement);
        $dbTable->setAdapter(new Adapter($driver));

        $dbQuery = new DataAccess\DBQuery($dbTable, ['alias' => 'col1', 'col2'], $service);
        $result = $dbQuery->fetch();

        $this->assertArrayInto(['alias' => 'value1', 'col2' => 'value2'], $result->toArray()[0]);
    }

    public function testJoin()
    {
        $data = [
            [
                't0_id'=> 1, 't0_col1' => 'value1',
                't1_col2' => 'value2', 't1_id' => 1,
                't2_col2' => 'value3', 't2_id' => 2,
            ],
            [
                't0_id'=> 1, 't0_col1' => 'value1',
                't1_col2' => 'value2', 't1_id' => 2,
                't2_col2' => 'value4', 't2_id' => 3,
            ],
            [
                't0_id'=> 1, 't0_col1' => 'value1',
                't1_col2' => 'value2', 't1_id' => 2,
                't2_col2' => 'value3', 't2_id' => 2,
            ]
        ];
        $driver = new Driver;
        $statement = new Statement($data);
        $driver->setStatement($statement);
        $adapter = new Adapter($driver);

        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());

        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable->setAdapter($adapter);

        $dbTable2 = new DataAccess\DBTable('table2', 'id', $service);
        $dbTable2->setAdapter($adapter);

        $dbTable3 = new DataAccess\DBTable(new TableIdentifier('table3', 'schema'), 'id', $service);
        $dbTable3->setAdapter($adapter);

        $dbQuery = new DataAccess\DBQuery($dbTable, ['col1'], $service);
        $dbQuery
            ->join([ 'parent' => $dbTable2 ], $dbTable, '$1.id = $2.table2_id', ['col2'])
            ->join($dbTable2, 'parent', '$1.parent_id = $2.id', ['col2']);

        $select = $dbQuery->getSelect();

        $this->assertArrayInto(['t0_col1' => 'col1', 't0_id' => 'id'], $select->getRawState($select::COLUMNS));

        $joins = $select->getRawState($select::JOINS);

        $expectedJoins = [
            [
                'name' => ['parent' => 'table2'],
                'on' => 'parent.id = table1.table2_id',
                'columns' => [
                    't1_col2' => 'col2',
                    't1_id' => 'id',
                ]
            ],
            [
                'name' => ['table2' => 'table2'],
                'on' => 'table2.parent_id = parent.id',
                'columns' => [
                    't2_col2' => 'col2',
                    't2_id' => 'id',
                ]
            ],
        ];

        $this->assertArrayInto($expectedJoins, $joins);

        $result = $dbQuery->fetch();

        $expectedArray = [
            'col1' => 'value1',
            'parent' => [
                [
                    'col2' => 'value2',
                    'table2' => [
                        [
                            'col2' => 'value3',
                        ],
                    ]
                ],
                [
                    'col2' => 'value2',
                    'table2' => [
                        [
                            'col2' => 'value4',
                        ],
                        [
                            'col2' => 'value3',
                        ],
                    ]
                ]
            ]
        ];

        $this->assertArrayInto($expectedArray, $result->toArray()[0]);
    }

    public function testInvalidJoin()
    {
        $this->setExpectedException('RuntimeException','The table table3 was not identified');
        $driver = new Driver;
        $adapter = new Adapter($driver);

        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());

        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable->setAdapter($adapter);

        $dbTable2 = new DataAccess\DBTable('table2', 'id', $service);
        $dbTable2->setAdapter($adapter);

        $dbTable3 = new DataAccess\DBTable('table3', 'id', $service);
        $dbTable3->setAdapter($adapter);

        $dbQuery = new DataAccess\DBQuery($dbTable, ['col1'], $service);
        $dbQuery->join($dbTable2, $dbTable3, '$1.id = $2.table2_id', ['col2']);
    }

    public function testInvalidJoinTable()
    {
        $this->setExpectedException('RuntimeException','Table must be a instance of DBTable');
        $driver = new Driver;
        $adapter = new Adapter($driver);

        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());

        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable->setAdapter($adapter);

        $dbQuery = new DataAccess\DBQuery($dbTable, ['col1'], $service);
        $dbQuery->join([], $dbTable, '$1.id = $2.table2_id', ['col2']);
    }

    public function testWhereConditions()
    {
        $driver = new Driver;
        $adapter = new Adapter($driver);

        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());

        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable->setAdapter($adapter);

        $dbTable2 = new DataAccess\DBTable(new TableIdentifier('table2', 'schema'), 'id', $service);
        $dbTable2->setAdapter($adapter);

        $dbQuery = new DataAccess\DBQuery($dbTable, ['col1'], $service);
        $dbQuery->join($dbTable2, $dbTable, '$1.id = $2.table2_id', ['col2'])
            ->addWhereConditions(['table1.id' => 1, 'schema.table2.id' => 3])
            ->addWhereConditions('table1.col = \'value\'')
            ->addWhereConditions(['$1.col2' => 1, '$1.col3 = 3'], $dbTable)
            ->addWhereConditions('$1.col4 = 1', $dbTable2);

        $expected = [
            'table1.id' => 1,
            'schema.table2.id' => 3,
            'table1.col = \'value\'',
            'table1.col2' => 1,
            'table1.col3 = 3',
            'schema.table2.col4 = 1'
        ];

        $this->assertArrayInto($expected, $dbQuery->getWhere());
    }

    public function testGetFromTable()
    {
        $driver = new Driver;
        $adapter = new Adapter($driver);

        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());

        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable->setAdapter($adapter);

        $dbQuery = new DataAccess\DBQuery($dbTable, ['col1'], $service);

        $this->assertEquals($dbTable, $dbQuery->getFromTable());
    }

    public function testPrototype()
    {
        $driver = new Driver;
        $driver->setStatement(new Statement([['t0_id' => 1, 't0_col1' => 'value1', 't0_col2' => 'value2']]));
        $adapter = new Adapter($driver);


        $service = new ServiceManager\ServiceManager(new ServiceManager\Config());

        $dbTable = new DataAccess\DBTable('table1', 'id', $service);
        $dbTable->setAdapter($adapter);

        $dbQuery = new DataAccess\DBQuery($dbTable, ['col1'], $service);

        $dbQuery->setArrayObjectPrototype(new \Zend\Stdlib\ArrayObject);
        $result = $dbQuery->fetch();

        $this->assertInstanceOf('Zend\Stdlib\ArrayObject', $result->current());
    }

    protected function assertArrayInto(array $base, array $array, $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        foreach ($base as $key => $value) {
            $constraint = new \PHPUnit_Framework_Constraint_ArrayHasKey($key);
            $constraint->evaluate($array, '');
            if (is_array($value)) {
                $this->assertArrayInto($value, (array)$array[$key], $delta, $maxDepth, $canonicalize, $ignoreCase);
            } else {
                $constraint = new \PHPUnit_Framework_Constraint_IsEqual(
                    $value,
                    $delta,
                    $maxDepth,
                    $canonicalize,
                    $ignoreCase
                );
                $constraint->evaluate($array[$key], '');
            }
        }
    }
}
