<?php

/**
 * AppserverIo\Apps\Example\Services\ImportProcessor
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

use AppserverIo\Lang\Boolean;
use AppserverIo\Messaging\StringMessage;
use AppserverIo\Psr\HttpMessage\PartInterface;
use TechDivision\Import\Utils\BunchKeys;
use TechDivision\Import\Subjects\ExportableSubjectInterface;
use TechDivision\Import\Configuration\SubjectConfigurationInterface;

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
class ImportProcessor extends AbstractPersistenceProcessor implements ImportProcessorInterface
{

    /**
     * The queue sender for sending the import message.
     *
     * @var \AppserverIo\Messaging\QueueSender
     * @Resource(name="import", type="pms/import")
     */
    protected $importSender;

    /**
     * The import action instance.
     *
     * @var \AppserverIo\Apps\Example\Applications\ImportApplication
     * @EnterpriseBean
     */
    protected $importApplication;

    /**
     * The system logger implementation.
     *
     * @var \AppserverIo\Logger\Logger
     * @Resource(lookup="php:global/log/System")
     */
    protected $systemLogger;

    /**
     * The callback visitor instance.
     *
     * @var \TechDivision\Import\Callbacks\CallbackVisitor
     * @Inject
     */
    protected $callbackVisitor;

    /**
     * The observer visitor instance.
     *
     * @var \TechDivision\Import\Observers\ObserverVisitor
     * @Inject
     */
    protected $observerVisitor;

    /**
     * The subject factory instance.
     *
     * @var \TechDivision\Import\Subjects\SubjectFactoryInterface
     * @Inject
     */
    protected $subjectFactory;

    /**
     * Returns the queue sender for sending the import message.
     *
     * @return \AppserverIo\Messaging\QueueSender The queue sender
     */
    protected function getImportSender()
    {
        return $this->importSender;
    }

    /**
     * Returns the import action instance.
     *
     * @return \AppserverIo\Apps\Example\Applications\ImportApplicationInterface The import action instance
     */
    protected function getImportApplication()
    {
        return $this->importApplication;
    }

    /**
     * Return's the system logger instance.
     *
     * @return \AppserverIo\Logger\Logger The sytsem logger instance
     */
    protected function getSystemLogger()
    {
        return $this->systemLogger;
    }

    /**
     * Return's the temporary upload directory specified in the php.ini.
     *
     * @return string The temporary upload directory
     */
    protected function getUploadTmpDir()
    {
        return $this->getApplication()->getBaseDirectory(DIRECTORY_SEPARATOR . ini_get('upload_tmp_dir'));
    }

    /**
     * Returns an ArrayObject with the CSV files that can be imported.
     *
     * @return \ArrayObject An array with the name of CSV files that can be imported
     */
    public function findAll()
    {

        // initialize an array object to load file uploads
        $overviewData = new \ArrayObject();

        // init file iterator on deployment directory
        $fileIterator = new \FilesystemIterator($this->getUploadTmpDir());

        // Iterate through all phar files and extract them to tmp dir
        foreach (new \RegexIterator($fileIterator, '/^.*\\.csv$/') as $importFile) {
            $overviewData->append($importFile->getPathname());
        }

        // return the array with the name of the uploaded files
        return $overviewData;
    }

    /**
     * Uploads the passed file part to the temporary upload directory.
     *
     * @param \AppserverIo\Psr\HttpMessage\PartInterface $fileToUpload   The file part to upload
     * @param \AppserverIo\Lang\Boolean                  $watchDirectory TRUE if the directory has to be watched
     *
     * @return void
     */
    public function upload(PartInterface $fileToUpload, Boolean $watchDirectory)
    {

        // save file to appservers upload tmp folder with tmpname
        $fileToUpload->init();
        $fileToUpload->write(
            tempnam($this->getUploadTmpDir(), 'example_upload_') . '.' . pathinfo($fileToUpload->getFilename(), PATHINFO_EXTENSION)
        );

        // check if we should watch the directory for periodic import
        if ($watchDirectory->booleanValue()) {
            // initialize the message with the name of the directory we want to watch
            $message = new StringMessage($this->getUploadTmpDir());

            // create a new message and send it
            $this->getCreateAIntervalTimerSender()->send($message, false);
        }
    }

    /**
     * Delete the file from the temporary upload directory
     *
     * @param string $filename The name of the file to upload
     *
     * @return void
     */
    public function delete($filename)
    {
        unlink($this->getUploadTmpDir() . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * Import the file with the passed filename from the temporary upload directory.
     *
     * @param string $filename The name of the file to import
     *
     * @return void
     */
    public function import($filename)
    {

        // initialize the message with the name of the file to import the data from
        $message = new StringMessage($filename);

        // create a new message and send it
        $this->getImportSender()->send($message, false);
    }

    /**
     * Scan's the upload directory for new CSV files that has to be imported.
     *
     * @return void
     * @Schedule(dayOfMonth = EVERY, month = EVERY, year = EVERY, second = ZERO, minute = EVERY, hour = EVERY)
     */
    public function scan()
    {
        try {
            $this->getImportApplication()->process();
        } catch (\Exception $e) {
            $this->getSystemLogger()->error($e->__toString());
        }
    }

    /**
     * Process the passed subject.
     *
     * @param \TechDivision\Import\Configuration\SubjectConfigurationInterface $subject  The subject configuration
     * @param string                                                           $serial   The serial number of the actual import process
     * @param string                                                           $pathname The relative name to the file that has to be processed
     *
     * @return void
     */
    public function processSubject(SubjectConfigurationInterface $subject, $serial, $pathname)
    {

        // initialize the subject and import the bunch
        $subjectInstance = $this->subjectFactory->createSubject($subject);

        try {
            // setup the subject instance
            $subjectInstance->setUp($serial);

            // initialize the callbacks/observers
            $this->callbackVisitor->visit($subjectInstance);
            $this->observerVisitor->visit($subjectInstance);

            // finally import the CSV file
            $subjectInstance->import($serial, $pathname);

            // query whether or not, we've to export artefacts
            if ($subjectInstance instanceof ExportableSubjectInterface) {
                $subjectInstance->export(
                    $this->matches[BunchKeys::FILENAME],
                    $this->matches[BunchKeys::COUNTER]
                );
            }

            // tear down the subject instance
            $subjectInstance->tearDown($serial);

        } catch (\Exception $e) {
            // query whether or not, we've to export artefacts
            if ($subjectInstance instanceof ExportableSubjectInterface) {
                // tear down the subject instance
                $subjectInstance->tearDown($serial);
            }

            // re-throw the exception
            throw $e;
        }
    }
}
