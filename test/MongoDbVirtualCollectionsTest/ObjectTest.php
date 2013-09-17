<?php

namespace MongoDbVirtualCollectionsTest;

use MongoDbVirtualCollectionsTest\Concrete\Collection\FooCollection;
use MongoDbVirtualCollectionsTest\Concrete\Object\Foo;

/**
 * Class ObjectTest
 * @package MongoDbVirtualCollectionsTest
 */
class ObjectTest extends AbstractTestCase
{
    /**
     * @var FooCollection
     */
    protected $collection;

    public function tearDown()
    {
        if ($this->collection) {
            $this->collection->getCollection()->drop();
            $this->collection = null;
        }

        parent::tearDown();
    }

    /**
     * @return FooCollection
     */
    public function getCollection()
    {
        if ($this->collection === null) {
            $this->collection = new FooCollection($this->getServiceLocator(), $this->getDriver());
        }

        return $this->collection;
    }

    /**
     * @return Foo
     */
    public function createObject()
    {
        return new Foo($this->getServiceLocator(), $this->getCollection());
    }

    public function testCreate()
    {
        $this->createObject();
    }

    /**
     * @depends testCreate
     */
    public function testGetCollection()
    {
        $this->assertEquals(
            $this->getCollection(),
            $this->createObject()->getCollection(),
            "getCollection() should return the istance of Collection used as dependency"
        );
    }

    /**
     * @depends testCreate
     */
    public function testSave()
    {
        $object = $this->createObject();
        $object->foo1 = 'bar';

        try {
            $object->save();
        } catch (\Exception $e) {
            $this->fail("save() thrown an exception with message: {$e->getMessage()}");
        }

        $this->assertEquals(
            $this->getCollection()->count(),
            1,
            "save() failed to write to database"
        );

        $this->assertEquals(
            $object->toArray(),
            $this->getCollection()->select()->current()->toArray(),
            'selecting after save() should return the same data array'
        );
    }

    /**
     * @depends testCreate
     * @depends testSave
     */
    public function testUpsert()
    {
        $object = $this->createObject();
        $object->foo1 = 'bar';
        $object->save();

        $object->foo2 = 'bar';

        $this->assertEquals(
            $this->getCollection()->count(),
            1,
            "save() failed to update"
        );

        $this->assertEquals(
            $this->getCollection()->count(array(
                'foo1' => array('$exists' => true)
            )),
            1,
            "save() failed to update"
        );
    }

    /**
     * @depends testCreate
     */
    public function testEnhance()
    {
        $object = $this->createObject();
        $object->foo1 = 'bar';
        $object->enhance(array(
            'foo1' => 'bar2',
            'foo2' => 'bar'
        ));

        $this->assertEquals(
            $object->foo2,
            'bar',
            "enhance() failed to set value"
        );

        $this->assertEquals(
            $object->foo1,
            'bar2',
            "enhance() failed to override value"
        );

        $object->enhance(array(
            'foo1' => 'bar'
        ));

        $this->assertEquals(
            $object->foo2,
            'bar',
            "enhance() should never drop values"
        );
    }

    /**
     * @depends testCreate
     */
    public function testPopulate()
    {
        $object = $this->createObject();
        $object->foo1 = 'bar';
        $object->populate(array(
            'foo1' => 'bar2',
            'foo2' => 'bar'
        ));

        $this->assertEquals(
            $object->foo2,
            'bar',
            "populate() failed to set value"
        );

        $this->assertEquals(
            $object->foo1,
            'bar2',
            "populate() failed to override value"
        );

        $object->populate(array(
            'foo1' => 'bar'
        ));

        $this->assertEquals(
            $object->foo2,
            null,
            "populate() failed to drop values"
        );
    }
}