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

namespace Buckaroo\Magento2Graphql\Model\Payment;

use Buckaroo\Magento2\Model\ConfigProvider\Method\Factory as MethodConfigProviderFactory;

/**
 * Checkout configuration of a Buckaroo payment method for available_payment_methods.buckaroo_additional.
 *
 * Plugin 2.x builds the Luma checkout configuration of every method in one place
 * (AbstractConfigProvider::fullConfig(), under payment.buckaroo.<method code>). This class returns
 * that same configuration, so every method, including new ones, is covered without a class per
 * method. Values are flattened into the key/value(s) list of BuckarooAdditionalData:
 * scalars become value, lists of objects with a code (issuers, cards, giftcards, genders) become
 * values, lists of scalars become a comma separated value and nested objects become "parent.child" keys.
 */
class MethodConfig
{
    /**
     * Keys that only make sense in the Luma checkout (they carry a session form key)
     */
    private const SKIPPED_KEYS = ['redirecturl'];

    /**
     * @var MethodConfigProviderFactory
     */
    private MethodConfigProviderFactory $configProviderFactory;

    /**
     * @param MethodConfigProviderFactory $configProviderFactory
     */
    public function __construct(MethodConfigProviderFactory $configProviderFactory)
    {
        $this->configProviderFactory = $configProviderFactory;
    }

    /**
     * Get the checkout configuration of a Buckaroo method as BuckarooAdditionalData entries
     *
     * @param string $methodCode
     * @return array
     */
    public function get(string $methodCode): array
    {
        if (strpos($methodCode, 'buckaroo_magento2_') !== 0
            || !$this->configProviderFactory->has($methodCode)
        ) {
            return [];
        }

        $config = $this->configProviderFactory->get($methodCode)->getConfig();
        $methodConfig = $config['payment']['buckaroo'][$methodCode] ?? [];

        return is_array($methodConfig) ? $this->flatten($methodConfig) : [];
    }

    /**
     * Flatten a configuration array into key/value(s) entries
     *
     * @param array $config
     * @param string $prefix
     * @return array
     */
    private function flatten(array $config, string $prefix = ''): array
    {
        $entries = [];
        foreach ($config as $key => $value) {
            if ($value === null || in_array($key, self::SKIPPED_KEYS, true)) {
                continue;
            }

            $name = $prefix . $key;
            if (!is_array($value)) {
                $entries[] = ['key' => $name, 'value' => $this->toString($value)];
                continue;
            }

            if ($this->isListOfOptions($value)) {
                $entries[] = ['key' => $name, 'values' => $this->toOptions($value)];
                continue;
            }

            if ($this->isList($value)) {
                $entries[] = [
                    'key' => $name,
                    'value' => implode(',', array_map([$this, 'toString'], array_filter($value, 'is_scalar')))
                ];
                continue;
            }

            array_push($entries, ...$this->flatten($value, $name . '.'));
        }

        return $entries;
    }

    /**
     * Map option lists (issuers, cards, giftcards, genders) to BuckarooMultipleValues
     *
     * @param array $options
     * @return array
     */
    private function toOptions(array $options): array
    {
        $values = [];
        foreach ($options as $option) {
            $code = $option['code'] ?? $option['genderType'] ?? null;
            if ($code === null || $code === '') {
                continue;
            }
            $img = $option['img'] ?? $option['logo'] ?? null;
            $values[] = [
                'code' => (string)$code,
                'name' => (string)($option['name'] ?? $option['title'] ?? $option['genderTitle'] ?? $code),
                'img' => is_string($img) && $img !== '' ? $img : null,
            ];
        }

        return $values;
    }

    /**
     * Whether the value is a list of objects that carry a code
     *
     * @param array $value
     * @return bool
     */
    private function isListOfOptions(array $value): bool
    {
        if (!$this->isList($value) || $value === []) {
            return false;
        }

        $first = reset($value);

        return is_array($first) && (isset($first['code']) || isset($first['genderType']));
    }

    /**
     * Whether the array is a list (0..n-1 keys)
     *
     * @param array $value
     * @return bool
     */
    private function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Convert a scalar to the string GraphQL returns
     *
     * @param mixed $value
     * @return string
     */
    private function toString($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string)$value : '';
    }
}
