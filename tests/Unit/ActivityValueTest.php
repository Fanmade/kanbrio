<?php

use App\Models\Activity;

it('encodes a non-empty payload as JSON and an empty or absent one as null', function () {
    expect(Activity::encodeValue(['Ada', 'Grace']))->toBe('["Ada","Grace"]')
        ->and(Activity::encodeValue(['direction' => 'blocked_by', 'reference' => 'KAN-3']))
        ->toBe('{"direction":"blocked_by","reference":"KAN-3"}')
        ->and(Activity::encodeValue([]))->toBeNull()
        ->and(Activity::encodeValue(null))->toBeNull();
});

it('decodes a stored payload, treating null, blank or non-JSON as an empty array', function () {
    expect(Activity::decodeValue('["Ada","Grace"]'))->toBe(['Ada', 'Grace'])
        ->and(Activity::decodeValue('{"reason":"WontFix","message":null}'))
        ->toBe(['reason' => 'WontFix', 'message' => null])
        ->and(Activity::decodeValue(null))->toBe([])
        ->and(Activity::decodeValue(''))->toBe([])
        ->and(Activity::decodeValue('not json'))->toBe([]);
});

it('round-trips a structured payload through encode and decode', function () {
    $payload = ['direction' => 'blocked_by', 'reference' => 'KAN-3'];

    expect(Activity::decodeValue(Activity::encodeValue($payload)))->toBe($payload);
});
