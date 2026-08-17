<?php

namespace SVR\LaravelCore\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SVR\LaravelCore\Models\BaseModel;
use SVR\LaravelCore\Tests\TestCase;

class BaseModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table): void {
            $table->id();
            $table->publicId();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    #[Test]
    public function it_generates_and_uses_an_immutable_public_id(): void
    {
        $model = TestModel::query()->create(['is_active' => 1]);

        $this->assertNotEmpty($model->public_id);
        $this->assertSame($model->public_id, $model->getRouteKey());

        $model->public_id = 'changed-id';

        $this->expectException(RuntimeException::class);
        $model->save();
    }

    #[Test]
    public function it_casts_is_active_and_serializes_dates_consistently(): void
    {
        $model = TestModel::query()->create(['is_active' => 1]);
        $data = $model->toArray();

        $this->assertTrue($data['is_active']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $data['created_at']
        );
    }
}

class TestModel extends BaseModel
{
    protected $table = 'test_models';

    protected $guarded = [];
}
