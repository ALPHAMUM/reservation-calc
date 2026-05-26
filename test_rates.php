<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\RateCalculatorService;
use App\Services\SettingsService;

$settingsService = app(SettingsService::class);
$calculator = new RateCalculatorService($settingsService);

function test($label, $res, $paxIndex, $roomType, $occupantIndex = null) {
    global $calculator;
    $result = $calculator->calculatePassengerRates($res, $paxIndex, $roomType, 1, $occupantIndex);
    echo "Test: $label\n";
    echo "  Acc: " . $result['acc'] . "\n";
    echo "--------------------------\n";
}

$date_wd = '2026-05-11'; // Monday
$date_we = '2026-05-08'; // Friday

// 1. Member Villa WD - Pax 0
test("Member Villa WD - Pax 0", [
    'gstType' => 'Member',
    'rate' => [['date' => $date_wd, 'val' => 100]] // val doesn't matter for logic, just presence of date
], 0, 'BALI'); // Expected: 10000

// 2. Member Villa WD - Pax 1
test("Member Villa WD - Pax 1", [
    'gstType' => 'Member',
    'rate' => [['date' => $date_wd, 'val' => 100]]
], 1, 'BALI'); // Expected: 0

// 3. Member Villa WE - Pax 0
test("Member Villa WE - Pax 0", [
    'gstType' => 'Member',
    'rate' => [['date' => $date_we, 'val' => 100]]
], 0, 'BALI'); // Expected: 14000

// 4. Guest Suite WD - Pax 0
test("Guest Suite WD - Pax 0", [
    'gstType' => 'Guest',
    'rate' => [['date' => $date_wd, 'val' => 100]]
], 0, 'RGNCY'); // Expected: 32500

// 5. Guest Suite WD - Pax 8 (Extra)
test("Guest Suite WD - Pax 8 (Extra)", [
    'gstType' => 'Guest',
    'rate' => [['date' => $date_wd, 'val' => 100]]
], 8, 'RGNCY'); // Expected: 3700

// 5b. Member Villa WD - Pax 4 (5th occupant, API extra rate)
test("Member Villa WD - Pax 4 (5th)", [
    'gstType' => 'Member',
    'rate' => [['date' => $date_wd, 'val' => 13700]]
], 4, 'BALI'); // Expected: 3700

// 5d. Member Villa FVN code on 5th — must be 3700, not 1 FVN
test("Member Villa WD - Pax 4 (5th, API 0.01)", [
    'gstType' => 'Member',
    'rate_metadata' => 'KEY-VILLA',
    'rate' => [['date' => $date_wd, 'val' => 0.01]]
], 3, 'BALI', 4); // billable index 3, occupant row 4 → extra → 3700

// 5c. Guest Suite WE - Pax 8 (9th, API weekend extra)
test("Guest Suite WE - Pax 8 (9th)", [
    'gstType' => 'Guest',
    'rate' => [['date' => $date_we, 'val' => 49200]]
], 8, 'RGNCY'); // Expected: 3700

// 6. Senior Citizen Guest Villa WD - Pax 0
test("Senior Citizen Guest Villa WD - Pax 0", [
    'gstType' => 'Senior Citizen Guest',
    'rate' => [['date' => $date_wd, 'val' => 100]]
], 0, 'BALI'); // Expected: 22500 / 1.12 = 20089.28
