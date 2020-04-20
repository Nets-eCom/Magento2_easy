<?php
namespace Dibs\EasyCheckout\Plugin;

class CsrfValidatorSkip
{

    /**
     * Instead of implementing CsrfAwareActionInterface, we use this for backward compatibility
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        // Skip CSRF check for dibs easycheckout
        if ($request->getModuleName() == 'easycheckout') {
            return;
        }

        $proceed($request, $action);
    }

}
