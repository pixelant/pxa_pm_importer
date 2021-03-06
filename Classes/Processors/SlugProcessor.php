<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\Processors\SlugFieldNotFoundException;
use Pixelant\PxaPmImporter\Utility\ExtbaseUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class SlugProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class SlugProcessor extends AbstractFieldProcessor
{
    /**
     * Process slug import field
     *
     * @param $value
     */
    public function process($value): void
    {
        $slugField = $this->configuration['fieldName'] ?: $this->property;

        if (!array_key_exists($slugField, $this->dbRow)) {
            throw new SlugFieldNotFoundException("Could not find slug field with name '{$slugField}'", 1557407283555);
        }

        $table = ExtbaseUtility::convertClassNameToTableName(get_class($this->entity));
        if (!array_key_exists($slugField, $GLOBALS['TCA'][$table]['columns'])
            && is_array($GLOBALS['TCA'][$table]['columns'][$slugField]['config'])
        ) {
            throw new \Exception(
                "TCA configuration invalid for slug field '$slugField' and table '{$table}'",
                1557408220115
            );
        }
        $tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$slugField]['config'];

        $currentSlug = (string)$this->dbRow[$slugField];

        $helper = GeneralUtility::makeInstance(SlugHelper::class, $table, $slugField, $tcaFieldConf);
        // If we should use slug from given by import file, sanitize it
        if ((bool)($this->configuration['useImportValue'] ?? false)) {
            $value = $helper->sanitize($value);
        } else {
            // Otherwise build using TCA configuration
            $value = $helper->generate(
                $this->getDbRowWithSimulatedValuesFromEntity($tcaFieldConf),
                $this->context->getNewRecordsPid()
            );
        }

        // Return directly in case no evaluations are defined
        if (!empty($tcaFieldConf['eval'])) {
            $state = RecordStateFactory::forName($table)->fromArray($this->dbRow, $this->context->getNewRecordsPid());

            $evalCodesArray = GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], true);
            if (in_array('uniqueInSite', $evalCodesArray, true)) {
                $value = $helper->buildSlugForUniqueInSite($value, $state);
            }
            if (in_array('uniqueInPid', $evalCodesArray, true)) {
                $value = $helper->buildSlugForUniqueInPid($value, $state);
            }
        }

        if ($currentSlug !== $value) {
            $this->updateSlugField($table, $slugField, $value);
        }
    }

    /**
     * Method to update slug field. It would be easier to extend this method in child processors
     *
     * @param string $table
     * @param string $field
     * @param string $value
     */
    protected function updateSlugField(string $table, string $field, string $value): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->update(
                $table,
                [$field => $value],
                ['uid' => $this->dbRow['uid']],
                [\PDO::PARAM_STR]
            );
    }

    /**
     * Create DB row with value for slug generation from import entity
     * Since properties might be already update by importer service and we need to use updated values for
     * slug generation
     *
     * @param array $tcaConfig
     * @return array
     */
    protected function getDbRowWithSimulatedValuesFromEntity(array $tcaConfig)
    {
        $dbRow = $this->dbRow;

        foreach ($tcaConfig['generatorOptions']['fields'] ?? [] as $fieldNameParts) {
            if (is_string($fieldNameParts)) {
                $fieldNameParts = GeneralUtility::trimExplode(',', $fieldNameParts);
            }
            foreach ($fieldNameParts as $fieldName) {
                $propertyName = ExtbaseUtility::convertColumnNameToPropertyName(
                    get_class($this->entity),
                    $fieldName
                );

                if (ObjectAccess::isPropertyGettable($this->entity, $propertyName)) {
                    $dbRow[$fieldName] = ObjectAccess::getProperty(
                        $this->entity,
                        $propertyName
                    );
                }
            }
        }

        return $dbRow;
    }
}
