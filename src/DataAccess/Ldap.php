<?php

namespace ABSCore\DataAccess;

use Zend\Ldap as ZendLdap;
use Zend\Paginator\Paginator as ZendPaginator;
use Zend\DB\ResultSet\ResultSet;
use ArrayObject;

use ABSCore\DataAccess\Paginator\Adapter\Ldap as LdapPaginator;

/**
 * Ldap
 *
 * @uses DataAccessInterface
 */
class Ldap implements DataAccessInterface
{
    /**
     * ldap
     *
     * @var Zend\Ldap\Ldap
     */
    protected $ldap;

    /**
     * attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * primaryKey
     *
     * @var mixed
     */
    protected $primaryKey;

    /**
     * objectClass
     *
     * @var mixed
     */
    protected $objectClass;

    /**
     * prototype
     *
     * @var mixed
     */
    protected $prototype;

    /**
     * defaultFilters
     *
     * @var Zend\Ldap\Filter\AbstractLogicalFilter
     */
    protected $defaultFilters;

    /**
     * Object construction
     *
     * @param Zend\Ldap\Ldap $ldap
     * @param mixed $objectClass
     * @param mixed $primaryKey
     * @return void
     */
    public function __construct(ZendLdap\Ldap $ldap, $objectClass, $primaryKey)
    {
        $this->setLdap($ldap)
             ->setObjectClass($objectClass)
             ->setPrimaryKey($primaryKey);
    }

    /**
     * setFilters
     *
     * @param Zend\Ldap\Filter\AbstractLogicalFilter $filters
     * @return void
     */
    public function setDefaultFilters(ZendLdap\Filter\AbstractLogicalFilter $defaultFilters)
    {
        $this->defaultFilters = $defaultFilters;
    }

    /**
     * getDefaultFilters
     *
     * @return Zend\Ldap\Filter\AbstractLogicalFilter
     */
    public function getDefaultFilters()
    {
        return $this->defaultFilters;
    }

    /**
     * setObjectClass
     *
     * @param mixed $objectClass
     * @return void
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = (string) $objectClass;
        return $this;
    }

    /**
     * getObjectClass
     *
     * @return void
     */
    public function getObjectClass()
    {
        return (string) $this->objectClass;
    }

    /**
     * setLdap
     *
     * @param ZendLdap\Ldap $ldap
     * @return void
     */
    public function setLdap(ZendLdap\Ldap $ldap)
    {
        $this->ldap = $ldap;
        return $this;
    }

    /**
     * getLdap
     *
     * @return void
     */
    public function getLdap()
    {
        return $this->ldap;
    }

    /**
     * setAttributes
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * getAttributes
     *
     * @return void
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * setPrimaryKey
     *
     * @param mixed $primaryKey
     * @return void
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * getPrimaryKey
     *
     * @return void
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * setArrayObjectPrototype
     *
     * @param mixed $prototype
     * @return void
     */
    public function setArrayObjectPrototype($prototype)
    {
        $this->prototype = $prototype;
        return $this;
    }

    /**
     * getArrayObjectPrototype
     *
     * @return void
     */
    public function getArrayObjectPrototype()
    {
        if ($this->prototype) {
            $prototype = clone $this->prototype;
        } else {
            $prototype = new ArrayObject();
        }
        return $prototype;
    }

    /**
     * Find an entry by id in Ldap server
     *
     * @param mixed $id
     * @return ArrayObject
     */
    public function find($id)
    {
        $primaryKey = $this->getPrimaryKey();
        if (is_null($primaryKey)) {
            throw new \Exception('Primary key must be set for find method.');
        }
        $ldap = $this->getLdap();
        $filter = ZendLdap\Filter::equals('objectClass', $this->getObjectClass())
                  ->addAnd(ZendLdap\Filter::equals($primaryKey, $id));
        $defaultFilters = $this->getDefaultFilters();
        if (!is_null($defaultFilters)) {
            $filter = $filter->addAnd($defaultFilters);
        }
        $results = $ldap->search([
            'filter' => $filter,
            'sizelimit' => 1,
            'attributes' => $this->getAttributes()
        ]);
        $object = $this->getArrayObjectPrototype();

        if (is_null($results->getFirst())) {
            throw new Exception\UnknowRegistryException();
        }

        $object->exchangeArray($results->getFirst());

        return $object;
    }

    /**
     * Make filters combinations by conditions
     *
     * @param array $conditions
     * @return mixed
     */
    protected function makeFilterByConditions(array $conditions)
    {
        if (empty($conditions)) {
            return null;
        }
        $filter = null;
        foreach($conditions as $field => $condition) {
            if (is_object($condition) && $condition instanceof ZendLdap\Filter\AbstractFilter) {
                $currentFilter = $condition;
            } else if (is_array($condition)) {
                $first = array_shift($condition);
                $currentFilter = ZendLdap\Filter::equals($field, (string) $first);
                foreach ($condition as $value) {
                    $currentFilter = $currentFilter->addOr(ZendLdap\Filter::equals($field, (string) $value));
                }
            } else {
                $currentFilter = $currentFilter = ZendLdap\Filter::equals($field, (string) $condition);
            }
            if (is_null($filter)) {
                $filter = $currentFilter;
            } else {
                $filter = $filter->addAnd($currentFilter);
            }
        }
        return $filter;
    }

    /**
     * Retrieve data from Ldap server based on conditions and options
     *
     * @param mixed $conditions
     * @param array $options
     * @return Zend\DB\ResultSet\ResultSet | Zend\Paginator\Paginator
     */
    public function fetchAll($conditions = null, array $options = [])
    {
        $result = null;
        $conditionsFilter = null;
        $filter = ZendLdap\Filter::equals('objectClass', $this->getObjectClass());
        $defaultFilters = $this->getDefaultFilters();
        if (!is_null($defaultFilters)) {
            $filter = $filter->addAnd($defaultFilters);
        }
        if (!is_null($conditions)) {
            if (is_object($conditions) && $conditions instanceof ZendLdap\Filter\AbstractFilter) {
                $conditionsFilter = $conditions;
            } else {
                $conditionsFilter = $this->makeFilterByConditions($conditions);
            }
        }
        if (!is_null($conditionsFilter)) {
            $filter = $filter->addAnd($conditionsFilter);
        }
        $attributes = $this->getAttributes();
        if (array_key_exists('attributes', $options)) {
            $attributes = $options['attributes'];
        }
        $ldap = $this->getLdap();
        if (!array_key_exists('paginated', $options) || !$options['paginated']) {
            $entries = $ldap->searchEntries([
                'filter' => $filter,
                'attributes' => $attributes
            ]);

            $result = new ResultSet();
            $result->setArrayObjectPrototype($this->getArrayObjectPrototype());
            $result->initialize($entries);
        } else {
            $adapter = new LdapPaginator($this->getLdap(), $filter, $attributes);
            $adapter->setArrayObjectPrototype($this->getArrayObjectPrototype());
            $paginator = new \Zend\Paginator\Paginator($adapter);
            if (array_key_exists('page', $options)) {
                $paginator->setCurrentPageNumber((int)$options['page']);
            }
            if (array_key_exists('perPage', $options)) {
                $paginator->setItemCountPerPage((int)$options['perPage']);
            }
            $result = $paginator;
        }
        return $result;
    }

    /**
     * Save an entry in Ldap server
     *
     * @param array $data
     */
    public function save($data) {
        throw new Exception('Not implemented.');
    }

    /**
     * Delete an entry in Ldap server
     *
     * @param array $conditions
     */
    public function delete($conditions)
    {
        throw new Exception('Not implemented.');
    }
}
