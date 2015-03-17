<?php

namespace ABSCore\DataAccess;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DataAccessInterface
 *
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
interface DataAccessInterface
{
    /**
     * Class Constructor
     *
     * @param string $resource                 Resource name
     * @param string|array $primaryKey         Primary keys
     * @param ServiceLocatorInterface $service Service locator
     * @access public
     */
    public function __construct($resource, $primaryKey, ServiceLocatorInterface $service);
    /**
     * Find a entry by primary keys
     *
     * @param array|string $primaryKey
     * @access public
     * @return mixed
     */
    public function find($primaryKey);

    /**
     * Fecth a set of entries
     *
     * @param mixed $conditions Set of conditions
     * @param array $options    Set of options
     * @access public
     * @return mixed
     */
    public function fetchAll($conditions=null, array $options = array());

    /**
     * Insert or Update a registry
     *
     * When passed primary keys then Update otherwise Insert
     *
     * @param mixed $data Set of values of one entry
     * @access public
     * @return mixed
     */
    public function save($data);

    /**
     * Delete entries by conditions
     *
     * @param mixed $conditions Set of conditions
     * @access public
     * @return mixed
     */
    public function delete($conditions);
}
