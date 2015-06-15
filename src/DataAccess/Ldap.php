<?php

namespace ABSCore\DataAccess;

use Zend\Ldap as ZendLdap;
use Zend\Paginator\Paginator as ZendPaginator;
use Zend\DB\ResultSet\ResultSet;
use ArrayObject;

use ABSCore\DataAccess\Paginator\Adapter\Ldap as LdapPaginator;

class Ldap implements DataAccessInterface
{
    protected $ldap;
    protected $attributes = [];
    protected $primaryKey;
    protected $objectClass;
    protected $prototype;

    public function __construct(ZendLdap\Ldap $ldap, $objectClass, $primaryKey)
    {
        $this->setLdap($ldap)
             ->setObjectClass($objectClass)
             ->setPrimaryKey($primaryKey);
    }

    public function setObjectClass($objectClass)
    {
        $this->objectClass = (string) $objectClass;
        return $this;
    }

    public function getObjectClass()
    {
        return (string) $this->objectClass;
    }

    public function setLdap(ZendLdap\Ldap $ldap)
    {
        $this->ldap = $ldap;
        return $this;
    }

    public function getLdap()
    {
        return $this->ldap;
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setArrayObjectPrototype($prototype)
    {
        $this->prototype = $prototype;
        return $this;
    }

    public function getArrayObjectPrototype()
    {
        if ($this->prototype) {
            $prototype = clone $this->prototype;
        } else {
            $prototype = new ArrayObject();
        }
        return $prototype;
    }

    public function find($id)
    {
        $primaryKey = $this->getPrimaryKey();
        if (is_null($primaryKey)) {
            throw new \Exception('Primary key must be set for find method.');
        }
        $ldap = $this->getLdap();
        $filter = ZendLdap\Filter::equals('objectClass', $this->getObjectClass())
                  ->addAnd(ZendLdap\Filter::equals($primaryKey, $id));
        $results = $ldap->search([
            'filter' => $filter,
            'sizelimit' => 1,
            'attributes' => $this->getAttributes()
        ]);
        $object = $this->getArrayObjectPrototype();
        $object->exchangeArray($results->getFirst());

        return $object;
    }

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

    public function fetchAll($conditions = null, array $options = array()) {
        $result = null;
        $conditionsFilter = null;
        $filter = ZendLdap\Filter::equals('objectClass', $this->getObjectClass());
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

    public function save($data) { }

    public function delete($conditions)
    {
        throw new Exception('Not implemented.');
    }
}
