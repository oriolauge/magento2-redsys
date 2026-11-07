<?php
namespace OAG\Redsys\Model\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Writes the module logs to var/log/redsys.log, isolated from system.log
 */
class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/redsys.log';
}
