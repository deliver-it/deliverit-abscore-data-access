<?php
namespace ABSCore\DataAccessTest;

use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\StatementContainer;

class Statement extends StatementContainer implements StatementInterface
{

    public function __construct($data = [])
    {
        $this->data = $data;
    }
    /**
     * Get resource
     *
     * @return resource
     */
    public function getResource()
    {
        return null;
    }

    /**
     * Prepare sql
     *
     * @param string $sql
     */
    public function prepare($sql = null)
    {
        return $this;
    }

    /**
     * Check if is prepared
     *
     * @return bool
     */
    public function isPrepared()
    {
        return true;
    }

    /**
     * Execute
     *
     * @param null|array|ParameterContainer $parameters
     * @return ResultInterface
     */
    public function execute($parameters = null)
    {
        $sql = $this->getSql();
        if (preg_match('/COUNT/', $sql) === 1) {
            $col = 'COUNT(1)';
            if (preg_match('/COUNT\(1\) AS "([a-zA-Z]+)"/', $sql, $match)) {
                $col = $match[1];
            }
            return new \ArrayIterator([[$col => count($this->data)]]);
        }
        return $this->data;
    }
}
