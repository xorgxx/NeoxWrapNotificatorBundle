# About

## Tests & Quality

The bundle is rigorously tested. To run tests:

```bash
make test
# or
vendor/bin/phpunit
```

Static analysis with PHPStan:
```bash
make stan
```

Code style (PSR-12):
```bash
make cs-check
# to fix:
make cs-fix
```

## License

MIT.
