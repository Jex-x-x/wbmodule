<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo '{"stocks":[{"sku":"5032781142964","amount":10}]}';
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    echo '{"code":"Success","message":""}';
}
