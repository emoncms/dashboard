<?php
    define('EMONCMS_EXEC', 1);
$html1 = '<div id="2" class="dial" style="position: absolute; margin: 0px; top: 40px; left: 920px; width: 160px; height: 160px;" feed="1" max="" scale="" units="" decimals="-1" offset="" type="10" graduations="1" unitend="0" displayminmax="0" minvaluefeed="emontx:use" maxvaluefeed="emontx:use" timeout="" feedid="emontx:use"><canvas id="can-2" width="160" height="160"></canvas><div id="can-2-tooltip-1"></div><div id="can-2-tooltip-2"></div></div><div id="3" class="rawdata" style="position: absolute; margin: 0px; top: 60px; left: 300px; width: 560px; height: 300px;" feedid="emontx:use" colour="000000" units="" dp="" scale="" fill="" initzoom="1"><iframe scrolling="no" marginheight="0" marginwidth="0" src="/emoncms/vis/rawdata?embed=1&feedid=emontx:use&colour=000000&units=&dp=&scale=&fill=&initzoom=1" style="width: 560px; height: 300px;" frameborder="0"></iframe></div><div id="4" class="cylinder" style="position: absolute; margin: 0px; top: 20px; left: 1160px; width: 160px; height: 330px;" topfeedid="emontx:use" botfeedid="emontx:use" temptype="0" decimals="-1" unitend="0"><canvas id="can-4" width="160" height="330"></canvas></div><div onclick="alert()"></div><script>alert(hello)</script>';

// sudo apt-get install php-mbstring
if (function_exists("mb_convert_encoding")) {
    $ver = "php5";
    if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION>5) $ver = "php7";
    require_once "$ver/Bootup.php";
    require_once "$ver/UTF8.php";
    require_once "$ver/AntiXSS.php";
    $antiXss = new AntiXSS();
    $html2 = htmlspecialchars_decode($antiXss->xss_clean($html1));
} else {
    $html2 = $html1;
}

if ($html1 == $html2) {
    print "Input html clean\n";  
} else {
    print "Modified output:\n\n";  
    print $html2;
}

