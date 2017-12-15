<?php

/**
 * AppserverIo\Apps\Example\Services\ImportProductProcessorInterface
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
 * Interface for a product import processor.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 */
interface ImportProductProcessorInterface
{

    /**
     * Load's and return's the product with the passed SKU.
     *
     * @param string $sku The SKU to load the product for
     *
     * @return array|null
     */
    public function loadProductBySku($sku);

    /**
     * Persists the passed product.
     *
     * @param array $product The product that has to be persisted
     *
     * @return void
     */
    public function persistProduct(array $product);
}
