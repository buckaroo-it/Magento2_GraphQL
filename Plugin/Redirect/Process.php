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

    /**
     * @var array
     */
    protected $message = [];

    public function __construct(
        Redirect $resultRedirectFactory,
        MainConfig $config,
        Log $logger
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->config = $config;
        $this->logger = $logger;
    }
    /**
     * Override redirect process
     *
     * @param ProcessInterface $process
     * @param callable $proceed
     * @param string $path
     * @param array $arguments
     *
     * @return mixed
     */
    public function aroundHandleProcessedResponse(ProcessInterface $process, callable $proceed, $path, $arguments = [])
    {

        try {
            if ($this->isFromGraphQl($process->getOrder())) {
                return $this->redirectWithData($path);
            }
        } catch (\Throwable $th) {
            $this->logger->debug(__METHOD__ . $th->getMessage());
        }
        return $proceed($path, $arguments);
    }
    /**
     * Override add error message to user
     *
     * @param ProcessInterface $process
     * @param callable $proceed
     * @param string $message
     *
     * @return void
     */
    public function aroundAddErrorMessage(ProcessInterface $process, callable $proceed, string $message)
    {
        $this->setMessage($message, MessageInterface::TYPE_ERROR);
        if (!$this->isFromGraphQl($process->getOrder())) {
            $proceed($message);
        }
    }

    /**
     * Override add success message to user
     *
     * @param ProcessInterface $process
     * @param callable $proceed
     * @param string $message
     *
     * @return void
     */
    public function aroundAddSuccessMessage(ProcessInterface $process, callable $proceed, string $message)
    {
        $this->setMessage($message, MessageInterface::TYPE_SUCCESS);
        if (!$this->isFromGraphQl($process->getOrder())) {
            $proceed($message);
        }
    }
    /**
     * Store message text & type
     *
     * @param string $message
     * @param string $type
     *
     * @return void
     */
    protected function setMessage(string $message, string $type)
    {
        $this->message = [
            "type" => $type,
            "text" => $message
        ];
    }

    /**
     * Redirect to spa/pwa with data
     *
     * @param string $path
     *
     * @return Magento\Framework\App\Response\RedirectInterface
     */
    protected function redirectWithData(string $path)
    {
        $data = [
            "route" => $path,
        ];

        if (isset($this->message['type']) && isset($this->message['text'])) {
            $data = array_merge($data, [
                "messageType" => $this->message['type'],
                "message" => $this->message['text']
            ]);
        }

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
}
