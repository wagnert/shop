<?php

/**
 * AppserverIo\Apps\Example\Repositories\ImportProductRepository
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

namespace AppserverIo\Apps\Example\Repositories;

use TechDivision\Import\Product\Utils\MemberNames;

/**
 * Product repository implementation.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 */
class ImportProductRepository extends AbstractImportRepository implements ImportProductRepositoryInterface
{

    /**
     * The prepared statement to load an existing product by its SKU.
     *
     * @var \PDOStatement
     */
    protected $productBySkuStmt;

    /**
     * Initializes the repository's prepared statements.
     *
     * @return void
     */
    public function init()
    {

        // load the utility class name
        $utilityClassName = $this->getUtilityClassName();

        // initialize the prepared statements
        $this->productBySkuStmt =
            $this->getConnection()->prepare($this->getUtilityClass()->find($utilityClassName::PRODUCT_BY_SKU));
    }

    /**
     * Return's the product with the passed SKU.
     *
     * @param string $sku The SKU of the product to return
     *
     * @return array|boolean The product
     */
    public function findOneBySku($sku)
    {
        // load and return the product with the passed SKU
        $this->productBySkuStmt->execute(array(MemberNames::SKU => $sku));
        return $this->productBySkuStmt->fetch(\PDO::FETCH_ASSOC);
    }
}
