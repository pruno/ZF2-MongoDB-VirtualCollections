<?php

namespace MongovcTests;

use Mongovc\Model\AbstractObject;
use Mongovc\Model\HydratingMongoCursor;
use MongovcTests\Model\Object\Bar;
use MongovcTests\Model\Object\Foo;
use MongovcTests\Model\SupportCollection\SupportCollection;
use MongovcTests\Model\VirtualCollection\BarCollection;
use MongovcTests\Model\VirtualCollection\BazCollection;
use MongovcTests\Model\VirtualCollection\FooCollection;

/**
 * @method \MongovcTests\Model\SupportCollection\SupportCollection getCollection()
 */
class SupportCollectionTest extends AbstractCollectionTest
{
    /**
     * @var FooCollection
     */
    protected $virtualCollection;

    /**
     * @var BarCollection
     */
    protected $secondaryVirtualCollection;

    /**
     * @var BazCollection
     */
    protected $tertiaryVirtualCollection;

    /**
     * @return SupportCollection
     */
    public function createCollection()
    {
        return new SupportCollection($this->getDriver());
    }

    /**
     * @return BarCollection
     */
    public function getVirtualCollection()
    {
        if ($this->virtualCollection === null) {
            return new FooCollection($this->getCollection());
        }

        return $this->virtualCollection;
    }

    /**
     * @return BarCollection
     */
    public function getSecondaryVirtualCollection()
    {
        if ($this->secondaryVirtualCollection === null) {
            return new BarCollection($this->getCollection());
        }

        return $this->secondaryVirtualCollection;
    }

    /**
     * @return BazCollection
     */
    public function getTertiaryVirtualCollection()
    {
        if ($this->tertiaryVirtualCollection === null) {
            return new BazCollection($this->getCollection());
        }

        return $this->tertiaryVirtualCollection;
    }

    public function testCreateObject()
    {
        try {
            parent::testCreateObject();
        } catch (\Exception $e) {
            $this->assertTrue(true);
            return;
        }

        $this->fail("Support collection should throw an exception while creating objects");
    }

    public function testCreateObjectFromRaw()
    {
        $this->assertTrue(
            $this->getCollection()->createObjectFromRaw(array(
                'foo' => 'bar',
                $this->getCollection()->getClassNameField() => $this->getVirtualCollection()->getAlias()
            )) instanceof AbstractObject,
            "createObjectFromRaw() should return an instance of AbstractObject"
        );
    }

    public function testFind()
    {
        $this->getVirtualCollection()->insert(array(
            'foo' => 'bar'
        ));

        $this->getSecondaryVirtualCollection()->insert(array(
            'bar' => 'foo'
        ));

        $this->getSecondaryVirtualCollection()->insert(array(
            'bar' => 'foo'
        ));

        $this->assertTrue(
            $this->getCollection()->find() instanceof HydratingMongoCursor,
            "find() should return an array of HydratingMongoCursor"
        );

        $this->assertTrue(
            $this->getCollection()->find(array('foo' => 'bar'))->current() instanceof Foo,
            "find()->current(array('foo' => 'bar')) should return an instance of Foo"
        );
    }

    public function testFindOne()
    {
        $this->getVirtualCollection()->insert(array(
            'foo' => 'bar'
        ));

        $this->getSecondaryVirtualCollection()->insert(array(
            'bar' => 'foo'
        ));

        $this->assertTrue(
            $this->getCollection()->findOne() instanceof AbstractObject,
            "findOne() should return an instance of AbstractObject"
        );

        $this->assertTrue(
            $this->getCollection()->findOne(array('bar' => 'foo')) instanceof Bar,
            "findOne(array('bar' => 'foo')) should return an instance of Bar"
        );
    }

    public function testHydratingMongoCursor()
    {
        try {
            parent::testHydratingMongoCursor();
        } catch (\Exception $e) {
            $this->assertTrue(true);
            return;
        }

        $this->fail("Support collection should throw an exception while creating objects");
    }

    // Otherwise @depends tag will fail
    public function testInsert()
    {
        parent::testInsert();
    }

    // Otherwise @depends tag will fail
    public function testFindRaw()
    {
        parent::testFindRaw();
    }

    /**
     * @depends testInsert
     */
    public function testFindById()
    {
        $idFoo = new \MongoId();
        $idBar = new \MongoId();

        $this->getVirtualCollection()->insert(array(
            '_id' => $idFoo,
            'foo' => 'bar'
        ));

        $this->getSecondaryVirtualCollection()->insert(array(
            '_id' => $idBar,
            'bar' => 'foo'
        ));

        // Test whitout primary field casting
        $this->assertTrue(
            $this->getCollection()->findById($idFoo) instanceof Foo,
            "get() should return an instance of Foo"
        );

        $this->assertTrue(
            $this->getCollection()->findById($idBar) instanceof Bar,
            "get() should return an instance of Bar"
        );

        // primary field casting is demanded to the model
        $this->assertTrue(
            $this->getCollection()->findById((string) $idFoo) instanceof Foo,
            "get() should return an instance of Foo"
        );

        $this->assertTrue(
            $this->getCollection()->findById((string) $idBar) instanceof Bar,
            "get() should return an instance of Bar"
        );
    }

    // Otherwise @depends tag will fail
    public function testCount()
    {
        parent::testCount();
    }

    /**
     * @depends testInsert
     * @depends testCount
     * @depends testFind
     */
    public function testVirtualizationGroup()
    {
        $collection = $this->getCollection();

        $this->assertTrue(
            $collection->getVirtualizationGroup() === array(),
            "virtualization group should be initialized as empty"
        );

        $this->getVirtualCollection()->insert(array(
            'foo' => 'bar'
        ));

        $this->getSecondaryVirtualCollection()->insert(array(
            'bar' => 'foo'
        ));

        $this->assertEquals(
            $collection->count(),
            2,
            "count() should return 2"
        );

        $collection->setVirtualizationGroup(array(
            $this->getVirtualCollection()->getAlias()
        ));

        $this->assertEquals(
            $collection->count(),
            1,
            "count() should return 1"
        );

        $this->assertTrue(
            $collection->find()->current() instanceof Foo,
            "find()->current() should return an instance of Foo"
        );

        // Test more than 1 element into group

        $this->getCollection()->setVirtualizationGroup();

        $this->getTertiaryVirtualCollection()->insert(array(
            'baz' => 'qux'
        ));

        $this->assertEquals(
            $collection->count(),
            3,
            "count() should return 2"
        );

        $collection->setVirtualizationGroup(array(
            $this->getVirtualCollection()->getAlias(),
            $this->getSecondaryVirtualCollection()->getAlias()
        ));

        $this->assertEquals(
            $collection->count(),
            2,
            "count() should return 2"
        );
    }
}