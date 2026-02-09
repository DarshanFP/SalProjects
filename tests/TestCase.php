<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base TestCase for all tests.
 *
 * Tests MUST use DatabaseTransactions (never RefreshDatabase).
 * migrate:fresh, migrate:rollback, and any DB-destructive commands are forbidden.
 *
 * @see tests/TESTING_DATABASE.md
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
