<?php

/**
 * AppserverIo\Apps\Example\Plugins\SubjectPlugin
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

namespace AppserverIo\Apps\Example\Plugins;

use AppserverIo\Messaging\QueueSender;
use AppserverIo\Messaging\ArrayMessage;
use TechDivision\Import\Utils\BunchKeys;
use TechDivision\Import\Utils\RegistryKeys;
use TechDivision\Import\ApplicationInterface;
use TechDivision\Import\Plugins\AbstractPlugin;
use TechDivision\Import\Exceptions\LineNotFoundException;
use TechDivision\Import\Exceptions\MissingOkFileException;
use TechDivision\Import\Configuration\SubjectConfigurationInterface;

/**
 * Plugin that processes the subjects.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2017 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-apps/example
 * @link      http://www.appserver.io
 */
class SubjectPlugin extends AbstractPlugin
{

    /**
     * The matches for the last processed CSV filename.
     *
     * @var array
     */
    protected $matches = array();

    /**
     * The number of imported bunches.
     *
     * @var integer
     */
    protected $bunches = 0;

    /**
     * The queue sender for sending the import message.
     *
     * @var \AppserverIo\Messaging\QueueSender
     */
    protected $importSender;

    /**
     * Initializes the plugin with the application instance.
     *
     * @param \TechDivision\Import\ApplicationInterface               $application     The application instance
     * @param \TechDivision\Import\Callbacks\CallbackVisitorInterface $callbackVisitor The callback visitor instance
     * @param \TechDivision\Import\Observers\ObserverVisitorInterface $observerVisitor The observer visitor instance
     * @param \TechDivision\Import\Subjects\SubjectFactoryInterface   $subjectFactory  The subject factory instance
     * @param \AppserverIo\Messaging\QueueSender                      $importSender    The queue sender instance
     */
    public function __construct(ApplicationInterface $application, QueueSender $importSender) {

        // call the parent constructor
        parent::__construct($application);

        // initialize the members
        $this->importSender = $importSender;
    }

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
     * Process the plugin functionality.
     *
     * @return void
     * @throws \Exception Is thrown, if the plugin can not be processed
     */
    public function process()
    {
        try {
            // immediately add the PID to lock this import process
            $this->lock();

            // load the plugin's subjects
            $subjects = $this->getPluginConfiguration()->getSubjects();

            // initialize the array for the status
            $status = array();

            // initialize the status information for the subjects
            /** @var \TechDivision\Import\Configuration\SubjectConfigurationInterface $subject */
            foreach ($subjects as $subject) {
                $status[$subject->getPrefix()] = array();
            }

            // and update it in the registry
            $this->getRegistryProcessor()->mergeAttributesRecursive($this->getSerial(), $status);

            // process all the subjects found in the system configuration
            /** @var \TechDivision\Import\Configuration\SubjectConfigurationInterface $subject */
            foreach ($subjects as $subject) {
                $this->processSubject($subject);
            }

            // update the number of imported bunches
            $this->getRegistryProcessor()->mergeAttributesRecursive(
                $this->getSerial(),
                array(RegistryKeys::BUNCHES => $this->bunches)
            );

            // stop the application if we don't process ANY bunch
            if ($this->bunches === 0) {
                $this->getApplication()->stop(
                    sprintf(
                        'Operation %s has been stopped by %s, because no import files can be found in directory %s',
                        $this->getConfiguration()->getOperationName(),
                        get_class($this),
                        $this->getConfiguration()->getSourceDir()
                    )
                );
            }

            // finally, if a PID has been set (because CSV files has been found),
            // remove it from the PID file to unlock the importer
            $this->unlock();

        } catch (\Exception $e) {
            // finally, if a PID has been set (because CSV files has been found),
            // remove it from the PID file to unlock the importer
            $this->unlock();

            // re-throw the exception
            throw $e;
        }
    }

    /**
     * Loads the files from the source directory and return's them sorted.
     *
     * @param \TechDivision\Import\Configuration\SubjectConfigurationInterface $subject The source directory to parse for files
     *
     * @return array The array with the files matching the subjects suffix
     * @throws \Exception Is thrown, when the source directory is NOT available
     */
    protected function loadFiles(SubjectConfigurationInterface $subject)
    {

        // clear the filecache
        clearstatcache();

        // load the actual status
        $status = $this->getRegistryProcessor()->getAttribute($this->getSerial());

        // query whether or not the configured source directory is available
        if (!is_dir($sourceDir = $status[RegistryKeys::SOURCE_DIRECTORY])) {
            throw new \Exception(sprintf('Source directory %s for subject %s is not available!', $sourceDir, $subject->getId()));
        }

        // initialize the array with the files matching the suffix found in the source directory
        $files = glob(sprintf('%s/*.%s', $sourceDir, $subject->getSuffix()));

        // sort the files for the apropriate order
        usort($files, function ($a, $b) {
            return strcmp($a, $b);
        });

        // return the sorted files
        return $files;
    }

    /**
     * Process the subject with the passed name/identifier.
     *
     * We create a new, fresh and separate subject for EVERY file here, because this would be
     * the starting point to parallelize the import process in a multithreaded/multiprocessed
     * environment.
     *
     * @param \TechDivision\Import\Configuration\SubjectConfigurationInterface $subject The subject configuration
     *
     * @return void
     * @throws \Exception Is thrown, if the subject can't be processed
     */
    protected function processSubject(SubjectConfigurationInterface $subject)
    {

        // initialize the bunch number
        $bunches = 0;

        // load the files
        $files = $this->loadFiles($subject);

        // iterate through all CSV files and process the subjects
        foreach ($files as $pathname) {
            // query whether or not that the file is part of the actual bunch
            if ($this->isPartOfBunch($subject->getPrefix(), $subject->getSuffix(), $pathname)) {

                // send a message that start processing the file asynchronous
                $this->getImportSender()->send(new ArrayMessage(array($this->getSerial(), $pathname, $subject)));

                // query whether or not the subject needs an OK file,
                // if yes remove the filename from the file
                if ($subject->isOkFileNeeded()) {
                    $this->removeFromOkFile($pathname, $subject->getSuffix());
                }

                // raise the number of the imported bunches
                $bunches++;
            }
        }

        // query whether or not we've files
        if (sizeof($files) > 0) {
            // initialize the wait flag
            $wait = true;

            do {
                // sleep for a short taime
                usleep(1000);
                // load the status from the registry
                $status = $this->getRegistryProcessor()->getAttribute($this->getSerial());

                // query whether or not we've files to wait for
                if (isset($status[RegistryKeys::FILES])) {
                    // load the processed files
                    $filesProcessed = $status[RegistryKeys::FILES];
                    // make sure that all files has been processed
                    foreach ($files as $file) {
                        if (isset($filesProcessed[$file]) && $filesProcessed[$file] === 1) {
                            continue;
                        } else {
                            continue 2;
                        }
                    }

                    // stop waiting when all files have been processed
                    $wait = false;
                }

            } while ($wait);
        }

        // raise the bunch number by the imported bunches
        $this->bunches = $this->bunches + $bunches;

        // reset the matches, because the exported artefacts
        $this->matches = array();

        // and and log a message that the subject has been processed
        $this->getSystemLogger()->debug(
            sprintf('Successfully processed subject %s with %d bunch(es)!', $subject->getId(), $bunches)
        );
    }

    /**
     * Queries whether or not, the passed filename is part of a bunch or not.
     *
     * @param string $prefix   The prefix to query for
     * @param string $suffix   The suffix to query for
     * @param string $filename The filename to query for
     *
     * @return boolean TRUE if the filename is part, else FALSE
     */
    protected function isPartOfBunch($prefix, $suffix, $filename)
    {

        // initialize the pattern
        $pattern = '';

        // query whether or not, this is the first file to be processed
        if (sizeof($this->matches) === 0) {
            // initialize the pattern to query whether the FIRST file has to be processed or not
            $pattern = sprintf(
                '/^.*\/(?<%s>%s)_(?<%s>.*)_(?<%s>\d+)\\.%s$/',
                BunchKeys::PREFIX,
                $prefix,
                BunchKeys::FILENAME,
                BunchKeys::COUNTER,
                $suffix
            );

        } else {
            // initialize the pattern to query whether the NEXT file is part of a bunch or not
            $pattern = sprintf(
                '/^.*\/(?<%s>%s)_(?<%s>%s)_(?<%s>\d+)\\.%s$/',
                BunchKeys::PREFIX,
                $this->matches[BunchKeys::PREFIX],
                BunchKeys::FILENAME,
                $this->matches[BunchKeys::FILENAME],
                BunchKeys::COUNTER,
                $suffix
            );
        }

        // initialize the array for the matches
        $matches = array();

        // update the matches, if the pattern matches
        if ($result = preg_match($pattern, $filename, $matches)) {
            $this->matches = $matches;
        }

        // stop processing, if the filename doesn't match
        return (boolean) $result;
    }

    /**
     * Return's an array with the names of the expected OK files for the actual subject.
     *
     * @return array The array with the expected OK filenames
     */
    protected function getOkFilenames()
    {

        // load the array with the available bunch keys
        $bunchKeys = BunchKeys::getAllKeys();

        // initialize the array for the available okFilenames
        $okFilenames = array();

        // prepare the OK filenames based on the found CSV file information
        for ($i = 1; $i <= sizeof($bunchKeys); $i++) {
            // intialize the array for the parts of the names (prefix, filename + counter)
            $parts = array();
            // load the parts from the matches
            for ($z = 0; $z < $i; $z++) {
                $parts[] = $this->matches[$bunchKeys[$z]];
            }

            // query whether or not, the OK file exists, if yes append it
            if (file_exists($okFilename = sprintf('%s/%s.ok', $this->getSourceDir(), implode('_', $parts)))) {
                $okFilenames[] = $okFilename;
            }
        }

        // prepare and return the pattern for the OK file
        return $okFilenames;
    }

    /**
     * Query whether or not, the passed CSV filename is in the OK file. If the filename was found,
     * it'll be returned and the method return TRUE.
     *
     * If the filename is NOT in the OK file, the method return's FALSE and the CSV should NOT be
     * imported/moved.
     *
     * @param string $filename The CSV filename to query for
     * @param string $suffix   The CSF filename suffix, csv by default
     *
     * @return void
     * @throws \Exception Is thrown, if the passed filename is NOT in the OK file or it can NOT be removed from it
     */
    protected function removeFromOkFile($filename, $suffix)
    {

        try {
            // try to load the expected OK filenames
            if (sizeof($okFilenames = $this->getOkFilenames()) === 0) {
                throw new MissingOkFileException(sprintf('Can\'t find a OK filename for file %s', $filename));
            }

            // iterate over the found OK filenames (should usually be only one, but could be more)
            foreach ($okFilenames as $okFilename) {
                // if the OK filename matches the CSV filename AND the OK file is empty
                if (basename($filename, sprintf('.%s', $suffix)) === basename($okFilename, '.ok') && filesize($okFilename) === 0) {
                    unlink($okFilename);
                    return;
                }

                // else, remove the CSV filename from the OK file
                $this->removeLineFromFile(basename($filename), $fh = fopen($okFilename, 'r+'));
                fclose($fh);

                // if the OK file is empty, delete the file
                if (filesize($okFilename) === 0) {
                    unlink($okFilename);
                }

                // return immediately
                return;
            }

            // throw an exception if either no OK file has been found,
            // or the CSV file is not in one of the OK files
            throw new \Exception(
                sprintf(
                    'Can\'t found filename %s in one of the expected OK files: %s',
                    $filename,
                    implode(', ', $okFilenames)
                )
            );

        } catch (LineNotFoundException $lne) {
            // wrap and re-throw the exception
            throw new \Exception(
                sprintf(
                    'Can\'t remove filename %s from OK file: %s',
                    $filename,
                    $okFilename
                ),
                null,
                $lne
            );
        }
    }
}
