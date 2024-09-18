<?php
namespace Wbs24\Wbapi;

class DbTest extends BitrixTestCase
{
    public function testSet()
    {
        // входные параметры
        $table = 'wbs24_wbapi_orders_stack';
        $order = <<<ORDER
s:1331:"{"result":{"posting_number":"51140602-0066-1","order_id":683792568,"order_number":"51140602-0066","status":"awaiting_deliver","delivery_method":{"id":22876206478000,"name":"wb","warehouse_id":22876206478000,"warehouse":"FBS","tpl_provider_id":24,"tpl_provider":"wb"},"tracking_number":"","tpl_integration_type":"wb","in_process_at":"2022-05-15T19:57:58Z","shipment_date":"2022-05-17T10:00:00Z","delivering_date":null,"provider_status":"","delivery_price":"","cancellation":{"cancel_reason_id":0,"cancel_reason":"","cancellation_type":"","cancelled_after_ship":false,"affect_cancellation_rating":false,"cancellation_initiator":""},"customer":null,"addressee":null,"products":[{"price":"532.000000","offer_id":"001223-ab","name":"Л'Этуаль","sku":545417214,"quantity":1,"mandatory_mark":["010290002480445621kr';RY:bGBN4("],"dimensions":{"height":"50.00","length":"350.00","weight":"200","width":"350.00"},"currency_code":"RUB"}],"barcodes":null,"analytics_data":null,"financial_data":null,"additional_data":[],"is_express":false,"requirements":{"products_requiring_gtd":[],"products_requiring_country":[],"products_requiring_mandatory_mark":[545417214]},"product_exemplars":{"products":[{"sku":545417214,"exemplars":[{"mandatory_mark":"010290002480445621kr';RY:bGBN4(","gtd":"","is_gtd_absent":false}]}]},"courier":null}}";
ORDER;
        $data = [
            'external_id' => '51140602-0066-1',
            'order' => $order,
        ];

        // результат для проверки
        $expectedResult = <<<RESULT
INSERT INTO `wbs24_wbapi_orders_stack` (`external_id`, `order`) VALUES ('51140602-0066-1', 's:1331:"{"result":{"posting_number":"51140602-0066-1","order_id":683792568,"order_number":"51140602-0066","status":"awaiting_deliver","delivery_method":{"id":22876206478000,"name":"wb","warehouse_id":22876206478000,"warehouse":"FBS","tpl_provider_id":24,"tpl_provider":"wb"},"tracking_number":"","tpl_integration_type":"wb","in_process_at":"2022-05-15T19:57:58Z","shipment_date":"2022-05-17T10:00:00Z","delivering_date":null,"provider_status":"","delivery_price":"","cancellation":{"cancel_reason_id":0,"cancel_reason":"","cancellation_type":"","cancelled_after_ship":false,"affect_cancellation_rating":false,"cancellation_initiator":""},"customer":null,"addressee":null,"products":[{"price":"532.000000","offer_id":"001223-ab","name":"Л\'Этуаль","sku":545417214,"quantity":1,"mandatory_mark":["010290002480445621kr\';RY:bGBN4("],"dimensions":{"height":"50.00","length":"350.00","weight":"200","width":"350.00"},"currency_code":"RUB"}],"barcodes":null,"analytics_data":null,"financial_data":null,"additional_data":[],"is_express":false,"requirements":{"products_requiring_gtd":[],"products_requiring_country":[],"products_requiring_mandatory_mark":[545417214]},"product_exemplars":{"products":[{"sku":545417214,"exemplars":[{"mandatory_mark":"010290002480445621kr\';RY:bGBN4(","gtd":"","is_gtd_absent":false}]}]},"courier":null}}";') ON DUPLICATE KEY UPDATE `external_id` = '51140602-0066-1', `order` = 's:1331:"{"result":{"posting_number":"51140602-0066-1","order_id":683792568,"order_number":"51140602-0066","status":"awaiting_deliver","delivery_method":{"id":22876206478000,"name":"wb","warehouse_id":22876206478000,"warehouse":"FBS","tpl_provider_id":24,"tpl_provider":"wb"},"tracking_number":"","tpl_integration_type":"wb","in_process_at":"2022-05-15T19:57:58Z","shipment_date":"2022-05-17T10:00:00Z","delivering_date":null,"provider_status":"","delivery_price":"","cancellation":{"cancel_reason_id":0,"cancel_reason":"","cancellation_type":"","cancelled_after_ship":false,"affect_cancellation_rating":false,"cancellation_initiator":""},"customer":null,"addressee":null,"products":[{"price":"532.000000","offer_id":"001223-ab","name":"Л\'Этуаль","sku":545417214,"quantity":1,"mandatory_mark":["010290002480445621kr\';RY:bGBN4("],"dimensions":{"height":"50.00","length":"350.00","weight":"200","width":"350.00"},"currency_code":"RUB"}],"barcodes":null,"analytics_data":null,"financial_data":null,"additional_data":[],"is_express":false,"requirements":{"products_requiring_gtd":[],"products_requiring_country":[],"products_requiring_mandatory_mark":[545417214]},"product_exemplars":{"products":[{"sku":545417214,"exemplars":[{"mandatory_mark":"010290002480445621kr\';RY:bGBN4(","gtd":"","is_gtd_absent":false}]}]},"courier":null}}";'
RESULT;

        // заглушки
        $CDBResultStub = $this->createMock(\CDBResult::class);
        $CDBResultStub->method('Fetch')
            ->willReturn(false);

        $CDatabaseStub = $this->createMock(\CDatabase::class);
        $CDatabaseStub->method('Query')
            ->willReturn($CDBResultStub);

        // проверка
        $CDatabaseStub->expects($this->once())
            ->method('Query')
            ->with($this->equalTo($expectedResult));

        // вычисление результата
        $db = new Db([
            'DB' => $CDatabaseStub,
        ]);
        $db->set($table, $data);
    }
}
