<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Service\Configuration;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Service\Configuration\AbstractConfiguration;

/**
 * Class AbstractConfigurationTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Service\Configuration
 */
class AbstractConfigurationTest extends UnitTestCase
{
    /**
     * @var AbstractConfiguration|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            AbstractConfiguration::class,
            ['getConfigurationSource', 'isSourceValid', 'parseConfiguration'],
            [],
            '',
            false
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function initializeWithInvalidSourceThrowsException()
    {
        $this->expectException(InvalidConfigurationSourceException::class);

        $this->subject->_call('initialize');
    }

    /**
     * @test
     */
    public function getSourceConfigurationReturnConfiguration()
    {
        $configuration = [
            'test' => 'bla'
        ];

        $this->subject->_set('configuration', $configuration);
        $this->assertEquals($configuration, $this->subject->getConfiguration());
    }

    /**
     * @test
     */
    public function getSourceConfigurationReturnSourceFromConfiguration()
    {
        $sourceConf = ['source' => 'test'];
        $configuration = [
            'test' => 'bla',
            'source' => $sourceConf
        ];

        $this->subject->_set('configuration', $configuration);
        $this->assertEquals($sourceConf, $this->subject->getSourceConfiguration());
    }

    /**
     * @test
     */
    public function getImportersConfigurationReturnSourceFromConfiguration()
    {
        $importers = ['importers' => 'test'];
        $configuration = [
            'test' => 'bla',
            'importers' => $importers
        ];

        $this->subject->_set('configuration', $configuration);
        $this->assertEquals($importers, $this->subject->getImportersConfiguration());
    }

    /**
     * @test
     */
    public function getSourceConfigurationIfNotSetThrowsException()
    {
        $configuration = [
            'test' => 'bla',
        ];

        $this->subject->_set('configuration', $configuration);
        $this->expectException(\UnexpectedValueException::class);
        $this->subject->getSourceConfiguration();
    }

    /**
     * @test
     */
    public function getImportersConfigurationIfNotSetThrowsException()
    {
        $configuration = [
            'test' => 'bla',
        ];

        $this->subject->_set('configuration', $configuration);
        $this->expectException(\UnexpectedValueException::class);
        $this->subject->getSourceConfiguration();
    }

    /**
     * @test
     */
    public function getLogCustomPathReturnNullIfLogNotSet()
    {
        $configuration = [
            'test' => 'bla',
        ];

        $this->subject->_set('configuration', $configuration);

        $this->assertNull($this->subject->getLogPath());
    }

    /**
     * @test
     */
    public function getLogCustomPathReturnLogPathIfSet()
    {
        $logPath = 'log_path/test.log';

        $configuration = [
            'log' => [
                'path' => $logPath
            ]
        ];

        $this->subject->_set('configuration', $configuration);

        $this->assertEquals($logPath, $this->subject->getLogPath());
    }
}
