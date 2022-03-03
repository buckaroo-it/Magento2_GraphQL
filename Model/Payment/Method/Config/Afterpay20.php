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

class Afterpay20 extends AbstractConfig
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
                "key"   => "buckaroo_identification_number",
                "label" => __("Identification number:"),
                "type"  => "text",
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
                "key"   => "customer_telephone",
                "label" => __("Telephone:"),
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
            ],
            [
                "key"   => "termsCondition",
                "label" => __("Terms and Conditions:"),
                "type"  => "checkbox",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ],
                    [
                        "name" => "data-accept-url",
                        "value" => "https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden"
                    ],
                    [
                        "name" => "data-accept-url-be",
                        "value" => "https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden"
                    ]
                    ,
                    [
                        "name" => "data-accept-url-b2b",
                        "value" => "https://www.afterpay.nl/nl/algemeen/zakelijke-partners/betalingsvoorwaarden-zakelijk"
                    ]
                ]
            ]
        ];
    }
}