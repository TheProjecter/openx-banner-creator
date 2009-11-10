<?php

require_once('zp-api.php');

function get_zp_user_id () {
  $zp_users = OA_Dal::factoryDO('Zpusers');

  $zp_user_id = $zp_users->get_zp_user_id(OA_Permission::getUserId());
  if (!$zp_user_id) {
    $zp_user_id = zp_api_common_uuid();
    $zp_users->set_zp_user_id($zp_user_id);
  }

  return $zp_user_id;
}

function get_template_detailes ($template_id) {
  return zetaprints_get_template_details('http://zetaprints.com', $template_id);
}

function get_preview_images ($xml) {
  return zetaprints_get_html_from_xml($xml, 'preview-images', 'http://zetaprints.com/');
}

function get_text_fields ($xml) {
  return zetaprints_get_html_from_xml($xml, 'input-fields', 'http://zetaprints.com/');
}

function get_image_fields ($xml) {
  return zetaprints_get_html_from_xml($xml, 'stock-images', 'http://zetaprints.com/');
}

function search_ajax_request ($keywords, $size, $qty) {
  list($width, $height) = explode('x', $size);
  $params = array();

  //$params['page'] = 'api-search';
  $params['Search'] = $keywords;
  $params['Width'] = $width;
  $params['Height'] = $height;
  $params['Units'] = 'px';
  $params['GeneratePrintPdf'] = 'image';
  $params['From'] = '0';
  $params['Qty'] = $qty;

  list($header, $content) = zp_api_common_post_request('http://zetaprints.com', '/?page=api-search', $params);

  list (, $content) = explode("\r\n", $content);
  return zetaprints_get_html_from_xml($content, 'search-results', '');
}

function form_ajax_request ($template_id) {
  $xml = get_template_detailes($template_id);

  $preview_image = get_preview_images($xml);
  $text_fields = get_text_fields($xml);
  $image_fields = get_image_fields($xml);

  return "$preview_image|$text_fields $image_fields";
}

function preview_ajax_request ($template_id) {
  $params = array();

  //Preparing params for image generating request to zetaprints
  foreach ($_POST as $key => $value)
    if (strpos($key, 'zetaprints-') !== false) {
      $_key = substr($key, 11);
      $_key = substr($_key, 0, 1).str_replace('_', ' ', substr($_key, 1));
      $params[$_key] = str_replace("\n", "\r\n", $value);
    }

  //$params['ID'] = get_zp_user_id();
  $params['From'] = '0';

  //Sending image generating request to zetaprints
  list($header, $content) = zp_api_common_post_request('http://zetaprints.com', '/?page=template-preview-ecard;TemplateID=' . $template_id, $params);

  //BUG. Getting strange numbers in the content
  list(, $url) = explode("\r\n", $content);

  return $url;
}

function save_ajax_request($banner_id, $campaign_id, $client_id, $image_id, $template_id, $banner_description, $banner_storagetype) {
  $file = fopen('http://zetaprints.com/preview/' . $image_id, 'rb');

  $data = fread($file, 1024);
  while (!feof($file))
    $data .= fread($file, 1024);

  fclose($file);

  $banner = OA_Dal::factoryDO('banners');
  // Get the existing banner details (if it is not a new banner)
  if (!empty($banner_id))
    $result  = $banner->get($banner_id);

  if (!$result) {
      
      OA::debug('here');
      $banner->bannerid = '';
      $banner->campaignid = $campaign_id;
      $banner->clientid = $client_id;
      $banner->url = '';
      $banner->imageurl = '';
      $banner->weight = $pref['default_banner_weight'];
      $banner->description = $banner_description;
      $banner->storagetype = $banner_storagetype;
  }

  require_once MAX_PATH . '/lib/OA/Creative/File.php';

  $file = OA_Creative_File::factoryString($image_id, $data);
  $file->store($banner->storagetype);

  if (!empty($banner->filename) && ($banner->storagetype == 'web' || $banner->storagetype == 'sql'))
      DataObjects_Banners::deleteBannerFile($banner->storagetype, $banner->filename);

  $file_detailes = $file->getFileDetails();

  $new_banner = (empty($banner_id)) ? true : false;

  if ($new_banner)
    $size_changed = ($file_detailes['width'] != $banner->width || $file_detailes['height'] != $banner->height);

  $banner->filename = $file_detailes['filename'];
  $banner->contenttype = $file_detailes['contenttype'];
  $banner->width = $file_detailes['width'];
  $banner->height = $file_detailes['height'];
  $banner->pluginversion = $file_detailes['pluginversion'];

  if ($new_banner) {
    $banner->insert();
    require_once MAX_PATH . '/lib/OA/Maintenance/Priority.php';
    OA_Maintenance_Priority::scheduleRun();
    $banner_id = $banner->bannerid;
  }
  else {
    $banner->update();

    // if size has changed
    if (size_changes) {
      MAX_adjustAdZones($banner_id);
      MAX_addDefaultPlacementZones($banner_id, $campaign_id);
    }
  }

  $zp_banners = OA_Dal::factoryDO('Zpbanners');
  $zp_banners->set_zp_banner_id($banner_id, $template_id);

  return "banner-edit.php?clientid=$client_id&campaignid=$campaign_id&bannerid=$banner_id";
}

function displayNavigationBanner($pageName, $aOtherCampaigns, $aOtherBanners, $aEntities)
{
    global $phpAds_TextDirection;

    $advertiserId = $aEntities['clientid'];
    $campaignId = $aEntities['campaignid'];
    $bannerId = $aEntities['bannerid'];
    $entityString = _getEntityString($aEntities);
    $aOtherEntities = $aEntities;
    unset($aOtherEntities['bannerid']);
    $otherEntityString = _getEntityString($aOtherEntities);
    if ($pageName == 'banner-designer.php' && empty($bannerId)) {
                $tabValue = 'banner-designer_new';
                $pageType = 'edit-new';
    }
    else {
    	$pageType = 'edit';
    }

    $advertiserEditUrl = '';
    $campaignEditUrl = '';

    if (OA_Permission::hasAccessToObject('clients', $advertiserId)) {
        $advertiserEditUrl = "advertiser-edit.php?clientid=$advertiserId";
    }
    if (!OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
        $campaignEditUrl = "campaign-edit.php?clientid=$advertiserId&campaignid=$campaignId";
    }

    // Build ad preview
    if ($bannerId && empty($_GET['nopreview'])) {
        require_once (MAX_PATH . '/lib/max/Delivery/adRender.php');
        $aBanner = Admin_DA::getAd($bannerId);
        $aBanner['storagetype'] = $aBanner['type'];
        $aBanner['bannerid'] = $aBanner['ad_id'];
        $bannerCode = MAX_adRender($aBanner, 0, '', '', '', true, '', false, false);
    }
    else {
        $bannerCode = '';
    }

    $advertiserDetails = phpAds_getClientDetails($advertiserId);
    $advertiserName = $advertiserDetails['clientname'];
    $campaignDetails = Admin_DA::getPlacement($campaignId);
    $campaignName = $campaignDetails['name'];
    $bannerName = $aOtherBanners[$bannerId]['name'];

    $builder = new OA_Admin_UI_Model_InventoryPageHeaderModelBuilder();
    $oHeaderModel = $builder->buildEntityHeader(array(
                                      array("name" => $advertiserName, "url" => $advertiserEditUrl),
                                      array("name" => $campaignName, "url" => $campaignEditUrl),
                                      array("name" => $bannerName)),
                                    "banner", $pageType);

    global $phpAds_breadcrumbs_extra;
    $phpAds_breadcrumbs_extra .= "<div class='bannercode'>$bannerCode</div>";
    if ($bannerCode != '') {
        $phpAds_breadcrumbs_extra .= "<br />";
    }

    addBannerPageTools($advertiserId, $campaignId, $bannerId, $aOtherCampaigns, $aOtherBanners, $aEntities);
    phpAds_PageHeader($tabValue, $oHeaderModel);
}

?>