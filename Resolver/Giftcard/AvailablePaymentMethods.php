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

namespace Buckaroo\Magento2Graphql\Resolver\Giftcard;


use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\CartInterface;

class AvailablePaymentMethods implements ResolverInterface
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $informationManagement;

    /**
     * @param PaymentInformationManagementInterface $informationManagement
     */
    public function __construct(PaymentInformationManagementInterface $informationManagement)
    {
        $this->informationManagement = $informationManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['quote'])) {
            throw new LocalizedException(__('"quote" value should be specified'));
        }

        $cart = $value['quote'];
        return $this->getPaymentMethodsData($cart, $value['already_paid']);
    }

    /**
     * Collect and return information about available payment methods,
     * return only methods that can do a pay reminder 
     * @param CartInterface $cart
     * @return array
     */
    private function getPaymentMethodsData(CartInterface $cart, float $alreadyPaid): array
    {
        $isGroupTransaction = $alreadyPaid > 0;

        $notAvailable = [
            'buckaroo_magento2_billink',
            'buckaroo_magento2_payperemail',
            'buckaroo_magento2_paylink',
            'buckaroo_magento2_sepadirectdebit',
            'buckaroo_magento2_transfer',
            'buckaroo_magento2_klarnakp',
            'buckaroo_magento2_klarnain',
            'buckaroo_magento2_applepay',
            'buckaroo_magento2_capayablein3',
            'buckaroo_magento2_capayablepostpay',
            'buckaroo_magento2_pospayment',
            'buckaroo_magento2_tinka'
        ];

        $paymentInformation = $this->informationManagement->getPaymentInformation($cart->getId());
        $paymentMethods = $paymentInformation->getPaymentMethods();

        $paymentMethodsData = [];
        foreach ($paymentMethods as $paymentMethod) {

            $paymentMethodCode = $paymentMethod->getCode();
            if (
                !$isGroupTransaction || (
                    $isGroupTransaction &&
                    $this->isMethodBuckaroo($paymentMethodCode) &&
                    !in_array($paymentMethodCode,  $notAvailable)
                )
            ) {
                $paymentMethodsData[] = [
                    'title' => $paymentMethod->getTitle(),
                    'code' => $paymentMethodCode,
                ];
            }
        }
        return $paymentMethodsData;
    }

    public function isMethodBuckaroo(string $paymentMethodCode)
    {
        return strpos($paymentMethodCode, 'buckaroo_magento2_') !== false;
    }
}
