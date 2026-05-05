<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$resp = Illuminate\Support\Facades\Http::withHeaders(['Authorization' => '12A3A9C3-3D8F-4204-9737-3C4ADE94650F'])->withoutVerifying()->get('https://intimusapi.balesinkey.com/api/upastraindata/intimusapiservice/getreslist', ['fromdate' => '2024-01-01', 'todate' => '2024-01-05']);
$list = $resp->json()['msg'] ?? [];

if (!empty($list)) {
    $firstId = $list[0]['conf'] ?? $list[0]['resNo'];
    $resp2 = Illuminate\Support\Facades\Http::withHeaders(['Authorization' => '12A3A9C3-3D8F-4204-9737-3C4ADE94650F'])->withoutVerifying()->get('https://intimusapi.balesinkey.com/api/upastraindata/intimusapiservice/getresdetforcalc', ['resnolist' => $firstId]);
    $msgs = $resp2->json()['msg'] ?? [];
    if (!empty($msgs)) {
        echo "=== FIRST PASSENGER RAW DATA ===\n";
        print_r($msgs[0]);
    }
}
