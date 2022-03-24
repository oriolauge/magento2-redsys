<?php

namespace OAG\Redsys\Controller\Callback;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Success extends Action implements HttpGetActionInterface
{
    public function execute()
    {
        die("todo");
    }
}
