<?php
$offset = $_GET['offset'] ?? false;

if ($offset == 0) {
    echo '{"data":{"listGoods":[{"nmID":66964167,"vendorCode":"23083","sizes":[{"sizeID":306660172,"price":8990,"discountedPrice":2157.6,"techSizeName":"25"},{"sizeID":306660173,"price":8990,"discountedPrice":2157.6,"techSizeName":"26"},{"sizeID":306660174,"price":8990,"discountedPrice":2157.6,"techSizeName":"27"},{"sizeID":306660175,"price":8990,"discountedPrice":2157.6,"techSizeName":"28"},{"sizeID":306660176,"price":8990,"discountedPrice":2157.6,"techSizeName":"29"},{"sizeID":306660177,"price":8990,"discountedPrice":2157.6,"techSizeName":"30"},{"sizeID":306660178,"price":8990,"discountedPrice":2157.6,"techSizeName":"31"},{"sizeID":306660179,"price":8990,"discountedPrice":2157.6,"techSizeName":"32"},{"sizeID":306660180,"price":8990,"discountedPrice":2157.6,"techSizeName":"33"},{"sizeID":306660181,"price":8990,"discountedPrice":2157.6,"techSizeName":"34"},{"sizeID":306660182,"price":8990,"discountedPrice":2157.6,"techSizeName":"36"},{"sizeID":306660183,"price":8990,"discountedPrice":2157.6,"techSizeName":"38"}],"currencyIsoCode4217":"RUB","discount":76,"editableSizePrice":false}]},"error":false,"errorText":""}';
} else {
    echo '{"data":{"listGoods":null},"error":false,"errorText":""}';
}
