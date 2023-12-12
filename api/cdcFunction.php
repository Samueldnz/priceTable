<?php declare(strict_types=1);

function numberToFixed(float $num, int $decimals): float {
    return (float) number_format($num, $decimals, '.', "");
}

function toString(float $num, int $decimals): string {
    return number_format($num, $decimals, '.', "");
}

function calculateEquationValue(
    float $presentValue, 
    float $finalValue, 
    float $interestRate, 
    int $numberOfInstallments, 
    bool $hasDownPayment):float
{
    $a = 0;
    $b = 0;
    $c = 0;

    if ($hasDownPayment) {
        $a = pow(1 + $interestRate, $numberOfInstallments - 2);
        $b = pow(1 + $interestRate, $numberOfInstallments - 1);
        $c = pow(1 + $interestRate, $numberOfInstallments);

        return ($presentValue * $interestRate *$b) - ($finalValue/$numberOfInstallments) * ($c - 1);
    }else{
        $a = pow(1 + $interestRate, -$numberOfInstallments);
        $b = pow(1 + $interestRate, -$numberOfInstallments - 1);

        return ($presentValue * $interestRate) - (($finalValue/$numberOfInstallments) * (1 - $a));
    }
}

function calculateDerivativeValue(
    float $presentValue, 
    float $finalValue, 
    float $interestRate, 
    int $numberOfInstallments, 
    bool $hasDownPayment):float
{
    $a = 0;
    $b = 0;
    $c = 0;

    if ($hasDownPayment) {
        $a = pow(1 + $interestRate, $numberOfInstallments - 2);
        $b = pow(1 + $interestRate, $numberOfInstallments - 1);

        return $presentValue * ($b + ($interestRate * $a * ($numberOfInstallments - 1))) - ($finalValue * $b);
    } else {
        $a = pow(1 + $interestRate, -$numberOfInstallments);
        $b = pow(1 + $interestRate, -$numberOfInstallments - 1);

        return $presentValue - ($finalValue * $b);
    }
}

function calculateInterestRate(
    float $presentValue, 
    float $finalValue, 
    int $time, 
    bool $hasDownPayment): float
{
    $tolerance = 0.0001;
    $interestRate = 0.1;
    $previousInterestRate = 0.0;

    $function = 0;
    $derivative = 0;
    $interation = 0;

    while(abs($previousInterestRate - $interestRate) >= $tolerance){
        $previousInterestRate = $interestRate;
        $function = calculateEquationValue($presentValue,$finalValue,$interestRate, $time, $hasDownPayment);

        $derivative = calculateDerivativeValue($presentValue, $finalValue, $interestRate, $time, $hasDownPayment);

        $interestRate = $interestRate - ($function/$derivative);

        $interestRate++;
    }

    return $interestRate;
}

function fe(
    bool $hasDownPayment, 
    float $interestRate): float 
{
    if($hasDownPayment){
        return 1 + $interestRate;
    }else{
        return 1;
    }
}

function calculateFactor(
    bool $hasDownPayment, 
    int $numberOfInstallments, 
    float $financingCoefficient, 
    float $interestRate): float 
{
    $g = $numberOfInstallments*$financingCoefficient;

    if($g == 0){
        throw new InvalidArgumentException("Division by ZERO");
    }

    $f = fe($hasDownPayment, $interestRate);

    return $f / $g;
}

function calculateFinalValue(
    float $financingCoefficient, 
    float $interestRate, 
    float $presentValue, 
    int $numberOfInstallments, 
    bool $hasDownPayment): float 
{
    $result = $presentValue / calculateFactor($hasDownPayment, $numberOfInstallments, $financingCoefficient, $interestRate);

    return numberToFixed($result, 2);
}

function getPresentValue(
    float $financingCoefficient, 
    float $interestRate, 
    float $finalValue, 
    int $numberOfInstallments, 
    bool $hasDownPayment): float
{
    $f = fe($hasDownPayment, $interestRate);
    $factor = ($f / $financingCoefficient);
    $presentValue = ($finalValue/$numberOfInstallments) * $factor;

    return $presentValue;
}

function calculatePaymentAmount(
    float $presentValue, 
    float $financingCoefficient):float
{
    return $presentValue*$financingCoefficient;
}

function getFinancingCoefficient(
    float $interestRate, 
    int $numberOfInstallments): float 
{
    $decimalRate = $interestRate/100;
    $factor = (1 - pow(1 + $decimalRate, -$numberOfInstallments));

    return $decimalRate/$factor; 
}

function convertMonthlyToAnnualInterestRate(float $interest): float 
{
    $decimalRate = $interest/=100;
    $result = (pow(1 + $decimalRate, 12) - 1) * 100;

    return numberToFixed($result, 2);
}

function getValueToReturn(
    float $paymentAmount, 
    int $numberOfInstallments, 
    int $monthsToGoBack): float 
{
    if ($monthsToGoBack > $numberOfInstallments) {
        return 0;
    } else {
        return $paymentAmount * $monthsToGoBack;
    }
}

function calculateBackedValue(
    float $monthsToGoBack, 
    float $valueToGoBack,
    float $interestRate):float
{
    $factor = pow(1 + $interestRate/100, $monthsToGoBack);
    return $valueToGoBack/$factor;
}

function buildPriceTable(
    float $presentValue,
    float $paymentAmount,
    int $numberOfInstallments,
    float $interestRate,
    bool $hasDownPayment):array
{
    $totalAcumulateInterest = 0;
    $totalAmortization = 0;
    
    if($hasDownPayment){
        $totalPaid = $paymentAmount;
    }else{
        $totalPaid = 0;
    }

    $priceTableArray = array(
                            array("Month", "Payment", "Interest", "Amortization", "Outstanding Balance"));

    $interestValue = 0;
    $amortization = 0;
    $outstandingBalance = $presentValue;

    for($i = 1; $i <= $numberOfInstallments; $i++){
        $interestValue = $outstandingBalance*$interestRate;

        $amortization = $paymentAmount - $interestValue;

        $outstandingBalance = $outstandingBalance - $amortization;

        if($outstandingBalance > 0){
            $outstandingBalance = $outstandingBalance;
        }else{
            $outstandingBalance = 0;
        }

        array_push($priceTableArray, array($i, toString($paymentAmount, 2), toString($interestValue, 3), toString($amortization, 2), toString($outstandingBalance, 2)));

        $totalAcumulateInterest = $totalAcumulateInterest + $interestValue;

        $totalPaid = $totalPaid + $paymentAmount;

        $totalAmortization = $totalAmortization + $amortization;
    }

    $totalPaidStr = toString($totalPaid, 2);
    $totalAcumulateInterestStr = toString($totalAcumulateInterest, 3);
    $totalAmortizationStr = toString($totalAmortization, 2);
    $outstandingBalanceStr = toString($outstandingBalance, 2);

    array_push($priceTableArray, array("Total:", "{$totalPaidStr}", "{$totalAcumulateInterestStr}", "{$outstandingBalanceStr}"));

    return $priceTableArray;
}
?>