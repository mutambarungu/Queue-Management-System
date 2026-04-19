<?php

namespace App\Support;

use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentCalendarFile
{
    private const DEFAULT_DURATION_MINUTES = 30;

    public static function content(Appointment $appointment, ?string $detailsUrl = null): string
    {
        $appointment->loadMissing([
            'serviceRequest.office',
            'serviceRequest.serviceType',
            'serviceRequest.replies.user',
            'staff.user',
            'staff.office',
        ]);

        $timezone = QueueBusinessCalendar::timezone();
        $start = Carbon::parse(
            $appointment->appointment_date . ' ' . $appointment->appointment_time,
            $timezone
        );
        $end = $start->copy()->addMinutes(self::DEFAULT_DURATION_MINUTES);

        $serviceRequest = $appointment->serviceRequest;
        $officeName = $serviceRequest?->office?->name
            ?? $appointment->staff?->office?->name
            ?? 'University Office';
        $serviceName = $serviceRequest?->serviceType?->name ?? 'Service Request';
        $location = trim((string) ($appointment->location ?: $officeName));
        $staffName = $appointment->staff?->user?->name ?? $appointment->staff?->name;
        $staffNote = $serviceRequest?->replies?->first(
            fn ($reply) => in_array($reply->user->role ?? null, ['staff', 'admin'], true)
        )?->message;

        $summary = $serviceName . ' Appointment';
        if ($officeName !== '') {
            $summary .= ' - ' . $officeName;
        }

        $descriptionLines = array_values(array_filter([
            config('app.name', 'University Queue') . ' appointment details',
            'Service: ' . $serviceName,
            'Office: ' . $officeName,
            $location !== '' ? 'Location: ' . $location : null,
            'When: ' . $start->format('l, F j, Y \a\t g:i A') . ' (' . $timezone . ')',
            $serviceRequest?->request_number ? 'Request ID: ' . $serviceRequest->request_number : null,
            $staffName ? 'Staff: ' . $staffName : null,
            $staffNote ? 'Staff note: ' . trim((string) $staffNote) : null,
            $detailsUrl ? 'Open details: ' . $detailsUrl : null,
        ]));

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'university-queue.local';
        $uid = 'appointment-' . $appointment->getKey() . '@' . $host;

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//University Queue//Appointment Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . self::escapeText(config('app.name', 'University Queue') . ' Appointments'),
            'X-WR-TIMEZONE:' . $timezone,
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . self::formatUtcStamp(now('UTC')),
            'DTSTART:' . self::formatUtcStamp($start->copy()->utc()),
            'DTEND:' . self::formatUtcStamp($end->copy()->utc()),
            'SUMMARY:' . self::escapeText($summary),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            $location !== '' ? 'LOCATION:' . self::escapeText($location) : null,
            'DESCRIPTION:' . self::escapeText(implode("\n", $descriptionLines)),
            $detailsUrl ? 'URL:' . $detailsUrl : null,
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:' . self::escapeText($summary . ' starts in 1 day'),
            'TRIGGER:-P1D',
            'END:VALARM',
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:' . self::escapeText($summary . ' starts in 30 minutes'),
            'TRIGGER:-PT30M',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $content = array_map(
            fn ($line) => self::foldLine((string) $line),
            array_filter($lines, fn ($line) => $line !== null && $line !== '')
        );

        return implode("\r\n", $content) . "\r\n";
    }

    public static function filename(Appointment $appointment): string
    {
        $reference = $appointment->serviceRequest?->request_number ?: 'appointment-' . $appointment->getKey();
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $reference));
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'appointment-' . $appointment->getKey();
        }

        return $slug . '.ics';
    }

    private static function formatUtcStamp(Carbon $dateTime): string
    {
        return $dateTime->format('Ymd\THis\Z');
    }

    private static function escapeText(string $value): string
    {
        return str_replace(
            ["\\", "\r\n", "\r", "\n", ';', ','],
            ["\\\\", '\n', '\n', '\n', '\;', '\,'],
            $value
        );
    }

    private static function foldLine(string $line): string
    {
        $segments = [];
        $remaining = $line;

        while (strlen($remaining) > 75) {
            $segment = self::utf8ByteSlice($remaining, 75);
            if ($segment === '') {
                break;
            }

            $segments[] = $segment;
            $remaining = substr($remaining, strlen($segment));
        }

        $segments[] = $remaining;

        return implode("\r\n ", $segments);
    }

    private static function utf8ByteSlice(string $value, int $bytes): string
    {
        if (function_exists('mb_strcut')) {
            return mb_strcut($value, 0, $bytes, 'UTF-8');
        }

        return substr($value, 0, $bytes);
    }
}
