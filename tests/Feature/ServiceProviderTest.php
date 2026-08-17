<?php

namespace SVR\LaravelCore\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;
use SVR\LaravelCore\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_default_configuration_and_blueprint_macros(): void
    {
        $this->assertSame('public_id', config('svr-core.public_id.column'));
        $this->assertTrue(Blueprint::hasMacro('publicId'));
        $this->assertTrue(Blueprint::hasMacro('auditFields'));
    }
}
