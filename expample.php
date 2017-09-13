<?php


$accountId = '99329206'; //айди акк для примера
$propertyId = 'UA-99329206-1'; //айди ресурса
$profileId = '150718566'; //айди представления
$ga = new GA(__DIR__ . 'service-account-credentials.json'); //Тут важно чтоб былоа гугл либа было подгружена, в ларавел по PSR-4 должна сама подгрузиться
//Получаем баунс рейт относительно дней месяца за последние 7 дней
$bounceRateReport = $ga->getBounceRate($profileId, now()->subDays(7), now())->getReports()[0];
//Получаем среднею длительность сессии относительно дней месяца за последние 7 дней
$avgSessionDurationReport = $ga->getAvgSessionDuration($profileId, now()->subDays(7), now())->getReports()[0];
//Получаем количество завершенных относительно дней месяца за последние 7 дней
$goalCompletionsReport = $ga->getGoalCompletions($profileId, now()->subDays(7), now())->getReports()[0];
//Вот кастомный запрос. Например мы хотим получить кол-во сессий (sessions), баунс рейт (bounceRate), количество баунсов
// (bounces) и количество завершенных целей (goalCompletionsAll)
//
//относительно скажем браузеров (browser) за последние 10 дней
//Это наш массив метрик
$MyMetrics = ['sessions', 'bounceRate', 'bounces', 'goalCompletionsAll'];
$customReport = $ga->getGaData($profileId, now()->subDays(10), now(), $MyMetrics,
    'browser')->getReports()[0]; //Берем первый элемент, т.к. у нас один репорт (мы взяли одно измерения - browser
//если больше, то будет несколько репортов)

//Вот с этого момента надо в вьюху выносить всё
//Покажу как вывзодить на примере $customReport
$report = $customReport;
$header = $report->getColumnHeader();
$dimensionHeaders = $header->getDimensions();
$metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
$rows = $report->getData()->getRows();

//Проходим по рядам (это у нас браузеры)
for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
    $row = $rows[ $rowIndex ];
    $dimensions = $row->getDimensions();
    $metrics = $row->getMetrics();
    for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
        print($dimensions[$i] . ' : '); //здесь получаем значение ряда (браузер)
    }

    for ($j = 0; $j < count( $metricHeaders ) && $j < count( $metrics ); $j++) {
        $entry = $metricHeaders[$j];
        $values = $metrics[$j];
        for ( $valueIndex = 0; $valueIndex < count( $values->getValues() ); $valueIndex++ ) {
            $value = $values->getValues()[ $valueIndex ];
            print($MyMetrics[$valueIndex] . ": " . $value . ' ');
        }
        print('<br>');
    }
}
die();