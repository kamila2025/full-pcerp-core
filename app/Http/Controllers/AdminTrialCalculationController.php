<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminTrialCalculationController extends Controller
{
    public function majipayM(Request $request)
    {
        Gate::authorize(PermissionNameEnum::分期試算);

        $initialLoanAmount = intval($request->input('initial_amount', 0));

        $array = [
            ['instllment_count' => 6, 'instllment_rate' => 1.0000],
            ['instllment_count' => 9, 'instllment_rate' => 1.0000],
            ['instllment_count' => 10, 'instllment_rate' => 1.0749],
            ['instllment_count' => 11, 'instllment_rate' => 1.0819],
            ['instllment_count' => 12, 'instllment_rate' => 1.0887],
            ['instllment_count' => 13, 'instllment_rate' => 1.0958],
            ['instllment_count' => 14, 'instllment_rate' => 1.1029],
            ['instllment_count' => 15, 'instllment_rate' => 1.1099],
            ['instllment_count' => 16, 'instllment_rate' => 1.1169],
            ['instllment_count' => 17, 'instllment_rate' => 1.124],
            ['instllment_count' => 18, 'instllment_rate' => 1.1314],
            ['instllment_count' => 19, 'instllment_rate' => 1.1386],
            ['instllment_count' => 20, 'instllment_rate' => 1.1458],
            ['instllment_count' => 21, 'instllment_rate' => 1.1532],
            ['instllment_count' => 22, 'instllment_rate' => 1.1604],
            ['instllment_count' => 23, 'instllment_rate' => 1.1677],
            ['instllment_count' => 24, 'instllment_rate' => 1.1751],
            ['instllment_count' => 25, 'instllment_rate' => 1.18237],
            ['instllment_count' => 26, 'instllment_rate' => 1.18968],
            ['instllment_count' => 27, 'instllment_rate' => 1.1975],
            ['instllment_count' => 28, 'instllment_rate' => 1.2043],
            ['instllment_count' => 29, 'instllment_rate' => 1.21161],
            ['instllment_count' => 30, 'instllment_rate' => 1.219],
        ];

        $loanValues = [];

        foreach ($array as $key => $item) {
            $loanValues[] = [
                'instllment_count'      => $item['instllment_count'],
                'total_handling_charge' => $totalHandlingCharge = floor(ceil($initialLoanAmount / 0.88) * ($item['instllment_rate']/$item['instllment_count'])),
                'total_pay_amount'      => $totalPayAmount = ($totalHandlingCharge * $item['instllment_count']),
                // 'transfer_amount'       => floor($totalPayAmount * $item['instllment_rate']),
            ];
        }

        return \Inertia\Inertia::render('Installment/Majipay2', [
            'pageTitle'         => '麻吉pay-手機無卡',
            'loanValues'        => $loanValues,
            'initialLoanAmount' => $initialLoanAmount,
            'installments'      => array_column($array, 'instllment_count'),
        ]);
    }

    public function majipay(Request $request)
    {
        Gate::authorize(PermissionNameEnum::分期試算);

        $initialLoanAmount = intval($request->input('initial_amount', 0));

        $installmentCount = intval($request->input('installment_count', 6));

        $termAmount = intval($request->input('term_amount', 0));

        $array = [
            ['instllment_count' => 6, 'instllment_rate' => 0.953],
            ['instllment_count' => 9, 'instllment_rate' => 0.939],
            ['instllment_count' => 12, 'instllment_rate' => 0.922],
            ['instllment_count' => 15, 'instllment_rate' => 0.906],
            ['instllment_count' => 18, 'instllment_rate' => 0.886],
            ['instllment_count' => 21, 'instllment_rate' => 0.874],
            ['instllment_count' => 24, 'instllment_rate' => 0.863],
            ['instllment_count' => 30, 'instllment_rate' => 0.834],
        ];

        $loanValues = [];

        foreach ($array as $key => $item) {
            $loanValues[] = [
                'instllment_count'      => $item['instllment_count'],
                'total_handling_charge' => $totalHandlingCharge = ceil($initialLoanAmount / $item['instllment_rate'] / $item['instllment_count']),
                'total_pay_amount'      => $totalPayAmount = ($totalHandlingCharge * $item['instllment_count']),
                'transfer_amount'       => floor($totalPayAmount * $item['instllment_rate']),
            ];
        }

        return \Inertia\Inertia::render('Installment/Majipay', [
            'pageTitle'         => '麻吉pay-電腦無卡',
            'loanValues'        => $loanValues,
            'initialLoanAmount' => $initialLoanAmount,
            'installmentCount'  => $installmentCount,
            'termAmount'        => $termAmount,
            'installments'      => array_column($array, 'instllment_count'),
            'transferAmount'    => floor($installmentCount * $termAmount * array_column($array, 'instllment_rate', 'instllment_count')[$installmentCount]),
        ]);
    }

    public function zingala(Request $request)
    {
        Gate::authorize(PermissionNameEnum::分期試算);

        $initialLoanAmount = intval($request->input('initial_amount', 0));

        $array = [
            ['instllment_count' => 6, 'instllment_rate' => 1.04],
            ['instllment_count' => 9, 'instllment_rate' => 1.055],
            ['instllment_count' => 12, 'instllment_rate' => 1.065],
            ['instllment_count' => 18, 'instllment_rate' => 1.09],
            ['instllment_count' => 24, 'instllment_rate' => 1.12],
        ];

        $loanValues = [];

        foreach ($array as $key => $item) {
            $loanValues[] =  [
                'instllment_count'      => $item['instllment_count'],
                'total_pay_amount'      => $totalPayAmount = round($initialLoanAmount * $item['instllment_rate'], 4),
                'transfer_amount'       => $initialLoanAmount,
                'total_handling_charge' => round($totalPayAmount / $item['instllment_count']),
            ];
        }

        return \Inertia\Inertia::render('Installment/Zingala', [
            'pageTitle'         => '中租銀角-電腦無卡',
            'loanValues'        => $loanValues,
            'initialLoanAmount' => $initialLoanAmount,
        ]);
    }

    public function gomypay(Request $request)
    {
        Gate::authorize(PermissionNameEnum::分期試算);

        $initialLoanAmount = intval($request->input('initial_amount', 0));

        $array = [
            ['instllment_count' => 3, 'instllment_rate' => 1.05],
            ['instllment_count' => 6, 'instllment_rate' => 1.06],
            ['instllment_count' => 9, 'instllment_rate' => 1.07],
            ['instllment_count' => 12, 'instllment_rate' => 1.075],
            ['instllment_count' => 18, 'instllment_rate' => 1.11],
            ['instllment_count' => 24, 'instllment_rate' => 1.13],
            ['instllment_count' => 30, 'instllment_rate' => 1.17],
        ];

        $loanValues = [];

        foreach ($array as $key => $item) {
            $loanValues[] =  [
                'instllment_count'      => $item['instllment_count'],
                'total_pay_amount'      => $totalPayAmount = round($initialLoanAmount * $item['instllment_rate'], 4),
                'transfer_amount'       => $initialLoanAmount,
                'total_handling_charge' => round($totalPayAmount / $item['instllment_count']),
            ];
        }

        return \Inertia\Inertia::render('Installment/Gomypay', [
            'pageTitle'         => '信用卡分期',
            'loanValues'        => $loanValues,
            'initialLoanAmount' => $initialLoanAmount,
        ]);
    }

    public function bobopay(Request $request)
    {
        Gate::authorize(PermissionNameEnum::分期試算);

        $initialLoanAmount = intval($request->input('initial_amount', 0));

        $array = [
            ['instllment_count' => 3, 'instllment_rate' => 1.055, 't_instllment_rate' => 1.07],
            ['instllment_count' => 6, 'instllment_rate' => 1.065, 't_instllment_rate' => 1.12],
            ['instllment_count' => 9, 'instllment_rate' => 1.09, 't_instllment_rate' => 1.12],
            ['instllment_count' => 12, 'instllment_rate' => 1.12, 't_instllment_rate' => 1.174],
            ['instllment_count' => 15, 'instllment_rate' => 1.15, 't_instllment_rate' => 1.2],
            ['instllment_count' => 18, 'instllment_rate' => 1.18, 't_instllment_rate' => 1.225],
            ['instllment_count' => 21, 'instllment_rate' => 1.21, 't_instllment_rate' => 1.249],
            ['instllment_count' => 24, 'instllment_rate' => 1.24, 't_instllment_rate' => 1.281],
            ['instllment_count' => 30, 'instllment_rate' => 1.3, 't_instllment_rate' => 1.43],
        ];

        $loanValues = [];

        foreach ($array as $key => $item) {
            $loanValues[] =  [
                'instllment_count'          => $item['instllment_count'],
                'total_pay_amount'          => $totalPayAmount = round($initialLoanAmount * $item['instllment_rate'], 4),
                't_total_pay_amount'        => $totalPayAmount1 = round($initialLoanAmount * $item['t_instllment_rate'], 4),
                'transfer_amount'           => $initialLoanAmount,
                'total_handling_charge'     => round($totalPayAmount / $item['instllment_count']),
                't_total_handling_charge'   => round($totalPayAmount1 / $item['instllment_count']),
            ];
        }

        return \Inertia\Inertia::render('Installment/Bobopay', [
            'pageTitle'         => 'BOBOPAY',
            'loanValues'        => $loanValues,
            'initialLoanAmount' => $initialLoanAmount,
        ]);
    }
}
