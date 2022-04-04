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

namespace Buckaroo\Magento2Graphql\Plugin;

use Buckaroo\Magento2\Logging\Log;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Buckaroo\Magento2Graphql\Model\MainConfig;
use Magento\Framework\Controller\Result\Redirect;
use Buckaroo\Magento2Graphql\Model\AdditionalDataProvider;
use Buckaroo\Magento2SecondChance\Controller\Checkout\SecondChance as DefaultSecondChance;

class SecondChance {

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    protected $resultRedirectFactory;

    /**
     * @var \Buckaroo\Magento2Graphql\Model\MainConfig
     */
    protected $config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;
    
    protected $logger;


    public function __construct(
        Redirect $resultRedirectFactory,
        MainConfig $config,
        Log $logger,
        Session $checkoutSession,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }
    public function aroundHandleRedirect(DefaultSecondChance $secondChance, callable $proceed, $path, $arguments = [])
    {
        try {
            if ($this->orderFromGraphQl()) {
                return $this->redirectWithData(
                    [
                        "route" => $path,
                        "cart_id" => $this->getCartId()
                    ]
                );
            }
        } catch (\Throwable $th) {
            $this->logger->debug(__METHOD__ . $th->getMessage());
        }
        return $proceed($path, $arguments);
    }
    /**
     * Test if order is from graphQl
     *
     * @return void
     */
    public function orderFromGraphQl()
    {
        return $this->checkoutSession
        ->getQuote()
        ->getPayment()
        ->getAdditionalInformation(AdditionalDataProvider::PAYMENT_FROM) === 'graphQl';
    }
    /**
     * Redirect to spa/pwa with data
     *
     * @param array $data
     *
     * @return Magento\Framework\App\Response\RedirectInterface
     */
    protected function redirectWithData(array $data)
    {
        return $this->resultRedirectFactory
            ->setUrl(
                $this->config->getBaseUrl() . "/" . $this->config->getPaymentProcessedPath() . '?' . http_build_query($data)
            );
    }
    /**
     * Get cart id from response
     *
     *
     * @return string|null
     */
    protected function getCartId()
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId((int)$this->checkoutSession->getQuote()->getId());
        $quoteIdMask->save();
        return $quoteIdMask->getMaskedId();
    }
}