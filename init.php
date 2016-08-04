<?
//Изменения статуса заказа c подробностями
AddEventHandler("sender", "OnTriggerList", array("OniksSenderEventHandler","onTriggerList"));
class OniksSenderEventHandler
{
	public static function onTriggerList($data)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/trigger_order_status_change.php');
		$data['TRIGGER'] = 'SenderTriggerSaleStatusOrderChangeExt';
		return $data;
	}
}
?>
