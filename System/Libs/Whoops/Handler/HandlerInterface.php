<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace System\Libs\Whoops\Handler;
use System\Libs\Whoops\Exception\Inspector;
use System\Libs\Whoops\Run;
use Exception;

interface HandlerInterface
{
    /**
     * @return int|null  A handler may return nothing, or a Handler::HANDLE_* constant
     */
    public function handle();

    /**
     * @param Run $run
     */
    public function setRun(Run $run);

    /**
     * @param Exception $exception
     */
    public function setException(Exception $exception);

    /**
     * @param Inspector $inspector
     */
    public function setInspector(Inspector $inspector);
}
