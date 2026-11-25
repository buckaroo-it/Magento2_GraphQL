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
use Buckaroo\Transaction\Response\TransactionResponse;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Starts an iDIN age verification and returns the redirect url of the issuer.
 *
 * Uses the same request builder (IdinVerifyRequest), transfer factory and client (TransactionVerify)
 * as the plugin's own checkout controller, wired in etc/di.xml. The result is tied to the Magento
 * customer session, so the front end has to share the Magento session cookie.
 */
class IdinOutputResolver implements ResolverInterface
{
    /**
     * @var BuilderInterface
     */
    private BuilderInterface $requestDataBuilder;

    /**
     * @var TransferFactoryInterface
     */
    private TransferFactoryInterface $transferFactory;

    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var Log
     */
    private Log $logger;

    /**
     * @param BuilderInterface $requestDataBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param Log $logger
     */
    public function __construct(
        BuilderInterface $requestDataBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        Log $logger
    ) {
        $this->requestDataBuilder = $requestDataBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (empty($args['input']['issuer'])) {
            throw new GraphQlInputException(
                __('A idin issuer is required')
            );
        }

        try {
            $response = $this->sendIdinRequest((string)$args['input']['issuer']);
        } catch (\Throwable $th) {
            $this->logger->addDebug((string)$th);
            throw new GraphQlInputException(
                __('Unknown buckaroo error occurred')
            );
        }

        $requiredAction = $response !== null ? $response->get('RequiredAction') : null;
        if (!empty($requiredAction['RedirectURL'])) {
            return ['redirect' => (string)$requiredAction['RedirectURL']];
        }

        throw new GraphQlInputException(
            __('Unfortunately iDIN not verified!')
        );
    }

    /**
     * Send the iDIN verify request
     *
     * @param string $issuer
     *
     * @return TransactionResponse|null
     */
    protected function sendIdinRequest(string $issuer): ?TransactionResponse
    {
        $transfer = $this->transferFactory->create(
            $this->requestDataBuilder->build(['issuer' => $issuer])
        );

        $result = $this->client->placeRequest($transfer);
        $response = $result['object'] ?? null;

        return $response instanceof TransactionResponse ? $response : null;
    }
}
