<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace System\Libs\Whoops\Handler;
use System\Libs\Whoops\Handler\Handler;
use System\Libs\Whoops\Exception\Frame;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class JsonResponseHandler extends Handler
{
    /**
     * @var bool
     */
    private $returnFrames = FALSE;

    /**
     * @var bool
     */
    private $onlyForAjaxRequests = FALSE;

    /**
     * @param  bool|NULL $returnFrames
     * @return NULL|bool
     */
    public function addTraceToOutput($returnFrames = NULL)
    {
        if(func_num_args() == 0) {
            return $this->returnFrames;
        }

        $this->returnFrames = (bool) $returnFrames;
    }

    /**
     * @param  bool|NULL $onlyForAjaxRequests
     * @return NULL|bool
     */
    public function onlyForAjaxRequests($onlyForAjaxRequests = NULL)
    {
        if(func_num_args() == 0) {
            return $this->onlyForAjaxRequests;
        }

        $this->onlyForAjaxRequests = (bool) $onlyForAjaxRequests;
    }

    /**
     * Check, if possible, that this execution was triggered by an AJAX request.
     *
     * @return bool
     */
    private function isAjaxRequest()
    {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        ;
    }

    /**
     * @return int
     */
    public function handle()
    {
        if($this->onlyForAjaxRequests() && !$this->isAjaxRequest()) {
            return Handler::DONE;
        }

        $exception = $this->getException();

        $response = array(
            'error' => array(
                'type'    => get_class($exception),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine()
            )
        );

        if($this->addTraceToOutput()) {
            $inspector = $this->getInspector();
            $frames    = $inspector->getFrames();
            $frameData = array();

            foreach($frames as $frame) {
                /** @var Frame $frame */
                $frameData[] = array(
                    'file'     => $frame->getFile(),
                    'line'     => $frame->getLine(),
                    'function' => $frame->getFunction(),
                    'class'    => $frame->getClass(),
                    'args'     => $frame->getArgs()
                );
            }

            $response['error']['trace'] = $frameData;
        }

        echo json_encode($response);
        return Handler::QUIT;
    }
}
