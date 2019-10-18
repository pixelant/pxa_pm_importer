<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Interface FieldProcessorInterface
 * @package Pixelant\PxaPmImporter\Processors
 */
interface FieldProcessorInterface
{
    /**
     * Init processor
     *
     * @param AbstractEntity $entity
     * @param array $dbRow
     * @param string $property
     * @param array $configuration
     */
    public function init(
        AbstractEntity $entity,
        array $dbRow,
        string $property,
        array $configuration
    ): void;

    /**
     * Pre-process value, before validation
     *
     * @param mixed &$value
     */
    public function preProcess(&$value): void;

    /**
     * Check if value is valid before import
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool;

    /**
     * Process single import field
     * This method is called to set value in model property and do any other required operations
     *
     * @param $value
     */
    public function process($value): void;

    /**
     * Get current entity
     *
     * @return AbstractEntity
     */
    public function getProcessingEntity(): AbstractEntity;

    /**
     * Get current record row
     *
     * @return array
     */
    public function getProcessingDbRow(): array;

    /**
     * Get processor configuration
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Get name of processing property
     *
     * @return string
     */
    public function getProcessingProperty(): string;

    /**
     * Tear down
     *
     * This is method called to reset object data
     */
    public function tearDown(): void;
}
