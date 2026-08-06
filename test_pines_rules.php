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
