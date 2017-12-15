<?php

/**
 * AppserverIo\Apps\Example\Observers\ImportReceiver
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

namespace AppserverIo\Apps\Example\Observers;

use TechDivision\Import\Subjects\SubjectInterface;
use TechDivision\Import\Observers\AbstractObserver;
use AppserverIo\Apps\Example\Utils\ColumnKeys;
use AppserverIo\Apps\Example\Services\ImportProductProcessorInterface;
use AppserverIo\Apps\Example\Utils\MemberNames;

/**
 * Dummy product observer implementation.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 */
class ProductObserver extends AbstractObserver
{

    /**
     * The product import processor instance.
     *
     * @var \AppserverIo\Apps\Example\Services\ImportProductProcessorInterface
     */
    protected $importProductProcessor;

    /**
     * Injects the entity manager and the DI provider instance.
     *
     * @param \AppserverIo\Apps\Example\Services\ImportProductProcessorInterface $importProductProcessor The product import processor instance
     */
    public function __construct(ImportProductProcessorInterface $importProductProcessor)
    {
        $this->importProductProcessor = $importProductProcessor;
    }

    /**
     * Return's the product import processor instance.
     *
     * @return \AppserverIo\Apps\Example\Services\ImportProductProcessorInterface The product import processor instance
     */
    public function getImportProductProcessor()
    {
        return $this->importProductProcessor;
    }

    /**
     * Will be invoked by the action on the events the listener has been registered for.
     *
     * @param \TechDivision\Import\Subjects\SubjectInterface $subject The subject instance
     *
     * @return array The modified row
     */
    public function handle(SubjectInterface $subject)
    {

        // initialize the row
        $this->setSubject($subject);
        $this->setRow($subject->getRow());

        // initialize the product
        $product = $this->initializeEntity(array());

        // try to load the product with the SKU found in the CSV file
        if ($found = $this->getImportProductProcessor()->loadProductBySku($this->getValue(ColumnKeys::SKU))) {
            $product = $this->mergeEntity($found, $product);
        }

        // query whether or not a name/url_key is available
        if ($this->getValue(ColumnKeys::NAME) && $this->getValue(ColumnKeys::URL_KEY)) {
            // set product data and save it
            $product[MemberNames::SKU] = $this->getValue(ColumnKeys::SKU);
            $product[MemberNames::NAME] = $this->getValue(ColumnKeys::NAME);
            $product[MemberNames::STATUS] = (integer) $this->getValue(ColumnKeys::PRODUCT_ONLINE);
            $product[MemberNames::URL_KEY] = $this->getValue(ColumnKeys::URL_KEY);
            $product[MemberNames::SALES_PRICE] = (integer) $this->getValue(ColumnKeys::PRICE);
            $product[MemberNames::DESCRIPTION] = $this->getValue(ColumnKeys::DESCRIPTION);
            $product[MemberNames::SHORT_DESCRIPTION] = $this->getValue(ColumnKeys::SHORT_DESCRIPTION);

            // persist the product
            $this->getImportProductProcessor()->persistProduct($product);
        }

        // return the processed row
        return $this->getRow();
    }
}
