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

use Buckaroo\Magento2\Logging\BuckarooLoggerInterface;
use Buckaroo\Magento2\Logging\Log;
use Buckaroo\Transaction\Response\TransactionResponse;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class IdinOutputResolver implements ResolverInterface
{
    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $requestDataBuilder;
    /**
     * @var TransferFactoryInterface
     */
    protected TransferFactoryInterface $transferFactory;
    /**
     * @var ClientInterface
     */
    protected ClientInterface $clientInterface;
    /**
     * @var BuckarooLoggerInterface
     */
    private BuckarooLoggerInterface $logger;

    /**
     *
     * @param BuilderInterface $requestDataBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $clientInterface
     * @param Log $logger
     */
    public function __construct(
        BuilderInterface $requestDataBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $clientInterface,
        BuckarooLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->transferFactory = $transferFactory;
        $this->clientInterface = $clientInterface;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!(isset($args['input']) && isset($args['input']['issuer']))) {
            throw new GraphQlInputException(
                __('A idin issuer is required')
            );
        }
        try {
            $response = $this->sendIdinRequest($args['input']['issuer']);
        } catch (\Throwable $th) {
            $this->logger->addError(sprintf(
                '[iDIN] | [GraphQL] | [%s:%s] - Validate iDIN | [ERROR]: %s',
                __METHOD__,
                __LINE__,
                $th->getMessage()
            ));
            throw new GraphQlInputException(
                __('Unknown buckaroo error occurred')
            );
        }

        if ($response->hasRedirect()) {
            return ['redirect' => $response->getRedirectUrl()];
        } else {
            throw new GraphQlInputException(
                __('Unfortunately iDIN not verified!')
            );
        }
    }

    /**
     * Send iDIN request
     *
     * @param string $issuer
     *
     * @return TransactionResponse $response
     * @throws GraphQlInputException
     */
    protected function sendIdinRequest(string $issuer): TransactionResponse
    {
        $transferO = $this->transferFactory->create(
            $this->requestDataBuilder->build(['issuer' => $issuer])
        );

        $response = $this->clientInterface->placeRequest($transferO);

        if (isset($response["object"]) && $response["object"] instanceof TransactionResponse) {
            return $response["object"];
        } else {
            throw new GraphQlInputException(
                __('TransactionResponse is not valid')
            );
        }
    }
}
