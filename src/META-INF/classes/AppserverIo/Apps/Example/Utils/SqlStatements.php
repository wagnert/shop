<?php

/**
 * AppserverIo\Apps\Example\Utils\SqlStatements
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

namespace AppserverIo\Apps\Example\Utils;

use TechDivision\Import\Utils\AbstractSqlStatements;

/**
 * The SQL statements.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 */
class SqlStatements extends AbstractSqlStatements
{

    /**
     * The key for the SQL statement to load a product by its SKU.
     *
     * @var string
     */
    const PRODUCT_BY_SKU = 'appserver_io.apps.example.subjects.sql_statements.product_by_sku';

    /**
     * The key for the SQL statement to create a new product.
     *
     * @var string
     */
    const PRODUCT_CREATE = 'appserver_io.apps.example.subjects.sql_statements.product_create';

    /**
     * The key for the SQL statement to update an existing product.
     *
     * @var string
     */
    const PRODUCT_UPDATE = 'appserver_io.apps.example.subjects.sql_statements.product_update';

    /**
     * The key for the SQL statement to delete an existing product.
     *
     * @var string
     */
    const PRODUCT_DELETE = 'appserver_io.apps.example.subjects.sql_statements.product_delete';

    /**
     * The SQL statements.
     *
     * @var array
     */
    private $statements = array(
        SqlStatements::PRODUCT_BY_SKU =>
            'SELECT * FROM product WHERE sku = :sku',
        SqlStatements::PRODUCT_DELETE =>
            'DELETE FROM product WHERE id = :id',
        SqlStatements::PRODUCT_CREATE =>
            'INSERT INTO product (parent_id,
                                  status,
                                  name,
                                  url_key,
                                  sku,
                                  sales_price,
                                  stroke_price,
                                  description,
                                  short_description,
                                  new_from,
                                  new_to,
                                  created_at,
                                  updated_at,
                                  deleted)
                  VALUES (:parent_id,
                          :status,
                          :name,
                          :url_key,
                          :sku,
                          :sales_price,
                          :stroke_price,
                          :description,
                          :short_description,
                          :new_from,
                          :new_to,
                          :created_at,
                          :updated_at,
                          :deleted)',
        SqlStatements::PRODUCT_UPDATE =>
            'UPDATE product
                SET parent_id = :parent_id,
                    status = :status,
                    name = :name,
                    url_key = :url_key,
                    sku = :sku,
                    sales_price = :sales_price,
                    stroke_price = :stroke_price,
                    description = :description,
                    short_description = :short_description,
                    new_from = :new_from,
                    new_to = :new_to,
                    created_at = :created_at,
                    updated_at = :updated_at,
                    deleted = :deleted
               WHERE id = :id'
    );

    /**
     * Initialize the the SQL statements.
     */
    public function __construct()
    {

        // merge the class statements
        foreach ($this->statements as $key => $statement) {
            $this->preparedStatements[$key] = $statement;
        }
    }
}
