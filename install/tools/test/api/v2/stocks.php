<?php
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
    echo '{"error":true,"errorText":"string","additionalErrors":[{}],"data":{"error":[{"barcode":"123456789","err":"указан отрицательный остаток"}]}}';
}
if ($method == 'GET') {
    echo '{"total":1,"stocks":[{"subject":"Носки","brand":"Lincoln&Sharks","name":"носки","size":"10,5","barcode":"2039893700627","barcodes":["2039893700627"],"article":"one-ring-7548","stock":1,"warehouseName":"string","warehouseId":205384680,"id":205384680,"chrtId":205384680,"nmId":114296358,"isCargoDelivery":false},{"subject":"Носки 2","brand":"Lincoln&Sharks","name":"носки 2","size":"11,5","barcode":"2039893700628","barcodes":["2039893700628"],"article":"22224435","stock":2,"warehouseName":"string","warehouseId":205384680,"id":205384681,"chrtId":205384681,"nmId":114296359,"isCargoDelivery":false}]}';
}
