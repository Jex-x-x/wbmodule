<?php
switch ($_REQUEST['options_type']) {
    case 'group_rights':
        include('options/group_rights_options.php');
        break;
    case 'base':
        include('options/base_options.php');
        break;
    case 'order':
        include('options/order_options.php');
        break;
    case 'price':
        include('options/price_options.php');
        break;
    case 'stock':
        include('options/stock_options.php');
        break;
    default:
        include('options/old_options.php');
        break;
}
