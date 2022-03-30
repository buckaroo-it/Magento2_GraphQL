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
use Buckaroo\Magento2\Model\ConfigProvider\Idin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class IdinResolver implements ResolverInterface
{
    /**
     * @var Idin
     */
    protected $idinConfig;

    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var Log
     */
    private $logger;

    public function __construct(
        Idin $idinConfig,
        GetCartForUser $getCartForUser,
        CustomerRepository $customerRepository,
        Log $logger
    ) {
        $this->idinConfig = $idinConfig;
        $this->getCartForUser = $getCartForUser;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['cart_id'])) {
            throw new GraphQlInputException(
               __('Required parameter "cart_id" is missing')
            );
        }
        try {
            $idin = $this->idinConfig->getIdinStatus(
                $this->getQuote($args['cart_id'], $context),
                $this->getCustomer($context->getUserId())
            );
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->logger->debug(__METHOD__.$th->getMessage());
            throw new GraphQlInputException(
               __('Unknown buckaroo error occurred')
            );
        }
        return [
            'issuers' => $this->idinConfig->getIssuers(),
            'active' => $idin['active'],
            'verified' => $idin['verified']
        ];
    }
    protected function getQuote(string $maskedQuoteId, ContextInterface $context)
    {
        // Shopping Cart validation
        return $this->getCartForUser->execute(
            $maskedQuoteId,
            $context->getUserId(), 
            (int)$context->getExtensionAttributes()->getStore()->getId()
        );
    }
    /**
     * Get customer by id
     *
     * @param mixed $customerId
     *
     * @return CustomerInterface|null
     */
    public function getCustomer($customerId)
    {
        if(empty($customerId)) {
            return;
        }
        return $this->customerRepository->getById($customerId);
    }
}
