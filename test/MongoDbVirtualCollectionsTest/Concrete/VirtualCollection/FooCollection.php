<?php

namespace MongoDbVirtualCollectionsTest\Concrete\VirtualCollection;

use MongoDbVirtualCollections\Model\AbstractVirtualCollection;
use MongoDbVirtualCollectionsTest\Concrete\Object\Foo;

/**
 * Class FooCollection
 * @package MongoDbVirtualCollectionsTest\Concrete\VirtualCollection
 */
class FooCollection extends AbstractVirtualCollection
{
    /**
     * @return Foo
     */
    public function createObjectPrototype()
    {
        return new Foo($this->getServiceLocator(), $this);
    }

    /**
     * @return array
     */
    public function getAssetSchema()
    {
        return array(
            'foo1',
            'foo2'
        );
    }
}