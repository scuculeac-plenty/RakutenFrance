<?php

try {
    set_time_limit(0);

    $username = SdkRestApi::getParam('username');
    $token = SdkRestApi::getParam('access_key');
    $environment = SdkRestApi::getParam('environment');
    $fileName = SdkRestApi::getParam('file_name');
    $fileContent = SdkRestApi::getParam('file_content');
    $version = SdkRestApi::getParam('stock_version');
    $profileId = SdkRestApi::getParam('profile_id');
    $mappingalias = SdkRestApi::getParam('mapping_alias');
    $channel = SdkRestApi::getParam('channel');

    $file = writeDataInFile($fileName, $fileContent);

    $url = $environment . "/stock_ws?action=import";
    $url .= "&login=" . $username;
    $url .= "&pwd=" . $token;
    $url .= "&version=" . $version;
    $url .= "&channel=" . $channel;
    $url .= "&profileid=" . $profileId;
    $url .= "&mappingalias=" . $mappingalias;

    $stockUpdateResponse = uploadFile($url, $fileName);
    unlink(realpath($fileName));

    return [
        "success" => true,
        "message" => $stockUpdateResponse
    ];
} catch (Exception $e) {
    $fileName = SdkRestApi::getParam('file_name');
    unlink(realpath($fileName));

    return
        [
            'success' => false,
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];
}

function writeDataInFile($fileName, $fileContent)
{
    $file = fopen($fileName, "w");
    fwrite($file, $fileContent);
    fclose($file);

    return $file;
}

function convertToArray($detailsInformation)
{
    if (is_array($detailsInformation)) {
        return $detailsInformation;
    }

    return [$detailsInformation];
}

function uploadFile($url, $fileName)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, ["file" => new CURLFile(realpath($fileName))]);
    $response = curl_exec($curl);

    if (curl_error($curl)) {
        throw new Exception(curl_error($curl));
    }

    $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $data = json_decode($json);
    curl_close($curl);

    return $data;
}
