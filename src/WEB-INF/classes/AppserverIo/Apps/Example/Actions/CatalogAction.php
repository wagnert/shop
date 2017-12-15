<?php

/**
 * AppserverIo\Apps\Example\Actions\CatalogAction
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

namespace AppserverIo\Apps\Example\Actions;

use AppserverIo\Routlt\DispatchAction;
use AppserverIo\Apps\Example\Utils\RequestKeys;
use AppserverIo\Psr\Servlet\Http\HttpServletRequestInterface;
use AppserverIo\Psr\Servlet\Http\HttpServletResponseInterface;

/**
 * Action implementation to render the catalog.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 *
 * @Path(name="/catalog")
 *
 * @Results({
 *     @Result(name="input", result="/dhtml/catalog.dhtml", type="AppserverIo\Routlt\Results\ServletDispatcherResult"),
 *     @Result(name="failure", result="/dhtml/catalog.dhtml", type="AppserverIo\Routlt\Results\ServletDispatcherResult")
 * })
 *
 */
class CatalogAction extends DispatchAction
{

    /**
     * The CatalogProcessor instance to handle the sample functionality.
     *
     * @var \AppserverIo\Apps\Example\Services\CatalogProcessor
     * @EnterpriseBean
     */
    protected $catalogProcessor;

    /**
     * Returns the CatalogProcessor instance to handle the catalog functionality.
     *
     * @return \AppserverIo\RemoteMethodInvocation\RemoteObjectInterface The instance
     */
    public function getCatalogProcessor()
    {
        return $this->catalogProcessor;
    }

    /**
     * Default action that renders the catalog.
     *
     * @param \AppserverIo\Psr\Servlet\Http\HttpServletRequestInterface  $servletRequest  The request instance
     * @param \AppserverIo\Psr\Servlet\Http\HttpServletResponseInterface $servletResponse The response instance
     *
     * @return string|null The action result
     *
     * @Action(name="/index/:slug")
     */
    public function indexAction(HttpServletRequestInterface $servletRequest, HttpServletResponseInterface $servletResponse)
    {

        // load the catalog view data for the passed path info
        /** @var \AppserverIo\Apps\Example\Dtos\CatalogViewData $viewData */
        $viewData = $this->getCatalogProcessor()->getCatalogViewData($servletRequest->getParameter(RequestKeys::SLUG));

        // append the catalog view data to the request attributes
        $servletRequest->setAttribute(RequestKeys::CATALOG_VIEW_DATA, $viewData);
    }
}
