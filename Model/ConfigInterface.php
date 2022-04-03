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
    const XML_PATH_MERCHANT_CODE = 'payment/oag_redsys/merchant_code';
    const XML_PATH_MERCHANT_NAME = 'payment/oag_redsys/merchant_name';
    const XML_PATH_TRANSACTION_TYPE = 'payment/oag_redsys/transaction_type';
    const XML_PATH_TERMINAL = 'payment/oag_redsys/terminal';
    const XML_PATH_SECRET_KEY = 'payment/oag_redsys/secret_key';
    const CALLBACK_ERROR_URL = 'redsys/callback/error';
    const CALLBACK_SUCCESS_URL = 'redsys/callback/success';
    const CALLBACK_PROCESS_PAYMENT_URL = 'redsys/callback/processpayment';

    /**
     * Holds max length copied from Redsys documentation
     */
    const MERCHANT_NAME_MAX_LENGTH = 25;
}
