<?php

class SenderTriggerSaleStatusOrderChangeExt extends \Bitrix\Sender\TriggerConnector
{
	public function getName()
	{
		return 'Изменения статуса заказа(+подробности заказа)';
	}

	public function getCode()
	{
		return "order_status_change";
	}

	public function getEventModuleId()
	{
		return 'sale';
	}

	public function getEventType()
	{
		return "OnSaleStatusOrderChange";
	}

	/** @return bool */
	public static function canBeTarget()
	{
		return true;
	}

	public function filter()
	{
		$eventData = $this->getParam('EVENT');
		$statusId = $this->getFieldValue('STATUS_ID', null);

		if(!($eventData['ENTITY'] instanceof \Bitrix\Sale\Order))
		{
			return false;
		}

		if($statusId != $eventData['ENTITY']->getField('STATUS_ID'))
		{
			return false;
		}

		return $this->filterConnectorData();
	}

	public function getConnector()
	{
		$connector = new \Bitrix\Sale\Sender\ConnectorOrder;
		$connector->setModuleId('sale');

		return $connector;
	}

	/** @return array */
	public function getProxyFieldsFromEventToConnector()
	{
		$eventData = $this->getParam('EVENT');
		return array('ID' => $eventData['ENTITY']->getId(), 'LID' => $this->getSiteId());
	}


	/**
	 * @return array
	 */
	public function getPersonalizeFields()
	{
		$result = array(
			'ORDER_ID' => ''
		);

		$eventData = $this->getParam('EVENT');
		if($eventData['ENTITY'] instanceof \Bitrix\Sale\Order)
		{
			$result['ORDER_ID'] = $eventData['ENTITY']->getId();
		}
		
		
		//////
		$arID = array();
		$arBasketItems = array();
		$dbBasketItems = CSaleBasket::GetList(
		     array(
		                "NAME" => "ASC",
		                "ID" => "ASC"
		             ),
		     array(
		                //"FUSER_ID" => CSaleBasket::GetBasketUserID(),
		                "LID" => SITE_ID,
		                "ORDER_ID" => $result['ORDER_ID']
		             ),
		     false,
		     false,
		     array("ID", "CALLBACK_FUNC", "MODULE", "PRODUCT_ID", "QUANTITY", "PRODUCT_PROVIDER_CLASS")
		             );
		while ($arItems = $dbBasketItems->Fetch())
		{
		     if ('' != $arItems['PRODUCT_PROVIDER_CLASS'] || '' != $arItems["CALLBACK_FUNC"])
		     {
		          CSaleBasket::UpdatePrice($arItems["ID"],
		                                 $arItems["CALLBACK_FUNC"],
		                                 $arItems["MODULE"],
		                                 $arItems["PRODUCT_ID"],
		                                 $arItems["QUANTITY"],
		                                 "N",
		                                 $arItems["PRODUCT_PROVIDER_CLASS"]
		                                 );
		          $arID[] = $arItems["ID"];
		     }
		}
		if (!empty($arID))
		     {
			     $dbBasketItems = CSaleBasket::GetList(
			     array(
			          "NAME" => "ASC",
			          "ID" => "ASC"
			          ),
			     array(
			          "ID" => $arID,
			          "ORDER_ID" => $result['ORDER_ID']
			          ),
			        false,
			        false,
			        array("NAME", "DETAIL_PAGE_URL")
			                );
			while ($arItems = $dbBasketItems->Fetch())
			{
			    $arBasketItems[] = $arItems;
			}
		}
		$orderItems = '';
		foreach ($arBasketItems as $key => $value) 
		{
			$orderItems .= '<a href="http://oniks-online.com.ua/'.$value["DETAIL_PAGE_URL"].'">'.$value["NAME"].'</a><br/>';
		}
		 $result["ORDER_ITEM_LINKS"] = $orderItems;
		

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getPersonalizeList()
	{
		return array(
			array(
				'CODE' => 'ORDER_ID',
				'NAME' => 'Номер заказа',
				'DESC' => 'Номер заказа - описание'
			),
			array(
				'CODE'=>'ORDER_ITEM_LINKS',
				'NAME'=>'Ссылки на товары пользователя',
				'DESC'=>'Ссылки на товары пользователя,  что он купил'
			),
		);
	}

	public function getForm()
	{
		$statusInput = '';
		$statusDb = \Bitrix\Sale\Internals\StatusLangTable::getList(array(
			'select' => array('STATUS_ID', 'NAME'),
			'filter' => array('=LID' => LANGUAGE_ID, '=STATUS_ID' => \Bitrix\Sale\OrderStatus::getAllStatuses()),
			'order' => array('STATUS.SORT')
		));
		while($status = $statusDb->fetch())
		{
			$selected = $status['STATUS_ID'] == $this->getFieldValue('STATUS_ID') ? ' selected' : '';
			$statusInput .= '<option value="' . $status['STATUS_ID'] . '"' . $selected . '>'
				. htmlspecialcharsbx($status['NAME'])
				. '</option>';
		}
		$statusInput = '<select name="' . $this->getFieldName('STATUS_ID') . '">' . $statusInput . '</select>';

		return '
			<table>
				<tr>
					<td>'.'Статус'.': </td>
					<td>'.$statusInput.'</td>
				</tr>
			</table>
		';
	}
}
