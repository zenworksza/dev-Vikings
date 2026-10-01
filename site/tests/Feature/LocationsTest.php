<?php

namespace Tests\Feature;

use App\Services\PortalClient;
use App\Support\HoursSummary;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.portal_api_url' => 'http://portal.test',
            'site.portal_api_token' => 'secret-token',
        ]);
        Cache::flush();
    }

    private function location(array $overrides = []): array
    {
        $days = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $name) {
            $open = $i < 4;
            $days[] = ['day' => $i + 1, 'name' => $name, 'closed' => ! $open,
                'opens_at' => $open ? '09:00' : null, 'closes_at' => $open ? '21:00' : null];
        }

        return array_replace_recursive([
            'slug' => 'vikings-malmesbury-malmesbury',
            'name' => 'Vikings Malmesbury',
            'description' => "Long tables.\nBig fire.",
            'address' => ['full' => 'Shop 18, Malmesbury, Western Cape'],
            'phone' => '064 519 2187',
            'seating' => ['tables' => 12, 'seats' => 48],
            'hours' => $days,
            'special_days' => [['date' => '2026-12-25', 'label' => 'Christmas', 'closed' => true, 'opens_at' => null, 'closes_at' => null]],
        ], $overrides);
    }

    public function test_index_lists_locations_and_calls_the_portal_with_the_token(): void
    {
        Http::fake(['portal.test/*' => Http::response(['data' => [$this->location()]])]);

        $this->get('/locations')
            ->assertOk()
            ->assertSee('Vikings Malmesbury')
            ->assertSee('Seats up to 48 guests')
            ->assertSee('/locations/vikings-malmesbury-malmesbury', false);

        Http::assertSent(fn (Request $r) => $r->url() === 'http://portal.test/api/v1/locations'
            && $r->hasHeader('Authorization', 'Bearer secret-token'));
    }

    public function test_index_with_no_locations_says_more_are_coming(): void
    {
        Http::fake(['portal.test/*' => Http::response(['data' => []])]);

        $this->get('/locations')->assertOk()->assertSee('opening new locations soon');
    }

    public function test_show_renders_grouped_hours_special_days_and_escapes_output(): void
    {
        Http::fake(['portal.test/*' => Http::response(['data' => $this->location(['name' => 'Vikings <script>x</script>'])])]);

        $this->get('/locations/vikings-malmesbury-malmesbury')
            ->assertOk()
            ->assertSee('Mon – Thu')
            ->assertSee('09:00 – 21:00')
            ->assertSee('Fri – Sun')
            ->assertSee('Closed')
            ->assertSee('25-12-2026')
            ->assertSee('Christmas')
            ->assertSee('12 tables')
            ->assertDontSee('<script>x</script>', false);
    }

    public function test_show_is_404_when_the_portal_says_so(): void
    {
        Http::fake(['portal.test/*' => Http::response(['message' => 'Not found'], 404)]);

        $this->get('/locations/nope')->assertNotFound();
    }

    public function test_responses_are_cached(): void
    {
        Http::fake(['portal.test/*' => Http::response(['data' => [$this->location()]])]);

        $this->get('/locations')->assertOk();
        $this->get('/locations')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_when_the_portal_is_down_the_page_degrades_gracefully(): void
    {
        Http::fake(['portal.test/*' => Http::response('boom', 500)]);

        $this->get('/locations')->assertOk()->assertSee('temporarily unavailable');
        $this->get('/locations/anything')->assertOk()->assertSee('temporarily unavailable');
    }

    public function test_the_last_good_copy_is_served_when_the_portal_fails(): void
    {
        Http::fake(['portal.test/*' => Http::response(['data' => [$this->location()]])]);
        $this->get('/locations')->assertSee('Vikings Malmesbury');

        // Cache expires, portal now failing.
        Cache::forget('portal.locations');
        Http::fake(['portal.test/*' => Http::response('boom', 500)]);

        $this->get('/locations')->assertOk()->assertSee('Vikings Malmesbury');
    }

    public function test_a_wrong_token_is_treated_as_unavailable_not_a_crash(): void
    {
        Http::fake(['portal.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $this->get('/locations')->assertOk()->assertSee('temporarily unavailable');
        $this->assertInstanceOf(PortalClient::class, app(PortalClient::class));
    }

    public function test_hours_summary_merges_consecutive_identical_days(): void
    {
        $days = collect(['Monday', 'Tuesday', 'Wednesday'])->map(fn ($n) => ['name' => $n, 'closed' => false, 'opens_at' => '10:00', 'closes_at' => '20:00'])
            ->push(['name' => 'Thursday', 'closed' => true, 'opens_at' => null, 'closes_at' => null])->all();

        $this->assertSame([
            ['days' => 'Mon – Wed', 'hours' => '10:00 – 20:00'],
            ['days' => 'Thursday', 'hours' => 'Closed'],
        ], HoursSummary::group($days));
    }
}
