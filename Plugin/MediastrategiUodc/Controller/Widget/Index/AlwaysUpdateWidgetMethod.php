<?php

namespace Dibs\EasyCheckout\Plugin\MediastrategiUodc\Controller\Widget\Index;

use Magento\Framework\Controller\Result\Json;

class AlwaysUpdateWidgetMethod
{
    /**
     * @param \Mediastrategi\UODC\Controller\Widget\Index $subject
     * @param $result Json
     *
     * @return mixed
     */
    public function afterExecute($subject, $result)
    {
        $resultObject = $this->getReflectionResult($result);
        if (is_null($resultObject)) {
            return $result;
        }

        if (!property_exists($resultObject, 'update')) {
            return $result;
        }

        $resultObject->update = true;
        $result->setJsonData(\json_encode($resultObject));

        return $result;
    }

    /**
     * Fetches result array using reflection class,
     * since there is no way to extract data directly
     *
     * @param Json $result
     *
     * @return \stdClass|null
     */
    private function getReflectionResult(Json $result) : ?\stdClass
    {
        try {
            $resultJsonReflection = new \ReflectionProperty(Json::class, 'json');
            $resultJsonReflection->setAccessible(true);
            $resultJson = $resultJsonReflection->getValue($result);

            return \json_decode($resultJson) ?: null;
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
