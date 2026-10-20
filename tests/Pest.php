<?php

declare(strict_types=1);

use Livewire\Features\SupportTesting\Testable;

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
 * Filament's `assertActionMounted()` asserts against the component instance,
 * whose public properties are left as dehydrated tuples by the Livewire 4.4
 * testing harness after a subsequent request. Reading the untuplified
 * snapshot data via `Testable::getData()` is reliable instead.
 */
expect()->extend('toHaveActionMounted', function (string $name) {
    /** @var Testable $testable */
    $testable = $this->value;

    $mountedActions = data_get($testable->getData(), 'mountedActions', []);

    expect($mountedActions)->not->toBeEmpty('Expected an action to be mounted.')
        ->and(data_get($mountedActions, (count($mountedActions) - 1).'.name'))->toBe($name);
});
