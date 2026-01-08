### Development Guidelines for NeoxWrapNotificatorBundle

#### Build and Configuration
- **PHP Version**: Requires PHP 8.3+.
- **Composer**: Standard Composer project. Use `composer install` to set up dependencies.
- **Makefile**: A `Makefile` is available for common tasks:
    - `make install`: Install dependencies.
    - `make cs-fix`: Run PHP-CS-Fixer to fix code style issues.
    - `make cs-check`: Check code style without fixing.
    - `make stan`: Run PHPStan analysis.
    - `make test`: Run PHPUnit tests.
    - `make ci`: Run all checks (cs-check, stan, test).

#### Testing
- **Configuration**: PHPUnit configuration is in `phpunit.xml.dist`.
- **Bootstrap**: A custom bootstrap file is located at `tests/bootstrap.php` to handle autoloader resolution.
- **Running Tests**:
    - Via Composer: `composer test`
    - Via Makefile: `make test`
    - Directly: `vendor/bin/phpunit`
- **Adding Tests**:
    - Unit tests should be placed in `tests/Unit`.
    - Integration tests should be placed in `tests/Integration`.
    - Tests should follow PSR-4 autoloading under the `Neox\WrapNotificatorBundle\Tests\` namespace.
    - Use PHPUnit attributes (e.g., `#[Test]`, `#[CoversClass]`) as seen in existing tests.

##### Example Test
To create and run a simple test:
1. Create a file `tests/Unit/SimpleExampleTest.php`:
```php
<?php
declare(strict_types=1);
namespace Neox\WrapNotificatorBundle\Tests\Unit;
use PHPUnit\Framework\TestCase;

final class SimpleExampleTest extends TestCase {
    public function testTrueIsTrue(): void {
        $this->assertTrue(true);
    }
}
```
2. Run it: `vendor/bin/phpunit tests/Unit/SimpleExampleTest.php`

#### Code Style and Static Analysis
- **Code Style**: Follows PSR-12. Use `composer cs-fix` or `make cs-fix` to automatically format code.
- **Static Analysis**: Uses PHPStan (level 5). Run it via `make stan` or `vendor/bin/phpstan analyse --configuration=phpstan.neon`.
    - *Note*: `composer analyse` expects `phpstan.neon.dist`, but the project uses `phpstan.neon`.
- **Strict Types**: All PHP files should start with `declare(strict_types=1);`.
- **Final Classes**: Use `final` for classes by default, especially for test classes and services.

#### Architecture Notes
- The bundle provides an ergonomic facade (`NotifierFacade`) over Symfony's Notifier, Mailer, Mercure, and Web Push.
- Use `DeliveryContext` to control delivery options like deferring (via Symfony Messenger), deduplication, or forcing specific transports.
