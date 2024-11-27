<?php

namespace Buckaroo\Magento2Graphql\Model\Payment\Method;

use Buckaroo\Magento2\Model\Method\AbstractMethod;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class AbstractMethodPlugin
{
    /**
     * @param AbstractMethod   $subject
     * @param AbstractMethod   $result
     * @param DataObject|array $data
     * @return AbstractMethod
     * @throws LocalizedException
     */
    public function afterAssignData(
        AbstractMethod $subject,
        AbstractMethod $result,
        DataObject|array $data
    ): AbstractMethod {
        if (!$data instanceof DataObject) {
            return $result;
        }
        $additionalSkip = $data->getAdditionalData();

        if (isset($additionalSkip['buckaroo_return_url'])) {
            $subject->getInfoInstance()->setAdditionalInformation(
                'buckaroo_return_url',
                $additionalSkip['buckaroo_return_url']
            );
        }
        if (isset($additionalSkip['buckaroo_cancel_url'])) {
            $subject->getInfoInstance()->setAdditionalInformation(
                'buckaroo_cancel_url',
                $additionalSkip['buckaroo_cancel_url']
            );
        }
        if (isset($additionalSkip['buckaroo_error_url'])) {
            $subject->getInfoInstance()->setAdditionalInformation(
                'buckaroo_error_url',
                $additionalSkip['buckaroo_error_url']
            );
        }
        if (isset($additionalSkip['buckaroo_reject_url'])) {
            $subject->getInfoInstance()->setAdditionalInformation(
                'buckaroo_reject_url',
                $additionalSkip['buckaroo_reject_url']
            );
        }
        return $result;
    }
}
