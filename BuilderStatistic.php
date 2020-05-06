<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 14.07.2018
 * Time: 14:36
 */

namespace Statistics;


use Statistics\StatisticData\Connections\BodyConnections;
use Statistics\StatisticData\Connections\HeaderConnections;
use Statistics\StatisticData\Connections\TotalConnections;
use Statistics\StatisticData\Detailed\BodyHorizontal;
use Statistics\StatisticData\Detailed\BodyVertical;
use Statistics\StatisticData\Detailed\HeaderHorizontal;
use Statistics\StatisticData\Detailed\HeaderVertical;
use Statistics\StatisticData\Detailed\TotalVertical;
use Statistics\StatisticData\General\AudioBodyGeneral;
use Statistics\StatisticData\General\AudioHeaderGeneral;
use Statistics\StatisticData\General\BodyGeneral;
use Statistics\StatisticData\General\HeaderGeneral;
use Statistics\StatisticData\General\TotalGeneral;
use Statistics\StatisticData\Organizations\BodyOrganizations;
use Statistics\StatisticData\Organizations\HeaderOrganizations;
use Statistics\StatisticData\Organizations\TotalOrganizations;
use Statistics\StatisticData\VMR\NumberVMR;
use Statistics\StatisticData\VMR\VmrHorizontal;
use Statistics\StatisticData\VMR\VmrVertical;


class BuilderStatistic implements InterfaceBuilder
{
    private $settings;
    private $statistic_data;
    private $general_stat;
    private $total_stat;
    private $data_connections;
    private $organizations;
    private $vmr_data;
    private $vmr;
    private $rooms_data;

    private $connections = [];
    private $companies = [];
    private $general = [];
    private $detailed = [];


    public function __construct(InterfaceNormalizationSettings $settings, InterfaceData $statistic)
    {
        $this->settings = $settings;
        $this->statistic_data = $statistic;
    }
    public function detailed()
    {
        $this->general_stat = $this->statistic_data->get('general');
        $this->total_stat = $this->statistic_data->get('total');

        if ('vertical' === $this->settings->getSettings('orientation')) {
            $header = new HeaderVertical($this->settings);
            $header->setVerticalData($this->general_stat, $this->statistic_data->get('user'));

            $total = new TotalVertical($this->settings, $header);
            $total->createVerticalTotal($this->general_stat);

            $body = new BodyVertical($this->settings, $header, $total);
            $body->createVerticalBody($this->general_stat);
            $body->createVerticalBodyTotal($this->total_stat);
            $body->convertVerticalBody();
            $this->detailed[$this->settings->getSettings('columns')] = $header->getHeaderValues();
            $this->detailed[$this->settings->getSettings('dataSource')] = $body->getBody();
        }
        if ('horizontal' === $this->settings->getSettings('orientation')) {
            $header = new HeaderHorizontal($this->settings);
            $header->setHorizontalData($this->general_stat);
            $body = new BodyHorizontal($this->settings, $header);
            $body->createBodyHorizontal($this->general_stat, $this->statistic_data->get('user'));
            $body->fillingData($this->general_stat);
            $body->fillingDataTotal($this->total_stat);
            if (1 === $this->settings->getSettings('no_total'))
                $body->noTotal();
            $body->convertHorizontalBody();
            $this->detailed[$this->settings->getSettings('columns')] = $header->getHeaderValues();
            $this->detailed[$this->settings->getSettings('dataSource')] = $body->getBody();
        }
    }

    public function general()
    {
        $this->total_stat = $this->statistic_data->get('total');
        $header = new HeaderGeneral($this->settings);
        $header->createHeaders();

        $total = new TotalGeneral($this->settings);
        $total->createTotal($this->total_stat);
        $body = new BodyGeneral($this->settings);
        $body->createBody($this->total_stat);
        $body->addTotal($total);
        $body->converterBody();

        $headerAudio = new AudioHeaderGeneral($this->settings);
        $headerAudio->createHeaders();
        $bodyAudio = new AudioBodyGeneral($this->settings);
        $bodyAudio->createBody($this->statistic_data->get('meetings_audio'));
        $bodyAudio->converterBody();

        $totalVideo = new TotalGeneral($this->settings);
        $totalVideo->createTotal($this->total_stat, 1);
        $bodyVideo = new BodyGeneral($this->settings);
        $bodyVideo->createBody($this->total_stat, 1);
        $bodyVideo->addTotal($totalVideo);
        $bodyVideo->converterBody();

        $totalWeb = new TotalGeneral($this->settings);
        $totalWeb->createTotal($this->total_stat, 10);
        $bodyWeb = new BodyGeneral($this->settings);
        $bodyWeb->createBody($this->total_stat, 10);
        $bodyWeb->addTotal($totalWeb);
        $bodyWeb->converterBody();


        $this->general[$this->settings->getSettings('columns')] = $header->getHeaderValues();
        $this->general[$this->settings->getSettings('dataSource')] = $body->getBody();
        $this->general[$this->settings->getSettings('columnsAudio')] = $headerAudio->getHeaderValues();
        $this->general[$this->settings->getSettings('dataSourceAudio')] = $bodyAudio->getBody();
        $this->general[$this->settings->getSettings('dataSourceVideo')] = $bodyVideo->getBody();
        $this->general[$this->settings->getSettings('dataSourceWeb')] = $bodyWeb->getBody();
    }

    public function connections()
    {
        $this->data_connections = $this->statistic_data->get('connections');
        $this->addConnectionsInSettings();

        $header = new HeaderConnections($this->settings);
        $header->setConnectionsData($this->data_connections);

        $total = new TotalConnections($this->settings, $header);
        $total->createTotal();
        $total->setTotalData($this->data_connections);

        $body = new BodyConnections($this->settings, $header, $total);
        $body->createBodyConnection($this->data_connections);
        $body->convertBodyConnections();
        if (1 === $this->settings->getSettings('no_total'))
            $header->noTotal();
        $this->connections[$this->settings->getSettings('columns')] = $header->getHeaderValues();
        $this->connections[$this->settings->getSettings('dataSource')] = $body->getBody();
    }

    public function companies()
    {
        $this->companies = [];

        $this->organizations = $this->statistic_data->get('company_total');

        $header = new HeaderOrganizations($this->settings);
        $header->createHeader();

        $total = new TotalOrganizations($this->settings);
        $total->createTotalCompanies($this->organizations);

        $body = new BodyOrganizations($this->settings, $total);
        $body->createBodyCompanies($this->organizations);
        $body->convertBodyCompanies();

        $this->companies[$this->settings->getSettings('columnsCompanies')] = $header->getHeaderValues();
        $this->companies[$this->settings->getSettings('dataSourceCompanies')] = $body->getBody();
    }

    public function vmr()
    {
        $this->rooms_data = $this->statistic_data->get('rooms');
        $this->vmr_data = $this->statistic_data->get('vmr');
        $vmr_name = new StatisticData\VMR\HeaderVertical($this->settings);
        $vmr_name->createRooms($this->rooms_data);
        $vmr_name->setHeaderData($this->vmr_data);
        $number_vmr = new NumberVMR($this->settings, $vmr_name->getHeader(), $this->rooms_data);
        if ('vertical' === $this->settings->getSettings('orientation')) {
            $header = $vmr_name;
            $body = new VmrVertical($this->settings, $header, $number_vmr);
            $body->createRooms($this->rooms_data);
            $body->createVmr($this->vmr_data);
            $body->convert();
            $this->vmr[$this->settings->getSettings('columns')] = $header->getHeaderValues();
            $this->vmr[$this->settings->getSettings('dataSource')] = $body->getBody();
        }
        if ('horizontal' === $this->settings->getSettings('orientation')) {
            $header = new StatisticData\VMR\HeaderHorizontal($this->settings);
            $header->setHorizontalData($this->vmr_data);
            $vmr = new VmrHorizontal($this->settings, $header, $number_vmr);
            $vmr->createRooms($this->rooms_data);
            $vmr->createVmr($this->vmr_data);
            $vmr->convert();
            $this->vmr[$this->settings->getSettings('columns')] = $header->getHeaderValues();
            $this->vmr[$this->settings->getSettings('dataSource')] = $vmr->getBody();
        }
    }

    /**
     * @return array
     */
    public function getCompanies()
    {
        return $this->companies;
    }

    /**
     * @return array
     */
    public function getGeneral()
    {
        return $this->general;
    }

    /**
     * @return array
     */
    public function getDetailed()
    {
        return $this->detailed;
    }

    /**
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * @return array
     */
    public function getVmr()
    {
        return $this->vmr;
    }

    private function addConnectionsInSettings()
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $connections_array = [
            'Windows_all' => $dictionary['stat_windows_all'],
            'Chrome' => $dictionary['stat_windows_chrome'],
            'Firefox' => $dictionary['stat_windows_firefox'],
            'IE' => $dictionary['stat_windows_ie'],
            'Edge' => $dictionary['stat_windows_edge'],
            'Windows_undefined' => $dictionary['stat_windows_undefined'],
            'Mobile_all' => $dictionary['stat_mobile_all'],
            'Android' => $dictionary['stat_mobile_android'],
            'IoS' => $dictionary['stat_mobile_ios'],
            //'Mobile_other' => $dictionary['stat_mobile_other'],
            'SIP' => $dictionary['stat_sip'],
            'H323' => $dictionary['stat_h323'],
            //'Other' => $dictionary['stat_other'],
        ];
        $this->settings->setSettings('connections', $connections_array);
    }
}