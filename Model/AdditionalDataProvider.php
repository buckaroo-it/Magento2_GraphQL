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

declare(strict_types=1);

namespace Buckaroo\Magento2Graphql\Model;

use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;

/**
 * Turns the Buckaroo field of PaymentMethodInput into the payment's additional_data.
 *
 * Registered in etc/graphql/di.xml for every Buckaroo method code, the way Magento's own payment
 * GraphQL modules do it: the field is named after the method code, e.g.
 * payment_method: { code: "buckaroo_magento2_sepadirectdebit", buckaroo_magento2_sepadirectdebit: { ... } }.
 * The plugin's DataAssignObserver then stores the fields it knows on the payment.
 */
class AdditionalDataProvider implements AdditionalDataProviderInterface
{
    public const PAYMENT_FROM = 'buckaroo_payment_from';

    /**
     * @inheritdoc
     */
    public function getData(array $data): array
    {
        $methodCode = (string)($data['code'] ?? '');
        $fields = $data[$methodCode] ?? [];

        $additionalData = is_array($fields) ? $fields : [];
        $additionalData[self::PAYMENT_FROM] = 'graphQl';

        return $additionalData;
    }
}
