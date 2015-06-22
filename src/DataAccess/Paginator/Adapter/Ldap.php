<?php

namespace ABSCore\DataAccess\Paginator\Adapter;

use Zend\Paginator\Adapter\AdapterInterface;
use Zend\DB\ResultSet\ResultSet;

use ABSCore\DataAccess\Ldap as DataAccess;
use Zend\Ldap as ZendLdap;

/**
 * Ldap
 *
 * @uses AdapterInterface
 */
class Ldap implements AdapterInterface
{
    /**
     * ldap
     *
     * @var Zend\Ldap\Ldap
     */
    protected $ldap;

    /**
     * filter
     *
     * @var Zend\Ldap\Filter\AbstractFilter
     */
    protected $filter;

    /**
     * attributes
     *
     * @var array
     */
    protected $attributes;

    /**
     * resultSet
     *
     * @var Zend\DB\ResultSet\ResultSet
     */
    protected $resultSet;

    /**
     * Class Constructor
     *
     * @param Query $query
     * @access public
     */
    public function __construct(ZendLdap\Ldap $ldap, ZendLdap\Filter\AbstractFilter $filter, array $attributes)
    {
        $this
        ->setLdap($ldap)
        ->setFilter($filter)
        ->setAttributes($attributes);
    }

    /**
     * Set attributes list
     *
     * @param array $attributes
     * @return Ldap
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Get attributes list
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * setFilter
     *
     * @param Zend\Ldap\Filter\AbstractFilter $filter
     * @return Ldap
     */
    public function setFilter(ZendLdap\Filter\AbstractFilter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Get filter object
     *
     * @return
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set LDAP instance
     *
     * @param Zend\Ldap\Ldap $ldap
     * @return  Ldap
     */
    public function setLdap(ZendLdap\Ldap $ldap)
    {
        $this->ldap = $ldap;
        return $this;
    }

    /**
     * Return LDAP instance
     *
     * @return Zend\Ldap\Ldap
     */
    public function getLdap()
    {
        return $this->ldap;
    }

    /**
     * Set prototype to result set
     *
     * @param mixed $prototype
     * @return Ldap
     */
    public function setArrayObjectPrototype($prototype)
    {
        $this->getResultSet()->setArrayObjectPrototype($prototype);
        return $this;
    }

    /**
     * Set result set object
     *
     * @param Zend\DB\ResultSet\ResultSet $resultSet
     * @return Ldap
     */
    public function setResultSet(ResultSet $resultSet)
    {
        $this->resultSet = $resultSet;
        return $this;
    }

    /**
     * Get result set object
     *
     * @return Zend\DB\ResultSet\ResultSet
     */
    public function getResultSet()
    {
        if (!$this->resultSet) {
            $this->resultSet = new ResultSet();
        }
        return $this->resultSet;
    }

    /**
     * Get current items
     *
     * @param int $offset
     * @param int $itemCountPerPage
     * @access public
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $result = $this->getLdap()->search([
            'filter' => $this->getFilter(),
            'attributes' => $this->getAttributes(),
            'sizelimit' => $offset + $itemCountPerPage
        ]);

        $resultArray = $result->toArray();

        $result = $this->getResultSet();
        $result->initialize(array_slice($resultArray, $offset));

        return $result;
    }

    /**
     * Get total of items
     *
     * @access public
     * @return int
     */
    public function count()
    {
        $result = $this->getLdap()->search([
            'filter' => $this->filter,
            'attributes' => $this->attributes
        ]);

        return $result->count();
    }
}
