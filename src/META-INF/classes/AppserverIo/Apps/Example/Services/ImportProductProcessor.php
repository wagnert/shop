<?php

/**
 * AppserverIo\Apps\Example\Services\ImportProductProcessor
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

namespace AppserverIo\Apps\Example\Services;

/**
 * A SLSB that handles the import process.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 *
 * @Stateless
 */
class ImportProductProcessor extends AbstractProcessor implements ImportProductProcessorInterface
{

    /**
     * The product repository instance.
     *
     * @var \AppserverIo\Apps\Example\Repositories\ImportProductRepositoryInterface
     * @Inject
     */
    protected $importProductRepository;

    /**
     * The product action instance.
     *
     * @var \AppserverIo\Apps\Example\Actions\ProductActionInterface
     * @Inject
     */
    protected $productAction;

    /**
     * The product action instance.
     *
     * @return \AppserverIo\Apps\Example\Repositories\ImportProductRepositoryInterface The product repository instance
     */
    public function getImportProductRepository()
    {
        return $this->importProductRepository;
    }

    /**
     * The product action instance.
     *
     * @return \AppserverIo\Apps\Example\Actions\ProductActionInterface The product action instance
     */
    public function getProductAction()
    {
        return $this->productAction;
    }

    /**
     * Return's the product with the passed SKU.
     *
     * @param string $sku The SKU of the product to return
     *
     * @return array|boolean The product
     */
    public function loadProductBySku($sku)
    {
        return $this->getImportProductRepository()->findOneBySku($sku);
    }

    /**
     * Create/update the passed entity, depending on the entity's status.
     *
     * @param array $product The entity data to create/update
     *
     * @return string The last inserted ID
     */
    public function persistProduct(array $product)
    {
        return $this->getProductAction()->persist($product);
    }
}
