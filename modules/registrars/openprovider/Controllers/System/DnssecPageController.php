<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\APIConfig;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use WHMCS\ClientArea;
use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use OpenProvider\WhmcsRegistrar\src\Configuration;

/**
 * Class DnssecPageController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DnssecPageController extends BaseController
{
    const PAGE_TITLE  = 'DNSSEC Records';
    const PAGE_NAME   = 'DNSSEC Records';
    const MODULE_NAME = 'dnssec';

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
    }

    public function show($params)
    {
        $ca = new ClientArea();

        $ca->setPageTitle(self::PAGE_NAME);

        $domainId = $_GET['domainid'];
        $domain = \WHMCS\Database\Capsule::table('tbldomains')
            ->where('id', $domainId)
            ->first();

        if (!$domain->dnsmanagement && isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        }
        elseif (!$domain->dnsmanagement) {
            header('Location: ' . '/');
        }

        $domainObj = DomainFullNameToDomainObject::convert($domain->domain);

        try {
            $domainOp = $this->apiHelper->getDomain($domainObj);
            $dnssecKeys = $domainOp['dnssecKeys'];
            $isDnssecEnabled = $domainOp['isDnssecEnabled'];

            $openproviderNameserversCount = 0;
            foreach ($domainOp['nameServers'] as $nameServer) {
                if (!in_array($nameServer['name'], APIConfig::getDefaultNameservers())) {
                    continue;
                }
                $openproviderNameserversCount++;
            }
        } catch (\Exception $e) {
            header('Location: ' . $_SERVER["HTTP_REFERER"]);
        }

        $ca->assign('dnssecKeys', $dnssecKeys);
        $ca->assign('isDnssecEnabled', $isDnssecEnabled);
        $ca->assign('apiUrlUpdateDnssecRecords', Configuration::getApiUrl('dnssec-record-update'));
        $ca->assign('apiUrlTurnOnOffDnssec', Configuration::getApiUrl('dnssec-enabled-update'));
        $ca->assign('domainId', $domainId);
        $ca->assign('jsModuleUrl', Configuration::getJsModuleUrl(self::MODULE_NAME));
        $ca->assign('cssModuleUrl', Configuration::getCssModuleUrl(self::MODULE_NAME));

        $ca->addToBreadCrumb('index.php', \Lang::trans('globalsystemname'));
        $ca->addToBreadCrumb('clientarea.php', \Lang::trans('clientareatitle'));
        $ca->addToBreadCrumb('clientarea.php?action=domains', \Lang::trans('clientareanavdomains'));
        $ca->addToBreadCrumb('clientarea.php?action=domaindetails&id=' . $domainId, $domain->domain);
        $ca->addToBreadCrumb('dnssec.php', self::PAGE_NAME);

        $ca->initPage();

        $ca->requireLogin();

        $primarySidebar = \Menu::primarySidebar('domainView');

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Overview')
            ->setLabel('Overview')
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}")
            ->setOrder(0);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Auto Renew')
            ->setLabel('Auto Renew')
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAutorenew")
            ->setOrder(10);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Nameservers')
            ->setLabel('Nameservers')
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabNameservers")
            ->setOrder(20);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Addons')
            ->setLabel('Addons')
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAddons")
            ->setOrder(30);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Contact Information')
            ->setLabel('Contact Information')
            ->setUri("clientarea.php?action=domaincontacts&domainid={$domainId}")
            ->setOrder(40);

        if ($openproviderNameserversCount > 1) {
            $primarySidebar->getChild('Domain Details Management')
                ->addChild('DNS Management')
                ->setLabel('DNS Management')
                ->setUri("clientarea.php?action=domaindns&domainid={$domainId}")
                ->setOrder(50);
        }

        $ca->setTemplate('/modules/registrars/openprovider/includes/templates/dnssec.tpl');

        $ca->output();
    }
}
