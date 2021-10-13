<?php
use Bitrix\Main\Application,
    Bitrix\Main\Loader,
    Its\Service\CIL;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!Loader::IncludeModule('itsservice.lk')) die();

$request = Application::getInstance()->getContext()->getRequest();
$query = $request->getValues();

if(!empty($query['id']) && !empty($query['name']) && !empty($query['url'])) {
  $dealId = htmlentities($query['id']);
  $dealId = str_replace("&nbsp;",'',$dealId);
  $dealId = intval($dealId);

  $name = htmlspecialcharsbx($query['name']);
  $url = htmlspecialcharsbx($query['url']);

  $f = fopen($_SERVER['DOCUMENT_ROOT']."/getil.txt", "a");
  fwrite($f, print_r($dealId.PHP_EOL, 1));
  fwrite($f, print_r($name.PHP_EOL, 1));
  fwrite($f, print_r($url.PHP_EOL, 1));
  fclose($f);

  if($dealId > 0) {
    CIL::updloadFile($dealId, $name, $url);
  }
} elseif($query['dealId']) {
  $dealId = intval($query['dealId']);
  if($dealId > 0) {
    $CLI = new CIL($dealId);
  }
}