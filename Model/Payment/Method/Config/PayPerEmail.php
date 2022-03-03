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

class PayPerEmail extends AbstractConfig
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
                "key"   => "customer_billingFirstName",
                "label" => __("Billing First Name:"),
                "type"  => "text",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ]
                ]
            ],
            [
                "key"   => "customer_billingLastName",
                "label" => __("Billing Last Name:"),
                "type"  => "text",
                "attributes"=> [
                    [
                        "name" => "required",
                        "value" => true
                    ]
                ]
            ],
            [
                "key"   => "customer_email",
                "label" => __("Email:"),
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