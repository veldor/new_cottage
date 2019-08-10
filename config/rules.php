<?php

return [
    //    MIGRATION BLOCK
    'migrate' => 'site/migration',
    //    END MIGRATION BLOCK

//    AUTH BLOCK
    'login' => 'site/login', // Форма входа на сайт
    'logout' => 'site/logout', // Выход из учётной записи
//    END AUTH BLOCK

//    COTTAGE BLOCK
    'cottage/show/<cottageNumber:[0-9]+(-a)?>' => 'cottage/show',
//      EDIT COTTAGE BLOCK
    'cottage/edit/<type:[0-9a-z\-]+>/<action:[0-9a-z\-]+>/<id:[0-9]+>' => 'cottage/edit',
    'cottage/create-additional/<cottageId:[0-9]+>' => 'cottage/create-additional',
    'cottage/switch-individual/<cottageId:[0-9]+>' => 'cottage/switch-individual',
    'cottage/switch-<type:power|membership|target>/<cottageId:[0-9]+>' => 'cottage/switch-use',
//    END COTTAGE BLOCK

//    PAYMENTS BLOCK
    'payment-actions/<action:[0-9a-z\-]+>/<id:[0-9]+>' => 'payments/edit',
//    TARIFFS BLOCK
    'tariffs' => 'tariffs/index',
    'tariffs/fill' => 'tariffs/fill',
    'tariffs/fill-<type:power|membership|target>' => 'tariffs/fill',
    'tariff/fill/<type:power|membership|target>/<period:[0-9\-]+>' => 'tariffs/fill',
    'tariffs/details/<type:energy|membership|target>/<period:[0-9\-]+>' => 'tariffs/details',
//    FINES BLOCK
    'fines/<action:enable|disable>/<finesId:[0-9]+>' => 'fines/change',
    '<action:lock-fine|unlock-fine>/<finesId:[0-9]+>' => 'fines/lock',
    '<action:lock-fine|unlock-fine>' => 'fines/lock',
    'single/<action:add>/<id:[0-9]+>' => 'payments/single',
    'single/<action:add>' => 'payments/single',

    'bill/<action:create>' => 'payments/bill',

//    POWER BLOCK
    'power/<action:delete|change>/<id:[0-9]+>' => 'indication/power',
    'power/<action:change>' => 'indication/power',
    'counter/<action:disable|enable|add|delete>/<id:[0-9]+>' => 'indication/counter',
    'counter/<action:disable|enable|add>' => 'indication/counter',
    'get/counter-start/<date:[0-9]{4}-[0-9]{2}>' => 'indication/get-counter-start',
//    MEMBERSHIP BLOCK
    'membership/<action:change>/<id:[0-9]+>' => 'indication/membership',
    'membership/<action:change>' => 'indication/membership',
    'get/membership-start/<date:[0-9]{4}-[1-4]{1}>' => 'indication/get-membership-start',
//    BILL BLOCK
    'bill/show/<id:[0-9]+>' => 'payments/show-bill',
    'transaction/show/<id:[0-9]+>' => 'payments/show-transaction',
    'bill/distribute/<id:[0-9]+>' => 'payments/distribute-bill',
    'bill/distribute' => 'payments/distribute-bill',
    'pay/bill/<id:[0-9]+>' => 'payments/pay',
    'pay/bill/<id:[0-9]+>/<bankId:[0-9]+>' => 'payments/pay',
    'pay/bill' => 'payments/pay',

//    EMAIL BLOCK
    'send/bill/<billId:[0-9]+>' => 'email/send-bill',
//    PRINT BLOCK
    'print/bill/<billId:[0-9]+>' => 'print/bill',

//    BANK TRANSACTION BLOCK
    'bank-transaction/get/<id:[0-9]+>' => 'payments/get-transaction',
    'bank-transaction/confirm-manual' => 'payments/confirm-bank-transaction',


//    FLOATING INFO BLOCKS
    'info/<type:power|membership|target|deposit>/<id:[0-9]+>' => 'info/float',
];