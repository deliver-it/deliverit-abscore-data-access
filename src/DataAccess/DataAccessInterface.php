<?php

namespace ABSCore\DataAccess;

use Zend\ServiceManager\ServiceLocatorInterface;

interface DataAccessInterface
{
    public function __construct($resource, $primaryKey, ServiceLocatorInterface $service);
    public function find($primaryKey);
    public function fetchAll($conditions, array $options);
    public function save($data);
    public function delete($conditions);
}
