<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testSimplePaginatorReturnsRelevantContextInformation()
    {
        $p = new Paginator(['item3', 'item4', 'item5'], 2, 2);

        $this->assertEquals(2, $p->currentPage());
        $this->assertTrue($p->hasPages());
        $this->assertTrue($p->hasMorePages());
        $this->assertEquals(['item3', 'item4'], $p->items());

        $pageInfo = [
            'per_page' => 2,
            'current_page' => 2,
            'first_page_url' => '/?page=1',
            'next_page_url' => '/?page=3',
            'prev_page_url' => '/?page=1',
            'from' => 3,
            'to' => 4,
            'data' => ['item3', 'item4'],
            'path' => '/',
        ];

        $this->assertEquals($pageInfo, $p->toArray());
    }

    public function testPaginatorRemovesTrailingSlashes()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test/']);

        $this->assertSame('http://website.com/test?page=1', $p->previousPageUrl());
    }

    public function testPaginatorGeneratesUrlsWithoutTrailingSlash()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame('http://website.com/test?page=1', $p->previousPageUrl());
    }

    public function testLengthPaginatorCorrectlyGenerateUrlsWithQueryAndPageNamesWithBrackets()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, [
            'path' => 'http://website.com/test',
            'pageName' => 'items[page]',
            'query' => [
                'status' => 'open',
                'items' => ['page' => 1, 'per_page' => 15],
            ],
        ]);

        $this->assertSame(
            'http://website.com/test?status=open&items%5Bpage%5D=2&items%5Bper_page%5D=15',
            $p->url($p->currentPage())
        );
    }

    public function testItRetrievesThePaginatorOptions()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame(['path' => 'http://website.com/test'], $p->getOptions());
    }

    public function testPaginatorReturnsPath()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame('http://website.com/test', $p->path());
    }

    public function testCanTransformPaginatorItems()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 3, 1, ['path' => 'http://website.com/test']);

        $p->through(function ($item) {
            return substr($item, 4, 1);
        });

        $this->assertInstanceOf(Paginator::class, $p);
        $this->assertSame(['1', '2', '3'], $p->items());
    }
}
