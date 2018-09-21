<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Configuration;

use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Exception\YamlResourceInvalidException;
use Symfony\Component\Yaml\Yaml;

class YamlConfiguration extends AbstractConfiguration
{
    /**
     * Path to yaml configuration
     *
     * @var string
     */
    protected $yamlPath = '';

    /**
     * Initialize
     *
     * @param string $yamlPath Absolute path to file
     */
    public function __construct(string $yamlPath)
    {
        $this->yamlPath = $yamlPath;
        parent::__construct();
    }

    /**
     * Check if yaml source is valid
     *
     * @return bool
     */
    public function isSourceValid(): bool
    {
        if (!empty($this->yamlPath)) {
            return $this->isFileValid($this->yamlPath);
        }

        return false;
    }

    /**
     *  Parse yaml configuration
     *
     * @return array
     */
    protected function parseConfiguration(): array
    {
        $configuration = Yaml::parseFile($this->yamlPath);

        if (!is_array($configuration)) {
            // @codingStandardsIgnoreStart
            throw new InvalidConfigurationSourceException('Parsed configuration is not array, but "' . gettype($configuration) . '"', 1535961126729);
            // @codingStandardsIgnoreEnd
        }

        if (isset($configuration['imports']) && is_array($configuration['imports'])) {
            foreach ($configuration['imports'] as $importYaml) {
                if (!empty($importYaml['resource'])) {
                    $importPath = dirname($this->yamlPath) . '/' . trim($importYaml['resource'], '/');

                    if ($this->isFileValid($importPath)) {
                        $configuration = array_merge($configuration, Yaml::parseFile($importPath));
                    } else {
                        // @codingStandardsIgnoreStart
                        throw new YamlResourceInvalidException('Invalid imports resource "' . $importYaml['resource'] . '"', 1537530881729);
                        // @codingStandardsIgnoreEnd
                    }
                }
            }
            unset($configuration['imports']);
        }

        return $configuration;
    }

    /**
     * Source
     *
     * @return string
     */
    protected function getConfigurationSource(): string
    {
        return $this->yamlPath;
    }
}
