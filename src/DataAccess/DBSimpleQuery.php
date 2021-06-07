<?php

namespace ABSCore\DataAccess;

use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Db\ResultSet\ResultSet;

/**
 * Class to make complex queries over DBSimpleQuery objects
 *
 * @property array $from
 * @property mixed $select
 * @property array $joins
 * @property array $tree
 *
 * @author Marcelo Jean <marcelojeam1@gmail.com>
 */
class DBSimpleQuery
{
    /** @var $sql string Command to execute. */
    protected $sql;

    /** @var $entity string Name of entity. */
    protected $entity;

    /** @var $service ServiceLocator */
    protected $service;

    /**
     * Class constructor
     *
     * @param string $sql SQL.
     * @param $resource string Nome da entidade relacional.
     * @param $service object Servi
     * @access public
     */
    public function __construct($sql, $resource, $service)
    {
        $this->setSql($sql);
        $this->setEntity($resource);
        $this->setServiceLocator($service);
    }

    /**
     * Set the sql.
     *
     * @param $sql
     * @return $this
     */
    private function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Return sql
     *
     * @return string
     */
    private function getSql()
    {
        return $this->sql;
    }

    /**
     * Set the service locator.
     *
     * @param $service
     * @return $this
     */
    private function setServiceLocator($service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Return the service locator.
     *
     * @return ServiceLocator
     */
    private function getServiceLocator()
    {
        return $this->service;
    }

    /**
     * Set the entity.
     *
     * @param $entity
     * @return $this
     */
    private function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Return entity.
     *
     * @return string
     */
    private function getEntity()
    {
        return $this->entity;
    }

    /**
     * Return adapter.
     *
     * @return Adapter
     */
    protected function getAdapter()
    {
        $driver = $this->getServiceLocator()->get('Config')['db']['adapters']['gp-adapter'];
        return new Adapter($driver);
    }

    /**
     * Return records.
     *
     * @return string|ResultSet
     */
    public function records()
    {
        try {
            $query = new DBTable($this->getEntity(), 'id', $this->getServiceLocator());
            $query->setAdapter($this->getAdapter());

            $stmt = $query->getAdapter()
                ->getDriver()
                ->createStatement($this->getSql())
                ->execute();

            $result = new ResultSet();
            return $result->initialize($stmt->getResource()->fetchAll());
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
