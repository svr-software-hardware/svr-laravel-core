<?php

namespace SVR\LaravelCore\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SVR\LaravelCore\Support\PublicIdGenerator;
use SVR\LaravelCore\Tests\TestCase;

class PublicIdGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_an_id_using_the_configured_length_and_alphabet(): void
    {
        config()->set('svr-core.public_id.length', 24);
        config()->set('svr-core.public_id.alphabet', 'AB');

        $publicId = app(PublicIdGenerator::class)->generate();

        $this->assertSame(24, strlen($publicId));
        $this->assertMatchesRegularExpression('/^[AB]+$/', $publicId);
    }

    #[Test]
    public function an_explicit_length_overrides_the_configuration(): void
    {
        config()->set('svr-core.public_id.length', 24);

        $this->assertSame(8, strlen(app(PublicIdGenerator::class)->generate(8)));
    }
}
