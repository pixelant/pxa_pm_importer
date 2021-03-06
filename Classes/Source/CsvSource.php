<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Source;

use Pixelant\PxaPmImporter\Exception\InvalidSourceFileException;

/**
 * Class CsvSource
 * @package Pixelant\PxaPmImporter\Source
 */
class CsvSource extends AbstractFileSource
{
    /**
     * Default CSV delimiter
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * How many rows to skip
     *
     * @var int
     */
    protected $skipRows = 0;

    /**
     * Source csv file stream
     *
     * @var \SplFileObject
     */
    protected $fileStream = null;

    /**
     * Initialize
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void
    {
        parent::initialize($configuration);

        if (!empty($configuration['delimiter'])) {
            $this->delimiter = $configuration['delimiter'];
        }
        if (!empty($configuration['skipRows'])) {
            $this->skipRows = (int)$configuration['skipRows'];
        }

        if ($this->isSourceFilePathValid()) {
            $this->fileStream = (new \SplFileObject($this->getAbsoluteFilePath()));
            // @codingStandardsIgnoreStart
            $this->fileStream->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
            // @codingStandardsIgnoreEnd
            $this->fileStream->setCsvControl($this->delimiter);
        } else {
            throw new InvalidSourceFileException('Could not read data from source file "' . $this->filePath . '"');
        }
    }

    /**
     * Rewind CSV source
     */
    public function rewind(): void
    {
        $this->fileStream->rewind();
        if ($this->skipRows > 0) {
            $this->fileStream->seek($this->skipRows);
        }
    }

    /**
     * Is end of file
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->fileStream->valid();
    }

    /**
     * Current key
     *
     * @return int|mixed
     */
    public function key()
    {
        return $this->fileStream->key();
    }

    /**
     * Current CSV line as array
     *
     * @return array
     */
    public function current(): array
    {
        $current = $this->fileStream->current();

        return $current;
    }

    /**
     * Next file line
     */
    public function next(): void
    {
        $this->fileStream->next();
    }

    /**
     * Count CSV lines
     *
     * @return int
     */
    public function count()
    {
        // Find max
        $this->fileStream->seek(PHP_INT_MAX);
        $linesTotal = $this->fileStream->key();

        // Reset
        $this->rewind();

        return $linesTotal;
    }
}
