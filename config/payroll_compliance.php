<?php

return [
    'bpjs' => [
        'health_salary_cap' => 12000000,
        'jp_salary_cap' => 10547400,
        'jkk_risk_levels' => [
            'very_low' => [
                'label' => 'Sangat Rendah',
                'percent' => 0.24,
            ],
            'low' => [
                'label' => 'Rendah',
                'percent' => 0.54,
            ],
            'medium' => [
                'label' => 'Sedang',
                'percent' => 0.89,
            ],
            'high' => [
                'label' => 'Tinggi',
                'percent' => 1.27,
            ],
            'very_high' => [
                'label' => 'Sangat Tinggi',
                'percent' => 1.74,
            ],
            'custom' => [
                'label' => 'Custom',
                'percent' => null,
            ],
        ],
    ],
];
