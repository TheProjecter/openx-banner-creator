<?php

require_once 'common.php';

phpAds_registerGlobalUnslashed('bannerid', 'campaignid', 'clientid', 'ajax', 'size', 'keywords', 'qty', 'template_id', 'image_url', 'description', 'type');

OA_Permission::enforceAccount(OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER);

if (isset($ajax)) {
  if ($ajax === 'search')
    echo search_ajax_request($keywords, $size, $qty);

  elseif ($ajax === 'form')
    echo form_ajax_request($template_id);

  elseif ($ajax === 'preview')
    echo preview_ajax_request($template_id);

  elseif ($ajax === 'save-image')
    echo save_ajax_request($bannerid, $campaignid, $clientid, $image_url, $template_id, $description, $type);

  return;
}

//get advertisers and set the current one
$aAdvertisers = getAdvertiserMap();
if (empty($clientid)) { //if it's empty
    $campaignid = null; //reset campaign id, we could derive it after we have clientid
    if ($session['prefs']['inventory_entities'][OA_Permission::getEntityId()]['clientid']) {
        //try previous one from session
        $sessionClientId = $session['prefs']['inventory_entities'][OA_Permission::getEntityId()]['clientid'];
        if (isset($aAdvertisers[$sessionClientId])) { //check if 'id' from session was not removed
            $clientid = $sessionClientId;
        }
    }
    if (empty($clientid)) { //was empty, is still empty - just pick one, no need for redirect
        $ids = array_keys($aAdvertisers);
        if (!empty($ids)) {
            $clientid = $ids[0];
        }
        else {
            $clientid = -1; //if no advertisers set to non-existent id
            $campaignid = -1; //also reset campaign id
        }
    }
}
else {
    if (!isset($aAdvertisers[$clientid])) {
        $page = basename($_SERVER['PHP_SELF']);
        OX_Admin_Redirect::redirect($page);
    }
}

//get campaigns - if there was any client id derived
if ($clientid > 0) {
    $aCampaigns = getCampaignMap($clientid);
    if (empty($campaignid)) { //if it's empty
        if ($session['prefs']['inventory_entities'][OA_Permission::getEntityId()]['campaignid'][$clientid]) {
            //try previous one from session
            $sessionCampaignId = $session['prefs']['inventory_entities'][OA_Permission::getEntityId()]['campaignid'][$clientid];
            if (isset($aCampaigns[$sessionCampaignId])) { //check if 'id' from session was not removed
                $campaignid = $sessionCampaignId;
            }
        }
        if (empty($campaignid)) { //was empty, is still empty - just pick one, no need for redirect
            $ids = array_keys($aCampaigns);
            $campaignid = !empty($ids) ? $ids[0] : -1; //if no campaigns set to non-existent id
        }
    }
    else {
        if (!isset($aCampaigns[$campaignid])) {
            $page = basename($_SERVER['PHP_SELF']);
            OX_Admin_Redirect::redirect("$page?clientid=$clientid");
        }
    }
}

OA_Permission::enforceAccessToObject('clients',   $clientid);
OA_Permission::enforceAccessToObject('campaigns', $campaignid);

if (OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
  OA_Permission::enforceAllowed(OA_PERM_BANNER_EDIT);
  OA_Permission::enforceAccessToObject('banners', $bannerid);
} else
  OA_Permission::enforceAccessToObject('banners', $bannerid, true);

$session['prefs']['inventory_entities'][OA_Permission::getEntityId()]['clientid'] = $clientid;
$session['prefs']['inventory_entities'][OA_Permission::getEntityId()]['campaignid'][$clientid] = $campaignid;
phpAds_SessionDataStore();

display_page($bannerid, $campaignid, $clientid);

function display_page($banner_id, $campaign_id, $client_id) {
  $page_name = basename($_SERVER['PHP_SELF']);
  $entities = array('clientid' => $client_id, 'campaignid' => $campaign_id, 'bannerid' => $banner_id);

  $entity_id = OA_Permission::getEntityId();
  if (OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
     $entity_type = 'advertiser_id';
  } else
    $entity_type = 'agency_id';

  // Display navigation
  $other_campaigns = Admin_DA::getPlacements(array($entity_type => $entity_id));
  $other_banners = Admin_DA::getAds(array('placement_id' => $campaign_id), false);
  displayNavigationBanner($page_name, $other_campaigns, $other_banners, $entities);

  if (!empty($banner_id)) {
    $form = explode('|', form_ajax_request(OA_Dal::factoryDO('Zpbanners')->get_zp_banner_id((int)$banner_id)));
  }

  $template = new OA_Plugin_Template('designer-form.html', 'bannerDesigner');
  //$oTpl->debugging = true;
  $template->assign('bannerid', $banner_id);
  $template->assign('campaignid', $campaign_id);
  $template->assign('clientid', $client_id);
  $template->assign('form', $form);

  $template->display();

  phpAds_PageFooter();
}

function getAdvertiserMap()
{
    $doClients = OA_Dal::factoryDO('clients');
    // Unless admin, restrict results shown.
    if (OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
        $doClients->clientid = OA_Permission::getEntityId();
    }
    else {
        $doClients->agencyid = OA_Permission::getEntityId();
    }

    $doClients->find();

    $aAdvertiserMap = array();
    while ($doClients->fetch() && $row = $doClients->toArray()) {
        $aAdvertiserMap[$row['clientid']] = array('name' => $row['clientname'],
            'url' => "advertiser-campaigns.php?clientid=".$row['clientid']);
    }

    return $aAdvertiserMap;
}


function getCampaignMap($advertiserId)
{
    $aCampaigns = Admin_DA::getPlacements(array('advertiser_id' => $advertiserId));

    $aCampaignMap = array();
    foreach ($aCampaigns as $campaignId => $aCampaign) {
        $campaignName = $aCampaign['name'];
        // mask campaign name if anonymous campaign
        $campaign_details = Admin_DA::getPlacement($campaignId);
        $campaignName = MAX_getPlacementName($campaign_details);
        $aCampaignMap[$campaignId] = array('name' => $campaignName);
    }

    return $aCampaignMap;
}
?>