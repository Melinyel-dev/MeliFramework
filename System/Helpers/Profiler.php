<?php

namespace Melidev\System\Helpers;

use Melidev\System\Core\Controller;

class Profiler{

    private static $marks = [];
    private static $sysMarks = [];
    private static $queries = [];
    private static $queriesFetch = [];
    private static $enabled = false;
    private static $renderingTime = 0;

    public static function enable(){
        self::$enabled = true;
    }

    public static function enabled(){
        return self::$enabled;
    }

    public static function query($queryInfos){
        self::$queries[] = $queryInfos;
        return count(self::$queries) - 1;
    }

    public static function queryFetch($queryInfos){
        if(!array_key_exists($queryInfos[0], self::$queriesFetch))
            self::$queriesFetch[$queryInfos[0]] = 0;
            self::$queriesFetch[$queryInfos[0]] += $queryInfos[1];
        return count(self::$queries) - 1;
    }

    public static function mark($name){
        self::$marks[$name][] = microtime(true)*1000;
    }

    public static function sys_mark($name){
        self::$sysMarks[$name][] = microtime(true)*1000;
    }

    public static function getMysqlTime(){
        $totalTime = 0;
        foreach (self::$queries as $query) {
            $totalTime += $query[1];
        }
        return round($totalTime,4);
    }

    public static function getFetchTime(){
        $totalTime = 0;
        foreach (self::$queriesFetch as $fetch) {
            $totalTime += $fetch;
        }
        return round($totalTime,4);
    }

    public static function getSysMarksResults($totalTime){
        $return = null;
        foreach (self::$sysMarks as $mark => $times) {
            if(array_key_exists(0, $times) && array_key_exists(1, $times)){
                $resultTime = round($times[1] - $times[0],4);
                $resultPourcent = $resultTime*100/$totalTime;
                if ($resultPourcent < 33) {
                    $resultColor = '#5Bbf81';
                } elseif ($resultPourcent < 66) {
                    $resultColor = '#FAA732';
                } else {
                    $resultColor = '#D36969';
                }
            }else{
                $resultTime = '/';
            }
            $return .= '<tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:60px;">'.$mark.'&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$resultTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$resultPourcent.'%;background-color:'.$resultColor.'">'.round($resultPourcent).'%</div></div></td></tr>';
        }
        return $return;
    }

    public static function getMarksResults($totalTime){
        $return = null;
        foreach (self::$marks as $mark => $times) {
            if(array_key_exists(0, $times) && array_key_exists(1, $times)){
                $resultTime = round($times[1] - $times[0],4);
                $resultPourcent = $resultTime*100/$totalTime;
                if ($resultPourcent < 33) {
                    $resultColor = '#5Bbf81';
                } elseif ($resultPourcent < 66) {
                    $resultColor = '#FAA732';
                } else {
                    $resultColor = '#D36969';
                }
            }else{
                $resultTime = '/';
            }
            $return .= '<tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:120px;">'.$mark.'&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$resultTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$resultPourcent.'%;background-color:'.$resultColor.'">'.round($resultPourcent).'%</div></div></td></tr>';
        }
        return $return;
    }

    public static function getQueriesResults(){
        $return = null;
        $i = 0;
        foreach (self::$queries as $query) {
            $strFetch = null;
            if (array_key_exists($i, self::$queriesFetch)) {
                $strFetch = '&nbsp;-&nbsp;Fetch(&nbsp;'.round(self::$queriesFetch[$i],4).'&nbsp;ms&nbsp;)';
            }
            $strQuery = str_ireplace('=', '<span style="color: #007700">=</span>', $query[0]);
            $strQuery = str_ireplace('select', '<span style="color: #0000BB">SELECT</span>', $strQuery);
            $strQuery = str_ireplace('from', '<span style="color: #0000BB">FROM</span>', $strQuery);
            $strQuery = str_ireplace('where', '<span style="color: #0000BB">WHERE</span>', $strQuery);
            $strQuery = str_ireplace('and', '<span style="color: #0000BB">AND</span>', $strQuery);
            $strQuery = str_ireplace('group by', '<span style="color: #0000BB">GROUP BY</span>', $strQuery);
            $strQuery = str_ireplace('order by', '<span style="color: #0000BB">ORDER BY</span>', $strQuery);
            $strQuery = str_ireplace('between', '<span style="color: #0000BB">BETWEEN</span>', $strQuery);
            $strQuery = str_ireplace('insert', '<span style="color: #0000BB">INSERT</span>', $strQuery);
            $strQuery = str_ireplace('into', '<span style="color: #0000BB">INTO</span>', $strQuery);
            $strQuery = str_ireplace('values', '<span style="color: #0000BB">VALUES</span>', $strQuery);
            $strQuery = str_ireplace('on duplicate key update', '<span style="color: #0000BB">ON DUPLICATE KEY UPDATE</span>', $strQuery);
            $strQuery = str_ireplace('update', '<span style="color: #0000BB">UPDATE</span>', $strQuery);
            $strQuery = str_ireplace('delete', '<span style="color: red">DELETE</span>', $strQuery);
            $strQuery = preg_replace('/\'([\w]*)\'/', "<span style=\"color: #E57F12\">'\\1'</span>", $strQuery);
            $return .= '<tr>
                <td style="padding:5px;vertical-align:top;width:290px;color:#900;font-weight:normal;background-color:#ddd;">MySQL(&nbsp;'.round($query[1],4).'&nbsp;ms&nbsp;)'.$strFetch.'&nbsp;&nbsp;</td><td style="padding:5px;color:#000;font-weight:normal;background-color:#ddd;">
                    <code style="background-color:#F7F7F9;border: 1px solid #E1E1E8;color: #DD1144;padding: 2px 4px;">
                        <span style="color: #000000">
                            '.$strQuery.'
                        </span></code></td></tr>';
            $i++;
        }
        return $return;
    }

    private static function headers_output(){
            $headersOutput = '<fieldset style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;" id="ci_profiler_http_headers">
                <legend style="color:#000;">&nbsp;&nbsp;HTTP HEADERS&nbsp;&nbsp;(<span onclick="var s=document.getElementById(\'ci_profiler_httpheaders_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'Show\'?\'Hide\':\'Show\';" style="cursor: pointer;">Show</span>)</legend>'
            .'<table style="width:100%;background-color: transparent;max-width: 100%;display:none;" id="ci_profiler_httpheaders_table">'."\n";
            foreach (array('HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD',' HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR') as $header){
                $val = isset($_SERVER[$header]) ? $_SERVER[$header] : '';
                $headersOutput .= '<tr><td style="vertical-align:top;width:50%;padding:5px;color:#900;background-color:#ddd;">'
                    .$header.'&nbsp;&nbsp;</td><td style="width:50%;padding:5px;color:#000;background-color:#ddd;">'.$val."</td></tr>\n";
            }
            $headersOutput .= '</table></fieldset>';
            return $headersOutput;
    }

    private static function session_output(){
        $sessionOutput = '<fieldset style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;" id="ci_profiler_csession"><legend style="color:#000;">&nbsp;&nbsp;SESSION DATA&nbsp;&nbsp;(<span onclick="var s=document.getElementById(\'ci_profiler_session_data\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'Show\'?\'Hide\':\'Show\';" style="cursor: pointer;">Show</span>)</legend><table style="width:100%;background-color: transparent;border-collapse: collapse;border-spacing: 0;max-width: 100%;display:none;" id="ci_profiler_session_data">';
            foreach ($_SESSION as $key => $val){
                if (is_array($val) OR is_object($val))
                {
                    $val = print_r($val, TRUE);
                }

                $sessionOutput .= '<tr><td style="padding:5px;vertical-align:top;color:#900;background-color:#ddd;">'
                    .$key.'&nbsp;&nbsp;</td><td style="padding:5px;color:#000;background-color:#ddd;">'.htmlspecialchars($val)."</td></tr>\n";
            }

            $sessionOutput .= "</table></fieldset>";
            return $sessionOutput;
    }

    public static function rendering($renderingTime){
        self::$renderingTime = $renderingTime;
    }

    public static function displayProfiler($totalTime, $controllerTime){
        if(self::enabled() && $GLOBALS['conf']['environment'] != 'prod'){
            $memoryUsage = number_format ( memory_get_usage() / 1024 , 2 , '.' , ' ' );
            $memoryPeakUsage = number_format ( memory_get_peak_usage() / 1024 , 2 , '.' , ' ' );

            $controller = Controller::getInstance();
            $systemTime = round($totalTime - $controllerTime, 4);
            $requestData = count(Input::all()) ? Debug::simple(Input::all()) : 'No data';

            $controlleurOutput = Debug::simple($controller);
            $mysqlTime = self::getMysqlTime();
            $fetchTime = self::getFetchTime();
            $mysqlTotalTime = round($mysqlTime + $fetchTime, 4);
            $codeControllerTime = round($controllerTime - self::$renderingTime, 4);

            $systemPourcent = $systemTime*100/$totalTime;
            if ($systemPourcent < 33) {
                $systemColor = '#5Bbf81';
            } elseif ($systemPourcent < 66) {
                $systemColor = '#FAA732';
            } else {
                $systemColor = '#D36969';
            }

            $controllerPourcent = $controllerTime*100/$totalTime;
            if ($controllerPourcent < 33) {
                $controllerColor = '#5Bbf81';
            } elseif ($controllerPourcent < 66) {
                $controllerColor = '#FAA732';
            } else {
                $controllerColor = '#D36969';
            }

            $mysqlTotalPourcent = $mysqlTotalTime*100/$totalTime;
            if ($mysqlTotalPourcent < 33) {
                $mysqlTotalColor = '#5Bbf81';
            } elseif ($mysqlTotalPourcent < 66) {
                $mysqlTotalColor = '#FAA732';
            } else {
                $mysqlTotalColor = '#D36969';
            }

            $mysqlPourcent = $mysqlTime*100/$totalTime;
            if ($mysqlPourcent < 33) {
                $mysqlColor = '#5Bbf81';
            } elseif ($mysqlPourcent < 66) {
                $mysqlColor = '#FAA732';
            } else {
                $mysqlColor = '#D36969';
            }

            $fetchPourcent = $fetchTime*100/$totalTime;
            if ($fetchPourcent < 33) {
                $fetchColor = '#5Bbf81';
            } elseif ($fetchPourcent < 66) {
                $fetchColor = '#FAA732';
            } else {
                $fetchColor = '#D36969';
            }

            $codePourcent = $codeControllerTime*100/$totalTime;
            if ($codePourcent < 33) {
                $codeColor = '#5Bbf81';
            } elseif ($codePourcent < 66) {
                $codeColor = '#FAA732';
            } else {
                $codeColor = '#D36969';
            }

            $renderingPourcent = self::$renderingTime*100/$totalTime;
            if ($renderingPourcent < 33) {
                $renderingColor = '#5Bbf81';
            } elseif ($renderingPourcent < 66) {
                $renderingColor = '#FAA732';
            } else {
                $renderingColor = '#D36969';
            }


            echo '
            <div style="clear:both;background-color:#fff;padding:10px;" id="codeigniter_profiler">

                <fieldset style="border:1px solid #900;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;" id="ci_profiler_benchmarks">
                <legend style="color:#900;">&nbsp;&nbsp;BENCHMARKS&nbsp;&nbsp;</legend>


                <table style="width:100%;background-color: transparent;max-width: 100%;">
                <tbody><tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;">Loading Time: System&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$systemTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$systemPourcent.'%;background-color:'.$systemColor.'">'.round($systemPourcent).'%</div></div></td></tr>

                '.self::getSysMarksResults($totalTime).'

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;">Controller Execution Time ( '.$controller->request->controller.'#'.$controller->request->action.' )&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$controllerTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$controllerPourcent.'%;background-color:'.$controllerColor.'">'.round($controllerPourcent).'%</div></div></td></tr>

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:60px;">MySQL + Fetch Total Time&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$mysqlTotalTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$mysqlTotalPourcent.'%;background-color:'.$mysqlTotalColor.'">'.round($mysqlTotalPourcent).'%</div></div></td></tr>

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:120px;">MySQL Total Time&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$mysqlTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$mysqlPourcent.'%;background-color:'.$mysqlColor.'">'.round($mysqlPourcent).'%</div></div></td></tr>

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:120px;">Fetch Total Time&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$fetchTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$fetchPourcent.'%;background-color:'.$fetchColor.'">'.round($fetchPourcent).'%</div></div></td></tr>

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:60px;">Code Controller Time&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$codeControllerTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$codePourcent.'%;background-color:'.$codeColor.'">'.round($codePourcent).'%</div></div></td></tr>

                '.self::getMarksResults($totalTime).'

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;padding-left:60px;">View Rendering Time&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.self::$renderingTime.' ms<div style="width:600px;background-color:#EEEEEE;height:20px;border-radius: 3px 3px 3px 3px;"><div style="height:20px;border-radius: 3px 3px 3px 3px;background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);color:#ffffff;text-align:center;text-shadow: 0px 0px 5px #000000;filter:dropshadow(color=#000000, offx=0, offy=0);background-size: 40px 40px;width:'.$renderingPourcent.'%;background-color:'.$renderingColor.'">'.round($renderingPourcent).'%</div></div></td></tr>

                <tr><td style="padding:5px;width:30%;color:#000;font-weight:bold;background-color:#ddd;">Total Execution Time&nbsp;&nbsp;</td><td style="padding:5px;width:70%;color:#900;font-weight:normal;background-color:#ddd;">'.$totalTime.' ms</td></tr>
                </tbody></table>
                </fieldset>

                <fieldset style="border:1px solid #5a0099;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;" id="ci_profiler_memory_usage">
                <legend style="color:#5a0099;">&nbsp;&nbsp;MEMORY USAGE&nbsp;&nbsp;</legend>
                <div style="color:#5a0099;font-weight:normal;padding:4px 0 4px 0;">'.$memoryUsage.' Ko<br/>'.$memoryPeakUsage.' Ko (peak)</div></fieldset>

                <fieldset style="border:1px solid #009900;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;" id="ci_profiler_post">
                <legend style="color:#009900;">&nbsp;&nbsp;REQUEST DATA&nbsp;&nbsp;</legend>
                <div style="color:#009900;font-weight:normal;padding:4px 0 4px 0;">'.$requestData.'</div></fieldset>

                <fieldset style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">
                <legend style="color:#0000FF;">&nbsp;&nbsp;DATABASE:&nbsp; QUERIES: '.count(self::$queries).'&nbsp;&nbsp;(<span onclick="var s=document.getElementById(\'ci_profiler_queries_db_0\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'Hide\'?\'Show\':\'Hide\';" style="cursor: pointer;">Hide</span>)</legend>

                <table id="ci_profiler_queries_db_0" style="width:100%;background-color: transparent;border-collapse: collapse;border-spacing: 0;max-width: 100%;">
                <tbody>
                    '.self::getQueriesResults().'
                </tbody></table>
                </fieldset>

                '.self::headers_output().'

                '.self::session_output().'

                <fieldset style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;" id="ci_profiler_config">
                <legend style="color:#000;">&nbsp;&nbsp;CONTROLLER DETAILS&nbsp;&nbsp;(<span onclick="var s=document.getElementById(\'ci_profiler_ctrl_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'Show\'?\'Hide\':\'Show\';" style="cursor: pointer;">Show</span>)</legend>
                    <div id="ci_profiler_ctrl_table" style="display:none;">'.$controlleurOutput.'</div>
                </fieldset>
            </div>
            ';
        }
    }

}

/* End of file */