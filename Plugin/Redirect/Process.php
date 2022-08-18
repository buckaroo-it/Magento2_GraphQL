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
use Buckaroo\Magento2\Controller\Redirect\Process as DefaultProcess;
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
     * @param DefaultProcess $process
     * @param callable $proceed
     * @param string $path
     * @param array $arguments
     *
     * @return mixed
     */
    public function aroundHandleProcessedResponse(DefaultProcess $process, callable $proceed, $path, $arguments = [])
    {
        try {
            $queryArguments = $this->getQueryArguments($arguments);

            if ($this->isIdinFromGraphQl($process->getResponseParameters())) {
                return $this->redirectWithData(
                    array_merge(
                        [
                            "route" => $path,
                            "cart_id" => $this->getCartId($process->getResponseParameters())
                        ],
                        $queryArguments
                    ),
                    $this->getReturnUrl(
                        $process->getResponseParameters()
                    )
                );
            }


            if ($this->isOrderFromGraphQl($process->getOrder())) {
                return $this->redirectWithData(
                    array_merge(
                        [
                            "route" => $path,
                            "order_number" => $process->getOrder()->getIncrementId()
                        ],
                        $queryArguments
                    )
                );
            }
        } catch (\Throwable $th) {
            $this->logger->debug(__METHOD__ . $th->getMessage());
        }
        return $proceed($path, $arguments);
    }
    /**
     * Override add error message to user
     *
     * @param DefaultProcess $process
     * @param callable $proceed
     * @param string $message
     *
     * @return void
     */
    public function aroundAddErrorMessage(DefaultProcess $process, callable $proceed, string $message)
    {
        $this->setMessage($message, MessageInterface::TYPE_ERROR);
        if (!$this->isFromGraphQl($process)) {
            $proceed($message);
        }
    }

    /**
     * Override add success message to user
     *
     * @param DefaultProcess $process
     * @param callable $proceed
     * @param string $message
     *
     * @return void
     */
    public function aroundAddSuccessMessage(DefaultProcess $process, callable $proceed, string $message)
    {
        $this->setMessage($message, MessageInterface::TYPE_SUCCESS);
        if (!$this->isFromGraphQl($process)) {
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
     * @param array $data
     * @param string $returnUrl
     *
     * @return Magento\Framework\App\Response\RedirectInterface
     */
    protected function redirectWithData(array $data, string $returnUrl = null)
    {
        if (isset($this->message['type']) && isset($this->message['text'])) {
            $data = array_merge($data, [
                "message_type" => $this->message['type'],
                "message" => $this->message['text']
            ]);
        }

        if ($returnUrl === null) {
            $returnUrl = $this->config->getBaseUrl() . "/" . $this->config->getPaymentProcessedPath();
        }

        return $this->resultRedirectFactory
            ->setUrl(
                $returnUrl . '?' . http_build_query($data)
            );
    }
    /**
     * Check if the request is from graphql
     *
     * @param DefaultProcess $process
     *
     * @return boolean
     */
    public function isFromGraphQl(DefaultProcess $process)
    {
        return $this->isOrderFromGraphQl($process->getOrder()) || $this->isIdinFromGraphQl($process->getResponseParameters());
    }
    /**
     * Check if is a idin request originating from graphql
     *
     * @param array $response
     *
     * @return boolean
     */
    protected function isIdinFromGraphQl($response)
    {
        return isset($response['add_idin_request_from']) && $response['add_idin_request_from'] === 'graphQl';
    }
    /**
     * Check if processed order came from graphQl
     *
     * @param OrderInterface $order
     *
     * @return boolean
     */
    protected function isOrderFromGraphQl(OrderInterface $order)
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
    /**
     * Get any query arguments set
     *
     * @param array $arguments
     *
     * @return array
     */
    private function getQueryArguments(array $arguments)
    {
        if (isset($arguments['_query'])) {
            return $arguments['_query'];
        }
        return [];
    }
    /**
     * Get cart id from response
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getCartId(array $response)
    {
        if (isset($response['add_idin_masked_quote_id'])) {
            return $response['add_idin_masked_quote_id'];
        }
    }
    /**
     * Get return url from response
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getReturnUrl(array $response)
    {
        if (!isset($response['add_idin_return_url'])) {
            return;
        }
        $cartId = $this->getCartId($response);
        if ($cartId === null) {
            return $response['add_idin_return_url'];
        }
        return $response['add_idin_return_url'] . "/" . $cartId;
    }
}
