<?php

test('the portal root redirects to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('the login page renders', function () {
    $this->get('/login')->assertOk();
});
