<?php

namespace OAG\Redsys\Controller\Callback;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Error extends Action implements HttpGetActionInterface
{
    public function execute()
    {
        $this->messageManager->addErrorMessage(__('Transaction denied.'));
        $this->_redirect('checkout/cart');
    }
}
