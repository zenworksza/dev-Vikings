<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_homepage_renders_with_hero_and_portal_links(): void
    {
        config(['site.portal_url' => 'https://portal.example']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Bring the hearth to your harbor.')
            ->assertSee('https://portal.example/register', false)
            ->assertSee('https://portal.example/login', false)
            ->assertSee('Make a booking')
            ->assertSee('href="'.route('locations.index').'" class="btn btn-sm">Make a booking', false);
    }
}
