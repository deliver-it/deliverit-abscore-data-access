<?php

namespace ABSCore\DataAccess;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Paginator\Adapter\DbTableGateway;
use Zend\Paginator\Paginator;
use Zend\Filter\Word\SeparatorToCamelCase;
use Exception;
use ArrayObject;

/**
 * Interface de acesso à dados de banco de dados.
 *
 * Esta implementação abstrai única e exclusivamente uma tabela
 *
 * @uses DataAccessInterface
 * @category ABSCore
 * @package DataAccess
 */
class DBTable implements DataAccessInterface
{

    /**
     * Constante que determina o sufixo para o protótipo customizado
     */
    const PROTOTYPE_SUFFIX = 'Prototype';

    /**
     * Gerenciador de serviços
     *
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     * @access protected
     */
    protected $serviceLocator;

    /**
     * Conjunto de chaves Primárias
     *
     * @var array
     * @access protected
     */
    protected $primaryKey;

    /**
     * Nome da tabela no banco de dados
     *
     * @var string
     * @access protected
     */
    protected $tableName;

    /**
     * Gateway da tabela no banco de dados
     *
     * @var \Zend\Db\TableGateway\TableGatewayInterface
     * @access protected
     */
    protected $tableGateway;

    /**
     * Adaptador de banco de dados
     *
     * @var \Zend\Db\Adapter\AdapterInterface
     * @access protected
     */
    protected $adapter;

    /**
     * Flag para identificar se a atualização de registro deve
     *
     * @var mixed
     * @access protected
     */
    protected $updateOnlyIfExists = false;

    /**
     * Contrutor da classe
     *
     * @param string $resource Nome do recurso(tabela no banco de dados)
     * @param mixed $primaryKey Chave(s) primárias
     * @param \Zend\ServiceManager\ServiceLocatorInterface $service Gerenciador de serviços
     * @access public
     */
    public function __construct($resource, $primaryKey, ServiceLocatorInterface $service)
    {
        $this->serviceLocator = $service;
        $this->setPrimaryKey($primaryKey);
        $this->setTableName($resource);
    }

    /**
     * Método para busca de registro único
     *
     * @throws \Exception Quando o registro não é encontrado
     * @param mixed $primaryKey Chave(s) primária(s)
     * @access public
     * @return mixed Registro encontrado
     */
    public function find($primaryKey)
    {
        $condition = $this->makeFindCondition($primaryKey);
        $rowset = $this->getTableGateway()->select($condition);
        $row = $rowset->current();
        if (!$row) {
            //@TODO change exception type
            throw new Exception('registry not found');
        }
        return $row;
    }

    /**
     * Define se deve atualizar um registro somente quando este já existir
     *
     * @param bool $flag
     * @access public
     * @return DBTable Próprio objeto para encadeamento
     */
    public function updateOnlyIfExists($flag)
    {
        $this->updateOnlyIfExists = (bool)$flag;
        return $this;
    }

    /**
     * Deve atualizar o registro quando somente quando existir?
     *
     * @access public
     * @return bool
     */
    public function isUpdateOnlyIfExists()
    {
        return (bool)$this->updateOnlyIfExists;
    }

    /**
     * Obtenção de registros
     *
     * @param mixed $conditions Condições de busca
     * @param array $options Opções de busca
     * @access public
     * @return \Zend\Paginator\Paginator | \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAll($conditions=null, array $options = array())
    {
        if (!array_key_exists('paginated',$options) || $options['paginated']) {
            $method = 'paginatedItems';
        } else {
            $method = 'unpaginatedItems';
        }
        return $this->$method($conditions, $options);
    }


    /**
     * Inserção ou Atualização de um item
     *
     * a atualização ocorre quando todas as chaves primárias são passadas
     *
     * @param mixed $data Conjunto de dados para salvamento
     * @access public
     * @return int número de itens afetados
     */
    public function save($data)
    {
        $update = true;
        $keys = array();
        foreach ($this->getPrimaryKey() as $key) {
            if (!array_key_exists($key,$data)) {
                $update = false;
                break;
            }
            $keys[$key] = $data[$key];
            unset($data[$key]);
        }

        if ($update && $this->isUpdateOnlyIfExists()) {
            try {
                $this->find($keys);
            } catch (Exception $e) {
                $update = false;
            }
        }

        if ($update) {
            return $this->getTableGateway()->update($data, $keys);
        } else {
            return $this->getTableGateway()->insert(array_merge($data,$keys));
        }
    }

    /**
     * Remove um item
     *
     * @param mixed $conditions Condições da remoção
     * @access public
     * @return int
     */
    public function delete($conditions)
    {
        return $this->getTableGateway()->delete($conditions);
    }

    /**
     * Define o gateway de tabela do banco de dados
     *
     * @param \Zend\Db\TableGateway\TableGatewayInterface $tableGateway
     * @access public
     * @return DBTable Próprio objeto para encadeamento
     */
    public function setTableGateway(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
        return $this;
    }

    /**
     * Obtenção do gateway de tabela do banco de dados
     *
     * @access public
     * @return \Zend\Db\TableGateway\TableGatewayInterface
     */
    public function getTableGateway()
    {
        if (is_null($this->tableGateway)) {
            $this->createTableGateway();
        }
        return $this->tableGateway;
    }

    /**
     * Obtenção das chaves primárias
     *
     * @access public
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Obtenção do nome da tabela
     *
     * @access public
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Obtenção do gerenciador de serviços
     *
     * @access public
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Define o adaptador de banco de dados
     *
     * @param \Zend\Db\Adapter\AdapterInterface $adapter
     * @access public
     * @return DBTable Próprio objeto para encadeamento
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Obtenção do adaptador de banco de dados
     *
     * @throws Exception quando um adaptador não foi anteriormente definido
     * @access public
     * @return \Zend\Db\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        if (is_null($this->adapter)) {
            throw new Exception('An adapter is required!');
        }
        return $this->adapter;
    }

    /**
     * Obtenção de conjunto de itens paginados
     *
     * @param mixed $conditions Condições de pesquisa
     * @param array $options Opções de pesquisa (order, page, perPage,..._)
     * @access protected
     * @return \Zend\Paginator\Paginator
     */
    protected function paginatedItems($conditions, array $options)
    {
        $order = null;
        if (array_key_exists('order',$options)) {
            $order = $options['order'];
        }

        $tableGateway = $this->getTableGateway();

        $adapter = new DbTableGateway($tableGateway,$conditions,$order);
        $paginator = new Paginator($adapter);

        if (array_key_exists('page', $options)) {
            $paginator->setCurrentPageNumber((int)$options['page']);
        }

        if (array_key_exists('perPage', $options)) {
            $paginator->setItemCountPerPage((int)$options['perPage']);
        }
        return $paginator;
    }

    /**
     * Obtenção de registros não paginados
     *
     * @param mixed $conditions Condições de pesquisa
     * @param array $options Opções de pesquisa
     * @access protected
     * @return \Zend\Db\ResultSet\ResultSet
     */
    protected function unpaginatedItems($conditions, array $options)
    {
        $function = function($select) use($conditions, $options) {
            if (!empty($conditions)) {
                $select->where($conditions);
            }

            if (array_key_exists('order', $options)) {
                $select->order($options['order']);
            }
        };
        return $this->getTableGateway()->select($function);
    }


    /**
     * Realiza a criação de um gateway de tabela do banco de dados com base no nome da tabela definido em tempo de
     * construção
     *
     * @access protected
     * @return DBTable Próprio objeto para encadeamento
     */
    protected function createTableGateway()
    {
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype($this->getPrototype());
        $tableGateway = new TableGateway($this->getTableName(), $this->getAdapter(), null, $resultSet);
        $this->setTableGateway($tableGateway);

        return $this;
    }

    /**
     * Obtenção do protótipo
     *
     * @access protected
     * @return mixed
     */
    protected function getPrototype()
    {
        $table = $this->getTableName();
        // montagem do nome do protótipo customizado
        $filter = new SeparatorToCamelCase();
        $prototypeName = $filter->filter($table).self::PROTOTYPE_SUFFIX;
        $serviceLocator = $this->getServiceLocator();

        // existe um serviço para o protótipo?
        if ($serviceLocator->has($prototypeName)) {
            $prototype = $serviceLocator->get($prototypeName);
        // definição de um protótipo default
        } else {
            $prototype = new ArrayObject();
        }

        return $prototype;
    }

    /**
     * Construção de condições para a busca de um único registro
     *
     * @throws Exception para quando uma chave é inválida
     * @param mixed $primaryKey
     * @access protected
     * @return array
     */
    protected function makeFindCondition($primaryKey) {
        // verifica as chaves primárias
        $this->verifyPrimaryKey($primaryKey);

        if (!is_array($primaryKey)) {
            $primaryKey = array((string)$primaryKey);
        }

        // obtenção das chaves definidas em tempo de contrução
        $keys = $this->getPrimaryKey();

        $conditions = array();
        // construção das condições
        foreach ($primaryKey as $key => $value) {
            if (is_string($key)) {
                // a chave passada não faz parte do conjunto de chaves da tabela?
                if (!in_array($key, $keys)) {
                    $message = sprintf('The key "%s" is not a valid primary key (%s)',$key,implode(',',$keys));
                    throw new Exception($message);
                }

                $conditions[$key] = $value;
            } else {
                $conditions[$keys[$key]] = $value;
            }
        }

        return $conditions;
    }

    /**
     * Realiza a verificação de um conjunto de chaves com o conjunto de chaves da tabela
     *
     * @throws \Exception quando a quantidade de chaves passadas é diferente da quantidade de chaves da tabela
     * @param mixed $primaryKey
     * @access protected
     * @return DBTable Próprio objeto para encadeamento
     */
    protected function verifyPrimaryKey($primaryKey)
    {
        $primaryCount = count($this->getPrimaryKey());
        if (!is_array($primaryKey)) {
            $passed = 1;
        } else {
            $passed = count($primaryKey);
        }

        if ($passed != $primaryCount) {
            $message = sprintf('%d keys are expected but %d was passed', $primaryCount, $passed);
            throw new Exception($message);
        }

        return $this;
    }

    /**
     * Define o conjunto de chaves primárias
     *
     * @throws \Exception quando o conjunto de chaves é vazio
     * @param mixed $primaryKey
     * @access protected
     * @return DBTable Próprio objeto para encadeamento
     */
    protected function setPrimaryKey($primaryKey)
    {
        if (empty($primaryKey)) {
            throw new Exception('At last one primary key must be passed!');
        }

        if (!is_array($primaryKey)) {
            $primaryKey = array((string)$primaryKey);
        }
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * Define o nome da tabela do banco de dados
     *
     * @throws \Exception quando o nome da tabela é vazio
     * @param string $name Nome da tabela
     * @access protected
     * @return DBTable Próprio objeto para encadeamento
     */
    protected function setTableName($name)
    {
        $name = (string)$name;
        if (empty($name)) {
            throw new Exception('The table name cannot be blank!');
        }
        $this->tableName = $name;

        return $this;
    }
}
