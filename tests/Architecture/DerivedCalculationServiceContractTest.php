<?php

namespace Tests\Architecture;

use App\Services\Budget\DerivedCalculationService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Contract freeze test for DerivedCalculationService.
 *
 * Ensures the public API remains stable: only the four sanctioned methods exist,
 * all return float, and none are static.
 */
class DerivedCalculationServiceContractTest extends TestCase
{
    private const ALLOWED_PUBLIC_METHODS = [
        'calculateRowTotal',
        'calculatePhaseTotal',
        'calculateProjectTotal',
        'calculateRemainingBalance',
        'calculateUtilization',
    ];

    public function test_only_allowed_public_methods_exist(): void
    {
        $reflection = new ReflectionClass(DerivedCalculationService::class);

        /** @var ReflectionMethod[] $publicMethods */
        $publicMethods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $m) => $m->getDeclaringClass()->getName() === DerivedCalculationService::class
        );

        $actualMethods = array_map(fn (ReflectionMethod $m) => $m->getName(), $publicMethods);
        $unauthorized = array_diff($actualMethods, self::ALLOWED_PUBLIC_METHODS);

        $this->assertEmpty(
            $unauthorized,
            'Unauthorized public method added to DerivedCalculationService: ' . implode(', ', $unauthorized)
        );
    }

    public function test_all_public_methods_return_float(): void
    {
        $reflection = new ReflectionClass(DerivedCalculationService::class);

        foreach (self::ALLOWED_PUBLIC_METHODS as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Method {$methodName} must exist"
            );

            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();

            $this->assertNotNull(
                $returnType,
                "Method {$methodName} must declare a return type"
            );

            $this->assertSame(
                'float',
                $returnType->getName(),
                "Method {$methodName} must return float, got {$returnType->getName()}"
            );
        }
    }

    public function test_all_public_methods_are_not_static(): void
    {
        $reflection = new ReflectionClass(DerivedCalculationService::class);

        foreach (self::ALLOWED_PUBLIC_METHODS as $methodName) {
            $method = $reflection->getMethod($methodName);

            $this->assertFalse(
                $method->isStatic(),
                "Method {$methodName} must not be static"
            );
        }
    }
}
