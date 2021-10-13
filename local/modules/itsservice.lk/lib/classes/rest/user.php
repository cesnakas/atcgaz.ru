<?php
namespace Its\Service\Rest;

class CRestUser
{
  const LISTS_IBLOCK_USER_PROFILE = 34;
  const LISTS_IBLOCK_REGION = 36;
  const GROUP_USER_IDS = [7];
  const GROUP_USER_PRE_APPROVAL = [9];
  const FIELDS_BITRIX_FOR_USER = [
    'LOGIN' => 'PROPERTY_164',
    'PASSWORD' => 'PROPERTY_162',
    'REGION' => 'PROPERTY_160',
  ];
}