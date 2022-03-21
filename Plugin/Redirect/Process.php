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

namespace Buckaroo\Magento2Graphql\Plugin\Redirect;

use Magento\Sales\Api\Data\OrderInterface;
use Buckaroo\Magento2Graphql\Model\MainConfig;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Controller\Result\Redirect;
use Buckaroo\Magento2Graphql\Model\AdditionalDataProvider;
use Buckaroo\Magento2\Controller\Redirect\ProcessInterface;
use Buckaroo\Magento2\Logging\Log;

class Process
{

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    protected $resultRedirectFactory;

    /**
     * @var \Buckaroo\Magento2Graphql\Model\MainConfig
     */
    protected $config;

    /**
     * @var \Buckaroo\Magento2\Logging\Log
     */
    protected $logger;

    public function __construct(
        Redirect $resultRedirectFactory,
        MainConfig $config,
        Log $logger
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->config = $config;
        $this->logger = $logger;
    }
    public function aroundHandleProcessedResponse(ProcessInterface $process, callable $proceed, $path, $arguments = [])
    {

        try {
            if ($this->isFromGraphQl($process->getOrder())) {
                return $this->redirectWithData(
                    $path,
                    $this->formatMessages(
                        $process->getMessages(true)
                    )
                );
            }
        } catch (\Throwable $th) {
            $this->logger->debug(__METHOD__ . $th->getMessage());
        }
        return $proceed($path, $arguments);
    }
    /**
     * Redirect to spa/pwa with data
     *
     * @param string $path
     *
     * @return Magento\Framework\App\Response\RedirectInterface
     */
    protected function redirectWithData(string $path, $messages)
    {
        $data = [
            "route" => $path,
            "messages" => $messages
        ];

        $this->logger->debug(__METHOD__ . $this->config->getBaseUrl());
        $this->logger->debug(__METHOD__ . $this->config->getPaymentProcessedPath());

        return $this->resultRedirectFactory
            ->setUrl(
                $this->config->getBaseUrl() . "/" . $this->config->getPaymentProcessedPath() . '?' . http_build_query($data)
            );
    }
    /**
     * Check if processed order came from graphQl
     *
     * @param OrderInterface $order
     *
     * @return boolean
     */
    protected function isFromGraphQl(OrderInterface $order)
    {
        if ($order->getIncrementId() === null) {
            return false;
        }

        $payment = $order->getPayment();

        if ($payment === null) {
            return false;
        }
        return $payment->getAdditionalInformation(AdditionalDataProvider::PAYMENT_FROM) === 'graphQl';
    }
    private function formatMessages($messageCollection)
    {
        $messages = [];
        if ($messageCollection != null) {
            $messages = $messageCollection->getItems();
        }

        $formattedMessages = [];
        foreach ($messages as $message) {
            if ($message instanceof MessageInterface) {
                $formattedMessages[] = [
                    "type" => $message->getType(),
                    "text" => $message->getText()
                ];
            }
        }
        return $formattedMessages;
    }
}
