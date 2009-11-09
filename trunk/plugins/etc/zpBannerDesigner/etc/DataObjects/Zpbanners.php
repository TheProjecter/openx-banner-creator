<?php
/**
 * Table Definition for zptable
 */
require_once MAX_PATH.'/lib/max/Dal/DataObjects/DB_DataObjectCommon.php';

class DataObjects_Zpbanners extends DB_DataObjectCommon
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'zpbanners';           // table name
    public $banner_id;                    // int(9)  not_null primary_key
    public $zp_banner_id;                  // string(36)  not_null

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('DataObjects_Zpbanners',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function get_zp_banner_id ($banner_id) {
        $this->banner_id = $this->quote($banner_id);
        
        if ($this->find()) {
            $this->fetch();
            return $this->zp_banner_id;
        }
        return false;
    }

    function set_zp_banner_id ($banner_id, $zp_banner_id) {
        $this->banner_id = $this->quote($banner_id);

        if ($this->find()) {
          $this->fetch();
          $this->zp_banner_id = $zp_banner_id;
          $this->update();
        } else {
          $this->banner_id = $this->quote($banner_id);
          $this->zp_banner_id = $zp_banner_id;
          return $this->insert();
        }
    }

    /**
     * A private method to return the account ID of the
     * account that should "own" audit trail entries for
     * this entity type; NOT related to the account ID
     * of the currently active account performing an
     * action.
     *
     * @return integer The account ID to insert into the
     *                 "account_id" column of the audit trail
     *                 database table.
     */
    function getOwningAccountIds()
    {
        $accountType = OA_Permission::getAccountType(false);
        switch ($accountType)
        {
            case OA_ACCOUNT_ADMIN:
                return parent::_getOwningAccountIdsByAccountId($accountId  = OA_Permission::getAccountId());
            case OA_ACCOUNT_ADVERTISER:
                $parentTable = 'clients';
                $parentKeyName = 'clientid';
                break;
            case OA_ACCOUNT_TRAFFICKER:
                $parentTable = 'affiliates';
                $parentKeyName = 'affiliateid';
                break;
            case OA_ACCOUNT_MANAGER:
                $parentTable = 'agency';
                $parentKeyName = 'agencyid';
                break;
        }
        return parent::getOwningAccountIds($parentTable, $parentKeyName);
    }

    function _auditEnabled()
    {
        return false;
    }

    function _getContextId()
    {
        return $this->banner_id;
    }

    function _getContext()
    {
        return 'zpbanners';
    }

    /**
     * build a mytable specific audit array
     *
     * @param integer $actionid
     * @param array $aAuditFields
     */
    function _buildAuditArray($actionid, &$aAuditFields)
    {
        $aAuditFields['key_desc']   = $this->$zp_banner_id;
    }
}
