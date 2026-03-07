<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;
use ZipArchive;

class OfficeController extends Controller
{
    public function index()
    {
        $offices = Office::with(['staff', 'subOffices'])->get();

        return view('admin.offices.index', compact('offices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:offices,name',
            'description' => 'nullable|string',
            'sub_offices' => 'nullable|string',
        ]);

        $office = Office::create($request->only('name', 'description'));
        $this->syncSubOffices($office, $request->input('sub_offices'));

        return redirect()->back()->with('success', 'Office created successfully.');
    }

    public function update(Request $request, Office $office)
    {
        $request->validate([
            'name' => 'required|unique:offices,name,' . $office->id,
            'description' => 'nullable|string',
            'sub_offices' => 'nullable|string',
        ]);

        $office->update($request->only('name', 'description'));
        $this->syncSubOffices($office, $request->input('sub_offices'));
        return redirect()->back()->with('success', 'Office updated successfully.');
    }

    private function syncSubOffices(Office $office, ?string $subOfficesText): void
    {
        $lines = preg_split('/\r\n|\r|\n|,/', (string) $subOfficesText) ?: [];
        $names = collect($lines)
            ->map(fn($line) => trim($line))
            ->filter()
            ->unique(fn($line) => strtolower($line))
            ->values();
        if ($names->isEmpty()) {
            return;
        }

        $existing = $office->subOffices()
            ->pluck('name')
            ->map(fn($name) => strtolower(trim($name)))
            ->all();

        foreach ($names as $name) {
            if (!in_array(strtolower($name), $existing, true)) {
                $office->subOffices()->create(['name' => $name]);
            }
        }
    }

    public function destroy(Office $office)
    {
        $office->delete();
        return redirect()->back()->with('success', 'Office deleted successfully.');
    }

    public function qrCodes(Request $request)
    {
        $perPage = max(10, min(100, (int) $request->integer('per_page', 20)));
        $page = LengthAwarePaginator::resolveCurrentPage();

        $allEntries = $this->buildQrEntries();
        $total = $allEntries->count();
        $items = $allEntries->forPage($page, $perPage)->values()->map(function (array $entry) {
            $entry['qr_code'] = $this->cachedQrCodeSvg($entry['url'], 200);
            return $entry;
        });

        $qrEntries = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.offices.qrcodes', compact('qrEntries'));
    }

    // Download QR code as svg
    public function download(Office $office, Request $request)
    {
        $subOfficeId = $request->integer('sub_office_id');
        $subOffice = $subOfficeId
            ? $office->subOffices()->whereKey($subOfficeId)->firstOrFail()
            : null;

        $url = $this->buildQrJoinUrl($office->id, $subOffice?->id);
        $fileName = $subOffice
            ? ($office->name . '-' . $subOffice->name . '-QR.svg')
            : ($office->name . '-QR.svg');

        $svg = QrCode::format('svg')->size(300)->generate($url);

        return response($svg)
            ->header('Content-Type', 'image/svg')
            ->header('Content-Disposition', "attachment; filename={$fileName}");
    }

    public function downloadAllPdf()
    {
        $qrEntries = $this->buildQrEntries()->map(function (array $entry) {
            $entry['qr_code'] = $this->cachedQrCodeSvg($entry['url'], 200);
            return $entry;
        });

        $pdf = Pdf::loadView('admin.offices.qrcodes-pdf', compact('qrEntries'))
            ->setPaper('a4', 'portrait'); // Optional: set page size and orientation

        return $pdf->download('All-Office-QR-Codes.pdf');
    }

    public function downloadAllSvgZip()
    {
        $qrEntries = $this->buildQrEntries();
        $zipFileName = 'All-Office-QR-Codes.zip';
        $zip = new ZipArchive;

        $tmpFile = tempnam(sys_get_temp_dir(), 'qrzip');

        if ($zip->open($tmpFile, ZipArchive::CREATE) === TRUE) {
            foreach ($qrEntries as $entry) {
                $svg = QrCode::format('svg')->size(300)->generate($entry['url']);
                $fileName = $entry['download_name'] . '.svg';
                $zip->addFromString($fileName, $svg);
            }

            $zip->close();
        }

        return response()->download($tmpFile, $zipFileName)->deleteFileAfterSend(true);
    }

    private function buildQrEntries(): Collection
    {
        $offices = Office::with('subOffices')->orderBy('name')->get();
        $entries = collect();

        foreach ($offices as $office) {
            $entries->push($this->makeQrEntry($office, null));

            foreach ($office->subOffices as $subOffice) {
                $entries->push($this->makeQrEntry($office, $subOffice->id, $subOffice->name));
            }
        }

        return $entries;
    }

    private function makeQrEntry(Office $office, ?int $subOfficeId = null, ?string $subOfficeName = null): array
    {
        $url = $this->buildQrJoinUrl($office->id, $subOfficeId);

        $laneLabel = $subOfficeName ? "{$office->name} / {$subOfficeName}" : "{$office->name} / General";

        return [
            'office_id' => $office->id,
            'sub_office_id' => $subOfficeId,
            'office_name' => $office->name,
            'sub_office_name' => $subOfficeName,
            'lane_label' => $laneLabel,
            'url' => $url,
            'download_name' => str_replace(['/', ' '], ['-', '-'], $laneLabel . '-QR'),
        ];
    }

    private function cachedQrCodeSvg(string $url, int $size = 200): string
    {
        $cacheKey = 'office_qr_svg:' . md5($size . '|' . $url);

        try {
            return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($url, $size) {
                return QrCode::size($size)->generate($url);
            });
        } catch (Throwable $e) {
            return QrCode::size($size)->generate($url);
        }
    }

    private function buildQrJoinUrl(int $officeId, ?int $subOfficeId = null): string
    {
        $signedPath = URL::signedRoute('queue.join.form', array_filter([
            'office_id' => $officeId,
            'sub_office_id' => $subOfficeId,
        ]), null, false);

        return $this->resolveQrBaseUrl() . $signedPath;
    }

    private function resolveQrBaseUrl(): string
    {
        $configuredBaseUrl = trim((string) config('app.qr_code_base_url'));
        if ($configuredBaseUrl !== '') {
            return rtrim($configuredBaseUrl, '/');
        }

        $request = request();
        if ($request && $request->getHost() !== '') {
            if (in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true)) {
                $hostIp = gethostbyname(gethostname());
                if ($hostIp && !in_array($hostIp, ['127.0.0.1', '::1'], true)) {
                    return rtrim($request->getScheme() . '://' . $hostIp . ':' . $request->getPort(), '/');
                }
            }

            return rtrim($request->getSchemeAndHttpHost(), '/');
        }

        $appUrl = trim((string) config('app.url'));
        if ($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        return 'http://localhost:8000';
    }
}
