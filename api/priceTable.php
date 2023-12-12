<?php declare(strict_types=1);

require 'cdcFunction.php';

function buildTableHTML(array $priceTable): string {
    $table = "";

    for ($i = 0; $i < count($priceTable); $i++) {

        if ($i == 0) {
            $table .= "<thead><tr>";
            foreach ($priceTable[$i] as $tableItem) {$table .= "<th> {$tableItem} </th>";}
            $table .= "</tr></thead>";
        }else{
            $table .= "<tr>";

            foreach ($priceTable[$i] as $tableItem) {
                if ($i == count($priceTable) - 1) {$table .= "<td> <b>  $tableItem   </b> </td>";
                } else {$table .= "<td>  $tableItem </td>";}
            }

            $table .= "</tr>";
        }
    }
    return $table;
}

function buildLeftBoxHTML(
    float $presentValue,
    float $finalValue,
    float $interestRate,
    int $numberOfInstallments,
    bool $hasDownPayment,
    int $monthsToGoBack):string
{
    $temporaryFinalValue = numberToFixed($finalValue, 2);
    $temporaryPresentvalue = numberToFixed($presentValue, 2);
    $temporaryInterestRate = numberToFixed($interestRate, 4);
    $annualInterestRate = convertMonthlyToAnnualInterestRate($interestRate);

    $financingCoefficient = getFinancingCoefficient($interestRate,$numberOfInstallments);

    $paymentAmount = numberToFixed(calculatePaymentAmount($presentValue, $financingCoefficient), 2);

    $valueToReturn = numberToFixed(getValueToReturn($paymentAmount, $numberOfInstallments, $monthsToGoBack), 2);

    if($hasDownPayment){
        $textInstallment = " (+ 1)";
        $textHasDownPayment = "Sim";
    }else{
        $textInstallment = "";
        $textHasDownPayment = "Não";
    }

    $interestRate = $interestRate * 100;

    return "<p><b>Parcelamento:</b> {$numberOfInstallments} {$textInstallment} </p>
    <p><b>Taxa:</b> {$interestRate}% Ao Mês ({$annualInterestRate}% Ao Ano) </p>
    <p><b>Valor Financiado:</b> $ {$temporaryPresentvalue} </p>
    <p><b>Valor Final:</b> $ {$temporaryFinalValue}</p>
    <p><b>Meses a Voltar(Adiantados):</b> {$monthsToGoBack} </p>
    <p><b>Valor a voltar(Adiantamento da dívida):</b> $ {$valueToReturn} </p>
    <p><b>Entrada:</b> {$textHasDownPayment} </p> ";
}

function buildRightBoxHTML(
    float $presentValue,
    float $finalValue,
    float $interestRate,
    int $numberOfInstallments,
    bool $hasDownPayment,
    float $backedValue):string
{
    $temporaryFinalValue = numberToFixed($finalValue, 2);
    $temporaryPresentvalue = numberToFixed($presentValue, 2);
    
    $realInterestRate = calculateInterestRate($presentValue, $finalValue, $numberOfInstallments, $hasDownPayment) * 100;

    $financingCoefficient = getFinancingCoefficient($interestRate,$numberOfInstallments);
    $financingCoefficient = numberToFixed($financingCoefficient,6);

    $paymentAmount = numberToFixed(calculatePaymentAmount($presentValue, $financingCoefficient), 2);

    $embeddedInterest = (($finalValue - $presentValue) / $presentValue);
    $embeddedInterest = numberToFixed($embeddedInterest,2);

    $discount = (($finalValue - $presentValue)/$finalValue) * 100;
    $discount = numberToFixed($discount,2);

    $appliedFactor = toString(calculateFactor($hasDownPayment, $numberOfInstallments, $financingCoefficient, $interestRate), 6);

    return "
    <p><b>Prestação:</b> $ {$paymentAmount}</p>
    <p> <b>Taxa Real:</b>  {$realInterestRate}%</p>
    <p> <b>Coeficiente de Financiamento:</b> {$financingCoefficient} </p>
    <p><b>Fator Aplicado:</b> {$appliedFactor}</p>
    <p> <b>Valor Corrigido:</b> $ {$backedValue} </p>
    <p> <b>Juros Embutido:</b> {$embeddedInterest}% </p>
    <p> <b>Desconto:</b>  {$discount}% </p>
    ";
}

function printPage(string $leftBoxHTML,string $rightBoxHTML,string $tabelaPriceHTML):void{
    $finalText = <<<HTML
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>CDC</title>
            <meta charset="utf8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
            <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
            <link
                rel="stylesheet"
                href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css"
            />
            <script src="js-webshim/minified/polyfiller.js"></script>
            
            <style>

                    #left-box, #right-box{
                        min-width: 10%;
                        border-style: dotted;
                        padding: 20px;
                        margin-bottom: 30px;
                        border-radius: 10px;
                    }

                    #summary-container{
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: space-around;

                        margin-top: 20px;
                    }

                    #table-container{
                        margin-top: 30px;
                        display: flex;
                        flex-direction: row;
                        justify-content: center;
                    }

                    #table-box{
                        background-color: rgba(128, 128, 128, 0.356);  
                        width: 800px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        border: 5px solid rgba(0, 0, 0, 0.61);
                        border-radius: 10px;
                        box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3);
                        padding-bottom: 20px;
                    }

                    #table-box p{
                        font-size: 1.5em;
                    }

                    table {
                        font-family: Arial, sans-serif;
                        border-collapse: collapse;
                        width: 90%;
                    }

                    th, td {
                        border: 1px solid rgba(0, 0, 0, 0.562);
                        text-align: center;
                        padding: 8px;
                    }
                    
            </style>
    </head>
    <body>
        <div id="result-container">
                    
                <div id="summary-container">

                    <div id="left-box">
                        {$leftBoxHTML}
                    </div>

                    <div id="right-box">
                        {$rightBoxHTML}
                    </div>
                    
                </div>

                <div id="table-container">
                    <div id="table-box">
                        <p>Price Table</p>
                        <table id="price-table">
                        {$tabelaPriceHTML}
                        </table>
                    </div>
                </div>            
            </div>
        
        </body>
        </html>
    HTML;

    echo $finalText;
}

$numberOfInstallments = (int) $_POST["np"];
$interestRate = (float) $_POST["tax"];
$presentValue = (float) $_POST["pv"];
$finalValue = (float) $_POST["pp"];
$monthsToGoBack = (int) $_POST["pb"];
$hasDownPayment = (bool) $_POST["dp"];

$priceTable;
$backedValue;
$financingCoefficient;
$paymentAmount;

if($interestRate != 0 && $finalValue == 0){
    $interestRate /= 100;
}else{
    $interestRate = calculateInterestRate($presentValue, $finalValue, $numberOfInstallments, $hasDownPayment);
}

$financingCoefficient = getFinancingCoefficient($interestRate,$numberOfInstallments);

if($finalValue == 0){
    $finalValue = calculateFinalValue($financingCoefficient, $interestRate, $presentValue, $numberOfInstallments, $hasDownPayment);
}

$paymentAmount = calculatePaymentAmount($presentValue, $financingCoefficient);

if($hasDownPayment){
    $paymentAmount /= 1 + $interestRate;
    $numberOfInstallments--;
    $presentValue -= $paymentAmount;
}

$priceTable = buildPriceTable($presentValue, $paymentAmount, $numberOfInstallments, $interestRate, $hasDownPayment);

$valueToReturn = getValueToReturn($paymentAmount, $numberOfInstallments, $monthsToGoBack);

$backedValue = calculateBackedValue($monthsToGoBack, $valueToReturn, $interestRate);

$priceTableHTML = buildTableHTML($priceTable);

$leftBoxHTML = buildLeftBoxHTML($presentValue,$finalValue, $interestRate, $numberOfInstallments, $hasDownPayment, $monthsToGoBack);

$rightBoxHTML = buildRightBoxHTML($presentValue,$finalValue,$interestRate,$numberOfInstallments,$hasDownPayment,$backedValue);

printPage($leftBoxHTML, $rightBoxHTML, $priceTableHTML);
?>