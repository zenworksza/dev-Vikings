<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['site.portal_api_url' => 'http://portal.test', 'site.portal_api_token' => 't', 'site.portal_url' => 'https://portal.example']);
        Cache::flush();

        $hours = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $name) {
            $hours[] = ['day' => ($i + 1) % 7, 'name' => $name, 'closed' => false, 'opens_at' => '09:00', 'closes_at' => '21:00'];
        }

        $location = [
            'slug' => 'vikings-malmesbury', 'name' => 'Vikings Malmesbury', 'description' => null,
            'address' => ['full' => 'Shop 18, Malmesbury'], 'phone' => '064 519 2187', 'booking_email' => 'kitchen@secret.example',
            'seating' => ['tables' => 12, 'seats' => 48], 'hours' => $hours, 'special_days' => [],
        ];

        Http::fake(['portal.test/api/v1/locations/*' => Http::response(['data' => $location]), 'portal.test/*' => Http::response(['data' => [$location]])]);
    }

    /** The page's main menu as label => whether it is the current section. */
    private function menu(string $uri): array
    {
        $html = $this->get($uri)->assertOk()->getContent();

        preg_match('/<nav aria-label="Main">(.*?)<\/nav>/s', $html, $nav);
        preg_match_all('/<a href="([^"]+)" class="btn btn-sm btn-quiet"( aria-current="page")?>([^<]+)<\/a>/', $nav[1], $m, PREG_SET_ORDER);

        return collect($m)->mapWithKeys(fn ($x) => [$x[3] => $x[2] !== ''])->all();
    }

    public function test_there_is_a_top_bar_with_only_the_franchisee_login_above_the_main_menu(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<div class="topbar">(.*?)<\/div>\s*<\/div>/s', $html, $top);

        $this->assertSame(1, substr_count($top[1], '<a '));
        $this->assertStringContainsString('https://portal.example/login', $top[1]);
        $this->assertStringContainsString('Franchisee login', $top[1]);
        $this->assertLessThan(strpos($html, '<nav aria-label="Main">'), strpos($html, 'class="topbar"'));
        $this->assertStringNotContainsString('Apply as a franchisee', $html);
    }

    public function test_the_main_menu_is_home_about_franchise_make_a_booking_contact_in_that_order(): void
    {
        $this->assertSame(['Home', 'About', 'Franchise', 'Make a booking', 'Contact'], array_keys($this->menu('/')));
    }

    public function test_the_current_section_is_marked_active_and_only_it(): void
    {
        foreach ([
            '/' => 'Home',
            '/about' => 'About',
            '/franchise' => 'Franchise',
            '/contact' => 'Contact',
            '/locations' => 'Make a booking',
            '/locations/vikings-malmesbury' => 'Make a booking',
            '/locations/vikings-malmesbury/book' => 'Make a booking',
        ] as $uri => $active) {
            $this->assertSame([$active], array_keys(array_filter($this->menu($uri))), "active item on {$uri}");
        }
    }

    public function test_make_a_booking_goes_to_the_locations_page(): void
    {
        $this->get('/')->assertSee('href="'.route('locations.index').'" class="btn btn-sm btn-quiet">Make a booking', false);
    }

    public function test_about_and_franchise_pages_have_their_content(): void
    {
        $this->get('/about')->assertSee('Vikings began as a single wood-fired hearth');
        $this->get('/franchise')->assertSee('Bring the hearth to your harbor.')->assertSee('https://portal.example/register', false);
    }

    public function test_contact_lists_locations_without_exposing_the_private_inbox(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('Vikings Malmesbury')
            ->assertSee('064 519 2187')
            ->assertSee('Shop 18, Malmesbury')
            ->assertSee('09:00 – 21:00')
            ->assertDontSee('kitchen@secret.example');
    }

    public function test_contact_degrades_gracefully_when_the_portal_is_down(): void
    {
        Http::swap(new Factory);
        Http::fake(['portal.test/*' => Http::response('boom', 500)]);
        Cache::flush();

        $this->get('/contact')->assertOk()->assertSee('temporarily unavailable');
    }
}
