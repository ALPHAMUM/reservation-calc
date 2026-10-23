<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BalesinPinesRateCalculatorService;

$calculator = app(BalesinPinesRateCalculatorService::class);

echo "=== Testing Balesin Pines Rules & Calculation Engine ===\n\n";

$passCount = 0;
$failCount = 0;

function assertRule(string $name, $actual, $expected) {
    global $passCount, $failCount;
    if (abs((float)$actual - (float)$expected) < 0.05 || $actual === $expected) {
        echo "✅ PASS: {$name} -> {$actual}\n";
        $passCount++;
    } else {
        echo "❌ FAIL: {$name} -> Expected {$expected}, got {$actual}\n";
        $failCount++;
    }
}

// 1. Deluxe Room Member Rate vs Guest Rate
$deluxeMember = $calculator->getBasePinesRate('DELUXE ROOM', true, 1, true);
assertRule("Deluxe Room Member Rate", $deluxeMember, 10000);

$deluxeGuest = $calculator->getBasePinesRate('DELUXE ROOM', false, 1, true);
assertRule("Deluxe Room Guest Rate", $deluxeGuest, 14500);

// 2. Junior Suite Member Rate vs Guest Rate
$suiteMember = $calculator->getBasePinesRate('JUNIOR SUITE', true, 1, true);
assertRule("Junior Suite Member Rate", $suiteMember, 15000);

$suiteGuest = $calculator->getBasePinesRate('JUNIOR SUITE', false, 1, true);
assertRule("Junior Suite Guest Rate", $suiteGuest, 21500);

// 1b. GRATE / MRATE published rates (force override of API val)
$grateDeluxe = $calculator->calculatePassengerRates([
    'gstType'   => 'Guest',
    'list_rate' => 'GRATE',
    'rateCode'  => '99999',
    'rate'      => [['date' => '2026-08-05', 'val' => 99999]],
], 0, 'DELUXE ROOM', 2, 0);
assertRule("GRATE Deluxe Room (room total via gross_share)", $grateDeluxe['acc_dates']['2026-08-05']['breakdown']['gross_share'] ?? 0, 14500);
assertRule("GRATE Deluxe Room (per occupant share)", $grateDeluxe['acc_dates']['2026-08-05']['val'] ?? 0, 7250);

$grateSuite = $calculator->calculatePassengerRates([
    'gstType'   => 'Guest',
    'list_rate' => 'GRATE',
    'rate'      => [['date' => '2026-08-05', 'val' => 1]],
], 0, 'JUNIOR SUITE', 2, 0);
assertRule("GRATE Junior Suite (room total)", $grateSuite['acc_dates']['2026-08-05']['breakdown']['gross_share'] ?? 0, 21500);

$mrateDeluxe = $calculator->calculatePassengerRates([
    'gstType'   => 'Member',
    'list_rate' => 'MRATE',
    'rate'      => [['date' => '2026-08-05', 'val' => 1]],
], 0, 'DELUXE ROOM', 2, 0);
assertRule("MRATE Deluxe Room (room total)", $mrateDeluxe['acc_dates']['2026-08-05']['breakdown']['gross_share'] ?? 0, 10000);

$mrateSuite = $calculator->calculatePassengerRates([
    'gstType'   => 'Member',
    'list_rate' => 'MRATE',
    'rate'      => [['date' => '2026-08-05', 'val' => 1]],
], 0, 'JUNIOR SUITE', 2, 0);
assertRule("MRATE Junior Suite (room total)", $mrateSuite['acc_dates']['2026-08-05']['breakdown']['gross_share'] ?? 0, 15000);

$grateExtra = $calculator->calculatePassengerRates([
    'gstType'   => 'Guest',
    'list_rate' => 'GRATE',
    'rate'      => [['date' => '2026-08-05', 'val' => 99999]],
], 2, 'DELUXE ROOM', 3, 2);
assertRule("GRATE Extra Person", $grateExtra['acc_dates']['2026-08-05']['val'] ?? 0, 3700);

$mrateExtra = $calculator->calculatePassengerRates([
    'gstType'   => 'Member',
    'list_rate' => 'MRATE',
    'rate'      => [['date' => '2026-08-05', 'val' => 99999]],
], 2, 'JUNIOR SUITE', 3, 2);
assertRule("MRATE Extra Person", $mrateExtra['acc_dates']['2026-08-05']['val'] ?? 0, 3700);

// 1c. Composite rateCode 25500 = Junior Suite GRATE 21500 + extra 3700
$compositeBase = $calculator->calculatePassengerRates([
    'gstType'  => 'Guest',
    'rateCode' => '25500',
    'rate'     => [['date' => '2026-08-05', 'val' => 25500]],
], 0, 'JUNIOR SUITE', 2, 0);
assertRule("Composite 25500 base occupant (room total)", $compositeBase['acc_dates']['2026-08-05']['breakdown']['gross_share'] ?? 0, 21500);
assertRule("Composite 25500 base occupant (per share)", $compositeBase['acc_dates']['2026-08-05']['val'] ?? 0, 10750);

$bd25500 = $compositeBase['acc_dates']['2026-08-05']['breakdown'] ?? [];
assertRule("Composite 25500 breakdown gross (full room/night)", $bd25500['gross_share'] ?? 0, 21500);
assertRule("Composite 25500 breakdown base (full room, not per share)", round(($bd25500['base'] ?? 0) * 1.12, 0), 21500);

$twoNight = $calculator->calculatePassengerRates([
    'gstType'  => 'Guest',
    'rateCode' => '25500',
    'rate'     => [
        ['date' => '2026-08-05', 'val' => 25500],
        ['date' => '2026-08-06', 'val' => 25500],
    ],
], 0, 'JUNIOR SUITE', 2, 0);
assertRule("2-night breakdown night 1 gross", $twoNight['acc_dates']['2026-08-05']['breakdown']['gross_share'] ?? 0, 21500);
assertRule("2-night breakdown night 2 gross", $twoNight['acc_dates']['2026-08-06']['breakdown']['gross_share'] ?? 0, 21500);
assertRule("2-night per-occupant total (10750 x 2)", $twoNight['acc'] ?? 0, 21500);

$compositeExtra = $calculator->calculatePassengerRates([
    'gstType'  => 'Guest',
    'rateCode' => '25500',
    'rate'     => [['date' => '2026-08-05', 'val' => 25500]],
], 2, 'JUNIOR SUITE', 3, 2);
assertRule("Composite 25500 extra person", $compositeExtra['acc_dates']['2026-08-05']['val'] ?? 0, 3700);

// Extra person with FVN marker must still bill 3700 (not 0)
$fvnExtra = $calculator->calculatePassengerRates([
    'gstType'  => 'Guest',
    'rateCode' => '0.01',
    'rate'     => [['date' => '2026-08-05', 'val' => 0.01]],
], 2, 'JUNIOR SUITE', 3, 2);
assertRule("FVN extra person daily", $fvnExtra['acc_dates']['2026-08-05']['val'] ?? 0, 3700);
assertRule("FVN extra person total acc", $fvnExtra['acc'] ?? 0, 3700);

$fvnExtra2n = $calculator->calculatePassengerRates([
    'gstType'  => 'Guest',
    'rate'     => [
        ['date' => '2026-08-05', 'val' => 0.01],
        ['date' => '2026-08-06', 'val' => 0.01],
    ],
], 2, 'DELUXE ROOM', 3, 2);
assertRule("FVN extra person 2 nights total", $fvnExtra2n['acc'] ?? 0, 7400);

$split25500 = $calculator->splitCompositeRoomRate(25500);
assertRule("splitCompositeRoomRate(25500) base", $split25500['base'] ?? 0, 21500);
assertRule("splitCompositeRoomRate(25500) extra", $split25500['extra'] ?? 0, 3700);

// 3. Member Unit Cap (3 units max, 4th unit defaults to Guest Rate)
$unit4Rate = $calculator->getBasePinesRate('DELUXE ROOM', true, 4, true);
assertRule("4th Unit Exceeding Limit (Guest Rate)", $unit4Rate, 14500);

// 4. Senior Citizen / PWD Discount on Base Occupant Individual Share
// Suite rate 15,000 / 2 occupants = 7,500 gross share
// SC/PWD Net = (7,500 / 1.12) * 0.80 = 5,357.14
$scBaseRes = $calculator->calculatePassengerRates([
    'gstType'  => 'Senior Citizen',
    'privCard' => 'SENIOR CITIZEN',
    'rateCode' => '15000',
    'rate'     => [['date' => '2026-08-05', 'val' => 15000]]
], 0, 'JUNIOR SUITE', 2, 0);

$scBaseAcc = $scBaseRes['acc_dates']['2026-08-05']['val'] ?? 0;
assertRule("SC/PWD Base Occupant Share (Junior Suite)", $scBaseAcc, 5357.14);

// 5. Senior Citizen / PWD Discount on Extra Person (3rd occupant)
// Extra person fee 3,700
// SC/PWD Net = (3,700 / 1.12) * 0.80 = 2,642.86
$scExtraRes = $calculator->calculatePassengerRates([
    'gstType'  => 'Senior Citizen',
    'privCard' => 'SENIOR CITIZEN',
    'rateCode' => '3700',
    'rate'     => [['date' => '2026-08-05', 'val' => 3700]]
], 2, 'JUNIOR SUITE', 3, 2);

$scExtraAcc = $scExtraRes['acc_dates']['2026-08-05']['val'] ?? 0;
assertRule("SC/PWD Extra Person Fee (3rd Occupant)", $scExtraAcc, 2642.86);

// 6. FVN 0.5/1.0 Fallback to Deluxe Member Rate for Junior Suite
$fvnFallbackRes = $calculator->calculatePassengerRates([
    'gstType'  => 'Member',
    'rateCode' => '15000',
    'rate'     => [['date' => '2026-08-05', 'val' => 15000]]
], 0, 'JUNIOR SUITE', 2, 0, 0.5);

$fvnFallbackAcc = $fvnFallbackRes['acc_dates']['2026-08-05']['val'] ?? 0;
// Gross falls back to 10,000 / 2 occupants = 5,000
assertRule("0.5 FVN Suite Fallback to Member Villa Rate Share", $fvnFallbackAcc, 5000);

echo "\nSummary: {$passCount} Passed, {$failCount} Failed.\n";
