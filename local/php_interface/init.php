<?php

use Bitrix\DocumentGenerator;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\HashDataProvider;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use CRestUtil;


//use Bitrix\Main;
//use Bitrix\Main\Entity;
//use Bitrix\Crm;
//use Bitrix\Crm\ItemIdentifier;
//use Bitrix\Crm\Service\Container;
//
//
//$eventManager = Main\EventManager::getInstance();
//$eventManager->addEventHandler("bizproc", "OnCreateWorkflow", "OnCreateWorkflowHandler");
//
//function OnCreateWorkflowHandler($arEvent, $arRes)
//{
//  Main\Loader::IncludeModule('crm');
//  $child = new ItemIdentifier(156, 33);
//  foreach($parents as $parent) {
//    if ($parent->getEntityTypeId() == \CCrmOwnerType::Deal) {
//      $dealId = $parent->getEntityId();
//      break;
//    }
//  }
//  $f = fopen($_SERVER['DOCUMENT_ROOT']."/myfile.txt", "a");
//  fwrite($f, print_r($arEvent, 1));
//  fwrite($f, print_r($arRes, 1));
//  fwrite($f, print_r($dealId, 1));
//  fclose($f);
//}

EventManager::getInstance()->addEventHandler(
  "documentgenerator",
  "onBeforeProcessDocument",
  "onBeforeProcessDocumentHandler",
);

function onBeforeProcessDocumentHandler($event)
{
  Loader::includeModule('documentgenerator');
  Loader::includeModule('crm');
  $document = $event->getParameter('document');

//  $document->setFields([
//    'Speakers' => [
//      'PROVIDER' => 'Bitrix\\DocumentGenerator\\DataProvider\\ArrayDataProvider',
//      'OPTIONS' => [
//        'ITEM_NAME' => 'Speaker',
//        'ITEM_PROVIDER' => 'Bitrix\\DocumentGenerator\\DataProvider\\HashDataProvider',
//      ],
//    ],
//    'SpeakersSpeakerName' => ['VALUE' => 'Speakers.Speaker.Name'],
//    'SpeakersSpeakerPosition' => ['VALUE' => 'Speakers.Speaker.Position'],
//    'SpeakersSpeakerCompany' => ['VALUE' => 'Speakers.Speaker.Company'],
//    'SpeakersSpeakerPhoto' => ['VALUE' => 'Speakers.Speaker.Photo', 'TYPE' => 'IMAGE'],
//  ]);
//
//  $document->setValues([
//    'Speakers' => [
//      [
//        'Name' => 'Антон Горбылев',
//        'Position' => 'Разработчик',
//        'Company' => '1С-Битрикс',
//        'Photo' => 'https://my.site/anton.png',
//      ],
//      [
//        'Name' => 'Антон Горбылев2',
//        'Position' => 'Разработчик',
//        'Company' => '1С-Битрикс',
//        'Photo' => 'https://my.site/anton.png',
//      ],
//      [
//        'Name' => 'Антон Горбылев3',
//        'Position' => 'Разработчик',
//        'Company' => '1С-Битрикс',
//        'Photo' => 'https://my.site/anton.png',
//      ],
//    ],
//  ]);


  $document->setFields([
    'Table' => [
      'PROVIDER' => 'Bitrix\\DocumentGenerator\\DataProvider\\ArrayDataProvider',
      'OPTIONS' => [
        'ITEM_NAME' => 'Item',
        'ITEM_PROVIDER' => 'Bitrix\\DocumentGenerator\\DataProvider\\HashDataProvider',
      ],
    ],
    'TableItemName' => ['VALUE' => 'Table.Item.Name'],
    'TableItemImage' => ['VALUE' => 'Table.Item.Image', 'TYPE' => 'IMAGE'],
    'TableItemPrice' => ['VALUE' => 'Table.Item.Price'],
  ]);

  $document->setValues([
    'Table' => [
      [
        'Name' => 'Item name 1',
        'Price' => '$111.23',
        'Image' => 'http://192.168.3.64/upload/stamp.png'
      ],
      [
        'Name' => 'Item name 2',
        'Price' => '$222.34',
        'Image' => 'http://192.168.3.64/upload/stamp.png'
      ],
    ],
  ]);


//
//
//  $f = fopen($_SERVER['DOCUMENT_ROOT']."/myfile.txt", "a");
//  fwrite($f, print_r($fields, 1));
//  fclose($f);
}