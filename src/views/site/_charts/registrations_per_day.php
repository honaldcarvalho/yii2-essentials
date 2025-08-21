<div class="text-weebz">
    <?php
    echo \onmotion\apexcharts\ApexchartsWidget::widget([
        'type' => 'bar', // default area
        'height' => '500', // default 350
        'chartOptions' => [
            // 'title' => [
            //     'text' => Yii::t('app', 'Registrations Per Day'),
            // ],
            'theme' => [
                'mode' => 'dark',
                'palette' => 'palette2'
            ],
            'chart' => [
                'background' => 'none',
                'toolbar' => [
                    'show' => true,
                    'autoSelected' => 'zoom'
                ],
            ],
            'xaxis' => [
                'type' => 'datetime',
                // 'categories' => $categories,
            ],
            'yaxis' => [
                'type' => 'number',
                // 'title'=> [
                //     'text'=> 'Acessos por Dia',
                // ]
                // 'categories' => $categories,
            ],
            'colors' => ['#64af44'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'endingShape' => 'rounded',
                    'columnWidth' => '90%'
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'stroke' => [
                'show' => true,
                'colors' => ['transparent']
            ],
            'legend' => [
                'verticalAlign' => 'bottom',
                'horizontalAlign' => 'left',
                'labels' => [
                    'colors' => ['#fff']
                ],
            ],
        ],
        'series' => $series
    ]);
    ?>
</div>