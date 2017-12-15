<?php

/**
 * AppserverIo\Apps\Example\Actions\Processors\ProductUpdateProcessor
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Apps\Example\Actions\Processors;

use Doctrine\ORM\EntityManagerInterface;
use TechDivision\Import\Utils\SqlStatementsInterface;
use TechDivision\Import\Connection\PDOConnectionWrapper;
use TechDivision\Import\Actions\Processors\AbstractUpdateProcessor;
use AppserverIo\Apps\Example\Utils\MemberNames;

/**
 * The product update processor implementation.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-product
 * @link      http://www.techdivision.com
 */
class ProductUpdateProcessor extends AbstractUpdateProcessor
{

    /**
     * The entity manager instance.
     *
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Initialize the repository with the passed connection and utility class name.
     * .
     * @param \TechDivision\Import\Connection\ConnectionInterface $connection   The connection instance
     * @param \TechDivision\Import\Utils\SqlStatementsInterface   $utilityClass The utility class instance
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SqlStatementsInterface $utilityClass
    ) {

        // set the passed instances
        $this->setEntityManager($entityManager);
        $this->setUtilityClass($utilityClass);

        // initialize the instance
        $this->init();
    }

    /**
     * Set's the entity manager instance.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager The entity manager instance
     *
     * @return void
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Set's the entity manager instance.
     *
     * @return \Doctrine\ORM\EntityManagerInterface The entity manager instance
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Return's the connection to use.
     *
     * @return \TechDivision\Import\Connection\ConnectionInterface The connection instance
     */
    public function getConnection()
    {
        return new PDOConnectionWrapper($this->getEntityManager()->getConnection()->getWrappedConnection());
    }

    /**
     * Return's the array with the SQL statements that has to be prepared.
     *
     * @return array The SQL statements to be prepared
     * @see \TechDivision\Import\Actions\Processors\AbstractBaseProcessor::getStatements()
     */
    protected function getStatements()
    {

        // load the utility class name
        $utilityClassName = $this->getUtilityClassName();

        // return the array with the SQL statements that has to be prepared
        return array(
            $utilityClassName::PRODUCT_UPDATE => $this->getUtilityClass()->find($utilityClassName::PRODUCT_UPDATE)
        );
    }

    /**
     * Update's the passed row.
     *
     * @param array       $row  The row to update
     * @param string|null $name The name of the prepared statement that has to be executed
     *
     * @return string The ID of the updated product
     */
    public function execute($row, $name = null)
    {
        parent::execute($row, $name);
        return $row[MemberNames::ID];
    }
}
