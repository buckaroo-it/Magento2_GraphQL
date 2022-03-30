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

namespace Buckaroo\Magento2Graphql\Resolver;

use Buckaroo\Magento2\Logging\Log;
use Buckaroo\Magento2\Gateway\GatewayInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2\Gateway\Http\TransactionBuilderFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class IdinOutputResolver implements ResolverInterface
{
    /**
     * @var \Buckaroo\Magento2\Gateway\Http\TransactionBuilder\IdinBuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var \Buckaroo\Magento2\Gateway\GatewayInterface
     */
    protected $gateway;

    /**
     * @var Log
     */
    private $logger;

    /**
     *
     * @param \Buckaroo\Magento2\Gateway\Http\TransactionBuilderFactory $transactionBuilderFactory
     * @param \Buckaroo\Magento2\Gateway\GatewayInterface $gateway
     * @param Log $logger
     */
    public function __construct(
        TransactionBuilderFactory $transactionBuilderFactory,
        GatewayInterface $gateway,
        Log $logger
    ) {
        $this->transactionBuilder = $transactionBuilderFactory->get('idin');
        $this->gateway            = $gateway;
        $this->logger             = $logger;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!(isset($args['input']) && isset($args['input']['issuer']))) {
            throw new GraphQlInputException(
                __('Required parameter "issuer" is missing')
            );
        }
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(
               __('Required parameter "cart_id" is missing')
            );
        }
        try {
            $response = $this->sendIdinRequest($args['input']['issuer'], $args['input']['cart_id']);
        } catch (\Throwable $th) {
            $this->logger->debug(__METHOD__.$th->getMessage());
            throw new GraphQlInputException(
                __('Unknown buckaroo error occurred')
            );
        }

        if (isset($response->RequiredAction) && isset($response->RequiredAction->RedirectURL)) {
            return ['redirect' => $response->RequiredAction->RedirectURL];
        } else {
            throw new GraphQlInputException(
                __('Unfortunately iDIN not verified!')
            );
        }
    }
    /**
     * Send idin request
     *
     * @param string $issuer
     * @param string $maskedQuoteId
     *
     * @return mixed $response
     * @throws \Exception
     */
    protected function sendIdinRequest($issuer, $maskedQuoteId)
    {
        $transaction = $this->transactionBuilder
            ->setIssuer($issuer)
            ->setAdditionalParameter('idin_request_from', 'graphQl')
            ->setAdditionalParameter('idin_masked_quote_id', $maskedQuoteId)
            ->build();

        return $this->gateway
            ->setMode(
                $this->transactionBuilder->getMode()
            )
            ->authorize($transaction)[0];
    }
}
