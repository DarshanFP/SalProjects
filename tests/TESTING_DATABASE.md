# Testing Database Configuration

**CRITICAL: No database reset or rollback is permitted.**

Tests must use `DatabaseTransactions` only. The following are **forbidden**:

- `RefreshDatabase` (runs migrate:fresh — wipes all data)
- `migrate:fresh`
- `migrate:rollback`
- `migrate:refresh`
- Any Artisan command that drops tables or deletes data

---

## Required: DatabaseTransactions

All tests that touch the database MUST use:

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions;
}
```

**What it does:** Wraps each test in a transaction and rolls back that transaction after the test. Your database is left unchanged — no data is deleted.

**What it does NOT do:** It does not run migrate:fresh. It does not drop tables. It does not wipe your data.
