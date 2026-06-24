<?php

/**
 * KAN-209: when a passkey endpoint returns an HTML page (e.g. an expired session
 * / 419 page) instead of JSON, the @laravel/passkeys client throws a JSON parse
 * error. The login screen used to surface that raw "Unexpected token '<'… is not
 * valid JSON" string; it must now show a friendly message instead.
 */
it('shows a friendly message when a passkey endpoint returns HTML instead of JSON', function () {
    $page = visit('/login');

    // Force the passkey button to render and make verification fail exactly the
    // way a non-JSON (HTML) response does inside the @laravel/passkeys client.
    /** @noinspection JSConstantReassignment */
    $page->script(<<<'JS'
        window.Passkeys = {
            isSupported: () => true,
            verify: () => Promise.reject(new SyntaxError(`Unexpected token '<', "<!DOCTYPE "... is not valid JSON`)),
        };
        window.Alpine.$data(document.querySelector('[data-test=passkey-verify-root]')).supported = true;
    JS);

    $page->click('@passkey-verify')
        ->waitForText('Passkey sign-in failed')
        ->assertDontSee('is not valid JSON')
        ->assertNoJavascriptErrors();
});

it('still surfaces a genuine passkey error message', function () {
    $page = visit('/login');

    /** @noinspection JSConstantReassignment */
    $page->script(<<<'JS'
        window.Passkeys = {
            isSupported: () => true,
            verify: () => Promise.reject(new Error('No matching passkey was found on this device.')),
        };
        window.Alpine.$data(document.querySelector('[data-test=passkey-verify-root]')).supported = true;
    JS);

    $page->click('@passkey-verify')
        ->waitForText('No matching passkey was found on this device.')
        ->assertNoJavascriptErrors();
});
