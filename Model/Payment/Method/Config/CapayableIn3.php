<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */

namespace Buckaroo\Magento2Graphql\Model\Payment\Method\Config;

use Buckaroo\Magento2Graphql\Model\Payment\Method\AbstractConfig;

class CapayableIn3 extends AbstractConfig
{
    /**
     * @inheritDoc 
    */
    public function getFields()
    {
        return [
            [
                "key"   => "customer_gender",
                "label" => __("Saluation"),
                "type"  => "checkbox_list",
                "values"=> [
                   [
                    "name" => __("Mr."),
                    "code" => "1"
                   ],
                   [
                    "name" => __("Mrs."),
                    "code" => "2"
                   ]

                ],
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ]
                ]
            ],
            [
                "key"   => "customer_billingName",
                "label" => __("Billing Name:"),
                "type"  => "text",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ]
                ]
            ],
            [
                "key"   => "customer_DoB",
                "label" => __("Date of Birth:"),
                "type"  => "date",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ]
                ]
            ]
            ,
            [
                "key"   => "orderAs",
                "label" => __("Business Model:"),
                "type"  => "select",
                "values"=> [
                   [
                    "name" => __("Private"),
                    "code" => "1"
                   ],
                   [
                    "name" => __("Company"),
                    "code" => "2"
                   ],
                   [
                    "name" => __("One-man business"),
                    "code" => "3"
                   ]

                ]
            ],
            [
                "key"   => "COCNumber",
                "label" => __("COC Number:"),
                "type"  => "text",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ],
                    [
                        "name" => "minlength",
                        "value" => 8
                    ]
                ]
            ],
            [
                "key"   => "CompanyName",
                "label" => __("Company Name:"),
                "type"  => "text",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ]
                ]
            ]
        ];
    }
}