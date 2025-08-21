<div class="text-weebz">
    <?php
    echo \onmotion\apexcharts\ApexchartsWidget::widget([
        'type' => 'donut', // default area
        'height' => '200', // default 350
        'chartOptions' => [
            // 'title' => [
            //     'text' => Yii::t('app', 'Vistors Operational System'),
            // ],
            'theme' => [
                'mode' => 'dark',
                'palette' => 'palette1',
                'monochrome' => [
                    'enabled' => true,
                    'color' => '#39a50a',
                    'shadeIntensity' => 0.65
                ],
            ],
            'chart' => [
                'background' => 'none',
                'toolbar' => [
                    'show' => true,
                    'autoSelected' => 'zoom'
                ],
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '60%',
                    ]
                ]
            ],
            'labels' => $series['labels'],
        ],
        'series' => $series['data'],
    ]);
    ?>
</div>