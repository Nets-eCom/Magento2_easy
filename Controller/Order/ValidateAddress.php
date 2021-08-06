<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Dibs\EasyCheckout\Model\Dibs\LocaleFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

class ValidateAddress implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var LocaleFactory
     */
    private $localeFactory;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

    /**
     * @var string[]
     */
    private $allowedCountryNames;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        LocaleFactory $localeFactory,
        DirectoryHelper $directoryHelper
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $jsonFactory;
        $this->localeFactory = $localeFactory;
        $this->directoryHelper = $directoryHelper;
    }

    public function execute()
    {
        $iso3Country = $this->request->getPostValue('countryCode', false);

        if (!$iso3Country) {
            return $this->respondWithError();
        }

        $countries = $this->directoryHelper->getCountryCollection()->toOptionArray();
        $allowedCountryCodes = array_column($countries, 'value');
        $this->allowedCountryNames = array_column($countries, 'label');

        $country = $this->localeFactory->create()->getCountryIdByIso3Code($iso3Country);
        if (!in_array($country, $allowedCountryCodes)) {
            return $this->respondWithError();
        }

        $result = $this->resultJsonFactory->create();
        return $result->setJsonData(json_encode(['valid' => 1, 'message' => 'valid']));
    }

    /**
     * @return Json
     */
    protected function respondWithError()
    {
        $message = __('Invalid country selected. Valid countries: %1', implode(', ', $this->allowedCountryNames));
        $data = ['valid' => 0, 'message' => $message];
        $result = $this->resultJsonFactory->create();
        return $result->setJsonData(json_encode($data));
    }
}
