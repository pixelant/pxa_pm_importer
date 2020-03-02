<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Updater;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Repository\FileReferenceRepository;
use Pixelant\PxaPmImporter\Processors\Relation\Updater\RelationPropertyUpdater;
use Pixelant\PxaProductManager\Domain\Model\Product;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Updater
 */
class RelationPropertyUpdaterTest extends UnitTestCase
{

    protected $subject;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(RelationPropertyUpdater::class, null, [], '', false);
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    /**
     * @test
     */
    public function updateObjectStorageWontDoAnythingIfStorageIsSame()
    {
        $product = new Product();
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 12);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 21);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);

        $product->setRelatedProducts($objectStorage);

        $this->subject->_call('updateObjectStorage', $product, 'relatedProducts', $objectStorage, [$relatedProduct1, $relatedProduct2]);

        $this->assertEquals($objectStorage, $product->getRelatedProducts());
    }

    /**
     * @test
     */
    public function updateObjectStorageWillSetObjectsFromImportIfStorageIsDifferent()
    {
        $product = new Product();
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 12);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 21);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);

        $product->setRelatedProducts($objectStorage);

        $relatedProduct3 = new Product();
        $relatedProduct3->_setProperty('uid', 121);

        $relatedProduct4 = new Product();
        $relatedProduct4->_setProperty('uid', 211);

        $import = [
            $relatedProduct3,
            $relatedProduct4
        ];

        $this->subject->_call('updateObjectStorage', $product, 'relatedProducts', $objectStorage, $import);

        $this->assertEquals($import, $product->getRelatedProducts()->toArray());
    }

    /**
     * @test
     */
    public function getEntityUidForCompareReturnUidOfEntityIfNotAFileReference()
    {
        $uid = 111122;
        $entity = new Product();
        $entity->_setProperty('uid', $uid);

        $this->assertEquals($uid, $this->subject->_call('getEntityUidForCompare', $entity));
    }

    /**
     * @test
     */
    public function getEntityUidForCompareReturnUidOfFileUidIfAFileReference()
    {
        $uid = 3344;
        $fileData = [
            'name' => 'testfile',
            'identifier' => 'testIdentifier',
            'uid' => $uid
        ];
        $file = new File($fileData, $this->createMock(ResourceStorage::class));

        $originalResourceMock = $this
            ->getMockBuilder(FileReference::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($originalResourceMock, 'originalFile', $file);

        $fileReference = $this->getAccessibleMock(
            ExtbaseFileReference::class,
            null,
            [],
            '',
            false
        );
        $fileReference->_set('originalResource', $originalResourceMock);

        $this->assertEquals($uid, $this->subject->_call('getEntityUidForCompare', $fileReference));
    }

    /**
     * @test
     */
    public function updateRelationPropertyForObjectStorageCallUpdateObjectStorage()
    {
        $objectStorage = new ObjectStorage();

        $entity = $this->createPartialMock(Product::class, ['getProperty']);
        $entity
            ->expects($this->atLeastOnce())
            ->method('getProperty')
            ->willReturn($objectStorage);


        $subject = $this->getAccessibleMock(RelationPropertyUpdater::class, ['updateObjectStorage'], [], '', false);
        $subject
            ->expects($this->once())
            ->method('updateObjectStorage');

        $subject->_call('update', $entity, 'property', []);
    }

    /**
     * @test
     */
    public function updateRelationPropertyForSingleEntityWillDeleteOldEntityIfFileReference()
    {
        $mockedFile = $this->createPartialMock(File::class, ['getUid']);
        $mockedFile->expects($this->once())->method('getUid')->willReturn(22);

        $mockedCoreReference = $this->createPartialMock(FileReference::class, ['getOriginalFile']);
        $mockedCoreReference->expects($this->once())->method('getOriginalFile')->willReturn($mockedFile);

        $mockedFileReference = $this->createPartialMock(ExtbaseFileReference::class, ['getOriginalResource']);
        $mockedFileReference->expects($this->once())->method('getOriginalResource')->willReturn($mockedCoreReference);


        $mockedOldFileReference = $this->createPartialMock(ExtbaseFileReference::class, ['getUid']);
        $mockedOldFileReference->expects($this->once())->method('getUid')->willReturn(33);


        $entity = new \Pixelant\PxaProductManager\Domain\Model\Category();
        $entity->_setProperty('uid', 111);
        $entity->_setProperty('image', $mockedOldFileReference);


        $prophesizedRepository = $this->prophesize(FileReferenceRepository::class);
        $prophesizedRepository->remove($mockedOldFileReference)->shouldBeCalled();

        $this->subject->_set('referenceRepository', $prophesizedRepository->reveal());
        $this->subject->_call('update', $entity, 'image', [$mockedFileReference]);

        $this->assertSame($entity->getImage(), $mockedFileReference);
    }

    /**
     * @test
     */
    public function updateRelationPropertyForSingleEntityWillSetProperty()
    {
        $newParentEntity = new Category();
        $newParentEntity->_setProperty('uid', 222);

        $parent = new Category();
        $parent->_setProperty('uid', 333);
        $entity = new Category();
        $entity->_setProperty('uid', 111);
        $entity->_setProperty('parent', $parent);

        $this->subject->_call('update', $entity, 'parent', [$newParentEntity]);

        $this->assertSame($entity->getParent(), $newParentEntity);
    }

    /**
     * @test
     */
    public function updateRelationPropertyForSingleEntityWillSetPropertyWhenOriginalValueIsNull()
    {
        $newParentEntity = new Category();
        $newParentEntity->_setProperty('uid', 5433);

        $entity = new Category();
        $entity->_setProperty('uid', 1);
        $entity->_setProperty('parent', null);

        $this->subject->_call('update', $entity, 'parent', [$newParentEntity]);

        $this->assertSame($entity->getParent(), $newParentEntity);
    }

    /**
     * @test
     */
    public function doesStorageDiffReturnTrueIfStorageIsDifferentWithSameCount()
    {
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 22);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 33);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);

        $relatedProduct3 = new Product();
        $relatedProduct3->_setProperty('uid', 121);


        $import = [
            $relatedProduct2,
            $relatedProduct3
        ];

        $this->assertTrue($this->subject->_call('doesStorageDiff', $objectStorage, $import));
    }

    /**
     * @test
     */
    public function doesStorageDiffReturnTrueIfStorageIsDifferentWithDifferentCount()
    {
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 22);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 33);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);


        $import = [
            $relatedProduct2
        ];

        $this->assertTrue($this->subject->_call('doesStorageDiff', $objectStorage, $import));
    }

    /**
     * @test
     */
    public function doesStorageDiffReturnTrueIfStorageIsSameButDifferentOrder()
    {
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 22);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 33);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);


        $import = [
            $relatedProduct2,
            $relatedProduct1
        ];

        $this->assertTrue($this->subject->_call('doesStorageDiff', $objectStorage, $import));
    }

    /**
     * @test
     */
    public function doesStorageDiffReturnFalseIfStorageIsSame()
    {
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 22);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 33);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);


        $import = [
            $relatedProduct1,
            $relatedProduct2,
        ];

        $this->assertFalse($this->subject->_call('doesStorageDiff', $objectStorage, $import));
    }
}
