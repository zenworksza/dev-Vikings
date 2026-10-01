<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\BookingCancelledForStaff;
use App\Mail\BookingDecided;
use App\Mail\BookingReceived;
use App\Mail\BookingRequestedForStaff;
use App\Mail\StaffLinks;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'vikings-malmesbury-malmesbury';

    private const MONDAY = '2026-10-12';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-01 09:00:00'); // a Thursday
        config(['site.portal_api_url' => 'http://portal.test', 'site.portal_api_token' => 't']);
        Cache::flush();
        Mail::fake();
        $this->portal();
    }

    private function portal(array $overrides = []): void
    {
        $days = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $name) {
            $open = $i < 4; // Mon–Thu 09:00–21:00, otherwise closed
            $days[] = ['day' => ($i + 1) % 7, 'name' => $name, 'closed' => ! $open,
                'opens_at' => $open ? '09:00' : null, 'closes_at' => $open ? '21:00' : null];
        }

        $location = array_replace_recursive([
            'slug' => self::SLUG, 'name' => 'Vikings Malmesbury', 'description' => null,
            'address' => ['full' => 'Shop 18'], 'phone' => '064 519 2187',
            'booking_email' => 'kitchen@example.com',
            'seating' => ['tables' => 12, 'seats' => 48],
            'hours' => $days,
            'special_days' => [['date' => '2026-10-13', 'label' => 'Closed for event', 'closed' => true, 'opens_at' => null, 'closes_at' => null]],
        ], $overrides);

        $this->fakePortal(Http::response(['data' => $location]));
    }

    /** Http::fake keeps the first stub registered, so start from a clean factory each time. */
    private function fakePortal($response): void
    {
        Http::swap(new Factory);
        Http::fake(['portal.test/*' => $response]);
        Cache::flush();
    }

    private function existing(int $party, string $time, BookingStatus $status = BookingStatus::Confirmed): Booking
    {
        $start = Carbon::parse(self::MONDAY.' '.$time);

        return Booking::create([
            'location_slug' => self::SLUG, 'location_name' => 'Vikings Malmesbury', 'location_email' => 'kitchen@example.com',
            'customer_name' => 'Other', 'customer_email' => 'o@example.com', 'customer_phone' => '0820000000',
            'party_size' => $party, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(90), 'status' => $status,
        ]);
    }

    private function form(array $overrides = []): array
    {
        return [
            'party_size' => 4, 'date' => self::MONDAY, 'time' => '18:00',
            'name' => 'Sigrid Olsen', 'email' => 'sigrid@example.com', 'phone' => '0821234567', 'notes' => 'Window table',
            ...$overrides,
        ];
    }

    private function bookUrl(): string
    {
        return '/locations/'.self::SLUG.'/book';
    }

    public function test_location_page_has_a_book_a_table_link(): void
    {
        $this->get('/locations/'.self::SLUG)->assertOk()->assertSee('Book a table')->assertSee($this->bookUrl(), false);
    }

    public function test_dates_are_day_month_year_and_chosen_from_a_dropdown_not_a_browser_date_input(): void
    {
        $this->get($this->bookUrl().'?party=2&date='.self::MONDAY)->assertOk()
            ->assertDontSee('type="date"', false)
            ->assertSee('Thu 01-10-2026 (today)')
            ->assertSee('Fri 02-10-2026 (tomorrow)')
            ->assertSee('<option value="2026-10-12" selected>Mon 12-10-2026</option>', false)
            ->assertSee('Available times — Monday 12-10-2026')
            ->assertDontSee('October');
    }

    public function test_the_booking_window_dropdown_covers_exactly_the_window(): void
    {
        $html = $this->get($this->bookUrl())->assertOk()->getContent();

        $this->assertSame(61, substr_count($html, '<option value="20'));
        $this->assertStringContainsString('value="2026-11-30"', $html); // today + 60
        $this->assertStringNotContainsString('value="2026-12-01"', $html);
    }

    public function test_booking_pages_and_emails_use_day_month_year(): void
    {
        $this->post($this->bookUrl(), $this->form());
        $booking = Booking::firstOrFail();

        $this->get(route('bookings.show', $booking))->assertSee('Monday 12-10-2026, 18:00');
        Mail::assertSent(BookingReceived::class, fn ($m) => str_contains($m->render(), 'Monday 12-10-2026, 18:00'));
        Mail::assertSent(BookingRequestedForStaff::class, fn ($m) => str_contains($m->render(), 'Monday 12-10-2026, 18:00')
            && str_contains($m->envelope()->subject, 'Mon 12-10-2026 at 18:00'));
    }

    public function test_customers_are_told_a_booking_is_not_confirmed_until_the_restaurant_replies(): void
    {
        $notice = 'Booking NOT confirmed unless you get a reply from us. Please verify with us.';

        // On the form, before any date is chosen and again above the submit button.
        $this->get($this->bookUrl())->assertSee($notice);
        $this->assertSame(2, substr_count($this->get($this->bookUrl().'?party=2&date='.self::MONDAY)->getContent(), $notice));

        $this->post($this->bookUrl(), $this->form());
        $booking = Booking::firstOrFail();

        $this->get(route('bookings.show', $booking))->assertSee($notice);
        Mail::assertSent(BookingReceived::class, fn ($m) => str_contains($m->render(), $notice));

        // Once confirmed, the warning goes away.
        $booking->update(['status' => BookingStatus::Confirmed]);
        $this->get(route('bookings.show', $booking))->assertDontSee($notice);
    }

    public function test_the_form_shows_times_within_opening_hours_that_finish_by_closing(): void
    {
        $response = $this->get($this->bookUrl().'?party=4&date='.self::MONDAY)->assertOk();

        $response->assertSee('value="09:00"', false)
            ->assertSee('value="19:30"', false)   // 19:30 + 90 min = 21:00, closing time
            ->assertDontSee('value="20:00"', false)
            ->assertDontSee('value="08:30"', false);
    }

    public function test_closed_days_and_special_day_closures_offer_no_times(): void
    {
        $this->get($this->bookUrl().'?party=2&date=2026-10-17')->assertSee('no tables are available'); // Saturday
        $this->get($this->bookUrl().'?party=2&date=2026-10-13')->assertSee('no tables are available'); // special closure
    }

    public function test_pending_and_confirmed_bookings_hold_seats_and_overlap_counts(): void
    {
        $this->existing(46, '17:00'); // runs to 18:30, 2 seats left across 17:00-18:30

        $this->get($this->bookUrl().'?party=4&date='.self::MONDAY)
            ->assertDontSee('value="17:00"', false)
            ->assertDontSee('value="18:00"', false)   // overlaps the 17:00 sitting
            ->assertSee('value="18:30"', false);       // starts when that sitting ends

        $this->get($this->bookUrl().'?party=2&date='.self::MONDAY)->assertSee('value="18:00"', false);
    }

    public function test_declined_and_cancelled_bookings_free_their_seats(): void
    {
        $this->existing(48, '18:00', BookingStatus::Declined);
        $this->existing(48, '18:00', BookingStatus::Cancelled);

        $this->get($this->bookUrl().'?party=4&date='.self::MONDAY)->assertSee('value="18:00"', false);
    }

    public function test_past_and_too_distant_dates_and_junk_are_ignored(): void
    {
        $this->get($this->bookUrl().'?party=2&date=2026-09-01')->assertOk()->assertDontSee('Available times');
        $this->get($this->bookUrl().'?party=2&date=2027-06-01')->assertOk()->assertDontSee('Available times');
        $this->get($this->bookUrl().'?party=abc&date=nonsense')->assertOk();
    }

    public function test_todays_times_respect_the_minimum_lead_time(): void
    {
        Carbon::setTestNow('2026-10-12 17:20:00'); // Monday; earliest bookable is 18:20 -> first slot 18:30

        $this->get($this->bookUrl().'?party=2&date='.self::MONDAY)
            ->assertDontSee('value="18:00"', false)
            ->assertSee('value="18:30"', false);
    }

    public function test_a_booking_request_is_stored_pending_and_both_sides_are_emailed(): void
    {
        $response = $this->post($this->bookUrl(), $this->form());

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(4, $booking->party_size);
        $this->assertSame('2026-10-12 18:00', $booking->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-10-12 19:30', $booking->ends_at->format('Y-m-d H:i'));
        $this->assertSame(40, strlen($booking->token));
        $this->assertSame('kitchen@example.com', $booking->location_email);

        Mail::assertSent(BookingRequestedForStaff::class, fn ($m) => $m->hasTo('kitchen@example.com')
            && $m->hasReplyTo('sigrid@example.com')
            && str_contains($m->render(), '/staff/bookings/'.$booking->id.'?'));
        Mail::assertSent(BookingReceived::class, fn ($m) => $m->hasTo('sigrid@example.com') && str_contains($m->render(), $booking->token));

        $this->get(route('bookings.show', $booking))->assertOk()->assertSee('Waiting for the restaurant');
    }

    public function test_a_time_that_was_not_offered_is_refused(): void
    {
        foreach ([['time' => '20:00'], ['time' => '18:15'], ['date' => '2026-10-17'], ['party_size' => 13]] as $bad) {
            $this->post($this->bookUrl(), $this->form($bad))->assertSessionHasErrors();
        }

        $this->assertSame(0, Booking::count());
        Mail::assertNothingSent();
    }

    public function test_the_last_seats_cannot_be_taken_twice(): void
    {
        $this->post($this->bookUrl(), $this->form(['party_size' => 12, 'time' => '18:00']))->assertSessionHasNoErrors();
        $this->existing(34, '18:00'); // 46 of 48 held

        $this->post($this->bookUrl(), $this->form(['party_size' => 4, 'time' => '18:00']))->assertSessionHasErrors('time');

        $this->assertSame(2, Booking::count());
    }

    public function test_bots_that_fill_the_honeypot_are_rejected(): void
    {
        $this->post($this->bookUrl(), $this->form(['website' => 'http://spam.example']))->assertSessionHasErrors('website');

        $this->assertSame(0, Booking::count());
    }

    public function test_names_cannot_inject_email_headers(): void
    {
        $this->post($this->bookUrl(), $this->form(['name' => "Eve\r\nBcc: victim@example.com"]))->assertSessionHasErrors('name');

        $this->assertSame(0, Booking::count());
    }

    public function test_a_failing_mailer_does_not_lose_the_booking(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->post($this->bookUrl(), $this->form())->assertRedirect();

        $this->assertSame(1, Booking::count());
    }

    public function test_a_location_without_seating_or_inbox_cannot_be_booked(): void
    {
        $this->portal(['seating' => ['seats' => null, 'tables' => null]]);
        $this->get($this->bookUrl())->assertOk()->assertSee('not available');
        $this->post($this->bookUrl(), $this->form())->assertNotFound();

        $this->portal(['booking_email' => null]);
        $this->get($this->bookUrl())->assertOk()->assertSee('not available');
    }

    public function test_unknown_locations_are_404(): void
    {
        $this->fakePortal(Http::response(['message' => 'no'], 404));

        $this->get('/locations/nope/book')->assertNotFound();
    }

    public function test_the_customer_can_cancel_and_the_restaurant_is_told(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Pending);

        $this->post(route('bookings.cancel', $booking))->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        Mail::assertSent(BookingCancelledForStaff::class, fn ($m) => $m->hasTo('kitchen@example.com'));

        // Already cancelled: nothing more is sent.
        $this->post(route('bookings.cancel', $booking));
        Mail::assertSent(BookingCancelledForStaff::class, 1);
    }

    public function test_a_past_booking_cannot_be_cancelled(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Confirmed);
        Carbon::setTestNow('2026-10-13 12:00:00');

        $this->post(route('bookings.cancel', $booking));

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_booking_pages_cannot_be_guessed(): void
    {
        $this->existing(4, '18:00');

        $this->get('/bookings/1')->assertNotFound();
        $this->get('/bookings/'.str_repeat('a', 40))->assertNotFound();
    }

    public function test_staff_links_must_be_signed(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Pending);

        $this->get('/staff/bookings/'.$booking->id)->assertForbidden();
        $this->post('/staff/bookings/'.$booking->id.'/confirm')->assertForbidden();
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_staff_links_expire_the_day_after_the_booking(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Pending);
        $url = StaffLinks::review($booking);

        $this->get($url)->assertOk()->assertSee('Confirm booking');

        Carbon::setTestNow('2026-10-14 00:00:00');
        $this->get($url)->assertForbidden();
    }

    public function test_staff_can_confirm_and_the_customer_is_told(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Pending);

        $this->post(StaffLinks::signed('staff.bookings.confirm', $booking))->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->decided_at);
        Mail::assertSent(BookingDecided::class, fn ($m) => $m->hasTo('o@example.com') && str_contains($m->render(), 'confirmed'));
    }

    public function test_staff_can_decline_with_a_reason_that_reaches_the_customer(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Pending);

        $this->post(StaffLinks::signed('staff.bookings.decline', $booking), ['reason' => 'Private function'])->assertRedirect();

        $this->assertSame(BookingStatus::Declined, $booking->fresh()->status);
        Mail::assertSent(BookingDecided::class, fn ($m) => str_contains($m->render(), 'Private function'));
        $this->get(route('bookings.show', $booking))->assertSee('Private function');
    }

    public function test_a_decided_booking_cannot_be_flipped_by_replaying_a_link(): void
    {
        $booking = $this->existing(4, '18:00', BookingStatus::Pending);
        $confirm = StaffLinks::signed('staff.bookings.confirm', $booking);
        $decline = StaffLinks::signed('staff.bookings.decline', $booking);

        $this->post($confirm);
        $this->post($decline);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        Mail::assertSent(BookingDecided::class, 1);
    }

    public function test_a_signed_link_only_works_for_its_own_booking(): void
    {
        $mine = $this->existing(4, '18:00', BookingStatus::Pending);
        $other = $this->existing(2, '12:00', BookingStatus::Pending);

        $url = str_replace('/staff/bookings/'.$mine->id.'/', '/staff/bookings/'.$other->id.'/', StaffLinks::signed('staff.bookings.confirm', $mine));
        $this->post($url)->assertForbidden();

        $this->assertSame(BookingStatus::Pending, $other->fresh()->status);
    }
}
