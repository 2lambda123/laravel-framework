<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithCollections;

class FoundationInteractsWithCollectionsTest extends TestCase
{
    use InteractsWithCollections;

    public function testSeeInCollectionFindsResults()
    {
        $collection = collect([1, 2, 3]);

        $this->assertCollectionHas($collection, 3);
    }

    public function testSeeInCollectionDoesNotFindResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $collection = collect([1, 2, 3]);

        $this->assertCollectionHas($collection, 4);
    }

    public function testDontSeeInCollectionDoesNotFindResults()
    {
        $collection = collect([1, 2, 3]);

        $this->assertCollectionMissing($collection, 4);
    }

    public function testDontSeeInCollectionFindsResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $collection = collect([1, 2, 3]);

        $this->assertCollectionMissing($collection, 3);
    }
}
