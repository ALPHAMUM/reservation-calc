<?php

namespace App\Services\Properties;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * AbstractPropertyHandler
 *
 * Base class shared by all three property handlers.
 * Add property-specific logic by overriding the abstract methods
 * (processListData / processDetailData) in each concrete handler.
 */
abstract class AbstractPropertyHandler
{
    protected string $slug        = '';
    protected string $listApiUrl  = '';
    protected string $detailApiUrl = '';
    protected string $apiKey      = '';

    // ──────────────────────────────────────────────────────────────────
    // Main entry-point — called by ReservationController::index()
    // ──────────────────────────────────────────────────────────────────

    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        $resNoList    = $request->get('resnolist');
        $fromDate     = $request->get('fromdate');
        $toDate       = $request->get('todate');
        $statusFilter = $request->get('status_filter');
        $search       = $request->get('search');
        $perPage      = min((int) $request->get('per_page', 10), 100);

        if (!$resNoList && !$fromDate) {
            $fromDate = date('Y-m-d');
            $toDate   = date('Y-m-d');
            if (!$statusFilter) {
                $statusFilter = ['CONFIRMED'];
            }
        }

        if ($statusFilter && !is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        // When drilling into specific reservations, ignore status filter
        if (is_string($resNoList) && trim($resNoList) !== '') {
            $statusFilter = [];
        }

        $reservations      = [];
        $pagedReservations = [];
        $viewType          = 'summary';
        $dateCols          = [];
        $error             = null;

        try {
            $listData = [];

            if ($fromDate && $toDate) {
                $listData = $this->fetchList($fromDate, $toDate);
            }

            if ($resNoList) {
                $viewType     = 'detail';
                $reservations = $this->fetchDetail($resNoList, $listData);
            } else {
                $viewType     = 'list';
                $reservations = $this->processListData($listData);
            }

            // Status filter
            if (!empty($statusFilter)) {
                $reservations = array_values(array_filter($reservations, function ($res) use ($statusFilter) {
                    return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                }));
            }

            // Search filter
            if ($search) {
                $s = strtolower(trim($search));
                $reservations = array_values(array_filter($reservations, function ($res) use ($s) {
                    $no   = strtolower($res['resNo'] ?? $res['conf'] ?? '');
                    $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? '');
                    return str_contains($no, $s) || str_contains($name, $s);
                }));
            }

            $pagedReservations = $reservations;

            // Build date columns for the detail table
            if ($viewType === 'detail' && !empty($pagedReservations)) {
                $allDates = [];
                foreach ($pagedReservations as $res) {
                    foreach ($res['rate'] ?? [] as $r) {
                        if ($d = ($r['date'] ?? null)) {
                            $allDates[$d] = true;
                        }
                    }
                }
                ksort($allDates);
                $dateCols = array_keys($allDates);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = 'Connection Error: Could not reach the API server.';
        } catch (\Exception $e) {
            $error = 'System Error: ' . $e->getMessage();
        }

        $settings = app(\App\Services\SettingsService::class)->getSettings();

        return view('dashboard', [
            'reservations' => $pagedReservations ?? [],
            'resNoList'    => $resNoList,
            'fromDate'     => $fromDate,
            'toDate'       => $toDate,
            'statusFilter' => $statusFilter,
            'dateCols'     => $dateCols,
            'viewType'     => $viewType,
            'error'        => $error,
            'settings'     => $settings,
            'property'     => $this->slug,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // API helpers
    // ──────────────────────────────────────────────────────────────────

    protected function fetchList(string $fromDate, string $toDate): array
    {
        $resp = Http::withHeaders(['Authorization' => $this->apiKey])
            ->withoutVerifying()
            ->timeout(30)
            ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);

        return $resp->successful() ? ($resp->json()['msg'] ?? []) : [];
    }

    protected function fetchDetail(string $resNoList, array $listData = []): array
    {
        $ids    = array_unique(array_filter(explode(',', $resNoList)));
        $chunks = array_chunk($ids, 25);
        $out    = [];

        // Build a lookup map from resNo → list row so we can merge member meta
        $listMap = [];
        foreach ($listData as $lr) {
            $key = trim($lr['resNo'] ?? $lr['conf'] ?? '');
            if ($key !== '') {
                $listMap[$key] = $lr;
            }
        }

        foreach ($chunks as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])
                ->withoutVerifying()
                ->timeout(30)
                ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);

            if (!$resp->successful()) {
                throw new \RuntimeException('API Error (Detail): Status ' . $resp->status());
            }

            $msgs = $resp->json()['msg'] ?? [];

            // Merge list-level member meta fields into each detail record
            foreach ($msgs as &$msg) {
                $rn = trim($msg['resNo'] ?? $msg['conf'] ?? '');
                $lr = $listMap[$rn] ?? [];

                $mNo = trim((string)($msg['memNo'] ?? $msg['memberNo'] ?? $msg['member_no'] ?? $lr['memberNo'] ?? $lr['memNo'] ?? $lr['member_no'] ?? $lr['memberno'] ?? $lr['MemberNo'] ?? $lr['mem_no'] ?? ''));
                if ($mNo !== '') {
                    $msg['memberNo'] = $mNo;
                    $msg['memNo']    = $mNo;
                }

                $cName = trim((string)($msg['custName'] ?? $msg['customer_name'] ?? $msg['customer'] ?? $lr['customer'] ?? $lr['custName'] ?? $lr['guestName'] ?? $lr['gstName'] ?? ''));
                if ($cName !== '') {
                    $msg['customer_name'] = $cName;
                    $msg['customer']      = $cName;
                    $msg['custName']      = $cName;
                }

                if (empty($msg['bookDate'])) $msg['bookDate'] = $lr['bookDate'] ?? '';
                if (empty($msg['conactNo'])) $msg['conactNo'] = $lr['conactNo'] ?? $lr['contactNo'] ?? '';
            }
            unset($msg);

            $out = array_merge($out, $this->processDetailData($msgs, $listData));
        }

        return $out;
    }

    /**
     * Helper to backfill missing/invalid memberNo and custName in list records by calling
     * the detail API endpoint (getresdetforcalc) for the reservations in listData.
     */
    protected function hydrateMemberNumbersFromDetail(array $listData): array
    {
        if (empty($listData)) {
            return [];
        }

        $resIds = [];
        $invalidValues = ['n/a', 'na', 'none', 'null', 'false', 'no'];

        foreach ($listData as $res) {
            $rn = trim((string)($res['resNo'] ?? $res['conf'] ?? $res['res_no'] ?? $res['confNo'] ?? ''));
            if ($rn !== '') {
                $resIds[] = $rn;
                $resIds[] = ltrim($rn, '0');
            }
        }

        $resIds = array_unique(array_filter($resIds));
        if (empty($resIds)) {
            return $listData;
        }

        $chunks = array_chunk($resIds, 25);
        $memNoMap    = [];
        $custNameMap = [];

        foreach ($chunks as $chunk) {
            try {
                $resp = Http::withHeaders(['Authorization' => $this->apiKey])
                    ->withoutVerifying()
                    ->timeout(15)
                    ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);

                if ($resp->successful()) {
                    $msgs = $resp->json()['msg'] ?? [];
                    foreach ($msgs as $msg) {
                        $rn = trim((string)($msg['resNo'] ?? $msg['conf'] ?? $msg['res_no'] ?? $msg['confNo'] ?? ''));
                        if ($rn === '') continue;

                        $mNo = trim((string)($msg['memNo'] ?? $msg['memberNo'] ?? $msg['member_no'] ?? $msg['MemberNo'] ?? $msg['memberno'] ?? $msg['mem_no'] ?? ''));
                        $mNoLower = strtolower($mNo);

                        if ($mNo !== '' && !in_array($mNoLower, $invalidValues)) {
                            $memNoMap[$rn] = $mNo;
                            $memNoMap[ltrim($rn, '0')] = $mNo;
                        }

                        $cName = trim((string)($msg['custName'] ?? $msg['customer_name'] ?? $msg['customer'] ?? $msg['cust_name'] ?? ''));
                        if ($cName !== '') {
                            $custNameMap[$rn] = $cName;
                            $custNameMap[ltrim($rn, '0')] = $cName;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore API errors during background hydration
            }
        }

        foreach ($listData as &$res) {
            $rn = trim((string)($res['resNo'] ?? $res['conf'] ?? $res['res_no'] ?? $res['confNo'] ?? ''));
            $unpaddedRn = ltrim($rn, '0');

            $mNo = trim((string)($res['memberNo'] ?? $res['memNo'] ?? $res['member_no'] ?? $res['memberno'] ?? $res['MemberNo'] ?? $res['mem_no'] ?? ''));
            $mNoLower = strtolower($mNo);
            $isValidMNo = $mNo !== '' && !in_array($mNoLower, $invalidValues);

            // If existing memberNo is invalid/empty, update it from detail map
            if (!$isValidMNo && (isset($memNoMap[$rn]) || isset($memNoMap[$unpaddedRn]))) {
                $validMNo = $memNoMap[$rn] ?? $memNoMap[$unpaddedRn];
                $res['memberNo'] = $validMNo;
                $res['memNo']    = $validMNo;
            }

            if ((empty($res['custName']) || empty($res['customer'])) && (isset($custNameMap[$rn]) || isset($custNameMap[$unpaddedRn]))) {
                $validCName = $custNameMap[$rn] ?? $custNameMap[$unpaddedRn];
                $res['custName'] = $validCName;
                if (empty($res['customer'])) $res['customer'] = $validCName;
            }
        }
        unset($res);

        return $listData;
    }

    // ──────────────────────────────────────────────────────────────────
    // Sanity / health check
    // ──────────────────────────────────────────────────────────────────

    public function sanityCheck(): array
    {
        $start     = microtime(true);
        $up        = false;
        $msg       = '';
        $sanityUrl = config("services.{$this->configKey()}.sanity_url");

        try {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])
                ->withoutVerifying()
                ->timeout(8)
                ->get($sanityUrl);

            $up = $resp->successful();
            if ($up) {
                $json = $resp->json();
                $msg  = is_array($json)
                    ? ($json['msg'] ?? ($json['message'] ?? 'OK'))
                    : (trim($resp->body()) ?: 'OK');
            } else {
                $msg = 'HTTP ' . $resp->status();
            }
        } catch (\Illuminate\Http\Client\ConnectionException) {
            $msg = 'Connection failed';
        } catch (\Exception $e) {
            $msg = 'Error: ' . $e->getMessage();
        }

        return [
            'label'   => $this->label(),
            'up'      => $up,
            'ms'      => round((microtime(true) - $start) * 1000),
            'message' => $msg,
            'checked' => now()->format('H:i:s'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Abstract interface — implement in each property handler
    // ──────────────────────────────────────────────────────────────────

    /** Process list API rows into view-ready reservation arrays. */
    abstract protected function processListData(array $listData): array;

    /** Process detail API rows (with rate calculation) into view-ready arrays. */
    abstract protected function processDetailData(array $msgs, array $listData = []): array;

    /** Human-readable property name, e.g. "Balesin City" */
    abstract public function label(): string;

    /** services.php config key, e.g. "balesin_city" */
    abstract public function configKey(): string;
}
