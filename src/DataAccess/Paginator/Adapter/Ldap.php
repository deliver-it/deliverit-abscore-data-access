<?php

namespace ABSCore\DataAccess\Paginator\Adapter;

use Zend\Paginator\Adapter\AdapterInterface;
use Zend\DB\ResultSet\ResultSet;

use ABSCore\DataAccess\Ldap as DataAccess;
use Zend\Ldap as ZendLdap;

class Ldap implements AdapterInterface
{
    protected $ldap;
    protected $filter;
    protected $attributes;

    /**
     * Class Constructor
     *
     * @param Query $query
     * @access public
     */
    public function __construct(ZendLdap\Ldap $ldap, ZendLdap\Filter\AbstractFilter $filter, array $attributes)
    {
        $this->ldap = $ldap;
        $this->filter = $filter;
        $this->attributes = $attributes;
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
        $result = $this->ldap->search([
            'filter' => $this->filter,
            'attributes' => $this->attributes,
            'sizelimit' => $offset + $itemCountPerPage
        ]);

        $resultArray = $result->toArray();

        $result = new ResultSet();
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
        $result = $this->ldap->search([
            'filter' => $this->filter,
            'attributes' => $this->attributes
        ]);

        return $result->count();
    }
}
