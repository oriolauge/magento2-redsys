<?php

namespace OAG\Redsys\Model;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Holds configuration paths
     */
    const XML_PATH_MERCHANTCODE = 'payment/oag_redsys/merchantcode';
    const XML_PATH_TRANSACTION_TYPE = 'payment/oag_redsys/transaction_type';
    const XML_PATH_TERMINAL = 'payment/oag_redsys/terminal';
    const XML_PATH_SECRET_KEY = 'payment/oag_redsys/secret_key';
}
