<?php

try {
    set_time_limit(0);

    $orderId = SdkRestApi::getParam('order_id');
    $username = SdkRestApi::getParam('username');
    $token = SdkRestApi::getParam('access_key');
    $file_content = SdkRestApi::getParam('file_content');
    $environment = SdkRestApi::getParam('environment');
    $fileName = SdkRestApi::getParam('file_name');
    $version = SdkRestApi::getParam('shipping_version');
    $channel = SdkRestApi::getParam('channel');
    
    $file = writeDataInFile($fileName, $file_content);

    $url = $environment . "/sales_ws?action=importitemshippingstatus";
    $url .= "&login=" . $username;
    $url .= "&pwd=" . $token;
    $url .= "&version=" . $version;
    $url .= "&channel=" . $channel;

    $shippingInformationResponse = sendShippingInformation($url, $fileName);
    unlink(realpath($fileName));

    if (isset($shippingInformationResponse->error)) {
        return
            [
                "success" => false,
                "code" => $shippingInformationResponse->error->code,
                "message" => implode(" \n", convertToArray($shippingInformationResponse->error->details->detail)),
                "orderId" => $orderId,
                "request" => $file_content
            ];
    }

    return [
        "success" => true,
        "orderId" => $orderId,
        "message" => $shippingInformationResponse->response->status,
        "request" => $file_content
    ];
} catch (Exception $e) {
    unlink(realpath(SdkRestApi::getParam('file_name')));
    return
        [
            'success' => false,
            "orderId" => $orderId,
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];
}


function writeDataInFile($fileName, $file_content)
{
    $file = fopen($fileName, "w");
    fwrite($file, $file_content);
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

function sendShippingInformation($url, $fileName)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, array("file" => new CURLFile(realpath($fileName))));
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
