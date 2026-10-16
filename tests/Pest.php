<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the full Laravel application and refresh the database
| between tests, so they run against the same schema as production.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
