<?php

namespace Its\Service\Controller;

use Bitrix\Main\Engine\Controller,
  Its\Service\CMain,
  Its\Service\CDeal,
  Its\Service\CCompany,
  CFile,
  Its\Service\CFileComments;
use Bitrix\Main\Web\MimeType;

class CAjax extends Controller
{
  public function applyAction()
  {
    $userId = $this->getCurrentUser()->getId();
    $request = $this->getRequest();
    $query = $request->getValues();
    $event = $query['event'];

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $systemFileId = intval($query['id']);
    $systemDealId = intval($query['dealId']);

    $dealIDs = [$systemDealId];

    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);

    $arDeal = $hlDealClassName::getlist([
      'select' => ['ID', 'UF_XML_ID', 'UF_CATEGORY_ID', 'UF_COMPANY_ID', 'UF_DEAL_RETOOL', 'UF_POST_APPROVED'],
      'filter' => ['ID' => $systemDealId]
    ])->fetch();

    if (CDeal::INSTALLATION_CENTER_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
      $arDealRetool = $hlDealClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_DEAL_RETOOL' => $dealIDs]
      ])->fetchAll();

      foreach ($arDealRetool as $deal) {
        $dealIDs[] = $deal['ID'];
      }
    }

    if (CDeal::TS_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
      $dealIDs[] = $arDeal['UF_DEAL_RETOOL'];

      $arDealRetool = $hlDealClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_DEAL_RETOOL' => $dealIDs]
      ])->fetchAll();

      foreach ($arDealRetool as $deal) {
        $dealIDs[] = $deal['ID'];
      }
    }

    //список статуса файлов
    $arFileType = CMain::getEnumField(25);

    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
    $arFiles = $hlFileClassName::getlist([
      'select' => ['*'],
      'filter' => [
        'UF_DEAL_ID' => $dealIDs,
        'UF_IS_AGREEMENT' => false,
      ]
    ])->fetchAll();

    $countSuccessFile = $countTSFile = $countTSSuccessFile = 0;
    $checkFile = false;
    $currentFile = [];

    foreach ($arFiles as $arFile) {
      if ($arFile['UF_DEAL_ID'] == $arDeal['ID']) {
        $countTSFile++;
      }

      if ($arFile['UF_STATUS'] == $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_SUCCESS]['ID']) {
        if ($arFile['UF_DEAL_ID'] == $arDeal['ID']) {
          $countTSSuccessFile++;
        }
        $countSuccessFile++;
      }

      if ($systemFileId == $arFile['ID']) {
        $currentFile = $arFile;
        $checkFile = true;
      }
    }

    $fields = [];

    $hlCompanyClassName = CMain::getHlEntity(CCompany::HL_COMPANY_NAME);

    $arUserInfo = CMain::getUserInfo($userId);

    if ($checkFile) {
      switch ($event) {
        case 'successDoc':
          $countTSSuccessFile++;
          $arFiles = $hlFileClassName::getlist([
            'select' => ['ID'],
            'filter' => ['UF_DEAL_ID' => $systemDealId, 'ID' => $systemFileId]
          ])->fetch();
          if ($arFiles['ID'] > 0) {
            $arFields = [
              'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_SUCCESS]['ID'],
            ];
            if ($arUserInfo['IS_PRE_APPROVED']) $arFields['UF_IS_PRE_APPROVED'] = true;

            $hlFileClassName::Update($systemFileId, $arFields);
          }

          if ($countTSFile == $countTSSuccessFile && $countTSSuccessFile > 0) {
            if (CDeal::TS_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
              $arParams = [
                'id' => $arDeal['UF_XML_ID'],
                'fields' => ['STAGE_ID' => CDeal::TS_DEAL_STAGE_ALL_DOCS_APPROVED],
                'params' => [
                  'REGISTER_SONET_EVENT' => 'Y'
                ]
              ];
              CMain::getDataHttp('crm.deal.update', $arParams);
            }

            if (CDeal::INSTALLATION_CENTER_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {

              $params = [
                'deal_update' => "crm.deal.update?".http_build_query([
                    'id' => $arDeal['UF_XML_ID'],
                    'fields' => ['STAGE_ID' => CDeal::SUBSIDION_DEAL_STAGE_DOCS_UNDER_REVIEW],
                    'params' => [
                      'REGISTER_SONET_EVENT' => 'Y'
                    ]
                  ]),
                'livemessage_add' => "crm.livefeedmessage.add?".http_build_query([
                    'fields' => [
                      'ENTITYTYPEID' => 2,
                      'ENTITYID' => $arDeal['UF_XML_ID'],
                      'POST_TITLE' => 'Все документы одобрены',
                      'MESSAGE' => "Все документы одобрены",
                    ]
                  ]),
              ];

              $arParams = [
                'halt' => false,
                'cmd' => $params,
              ];
              CMain::getDataHttp('batch', $arParams);
            }

            if (CDeal::ACCREDITATION_DEAL_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
              $params = [
                'deal_update' => "crm.deal.update?".http_build_query([
                    'id' => $arDeal['UF_XML_ID'],
                    'fields' => ['STAGE_ID' => CDeal::ACCREDITATION_DEAL_STAGE_SIGNING_AGREEMENT],
                    'params' => [
                      'REGISTER_SONET_EVENT' => 'Y'
                    ]
                  ]),
                'livemessage_add' => "crm.livefeedmessage.add?".http_build_query([
                    'fields' => [
                      'ENTITYTYPEID' => 2,
                      'ENTITYID' => $arDeal['UF_XML_ID'],
                      'POST_TITLE' => 'Все документы одобрены',
                      'MESSAGE' => "Все документы одобрены",
                    ]
                  ]),
                'get_deal' => "crm.deal.get?".http_build_query(['id' => $arDeal['UF_XML_ID']]),
                'notify' => "im.notify.system.add?".http_build_query([
                    'USER_ID' => '$result[get_deal][ASSIGNED_BY_ID]',
                    'MESSAGE' => "Все документы одобрены <a href='https://its-online.bitrix24.ru/crm/deal/details/" . $arDeal['UF_XML_ID'] . "/' target='_blank'>по сделке</a> "
                  ]),
              ];

              $arParams = [
                'halt' => false,
                'cmd' => $params,
              ];
              CMain::getDataHttp('batch', $arParams);
            }
          }

          if ($arDeal['UF_COMPANY_ID'] > 0) {
            $hlCompanyClassName::update($arDeal['UF_COMPANY_ID'], ['UF_IS_NEW' => false]);
          }
          break;
        case 'rejectDoc':
          $arFiles = $hlFileClassName::getlist([
            'select' => ['ID'],
            'filter' => ['UF_DEAL_ID' => intval($query['dealId']), 'ID' => $systemFileId]
          ])->fetch();
          if ($arFiles['ID'] > 0) {
            $arFields = [
              'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_REJECT]['ID'],
              'UF_FILE_SIGNED' => '',
            ];
            $hlFileClassName::Update($systemFileId, $arFields);

            $arAgreementFile = $hlFileClassName::getlist([
              'select' => ['ID'],
              'filter' => ['UF_DEAL_ID' => intval($query['dealId']), 'UF_IS_AGREEMENT' => true]
            ])->fetch();
            if($arAgreementFile['ID'] > 0) {
              $hlFileClassName::delete($arAgreementFile['ID']);
            }

            if(!empty($query['comment'])) {
              $hlFileCommentsClassName = CMain::getHlEntity(CFileComments::HL_FILE_COMMENTS_NAME);
              $arFields = [
                'UF_MESSAGE' => $query['comment'],
                'UF_FILE_ID' => $systemFileId,
                'UF_USER_ID' => $userId
              ];

              $hlFileCommentsClassName::add($arFields);
            }
          }

          if (CDeal::ACCREDITATION_DEAL_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
            $stageId = CDeal::ACCREDITATION_DEAL_STAGE_CHECK_DOCUMENTS;
          }

          if (CDeal::INSTALLATION_CENTER_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
            $stageId = CDeal::SUBSIDION_DEAL_STAGE_DOCS_UNDER_REVIEW;
          }
          if (CDeal::TS_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
            $stageId = CDeal::TS_DEAL_STAGE_DOCS_IN_CORRECTION;
          }

          if (!empty($stageId)) {
            $fields['STAGE_ID'] = $stageId;
            $fields['UF_CRM_1615135271'] = '';
          }

          $params = [
            'deal_update' => "crm.deal.update?".http_build_query([
                'id' => $arDeal['UF_XML_ID'],
                'fields' => $fields,
                'params' => [
                  'REGISTER_SONET_EVENT' => 'Y'
                ]
              ]),
            'livemessage_add' => "crm.livefeedmessage.add?".http_build_query([
                'fields' => [
                  'ENTITYTYPEID' => 2,
                  'ENTITYID' => $arDeal['UF_XML_ID'],
                  'POST_TITLE' => 'Необходимо исправить документ',
                  'MESSAGE' => "Необходимо исправить документ <a href='https://xn--j1ab.xn--90ad8a.xn--p1ai/" . CFile::GetPath($currentFile['UF_FILE']) . "' target='_blank'>" . $currentFile['UF_NAME'] . "</a>
загрузив исправленную версию по ссылке <a href='https://xn--j1ab.xn--90ad8a.xn--p1ai/restapi/correctiondoc.php?dealId=" . $systemDealId . "&fileId=" . $systemFileId . "' target='_blank'>Замена</a>

Коментарий: " . $query['comment'],
                ]
              ]),
            'get_deal' => "crm.deal.get?".http_build_query(['id' => $arDeal['UF_XML_ID']]),
            'notify' => "im.notify.system.add?".http_build_query([
                'USER_ID' => '$result[get_deal][ASSIGNED_BY_ID]',
                'MESSAGE' => "Необходимо исправить документ <a href='https://xn--j1ab.xn--90ad8a.xn--p1ai/" . CFile::GetPath($currentFile['UF_FILE']) . "' target='_blank'>" . $currentFile['UF_NAME'] . "</a>
<a href='https://its-online.bitrix24.ru/crm/deal/details/" . $arDeal['UF_XML_ID'] . "/' target='_blank'>для сделки</a> 
загрузив исправленную версию по ссылке <a href='https://xn--j1ab.xn--90ad8a.xn--p1ai/restapi/correctiondoc.php?dealId=" . $systemDealId . "&fileId=" . $systemFileId . "' target='_blank'>Замена</a>

Коментарий: " . $query['comment']
              ]),
          ];

          $arParams = [
            'halt' => false,
            'cmd' => $params,
          ];
          CMain::getDataHttp('batch', $arParams);

          if ($arDeal['UF_COMPANY_ID'] > 0) {
            $hlCompanyClassName::update($arDeal['UF_COMPANY_ID'], ['UF_IS_NEW' => false]);
          }

          break;
      }
    }

    return ['response' => 'success'];
  }

  public function createArhiveAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $systemDealId = intval($query['dealId']);

    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);

    $rsSystemFiles = $hlFileClassName::getlist([
      'select' => ['UF_FILE', 'UF_FILE_SIGNED'],
      'filter' => [
        'UF_DEAL_ID' => $systemDealId,
        'UF_IS_AGREEMENT' => false,
      ]
    ]);
    $arFileIDs = [];
    while ($arSystemFiles = $rsSystemFiles->fetch()) {
      $arFileIDs[] = $arSystemFiles['UF_FILE'];
      if ($arSystemFiles['UF_FILE_SIGNED'] > 0) {
        $arFileIDs[] = $arSystemFiles['UF_FILE_SIGNED'];
      }
    }

    $zipFileUri = CMain::createZip($arFileIDs, $systemDealId);

    return ['response' => 'success', 'file' => $zipFileUri, 'fileName' => basename($zipFileUri)];
  }

  public function createArhiveTSAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $systemDealId = intval($query['dealId']);
    $arDealIDs = [$systemDealId];

    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);

    $arDeal = $hlDealClassName::getlist([
      'select' => ['ID'],
      'filter' => ['UF_DEAL_RETOOL' => $systemDealId]
    ])->fetchAll();
    foreach ($arDeal as $deal) {
      $arDealIDs[] = $deal['ID'];
    }

    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
    $rsSystemFiles = $hlFileClassName::getlist([
      'order' => ['UF_DEAL_ID' => 'asc'],
      'select' => ['UF_FILE', 'UF_FILE_SIGNED'],
      'filter' => [
        'UF_DEAL_ID' => $arDealIDs,
        'UF_IS_AGREEMENT' => false,
      ]
    ]);
    $arFileIDs = [];
    while ($arSystemFiles = $rsSystemFiles->fetch()) {
      $arFileIDs[] = $arSystemFiles['UF_FILE'];
      if ($arSystemFiles['UF_FILE_SIGNED'] > 0) {
        $arFileIDs[] = $arSystemFiles['UF_FILE_SIGNED'];
      }
    }

    $zipFileUri = CMain::createZip($arFileIDs, $systemDealId);

    return ['response' => 'success', 'file' => $zipFileUri, 'fileName' => basename($zipFileUri)];
  }

  public function loadAgreementAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);

    $arFileType = CMain::getEnumField(25);

    $values = $request->getFileList()->toArray()['file'];

    $arFile = CFile::MakeFileArray($values['tmp_name']);
    $mimeTypeList = MimeType::getMimeTypeList();
    foreach ($mimeTypeList as $k => $v) {
      if($v == $arFile['type']) {
        $extensionFile = $k;
        break;
      }
    }
    $arFile['name'] = 'Соглашение.'.$extensionFile;

    if (!empty($arFile)) {
      $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
      $currentFile = $hlFileClassName::getList([
        'select' => ['ID'],
        'filter' => [
          'UF_DEAL_ID' => $dealId,
          'UF_IS_AGREEMENT' => true,
        ]
      ])->fetch();

      if(empty($currentFile)) {
        $arNewFile = [
          'UF_NAME' => $arFile['name'],
          'UF_DOWNLOAD_URL' => '',
          'UF_FILE' => $arFile,
          'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
          'UF_DEAL_ID' => $dealId,
          'UF_IS_AGREEMENT' => true,
        ];

        $hlFileClassName::add($arNewFile);
      } else {
        $arUpdate = [
          'UF_FILE' => $arFile,
        ];

        $hlFileClassName::update($currentFile['ID'], $arUpdate);
      }


      $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
      $arDeal = $hlDealClassName::getlist([
        'select' => [
          'UF_XML_ID'
        ],
        'filter' => ['ID' => $dealId],
      ])->fetch();

      $base64 = base64_encode(file_get_contents($values['tmp_name']));

      $params = [
        'deal_update' => "crm.deal.update?".http_build_query([
            'id' => $arDeal['UF_XML_ID'],
            'fields' => [
              'UF_CRM_1620127099' => [
                'fileData' => ['Соглашение.pdf', $base64]
              ]
            ]
          ]),
        'get_deal' => "crm.deal.get?".http_build_query(['id' => $arDeal['UF_XML_ID']]),
        'livemessage_add' => "crm.livefeedmessage.add?".http_build_query([
            'fields' => [
              'ENTITYTYPEID' => 2,
              'ENTITYID' => $arDeal['UF_XML_ID'],
              'POST_TITLE' => 'Документ об аккредитации получен в поле "Соглашение (файл)"',
              'MESSAGE' => 'Документ об аккредитации получен в поле <a href="https://its-online.bitrix24.ru$result[get_deal][UF_CRM_1620127099][showUrl]" target="_blank">"Соглашение (файл)"</a>',
            ]
          ]),
        'notify' => "im.notify.system.add?".http_build_query([
            'USER_ID' => '$result[get_deal][ASSIGNED_BY_ID]',
            'MESSAGE' => "Документ об аккредитации получен в поле \"Соглашение (файл)\" <a href='https://its-online.bitrix24.ru/crm/deal/details/" . $arDeal['UF_XML_ID'] . "/' target='_blank'>для сделки</a>"
          ]),
      ];

      $arParams = [
        'halt' => false,
        'cmd' => $params,
      ];
      CMain::getDataHttp('batch', $arParams);
    }
  }

  public function loadConclusionAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);

    $arFileType = CMain::getEnumField(25);

    $values = $request->getFileList()->toArray()['file'];

    $arFile = CFile::MakeFileArray($values['tmp_name']);
    $arFile['name'] = $values['name'];

    if (!empty($arFile)) {
      $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
      $arNewFile = [
        'UF_NAME' => $values['name'],
        'UF_DOWNLOAD_URL' => '',
        'UF_FILE' => $arFile,
        'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
        'UF_DEAL_ID' => $dealId,
        'UF_IS_CET_CONCLUSION' => true,
      ];

      $hlFileClassName::add($arNewFile);
    }
  }

  public function loadConclusionSubsidionAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);

    $arFileType = CMain::getEnumField(25);

    $values = $request->getFileList()->toArray()['file'];

    $arFile = CFile::MakeFileArray($values['tmp_name']);

    $mimeTypeList = MimeType::getMimeTypeList();
    foreach ($mimeTypeList as $k => $v) {
      if($v == $arFile['type']) {
        $extensionFile = $k;
        break;
      }
    }
    $arFile['name'] = 'Заключение о выдаче субсидии.'.$extensionFile;

    if (!empty($arFile)) {
      $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
      $arNewFile = [
        'UF_NAME' => 'Заключение о выдаче субсидии',
        'UF_DOWNLOAD_URL' => '',
        'UF_FILE' => $arFile,
        'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
        'UF_DEAL_ID' => $dealId,
        'UF_IS_CONCLUSION_SUBSIDION' => true,
      ];

      $hlFileClassName::add($arNewFile);
    }
  }

  public function loadAgreementSubsidionAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);

    $arFileType = CMain::getEnumField(25);

    $values = $request->getFileList()->toArray()['file'];

    $arFile = CFile::MakeFileArray($values['tmp_name']);
    $mimeTypeList = MimeType::getMimeTypeList();
    foreach ($mimeTypeList as $k => $v) {
      if($v == $arFile['type']) {
        $extensionFile = $k;
        break;
      }
    }
    $arFile['name'] = 'Соглашение о субсидии.'.$extensionFile;
    if (!empty($arFile)) {
      $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
      $currentFile = $hlFileClassName::getList([
        'select' => ['ID'],
        'filter' => [
          'UF_DEAL_ID' => $dealId,
          'UF_IS_AGREEMENT_SUBSIDION' => true,
        ]
      ])->fetch();

      if(empty($currentFile)) {
        $arNewFile = [
          'UF_NAME' => $arFile['name'],
          'UF_DOWNLOAD_URL' => '',
          'UF_FILE' => $arFile,
          'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
          'UF_DEAL_ID' => $dealId,
          'UF_IS_AGREEMENT_SUBSIDION' => true,
        ];

        $hlFileClassName::add($arNewFile);
      } else {
        $arUpdate = [
          'UF_FILE' => $arFile,
          'UF_FILE_SIGNED' => '',
        ];

        $hlFileClassName::update($currentFile['ID'], $arUpdate);
      }

      $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
      $arDeal = $hlDealClassName::getlist([
        'select' => [
          'UF_XML_ID',
          'UF_NAME'
        ],
        'filter' => ['ID' => $dealId],
      ])->fetch();

      $base64 = base64_encode(file_get_contents($values['tmp_name']));

      $params = [
        'deal_update' => "crm.deal.update?".http_build_query([
            'id' => $arDeal['UF_XML_ID'],
            'fields' => [
              'UF_CRM_1622202964319' => [
                'fileData' => [$arFile['name'], $base64]
              ]
            ]
          ]),
        'get_deal' => "crm.deal.get?".http_build_query(['id' => $arDeal['UF_XML_ID']]),
        'livemessage_add' => "crm.livefeedmessage.add?".http_build_query([
            'fields' => [
              'ENTITYTYPEID' => 2,
              'ENTITYID' => $arDeal['UF_XML_ID'],
              'POST_TITLE' => 'Соглашение о субсидии получено в поле "Соглашение об аккредитации"',
              'MESSAGE' => 'Соглашение о субсидии получено в поле "Соглашение об аккредитации"
Файл соглашения пожно посмотреть <a href="$result[get_deal][UF_CRM_1622202964319][showUrl]" target="_blank">здесь</a>',
            ]
          ]),
        'notify' => "im.notify.system.add?".http_build_query([
            'USER_ID' => '$result[get_deal][ASSIGNED_BY_ID]',
            'MESSAGE' => 'Соглашение о субсидии получено в поле "Соглашение об аккредитации"<a href="https://its-online.bitrix24.ru/crm/deal/details/' . $arDeal['UF_XML_ID'] . '/" target="_blank">'.$arDeal['UF_NAME'].'</a>
Файл соглашения пожно посмотреть <a href="$result[get_deal][UF_CRM_1622202964319][showUrl]" target="_blank">здесь</a>',
          ]),
      ];

      $arParams = [
        'halt' => false,
        'cmd' => $params,
      ];
      $res = CMain::getDataHttp('batch', $arParams);

      CMain::sendDocumentsSignature($dealId);
    }
  }

  public function companyAccreditableAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $companyId = intval($query['companyId']);
    $dealId = intval($query['dealId']);

    $hlCompanyClassName = CMain::getHlEntity(CCompany::HL_COMPANY_NAME);
    $bitrixCompanyId = $hlCompanyClassName::getlist([
      'select' => ['UF_XML_ID'],
      'filter' => ['ID' => $companyId]
    ])->fetch()['UF_XML_ID'];

    $hlCompanyClassName::update($bitrixCompanyId, ['UF_ACCREDITED' => true]);

    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
    $arDeal = $hlDealClassName::getlist([
      'select' => [
        'UF_XML_ID'
      ],
      'filter' => ['ID' => $dealId],
    ])->fetch();

    $params = [
      'company_update' => "crm.company.update?".http_build_query([
          'id' => $bitrixCompanyId,
          'fields' => [
            'UF_CRM_1614626235366' => true,
          ]
        ]),
      'livemessage_add' => "crm.livefeedmessage.add?".http_build_query([
          'fields' => [
            'ENTITYTYPEID' => 2,
            'ENTITYID' => $arDeal['UF_XML_ID'],
            'POST_TITLE' => 'Компания прошла аккредитацию',
            'MESSAGE' => "Компания прошла аккредитацию",
          ]
        ]),
      'deal_update' => "crm.deal.update?".http_build_query([
          'id' => $arDeal['UF_XML_ID'],
          'fields' => [
            'STAGE_ID' => CDeal::ACCREDITATION_DEAL_STAGE_END_APPROVED
          ]
        ]),
    ];

    $arParams = [
      'halt' => false,
      'cmd' => $params,
    ];
    $res = CMain::getDataHttp('batch', $arParams);
  }

  public function companyPreApprovedAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);
    if ($dealId > 0) {
      $dealIDs = [$dealId];
      $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
      $hlDealClassName::update($dealId, ['UF_PRE_APPROVED' => true]);

      $arDealRetool = $hlDealClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_DEAL_RETOOL' => $dealIDs]
      ])->fetchAll();

      foreach ($arDealRetool as $deal) {
        $dealIDs[] = $deal['ID'];
      }

      $arFileType = CMain::getEnumField(25);

      $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
      $arFiles = $hlFileClassName::getlist([
        'select' => ['*'],
        'filter' => [
          'UF_DEAL_ID' => $dealIDs,
          'UF_IS_AGREEMENT' => false,
        ]
      ])->fetchAll();

      foreach ($arFiles as $arFile) {
        $hlFileClassName::update($arFile['ID'], ['UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID']]);
      }
    }
  }

  public function companyPostApprovedAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);
    if ($dealId > 0) {
      $dealIDs = [$dealId];
      $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
      $hlDealClassName::update($dealId, ['UF_POST_APPROVED' => true]);

      $arDealRetool = $hlDealClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_DEAL_RETOOL' => $dealIDs]
      ])->fetchAll();

      foreach ($arDealRetool as $deal) {
        $dealIDs[] = $deal['ID'];
      }
    }
  }

  public function subsidionApprovedAction()
  {
    $request = $this->getRequest();
    $query = $request->getValues();

    if (!$request->isPost() || !check_bitrix_sessid()) return;

    $dealId = intval($query['dealId']);
    if($dealId <= 0) die();

    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
    $arDeal = $hlDealClassName::getlist([
      'select' => [
        'UF_XML_ID'
      ],
      'filter' => ['ID' => $dealId],
    ])->fetch();

    $arParams = [
      'id' => $arDeal['UF_XML_ID'],
      'fields' => [
        'STAGE_ID' => CDeal::SUBSIDION_DEAL_STAGE_END_APPROVED
      ]
    ];
    CMain::getDataHttp('crm.deal.update', $arParams);
  }
}