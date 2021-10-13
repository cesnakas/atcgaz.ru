<?php
use Bitrix\Main\Application,
    Bitrix\Main\Loader,
    Its\Service\CMain;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!Loader::IncludeModule('itsservice.lk')) die();

$request = Application::getInstance()->getContext()->getRequest();
$query = $request->getValues();

$dealId = intval($query['deal_id']);

$arResultBP = CMain::getDataHttp('bizproc.workflow.instances', [
  'select' => ['ID'],
  'filter' => [
    'ENTITY' => 'CCrmDocumentDeal',
    'DOCUMENT_ID' => 'DEAL_'.$dealId
  ],
]);

$arRequests = [];
foreach($arResultBP['result'] as $arBP) {
  $arRequests[$arBP['ID']] = 'bizproc.workflow.kill?ID='.$arBP['ID'];
}
if(!empty($arRequests)) {
  $arParams = [
    'halt' => false,
    'cmd' => [
      $arRequests
    ],
  ];
  $arResultBP = CMain::getDataHttp('bizproc.workflow.kill', $arParams);
}
