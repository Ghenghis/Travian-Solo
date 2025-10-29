# Testing

- Run tests: `docker-compose exec php vendor/bin/phpunit --colors=always --testdox`.
- Header-emitting tests run with `@runInSeparateProcess`.
- Coverage optional (xdebug not enabled by default in container).
- Add integration tests around Controller flows before refactors.
- See also `UNIT-TESTING-GUIDE.md` and docs/.
