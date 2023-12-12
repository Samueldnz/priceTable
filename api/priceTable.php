<?php declare(strict_types=1);

require 'cdcFunctions.php';

function getLeftBoxHTMLText(float $cashPrice,float $futurePrice,int $numInstallment,float $interestRate,bool $hasDownPayment,int $monthsToBack):string{
    $futurePriceTemp = numberToFixed((float) $futurePrice,2);
    $cashPriceTemp = numberToFixed((float) $cashPrice,2);
    $numInstallmentTemp = (int) $numInstallment;
    $monthsToBackTemp = (int) $monthsToBack;
    $interestRateTemp = numberToFixed((float) $interestRate,4);
    $annualInterestRate = convertMonthlyInterestToAnnual($interestRate);

    $financingCoefficient = calculateFinancingCoefficient($interestRate,$numInstallment);

    $pmt = numberToFixed(getPMT($cashPrice,$financingCoefficient), 2);
    $valueToReturn = numberToFixed(calculateValueToReturn($pmt,$numInstallmentTemp,$monthsToBackTemp), 2);

    $textInstallment = $hasDownPayment ? " (+ 1)": "";
    $textHasDownPayment = $hasDownPayment ? "Sim" : "Não";

    $interestRateTemp *= 100;
    return "<p><b>Parcelamento:</b> {$numInstallment} {$textInstallment} </p>
    <p><b>Taxa:</b> {$interestRateTemp}% Ao Mês ({$annualInterestRate}% Ao Ano) </p>
    <p><b>Valor Financiado:</b> $ {$cashPriceTemp} </p>
    <p><b>Valor Final:</b> $ {$futurePriceTemp}</p>
    <p><b>Meses a Voltar(Adiantados):</b> {$monthsToBack} </p>
    <p><b>Valor a voltar(Adiantamento da dívida):</b> $ {$valueToReturn} </p>
    <p><b>Entrada:</b> {$textHasDownPayment} </p> ";
}

function getRightBoxHTMLText(float $cashPrice, float $futurePrice, int $numInstallment, float $interestRate, bool $hasDownPayment, float $adjustedValue): string{

    $realInterestRate = 0;

    $futurePriceTemp = numberToFixed((float) $futurePrice,2);
    $cashPriceTemp = numberToFixed((float) $cashPrice,2);
    $numInstallmentTemp = (int) $numInstallment;


    $realInterestRate = calculateInterestRate($cashPrice,$futurePrice, $numInstallment,$hasDownPayment) * 100;

    
    $financingCoefficient = calculateFinancingCoefficient($interestRate,$numInstallment);

    $realInterestRate = numberToFixed($realInterestRate,4);
 
    $pmt = toFixed(getPMT($cashPrice,$financingCoefficient),2);
 
    $embeddedInterest = (($futurePrice - $cashPrice) / $cashPrice) * 100;
    $embeddedInterest = numberToFixed($embeddedInterest,2);
    $discount = (($futurePrice - $cashPrice) / $futurePrice) * 100;
    $discount = numberToFixed($discount,2);
    $appliedFactor = toFixed(calculateAppliedFactor($hasDownPayment,$numInstallment,$financingCoefficient,$interestRate),6);
    $financingCoefficient = numberToFixed($financingCoefficient,6);
    return "
    <p><b>Prestação:</b> $ {$pmt}</p>
    <p> <b>Taxa Real:</b>  {$realInterestRate}%</p>
    <p> <b>Coeficiente de Financiamento:</b> {$financingCoefficient} </p>
    <p><b>Fator Aplicado:</b> {$appliedFactor}</p>
    <p> <b>Valor Corrigido:</b> $ {$adjustedValue} </p>
    <p> <b>Juros Embutido:</b> {$embeddedInterest}% </p>
    <p> <b>Desconto:</b>  {$discount}% </p>
    ";
}


function getPriceTableHTMLText(array $priceTable): string {
    $table = "";

    for ($i = 0; $i < count($priceTable); $i++) {

        if ($i == 0) {
            $table .= "<thead><tr>";

            foreach ($priceTable[$i] as $tableItem) {
                $table .= "<th> {$tableItem} </th>";
            }

            $table .= "</tr></thead>";
        } else {
            $table .= "<tr>";

            foreach ($priceTable[$i] as $tableItem) {
                if ($i == count($priceTable) - 1) {
                    $table .= "<td> <b>  $tableItem   </b> </td>";
                } else {
                    $table .= "<td>  $tableItem </td>";
                }
            }

            $table .= "</tr>";
        }
    }

    return $table;
}

function printPage(string $leftBoxContent,string $rightBoxContent,string $tabelaPriceContent):void{
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
                        {$leftBoxContent}
                    </div>

                    <div id="right-box">
                        {$rightBoxContent}
                    </div>
                    
                </div>

                <div id="table-container">
                    <div id="table-box">
                        <p>Price Table</p>
                        <table id="price-table">
                        {$tabelaPriceContent}
                        </table>
                    </div>
                </div>            
            </div>
        
        </body>
        </html>
    HTML;


    echo $finalText;

}



/*
Number of installments: np
Interest rate: tax
Cash price: pv
Credit price (future price): pp
Months to Pay Back: pb
Has down payment?: dp
*/


$numInstallment = (int) $_POST["np"];
$interestRate = (float) $_POST["tax"];
$presentValue = (float) $_POST["pv"];
$finalValue = (float) $_POST["pp"];
$monthsToBack = (int) $_POST["pb"];
$hasDownPayment = (bool) $_POST["dp"];


$priceTable;
$valorCorrigido;
$financingCoefficient;
$pmt;


if($interestRate != 0 && $finalValue == 0){
    $interestRate /= 100;

}else{
    $interestRate = calculateInterestRate($presentValue,$finalValue,$numInstallment,$hasDownPayment);
}


$financingCoefficient = calculateFinancingCoefficient($interestRate, $numInstallment);

if( $finalValue == 0){
    $finalValue = futureValue($financingCoefficient,$interestRate,$presentValue,$numInstallment,$hasDownPayment);
} 
$pmt = getPMT($presentValue,$financingCoefficient);

if($hasDownPayment){
    $pmt /= 1 + $interestRate;
    $numInstallment--;
    $presentValue -= $pmt;

    
}

$priceTable = getPriceTable($presentValue,$pmt,$numInstallment,$interestRate,$hasDownPayment);

$valorCorrigido = getAdjustedValue($priceTable,$numInstallment,$monthsToBack);

$priceTableText =  getPriceTableHTMLText($priceTable);

$leftBoxText = getLeftBoxHTMLText($presentValue,$finalValue,$numInstallment,$interestRate,$hasDownPayment, $monthsToBack);
$rightBoxText = getRightBoxHTMLText($presentValue,$finalValue,$numInstallment,$interestRate,$hasDownPayment, $valorCorrigido);

printPage($leftBoxText,$rightBoxText,$priceTableText);
?>