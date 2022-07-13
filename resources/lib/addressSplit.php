<?php

use VIISON\AddressSplitter\AddressSplitter;

$address = SdkRestApi::getParam('address');

try {
    return [
        'success' => true,
        'content' => AddressSplitter::splitAddress($address)
    ];
} catch (Exception $exception) {
    return [
        'success' => false,
        'content' => $exception->getMessage()
    ];
}



