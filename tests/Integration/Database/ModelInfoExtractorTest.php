<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelInfoExtractor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModelInfoExtractorTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('parent_test_models', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('model_info_extractor_test_model', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid();
            $table->string('name');
            $table->boolean('a_bool');
            $table->foreignId('parent_test_model_id')->constrained();
            $table->timestamp('nullable_date')->nullable();
            $table->timestamps();
        });
    }

    public function test_extracts_model_data()
    {
        $extractor = new ModelInfoExtractor($this->app);
        $modelInfo = $extractor->handle(ModelInfoExtractorTestModel::class);

        $this->assertEquals(ModelInfoExtractorTestModel::class, $modelInfo['class']);
        $this->assertEquals(Schema::getConnection()->getConfig()['name'], $modelInfo['database']);
        $this->assertEquals('model_info_extractor_test_model', $modelInfo['table']);
        $this->assertNull($modelInfo['policy']);
        $this->assertCount(8, $modelInfo['attributes']);

        $this->assertAttributes([
            'name' => 'id',
            'increments' => true,
            'nullable' => false,
            'default' => null,
            'unique' => true,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => null,
        ], $modelInfo['attributes'][0]);

        $this->assertAttributes([
            'name' => 'uuid',
            'increments' => false,
            'nullable' => false,
            'default' => null,
            'unique' => false,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => null,
        ], $modelInfo['attributes'][1]);

        $this->assertAttributes([
            'name' => 'name',
            'increments' => false,
            'nullable' => false,
            'default' => null,
            'unique' => false,
            'fillable' => false,
            'hidden' => false,
            'appended' => null,
            'cast' => null,
        ], $modelInfo['attributes'][2]);

        $this->assertAttributes([
            'name' => 'a_bool',
            'increments' => false,
            'nullable' => false,
            'default' => null,
            'unique' => false,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => 'bool',
        ], $modelInfo['attributes'][3]);

        $this->assertAttributes([
            'name' => 'parent_test_model_id',
            'increments' => false,
            'nullable' => false,
            'default' => null,
            'unique' => false,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => null,
        ], $modelInfo['attributes'][4]);

        $this->assertAttributes([
            'name' => 'nullable_date',
            'increments' => false,
            'nullable' => true,
            'default' => null,
            'unique' => false,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => 'datetime',
        ], $modelInfo['attributes'][5]);

        $this->assertAttributes([
            'name' => 'created_at',
            'increments' => false,
            'nullable' => true,
            'default' => null,
            'unique' => false,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => 'datetime',
        ], $modelInfo['attributes'][6]);

        $this->assertAttributes([
            'name' => 'updated_at',
            'increments' => false,
            'nullable' => true,
            'default' => null,
            'unique' => false,
            'fillable' => true,
            'hidden' => false,
            'appended' => null,
            'cast' => 'datetime',
        ], $modelInfo['attributes'][7]);

        $this->assertCount(1, $modelInfo['relations']);
        $this->assertEqualsCanonicalizing([
            'name' => 'parentModel',
            'type' => 'BelongsTo',
            'related' => "Illuminate\Tests\Integration\Database\ParentTestModel",
        ], $modelInfo['relations'][0]);

        $this->assertEmpty($modelInfo['events']);
        $this->assertCount(1, $modelInfo['observers']);
        $this->assertEquals('created', $modelInfo['observers'][0]['event']);
        $this->assertCount(1, $modelInfo['observers'][0]['observer']);
        $this->assertEquals("Illuminate\Tests\Integration\Database\ModelInfoExtractorTestModelObserver@created", $modelInfo['observers'][0]['observer'][0]);
    }

    private function assertAttributes($expectedAttributes, $actualAttributes)
    {
        foreach(['name', 'increments', 'nullable', 'unique', 'fillable', 'hidden', 'appended', 'cast'] as $key) {
            $this->assertEquals($expectedAttributes[$key], $actualAttributes[$key]);
        }
        // We ignore type because it varies from DB to DB
        $this->assertArrayHasKey('type', $actualAttributes);
        $this->assertArrayHasKey('default', $actualAttributes);
    }
}

#[ObservedBy(ModelInfoExtractorTestModelObserver::class)]
class ModelInfoExtractorTestModel extends Model
{
    use HasUuids;

    public $table = 'model_info_extractor_test_model';
    protected $guarded = ['name'];
    protected $casts = ['nullable_date' => 'datetime', 'a_bool' => 'bool'];

    public function parentModel(): BelongsTo
    {
        return $this->belongsTo(ParentTestModel::class);
    }
}

class ParentTestModel extends Model
{
    public $table = 'parent_test_models';
    public $timestamps = false;
}

class ModelInfoExtractorTestModelObserver
{
    public function created()
    {
    }
}
