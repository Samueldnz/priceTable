<?php declare(strict_types=1);

function numberToFixed(float $num, int $decimals): float {
    return (float) number_format($num, $decimals, '.', "");
}

function toFixed(float $num, int $decimals): string {
    return number_format($num, $decimals, '.', "");
}

function fe(bool $isFirstRate, float $interestRate): float {
    return ($isFirstRate) ? 1 + $interestRate : 1;
}

function calculateFinancingCoefficient(float $interestRate, int $installmentQuantity): float {

    if($interestRate > 1){
        $adjustedRate = $interestRate/100;
    }else{
        $adjustedRate = $interestRate;
    }

    return $adjustedRate / (1 - pow(1 + $adjustedRate, $installmentQuantity * -1));
}

function presentValue(float $financingCoefficient, float $interestRate, float $futurePrice, int $installments, bool $isFirstRate): float {
    $f = fe($isFirstRate, $interestRate);

    return ($futurePrice / $installments) * ($f / $financingCoefficient);
}

function calculateAppliedFactor(bool $hasDownPayment, int $numInstallments, float $financingCoefficient, float $interestRate): float {
    $f = fe($hasDownPayment, $interestRate);

    return $f / ($numInstallments * $financingCoefficient);
}

function futureValue(float $financingCoefficient, float $interestRate, float $cashPrice, int $installments, bool $hasDownPayment): float {
    $result = $cashPrice / calculateAppliedFactor($hasDownPayment, $installments, $financingCoefficient, $interestRate);

    return numberToFixed($result, 2);
}


function convertMonthlyInterestToAnnual(float $interest): float {
    $interestTemp = $interest /= 100;
    $result = (pow(1 + $interestTemp, 12) - 1) * 100;

    return numberToFixed($result, 2);
}

function getAdjustedValue(array $priceTable, int $numInstallments, int $monthsToGoBack): float {

    if ($monthsToGoBack == 0 || $monthsToGoBack >= $numInstallments || !is_array($priceTable)) {
        return 0;
    } else {
        $size = count($priceTable);

        // Ensure the array has enough elements
        if ($size > $monthsToGoBack) {
            // Cast the value to float before returning
            return (float) $priceTable[$size - $monthsToGoBack - 1][4];
        } else {
            return 0; // Handle the case where the array doesn't have enough elements
        }
    }
}


function calculateValueToReturn(float $pmt, int $numInstallments, int $monthsToGoBack): float {
    if ((int) $monthsToGoBack > (int) $numInstallments) {
        return 0;
    } else {
        return $pmt * $monthsToGoBack;
    }
}

function calculateFunctionValue(float $futurePrice, float $interestRate, float $cashPrice, bool $hasDownPayment, int $numInstallments): float {
    $a = 0;
    $b = 0;
    $c = 0;

    if ($hasDownPayment) {
        $a = pow(1 + $interestRate, $numInstallments - 2);
        $b = pow(1 + $interestRate, $numInstallments - 1);
        $c = pow(1 + $interestRate, $numInstallments);

        return ($cashPrice * $interestRate *$b) - ($futurePrice/$numInstallments) * ($c - 1);
    }else{
        $a = pow(1 + $interestRate, -$numInstallments);
        $b = pow(1 + $interestRate, -$numInstallments - 1);

        return ($cashPrice * $interestRate) - (($futurePrice/$numInstallments) * (1 - $a));
    }
}

function calculateDerivativeValue(float $futurePrice, float $interestRate, float $cashPrice, bool $hasDownPayment, int $numInstallments): float {
    $a = 0;
    $b = 0;
    $c = 0;

    if ($hasDownPayment) {
        $a = pow(1 + $interestRate, $numInstallments - 2);
        $b = pow(1 + $interestRate, $numInstallments - 1);

        return $cashPrice * ($b + ($interestRate * $a * ($numInstallments - 1))) - ($futurePrice * $b);
    } else {
        $a = pow(1 + $interestRate, -$numInstallments);
        $b = pow(1 + $interestRate, -$numInstallments - 1);

        return $cashPrice - ($futurePrice * $b);
    }
}

function getPMT(float $cashPrice, float $financingCoefficient):float{
    return $cashPrice*$financingCoefficient;
}

function calculateInterestRate(float $cashPrice, float $futurePrice, int $numInstallments, bool $hasDownPayment): float {
    $tolerance = 0.0001;
    $interestRate = 0.1; // Initial guess
    $previousInterestRate = 0.0;

    $functionValue = 0;
    $derivativeValue = 0;
    $iteration = 0;

    while (abs($previousInterestRate - $interestRate) >= $tolerance) {
        $previousInterestRate = $interestRate;
        $functionValue = calculateFunctionValue($futurePrice, $interestRate, $cashPrice, $hasDownPayment, $numInstallments);

        $derivativeValue = calculateDerivativeValue($futurePrice, $interestRate, $cashPrice, $hasDownPayment, $numInstallments);

        $interestRate = $interestRate - ($functionValue / $derivativeValue);

        $iteration++;
    }

    return $interestRate;
}

function getPriceTable(float $cashPrice, float $pmt, int $numInstallments, float $interestRate, bool $hasDownPayment):array{

    $totalInterest = 0;
    $totalAmortization = 0;
    $totalPaid = $hasDownPayment ? $pmt : 0;

    $priceTable = array(array("Month", "Payment", "Interest", "Amortization", "Outstanding Balance"));

    $interest = $interestRate;
    $amortization = 0;
    $outstandingBalance = $cashPrice;

    for ($i = 1; $i <= $numInstallments; $i++) {

        $interest = ($outstandingBalance * $interestRate);

        $amortization = ($pmt - $interest);

        $outstandingBalance -= $amortization;

        if($outstandingBalance > 0){
            $outstandingBalance = $outstandingBalance;
        }else{
            $outstandingBalance = 0;
        }
        

        array_push($priceTable, array($i, toFixed($pmt, 2), toFixed($interest, 3), toFixed($amortization, 2), toFixed($outstandingBalance, 2)));

        $totalInterest += $interest;
        $totalPaid += $pmt;
        $totalAmortization += $amortization;
    }

    $totalPaid = toFixed($totalPaid, 2);
    $totalInterest = toFixed($totalInterest, 3);
    $totalAmortization = toFixed($totalAmortization, 2);
    $outstandingBalanceStr = toFixed($outstandingBalance, 2);

    array_push($priceTable, array("Total:", "{$totalPaid}", "{$totalInterest}", "{$totalAmortization}", "{$outstandingBalanceStr}"));

    return $priceTable;
}
?>