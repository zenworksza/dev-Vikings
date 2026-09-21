<?php

use App\Enums\PageStatus;
use App\Models\Page;

test('a published page is viewable at its slug', function () {
    $page = Page::create([
        'title' => 'About Us',
        'slug' => 'about-us',
        'status' => PageStatus::Published,
    ]);

    $page->sections()->create([
        'type' => 'rich-text',
        'sort_order' => 0,
        'data' => ['content' => '<p>Hello from the CMS.</p>'],
    ]);

    $this->get('/about-us')
        ->assertOk()
        ->assertSee('Hello from the CMS.', escape: false);
});

test('a draft page 404s', function () {
    Page::create([
        'title' => 'Coming Soon',
        'slug' => 'coming-soon',
        'status' => PageStatus::Draft,
    ]);

    $this->get('/coming-soon')->assertNotFound();
});

test('an unknown slug 404s', function () {
    $this->get('/does-not-exist')->assertNotFound();
});

test('the homepage falls back to the static welcome view when no home page is seeded', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Own a location. Grow the brand.');
});

test('the homepage renders the published home CMS page when one exists', function () {
    $page = Page::create([
        'title' => 'Home',
        'slug' => 'home',
        'status' => PageStatus::Published,
    ]);

    $page->sections()->create([
        'type' => 'hero',
        'sort_order' => 0,
        'data' => ['headline' => 'Custom CMS Headline'],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Custom CMS Headline');
});

test('sections render in sort order', function () {
    $page = Page::create([
        'title' => 'Ordered',
        'slug' => 'ordered',
        'status' => PageStatus::Published,
    ]);

    $page->sections()->create([
        'type' => 'call-to-action',
        'sort_order' => 1,
        'data' => ['heading' => 'Second'],
    ]);

    $page->sections()->create([
        'type' => 'call-to-action',
        'sort_order' => 0,
        'data' => ['heading' => 'First'],
    ]);

    $response = $this->get('/ordered');

    $response->assertOk();
    expect(strpos($response->getContent(), 'First'))
        ->toBeLessThan(strpos($response->getContent(), 'Second'));
});
