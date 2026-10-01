<?php

namespace App\Support;

/** Turns the API's seven daily rows into compact lines such as "Mon – Thu  09:00 – 21:00". */
class HoursSummary
{
    /**
     * @param  list<array{name: string, closed: bool, opens_at: ?string, closes_at: ?string}>  $days  Monday to Sunday
     * @return list<array{days: string, hours: string}>
     */
    public static function group(array $days): array
    {
        $runs = [];

        foreach ($days as $day) {
            $hours = $day['closed'] ? 'Closed' : "{$day['opens_at']} – {$day['closes_at']}";
            $last = array_key_last($runs);

            if ($last !== null && $runs[$last]['hours'] === $hours) {
                $runs[$last]['to'] = $day['name'];
            } else {
                $runs[] = ['from' => $day['name'], 'to' => $day['name'], 'hours' => $hours];
            }
        }

        return array_map(fn (array $run) => [
            'days' => $run['from'] === $run['to']
                ? $run['from']
                : substr($run['from'], 0, 3).' – '.substr($run['to'], 0, 3),
            'hours' => $run['hours'],
        ], $runs);
    }
}
