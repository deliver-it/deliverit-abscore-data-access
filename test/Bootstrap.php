<?php
namespace ABSCore\DataAccessTest;

use Zend\Loader\StandardAutoloader;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

class Bootstrap
{
    public static function init()
    {
        $loader = new StandardAutoloader(array('autoregister_zf' => true));
        $loader->registerNamespace('ABSCore\DataAccess', __DIR__.'/../src/DataAccess');
        $loader->register();
    }
}

Bootstrap::init();
