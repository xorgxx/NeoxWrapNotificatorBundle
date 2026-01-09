# À propos

## Tests & Qualité

Le bundle est rigoureusement testé. Pour lancer les tests :

```bash
make test
# ou
vendor/bin/phpunit
```

Analyse statique avec PHPStan :
```bash
make stan
```

Style de code (PSR-12) :
```bash
make cs-check
# pour corriger :
make cs-fix
```

## Licence

MIT.
