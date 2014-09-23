<?php
namespace ABSCore\DataAccess;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\','/',__NAMESPACE__),
                ),
            ),
        );
    }
}
