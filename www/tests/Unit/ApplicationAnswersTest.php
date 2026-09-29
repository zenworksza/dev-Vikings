<?php

use App\Support\ApplicationAnswers;

test('repeater items keyed by uuid are numbered, not shown as ids', function () {
    $rows = ApplicationAnswers::rows([
        'principals' => [
            '058ddf94-dd0d-4631-9f39-e07019e74dd9' => ['surname' => 'de Beer', 'id_number' => '7802015800086'],
            '1c9f2a10-7b6e-4c7a-8d55-0a2f1e3b9c44' => ['surname' => 'Olsen'],
        ],
    ]);

    expect($rows)->toBe([
        'Person #1 › Surname' => 'de Beer',
        'Person #1 › ID number' => '7802015800086',
        'Person #2 › Surname' => 'Olsen',
    ]);
});

test('money is shown in rand and booleans as Yes/No', function () {
    $rows = ApplicationAnswers::rows(['net_worth' => 2500000, 'insolvent' => false, 'entity_type' => 'sole_proprietorship']);

    expect($rows)->toBe([
        'Net Worth' => 'R 2 500 000',
        'Ever declared insolvent' => 'No',
        'Entity Type' => 'sole_proprietorship',
    ]);
});

test('empty answers produce no rows', function () {
    expect(ApplicationAnswers::rows(null))->toBe([])
        ->and(ApplicationAnswers::rows(['a' => '', 'b' => null, 'c' => []]))->toBe([]);
});
