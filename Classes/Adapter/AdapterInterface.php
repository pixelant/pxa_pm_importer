<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

use Pixelant\PxaPmImporter\Source\SourceInterface;

/**
 * Interface AdapterInterface
 * @package Pixelant\PxaPmImporter\Adapter
 */
interface AdapterInterface
{
    /**
     * Initialize adapter
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void;

    /**
     * Adapt single row
     * Expect to return array with field name to value
     *
     * ['name' => 'product 1', 'sku' => 123]
     *
     * @param mixed $key Row key
     * @param mixed $rawData Raw data for single import item
     * @param int $languageUid Current import language
     * @return array
     */
    public function adaptRow($key, $rawData, int $languageUid): array;

    /**
     * Check if row should be included in import
     *
     * @param mixed $key Row key
     * @param mixed $rawData Raw data for single import item
     * @param int $languageUid Current import language
     * @return bool
     */
    public function includeRow($key, $rawData, int $languageUid): bool;

    /**
     * Array with UIDs of import languages
     * Importer will iterate through each language
     * and run import source data with language uid
     *
     * @return array
     */
    public function getImportLanguages(): array;

    /**
     * Count amount of items to be importer
     *
     * @param SourceInterface $source
     * @return int
     */
    public function countAmountOfItems(SourceInterface $source): int;
}
