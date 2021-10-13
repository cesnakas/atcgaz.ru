<?php
use Bitrix\Main\Loader;

IncludeModuleLangFile(__FILE__);

$arClasses = [
  "\Its\Service\CMain" => "lib/classes/main.php",
  "\Its\Service\Rest\CRest" => "lib/classes/rest/rest.php",
  "\Its\Service\Rest\CRestUser" => "lib/classes/rest/user.php",
  "\Its\Service\Agents\CAgent" => "lib/classes/agents/agent.php",
  "\Its\Service\CDeal" => "lib/classes/deal.php",
  "\Its\Service\CCompany" => "lib/classes/company.php",
  "\Its\Service\Controller\CAjax" => "lib/controller/ajax.php",
  "\Its\Service\CFileComments" => "lib/classes/fileComments.php",
  "\Its\Service\CIL" => "lib/classes/il.php",
  "\Its\Service\CILPB" => "lib/classes/ilpb.php",

  "\Its\Service\Gbo\CCompany" => "lib/classes/gbo/company.php",
  "\Its\Service\Gbo\CHelper" => "lib/classes/gbo/helper.php",
  "\Its\Service\Gbo\CRegions" => "lib/classes/gbo/regions.php",
  "\Its\Service\Gbo\CAccreditation" => "lib/classes/gbo/accreditation.php",
  "\Its\Service\Gbo\CFile" => "lib/classes/gbo/file.php",
  "\Its\Service\Gbo\CEntyty" => "lib/classes/gbo/entity.php",
  "\Its\Service\Gbo\CFileName" => "lib/classes/gbo/filename.php",
];

Loader::registerAutoLoadClasses("itsservice.lk", $arClasses);