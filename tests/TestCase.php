<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * This repo IS the live server. If config is cached, phpunit.xml's sqlite
     * settings are ignored and tests would hit the production database, so
     * refuse to boot unless we're really on the in-memory test database.
     * (Run `php artisan config:clear` first, then `php artisan config:cache` after.)
     */
    public function createApplication()
    {
        $app = parent::createApplication();
        if ($app->configurationIsCached() || config('database.default') !== 'sqlite' || ! $app->environment('testing')) {
            fwrite(STDERR, "\nRefusing to run tests: config is cached or not on the sqlite test database. Run `php artisan config:clear` first.\n");
            exit(1);
        }

        return $app;
    }
}
