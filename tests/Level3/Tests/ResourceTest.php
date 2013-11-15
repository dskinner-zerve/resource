<?php
/*
 * This file is part of the Level3 package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Level3\Tests;
use Level3\Resource;
use Level3\Resource\Link;
use DateTime;

class ResourceTest extends TestCase
{
    public function setUp()
    {
        $this->resource = new Resource();
    }

    public function testSetId()
    {
        $id = 'foo';

        $this->assertSame($this->resource, $this->resource->setId($id));
        $this->assertSame($id, $this->resource->getId());
    }

    public function testSetLink()
    {
        $link = $this->createLinkMock();
        $this->resource->setLink('foo', $link);

        $links = $this->resource->getAllLinks();
        $this->assertSame($link, $links['foo']);
    }

    public function testSetLinks()
    {
        $linksExpected = [
            $this->createLinkMock(),
            $this->createLinkMock()
        ];

        $this->resource->setLinks('foo', $linksExpected);

        $links = $this->resource->getAllLinks();
        $this->assertSame($linksExpected, $links['foo']);
    }

    public function testGetLink()
    {
        $link = $this->createLinkMock();
        $this->resource->setLink('foo', $link);

        $links = $this->resource->getLinks('foo');
        $this->assertSame($link, $links);

        $this->assertNull($this->resource->getLinks('bar'));
    }

    public function testLinkResource()
    {
        $linkedResource = new Resource($this->repository);
        $linkedResource->setURI('foo');

        $this->resource->linkResource('foo', $linkedResource);

        $links = $this->resource->getLinkedResources('foo');
        $this->assertSame($linkedResource, $links);

        $this->assertNull($this->resource->getLinkedResources('bar'));
    }

    public function testLinkResources()
    {
        $linkedResourceA = new Resource($this->repository);
        $linkedResourceA->setURI('foo');

        $linkedResourceB = new Resource($this->repository);
        $linkedResourceB->setURI('bar');

        $this->resource->linkResources('foo', [
            $linkedResourceA,
            $linkedResourceB
        ]);


        $links = $this->resource->getLinkedResources('foo');
        $this->assertSame([
            $linkedResourceA,
            $linkedResourceB
        ], $links);

        $this->assertNull($this->resource->getLinkedResources('bar'));
    }

    public function testExpandLinkedResourcesOne()
    {
        $linkedResource = new Resource($this->repository);
        $linkedResource->setURI('foo');

        $this->resource->linkResource('foo', $linkedResource);
        $this->assertCount(0, $this->resource->getAllResources());

        $this->resource->expandLinkedResources('bar');
        $this->assertCount(0, $this->resource->getAllResources());

        $this->resource->expandLinkedResources('foo');
        $this->assertSame(
            $linkedResource,
            $this->resource->getResources('foo')
        );
    }

    public function testExpandLinkedResourcesMany()
    {
        $linkedResourceA = new Resource($this->repository);
        $linkedResourceA->setURI('foo');

        $linkedResourceB = new Resource($this->repository);
        $linkedResourceB->setURI('bar');

        $this->resource->linkResources('foo', [
            $linkedResourceA,
            $linkedResourceB
        ]);

        $this->assertCount(0, $this->resource->getAllResources());

        $this->resource->expandLinkedResources('foo');
        $this->assertSame(
            [$linkedResourceA, $linkedResourceB],
            $this->resource->getResources('foo')
        );
    }

    public function testExpandLinkedResourcesTreeOneLevel()
    {
        $linkedResource = new Resource($this->repository);
        $linkedResource->setURI('foo');

        $this->resource->linkResource('foo', $linkedResource);
        $this->assertCount(0, $this->resource->getAllResources());

        $this->resource->expandLinkedResourcesTree(['foo']);
        $this->assertSame(
            $linkedResource,
            $this->resource->getResources('foo')
        );
    }

    public function testExpandLinkedResourcesTreeTwoLevel()
    {
        $linkedLevel1Resource = new Resource($this->repository);
        $linkedLevel1Resource->setURI('foo');

        $linkedLevel2Resource = new Resource($this->repository);
        $linkedLevel2Resource->linkResource('bar', $linkedLevel1Resource);

        $this->resource->addResource('foo', $linkedLevel2Resource);
        $this->assertCount(0, $linkedLevel2Resource->getAllResources());

        $this->resource->expandLinkedResourcesTree(['foo', 'bar']);
        $this->assertSame(
            $linkedLevel1Resource,
            $linkedLevel2Resource->getResources('bar')
        );
    }

    public function testExpandLinkedResourcesTreeTwoLevelLinked()
    {
        $linkedLevel1Resource = new Resource($this->repository);
        $linkedLevel1Resource->setURI('foo');

        $linkedLevel2Resource = new Resource($this->repository);
        $linkedLevel2Resource->setURI('foo');
        $linkedLevel2Resource->linkResource('bar', $linkedLevel1Resource);

        $this->resource->linkResource('foo', $linkedLevel2Resource);
        $this->assertCount(0, $linkedLevel2Resource->getAllResources());

        $this->resource->expandLinkedResourcesTree(['foo', 'bar']);
        $this->assertSame(
            $linkedLevel1Resource,
            $linkedLevel2Resource->getResources('bar')
        );
    }

    public function testExpandLinkedResourcesTreeThreeLevelLinked()
    {
        $linkedLevel1Resource = new Resource($this->repository);
        $linkedLevel1Resource->setURI('foo');

        $linkedLevel2Resource = new Resource($this->repository);
        $linkedLevel2Resource->setURI('foo');
        $linkedLevel2Resource->linkResource('bar', $linkedLevel1Resource);

        $linkedLevel3Resource = new Resource($this->repository);
        $linkedLevel3Resource->setURI('foo');
        $linkedLevel3Resource->linkResource('qux', $linkedLevel2Resource);

        $this->resource->linkResource('foo', $linkedLevel3Resource);
        $this->assertCount(0, $linkedLevel2Resource->getAllResources());

        $this->resource->expandLinkedResourcesTree(['foo', 'qux', 'bar']);
        $this->assertSame(
            $linkedLevel1Resource,
            $linkedLevel2Resource->getResources('bar')
        );
    }

    public function testExpandLinkedResourcesTreeNotExists()
    {
        $this->resource->expandLinkedResourcesTree(['foo', 'bar']);
        $this->assertNull($this->resource->getResources('bar'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLinkResourceInvalid()
    {
        $linkedResource = new Resource($this->repository);

        $this->resource->linkResource('foo', $linkedResource);
        $links = $this->resource->getAllLinks();
        $this->assertInstanceOf('Level3\Resource\Link', $links['foo'][0]);
        $this->assertSame('foo', $links['foo'][0]->getHref());
    }

    public function testAddResource()
    {
        $resource = new Resource($this->repository);

        $this->resource->addResource('foo', $resource);
        $resources = $this->resource->getAllResources();
        $this->assertSame($resource, $resources['foo']);
    }

    public function testGetResource()
    {
        $resource = new Resource($this->repository);

        $this->resource->addResource('foo', $resource);
        $resources = $this->resource->getResources('foo');
        $this->assertSame($resource, $resources);

        $this->assertNull($this->resource->getResources('bar'));
    }

    public function testSetData()
    {
        $this->assertSame($this->resource, $this->resource->setData(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $this->resource->getData());
    }

    public function testAddData()
    {
        $this->assertSame($this->resource, $this->resource->addData('foo', 'bar'));
        $this->assertSame(['foo' => 'bar'], $this->resource->getData());
    }

    public function testSetURI()
    {
        $uri = 'foo';

        $this->assertSame($this->resource, $this->resource->setURI($uri));
        $this->assertSame($uri, $this->resource->getURI());
    }

    public function testSetLastUpdate()
    {
        $date = new DateTime();

        $this->assertSame($this->resource, $this->resource->setLastUpdate($date));
        $this->assertSame($date, $this->resource->getLastUpdate());
    }

    public function testSetCache()
    {
        $cache = 10;

        $this->assertSame($this->resource, $this->resource->setCache($cache));
        $this->assertSame($cache, $this->resource->getCache());
    }

    /*public function testToArray()
    {
        $link = new Link('bar');
        $this->resource->setLink('foo', $link);
        $this->resource->setURI('foo');

        $linksExpected = [
            new Link('bar/foo'),
            new Link('bar/qux')
        ];

        $this->resource->setLinks('bar', $linksExpected);

        $resource = new Resource($this->repository);
        $this->resource->addResource('foo', $resource);

        $result = $this->resource->toArray();
        $this->assertTrue(isset($result['_links']['self']));
        $this->assertSame($result['_links']['self']['href'], 'foo');

        $this->assertTrue(isset($result['_links']['foo']));
        $this->assertSame($result['_links']['foo']['href'], 'bar');

        $this->assertTrue(isset($result['_links']['bar']));
        $this->assertSame($result['_links']['bar'][0]['href'], 'bar/foo');
        $this->assertSame($result['_links']['bar'][1]['href'], 'bar/qux');

        $this->assertTrue(isset($result['_embedded']['foo']));
        $this->assertTrue(is_array($result['_embedded']['foo']));
    }*/
}
