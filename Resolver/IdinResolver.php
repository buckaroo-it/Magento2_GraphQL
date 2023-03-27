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
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2Graphql\Resolver\AbstractCartResolver;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class IdinResolver extends AbstractCartResolver
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
        GetCartForUser $getCartForUser,
        Idin $idinConfig,
        CustomerRepository $customerRepository,
        Log $logger
    ) {
        parent::__construct($getCartForUser);
        $this->idinConfig = $idinConfig;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        parent::resolve($field, $context, $info, $value, $args);

        try {
            $idin = $this->idinConfig->getIdinStatus(
                $this->getQuote($args['cart_id'], $context),
                $this->getCustomer($context->getUserId())
            );
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->logger->addDebug($th);
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
    /**
     * Get customer by id
     *
     * @param mixed $customerId
     *
     * @return CustomerInterface|null
     */
    public function getCustomer($customerId)
    {
        if (empty($customerId)) {
            return;
        }
        return $this->customerRepository->getById($customerId);
    }
}
