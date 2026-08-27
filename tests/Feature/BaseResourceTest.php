<?php

namespace SVR\LaravelCore\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use SVR\LaravelCore\Http\Resources\BaseResource;
use SVR\LaravelCore\Models\AuditableModel;
use SVR\LaravelCore\Models\BaseModel;
use SVR\LaravelCore\Tests\TestCase;

class BaseResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('resource_users', function (Blueprint $table): void {
            $table->id();
            $table->publicId();
            $table->string('email');
            $table->timestamps();
        });

        config()->set('svr-core.audit.users_table', 'resource_users');

        Schema::create('resource_categories', function (Blueprint $table): void {
            $table->id();
            $table->publicId();
            $table->string('name');
            $table->string('tax_id');
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('resource_users');
            $table->auditFields(nullable: true);
            $table->timestamps();
        });

        Schema::create('resource_containers', function (Blueprint $table): void {
            $table->id();
            $table->publicId();
            $table->foreignId('profile_id')
                ->constrained('resource_categories');
            $table->timestamps();
        });
    }

    #[Test]
    public function it_transforms_loaded_relations_and_their_foreign_keys(): void
    {
        $user = ResourceUser::query()->create([
            'email' => 'admin@svr.com',
        ]);

        $category = ResourceCategory::query()->create([
            'name' => 'Electronics',
            'tax_id' => 'XAXX010101000',
            'reviewer_id' => $user->getKey(),
            'created_by_id' => $user->getKey(),
            'updated_by_id' => $user->getKey(),
        ]);

        $category->load('created_by', 'updated_by', 'reviewer');

        $data = (new BaseResource($category))->resolve();

        $this->assertSame($category->public_id, $data['id']);
        $this->assertArrayNotHasKey('public_id', $data);
        $this->assertSame($user->public_id, $data['created_by_id']);
        $this->assertSame($user->public_id, $data['updated_by_id']);
        $this->assertSame($user->public_id, $data['reviewer_id']);
        $this->assertSame('XAXX010101000', $data['tax_id']);
        $this->assertSame($user->public_id, $data['created_by']['id']);
        $this->assertSame($user->public_id, $data['updated_by']['id']);
        $this->assertArrayNotHasKey('public_id', $data['created_by']);
        $this->assertArrayNotHasKey('public_id', $data['updated_by']);
    }

    #[Test]
    public function it_does_not_expose_unloaded_internal_foreign_keys(): void
    {
        $user = ResourceUser::query()->create([
            'email' => 'admin@svr.com',
        ]);

        $category = ResourceCategory::query()->create([
            'name' => 'Electronics',
            'tax_id' => 'XAXX010101000',
            'reviewer_id' => $user->getKey(),
            'created_by_id' => $user->getKey(),
            'updated_by_id' => $user->getKey(),
        ]);

        $data = (new BaseResource($category))->resolve();

        $this->assertArrayNotHasKey('created_by_id', $data);
        $this->assertArrayNotHasKey('updated_by_id', $data);
        $this->assertArrayNotHasKey('reviewer_id', $data);
        $this->assertSame('XAXX010101000', $data['tax_id']);
        $this->assertArrayNotHasKey('created_by', $data);
        $this->assertArrayNotHasKey('updated_by', $data);
    }

    #[Test]
    public function it_recursively_transforms_loaded_collections(): void
    {
        $user = ResourceUser::query()->create([
            'email' => 'admin@svr.com',
        ]);

        $category = ResourceCategory::query()->create([
            'name' => 'Electronics',
            'tax_id' => 'XAXX010101000',
            'reviewer_id' => $user->getKey(),
            'created_by_id' => $user->getKey(),
            'updated_by_id' => $user->getKey(),
        ]);

        $user->load('categories');

        $data = (new BaseResource($user))->resolve();

        $this->assertSame(
            $category->public_id,
            $data['categories'][0]['id']
        );
        $this->assertArrayNotHasKey(
            'public_id',
            $data['categories'][0]
        );
        $this->assertArrayNotHasKey(
            'created_by_id',
            $data['categories'][0]
        );
        $this->assertArrayNotHasKey(
            'reviewer_id',
            $data['categories'][0]
        );
        $this->assertSame(
            'XAXX010101000',
            $data['categories'][0]['tax_id']
        );
    }

    #[Test]
    public function it_preserves_external_ids_in_a_nested_model(): void
    {
        $user = ResourceUser::query()->create([
            'email' => 'admin@svr.com',
        ]);

        $category = ResourceCategory::query()->create([
            'name' => 'Electronics',
            'tax_id' => 'XAXX010101000',
            'reviewer_id' => $user->getKey(),
            'created_by_id' => $user->getKey(),
            'updated_by_id' => $user->getKey(),
        ]);

        $container = ResourceContainer::query()->create([
            'profile_id' => $category->getKey(),
        ]);

        $container->load('profile');

        $data = (new BaseResource($container))->resolve();

        $this->assertSame(
            'XAXX010101000',
            $data['profile']['tax_id']
        );
        $this->assertSame(
            $category->public_id,
            $data['profile_id']
        );
    }
}

class ResourceUser extends BaseModel
{
    protected $table = 'resource_users';

    protected $guarded = [];

    public function categories(): HasMany
    {
        return $this->hasMany(
            ResourceCategory::class,
            'created_by_id'
        );
    }
}

class ResourceCategory extends AuditableModel
{
    protected $table = 'resource_categories';

    protected $guarded = [];

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(
            ResourceUser::class,
            'created_by_id'
        );
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(
            ResourceUser::class,
            'updated_by_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            ResourceUser::class,
            'reviewer_id'
        );
    }
}

class ResourceContainer extends BaseModel
{
    protected $table = 'resource_containers';

    protected $guarded = [];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(
            ResourceCategory::class,
            'profile_id'
        );
    }
}
