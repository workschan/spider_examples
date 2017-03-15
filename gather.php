<?php
error_reporting(7);

const DIR = 'temp/';
if (!is_dir(DIR)) {
    mkdir(DIR);
}

$urls[] = 'http://pic.qiantucdn.com/58pic/16/95/40/79d58PICrIj_1024.jpg';
$urls[] = 'https://cbu01.alicdn.com/cms/upload/2016/883/878/2878388_1073447813.png';
$urls[] = 'http://mat1.gtimg.com/www/images/qq2012/qqLogoFilter.png';
gather($urls);

/**
 * Check if a whole jpg file.
 * @param $data string
 *
 * @return bool true or false.
 */
function isJPG($data)
{
    return mb_substr($data, -4) === 'ffd9';
}

/**
 * Check if a whole png file.
 * @param $data string
 *
 * @return bool true or false.
 */
function isPNG($data)
{
    return substr($data, -16) === '49454e44ae426082';
}

/**
 * Check if a whole gif file.
 * @param $data string
 *
 * @return bool true or false.
 */
function isGIF($data)
{
    return substr($data, -2) === '3b';
}

function isGeneralImage($data) {
    $data = preg_replace('/\/\*.*\*\//', '', $data); // remove image's comments
    $data = bin2hex($data);
    return isJPG($data) || isPNG($data) || isGIF($data);
}

$retry=[];

function gather($urls)
{
    global $retry;

    $sockets = [];
    $urlInfo = [];
    $hadDone = [];
    $datas = [];
    $errno = [];
    $errstr = [];
    $filename = [];

    foreach ($urls as $key => $value) {
        $urlInfo[$key]= parse_url($urls[$key]);
        $urlInfo[$key]['port'] = (isset ($urlInfo[$key]['port'])) ? $urlInfo[$key]['port'] :
            ($urlInfo[$key]['scheme']==='https'?443:80);
        $urlInfo[$key]['path'] = ($urlInfo[$key]['path']) ? $urlInfo[$key]['path'] : "/";

        $filename[$key] = str_replace('/', '_', substr($urlInfo[$key]['path'], 1));

        if (is_file(DIR . $filename[$key])) {
            $content = file_get_contents(DIR . $filename[$key]);
            if (isGeneralImage($content)) {
                unset($urls[$key]);
                echo 'File '.$filename[$key]." is existed\n";
            }
        }
    }

    if(count($urls) === 0) {
        return false;
    }

    foreach ($urls as $key => $value) {
        echo 'Get ' . $filename[$key] . "\n";
        $scheme = '';
        if($urlInfo[$key]['port'] === 443) {
            $scheme = 'ssl://';
        }
        $sockets[$key]= fsockopen($scheme . $urlInfo[$key]['host'], $urlInfo[$key]['port'], $errno[$key], $errstr[$key], 30);
        stream_set_timeout($sockets[$key], 60);
        stream_set_blocking($sockets[$key], 0);
        $query = (isset ($urlInfo[$key]['query'])) ? "?" . $urlInfo[$key]['query'] : "";
        fwrite($sockets[$key], "GET " . $urlInfo[$key]['path']
            . "$query HTTP/1.1\r\nHost: " . $urlInfo[$key]['host'] . "\r\nConnection: close\r\n\r\n");

    }

    $urlNum = count($urls);
    $done = false;
    while (!$done) {
        foreach ($urls as $key => $value) {

            if ($sockets[$key] && !feof($sockets[$key])) {
                //usleep(200000);
                $temp = fgets($sockets[$key], 1024);
                $datas[$key] .= $temp;

            } else if($sockets[$key]){
                fclose($sockets[$key]);
                $tempurl = $urls[$key];
                unset($sockets[$key]);

                // Just retry 3 times
                if (!isGeneralImage($datas[$key]) && $retry[$filename[$key]] < 3) {
                    $retry[$filename[$key]]++;
                    echo 'Retry '.$retry[$filename[$key]].' get ' . $filename[$key] . "\n";
                    gather([$tempurl]);

                }else {
                    if($retry[$filename[$key]] == 3) {
                        $ext = '_error.html';
                    }

                    $string = substr($datas[$key], strpos($datas[$key], "\r\n\r\n") + 4);

                    $handle = fopen(DIR . $filename[$key] . $ext, 'w');
                    fwrite($handle, $string);
                    fclose($handle);
                    echo $filename[$key] . " is saved.\n";
                }

                $hadDone[$key] = 1;
            }
        }
        $done = (array_sum($hadDone) === $urlNum);
    }
    return true;
}
