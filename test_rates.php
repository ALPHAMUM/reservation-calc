<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\RateCalculatorService;
use App\Services\SettingsService;

$settingsService = app(SettingsService::class);
$calculator = new RateCalculatorService($settingsService);

function test($label, $res, $paxIndex, $roomType) {
    global $calculator;
    $result = $calculator->calculatePassengerRates($res, $paxIndex, $roomType);
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

// 6. Senior Citizen Guest Villa WD - Pax 0
test("Senior Citizen Guest Villa WD - Pax 0", [
    'gstType' => 'Senior Citizen Guest',
    'rate' => [['date' => $date_wd, 'val' => 100]]
], 0, 'BALI'); // Expected: 22500 / 1.12 = 20089.28
