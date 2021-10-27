<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem;

class TqPayment extends \CBitrixComponent implements Controllerable, \Bitrix\Main\Errorable
{
    private $componentPage = '';

    protected $errorCollection;

    public function configureActions()
    {
        return [
            'payment' => [ // Ajax-метод
                'prefilters' => [],
            ],
        ];
    }

    private function getDefaultParams()
    {
        $this->arResult['PARAMS'] = [
            'LAST_NAME' => [
                'TITLE' => Loc::getMessage('LAST_NAME'),
                'TYPE' => 'text',
                'REQUIRED' => true,
                'VALUE' => '',
            ],
            'NAME' => [
                'TITLE' => Loc::getMessage('NAME'),
                'TYPE' => 'text',
                'REQUIRED' => true,
                'VALUE' => '',
            ],
            'PHONE' => [
                'TITLE' => Loc::getMessage('PHONE'),
                'TYPE' => 'tel',
                'REQUIRED' => true,
                'VALUE' => '',
            ],
            'MAIL' => [
                'TITLE' => Loc::getMessage('MAIL'),
                'TYPE' => 'email',
                'REQUIRED' => true,
                'VALUE' => '',
            ],
            'NUMBER' => [
                'TITLE' => Loc::getMessage('NUMBER'),
                'TYPE' => 'number',
                'REQUIRED' => true,
                'VALUE' => ''
            ],
            'SUM' => [
                'TITLE' => Loc::getMessage('SUM'),
                'TYPE' => 'number',
                'REQUIRED' => true,
                'VALUE' => ''
            ],


        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        $this->errorCollection = new ErrorCollection();
        $this->getDefaultParams();
    }

    private function validateData($request)
    {
        foreach ($request as $param) {
            $currentProp = $this->arResult['PARAMS'][$param['name']];
            if (!isset($currentProp)) {
                $this->errorCollection[] = new Error(Loc::getMessage('ERRORS_FIELD', [
                    '#FIELD#' => $param['name']
                ]));
            }
            if ($currentProp['REQUIRED'] && empty($param['value'])) {
                $this->errorCollection[] = new Error(Loc::getMessage('ERRORS_VALUE', [
                    '#FIELD#' => $currentProp['TITLE']
                ]));
            }
            $this->arResult['PARAMS'][$param['name']]['VALUE'] = $param['value'];
        }

    }

    public function paymentAction($request)
    {
        $this->getDefaultParams();
        $this->validateData($request);
        $result = [];
        if (empty($this->errorCollection->toArray())) {
            $orderID = $this->createOrder();

            if ($orderID > 0) {
                $result = $this->getPaymentLink($orderID);
            }

        }
        return $result;
    }

    private function createOrder()
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        $order = Order::create(SITE_ID, CSaleUser::GetAnonymousUserID());
        $order->setPersonTypeId(3);
        $order->setField('PRICE', $this->arResult['PARAMS']['SUM']['VALUE']);
        $basket = Bitrix\Sale\Basket::create(SITE_ID);
        $item = $basket->createItem("catalog", 0);
        $item->setFields([
            'NAME' => 'Граверные работы',
            'PRICE' => $this->arResult['PARAMS']['SUM']['VALUE'],
            'CUSTOM_PRICE' => 'Y',
            'CURRENCY' => 'RUB',
            'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
            'QUANTITY' => 1
        ]);
        $order->setBasket($basket);
        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem();
        $paySystemService = PaySystem\Manager::getObjectById(9);
        $payment->setFields(array(
            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
            'SUM' => $this->arResult['PARAMS']['SUM']['VALUE'],
        ));
        $propertyCollection = $order->getPropertyCollection();
        foreach ($propertyCollection->getGroups() as $group) {
            foreach ($propertyCollection->getGroupProperties($group['ID']) as $property) {
                $p = $property->getProperty();
                $value = $this->arResult['PARAMS'][$p["CODE"]]['VALUE'];
                if (!empty($value)) {
                    $property->setValue($value);
                }
            }
        }
        $order->doFinalAction(true);
        $order->save();
        return $order->getId();
    }

    private function getPaymentLink($id)
    {
        ob_start();
        $orderObj = Order::load($id);
        $paymentCollection = $orderObj->getPaymentCollection();
        $payment = $paymentCollection[0];
        $service = PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $service->initiatePay($payment, $context->getRequest());
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    public function executeComponent()
    {
        CJSCore::Init(array("fx", "ajax"));
        $this->includeComponentTemplate($this->componentPage);
    }

    public function getErrors()
    {
        return $this->errorCollection->toArray();
    }

    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code);
    }

}
