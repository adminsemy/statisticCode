<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 07.07.2018
 * Time: 10:58
 */

namespace Statistics;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Statistics\DB\QueryStatisticBuilder;
use Statistics\StatisticData\Connections\ChartConnections;
use Statistics\StatisticData\Date;
use Statistics\StatisticData\Detailed\ChartDetailed;
use Statistics\StatisticData\Detailed\ChartHorizontal;
use Statistics\StatisticData\General\ChartGeneral;

class Statistic implements InterfaceData
{
    const STATISTIC_TABLE = 'stat_pexip';
    private $statistic_data = [];
    private $settings;
    private $lang_dictionary;

    public function __construct(array $settings, $lang_dictionary)
    {
        $this->settings = $settings;
        $this->lang_dictionary = $lang_dictionary;
    }

    public function getStatistic()
    {

        $normal_settings = new NormalizationSettings($this->settings,
            $this->lang_dictionary,
            $constants = [
                'SETUP_FORMAT_STAT_OUT_DATA' => SETUP_FORMAT_STAT_OUT_DATA,
                'CLIENT_TIME_ZONE' => CLIENT_TIME_ZONE,
                'RECORDING_CAMERA_NAME' => RECORDING_CAMERA_NAME,
                'USER_ROLE' => USER_ROLE,
                'USER_ID' => USER_ID,
                'COMPANY_ID' => COMPANY_ID
            ]
        );
        $queries = new QueryStatisticBuilder($normal_settings);
        $normal_settings->addConfigData(db_query($queries->getQuery('config'))->fetch_all(MYSQLI_ASSOC));
        $view = $normal_settings->getSettings('view');
        $build = new BuilderStatistic($normal_settings, $this);
        $date = new Date($normal_settings);

        if ($normal_settings->getSettings('file')) {
            $format_data = new ExcelFormatData($normal_settings, new Spreadsheet());
        } else {
            $format_data = new ArrayFormatData();
        }

        if ('detail' === $view || 'devices' === $view){
            $this->statistic_data['general'] = db_query($queries->getQuery('general'))->fetch_all(MYSQLI_ASSOC);
            $this->statistic_data['total'] = db_query($queries->getQuery('total'))->fetch_all(MYSQLI_ASSOC);
            $this->statistic_data['company_total'] = db_query($queries->getQuery('company_total'))->fetch_all(MYSQLI_ASSOC);
            $this->statistic_data['user'] = db_query($queries->getQuery('user'))->fetch_all(MYSQLI_ASSOC);
            $chart = new ChartDetailed($normal_settings, $this->statistic_data['general'], $this->statistic_data['total'], $this->statistic_data['user']);
            $chart->createChart();
            $chart->addChartData();
            $build->detailed();
            $build->companies();
            $format_data->setArrayData($build->getDetailed());
            $format_data->setArrayData($build->getCompanies());
            $format_data->setArrayData($chart->getChartLabels(), $normal_settings->getSettings('chartLabels'));
            $format_data->setArrayData($chart->getChart());
        }
        if ('general' === $view){
            $this->statistic_data['total'] = db_query($queries->getQuery('total'))->fetch_all(MYSQLI_ASSOC);
            $this->statistic_data['company_total'] = db_query($queries->getQuery('company_total'))->fetch_all(MYSQLI_ASSOC);
            $this->statistic_data['meetings_audio'] = db_query($queries->getQuery('meetings_audio'))->fetch_all(MYSQLI_ASSOC);
            $chart = new ChartGeneral($normal_settings);
            $chart->createBody($this->statistic_data['total']);

            $date->generateDate($this->statistic_data['total']);
            $build->general();
            $build->companies();

            $format_data->setArrayData($build->getGeneral());
            $format_data->setArrayData($build->getCompanies());
            $format_data->setArrayData($date->get(), $normal_settings->getSettings('date'));
            $format_data->setArrayData($chart->getChartLabels(), $normal_settings->getSettings('chartLabels'));
            $format_data->setArrayData($chart->getChart());
        }
        if ('connections' === $view){
            $this->statistic_data['connections'] = db_query($queries->getQuery('connections'))->fetch_all(MYSQLI_ASSOC);
            $build->connections();
            $date->generateDate($this->statistic_data['connections']);
            $chart = new ChartConnections($normal_settings);
            $chart->createChart();
            $chart->addChartData($this->statistic_data['connections']);
            $format_data->setArrayData($build->getConnections());
            $format_data->setArrayData($date->get(), 'date');
            $format_data->setArrayData($chart->getChartConnectionColumn('value'), 'chartTotals');
            $format_data->setArrayData($chart->getChartConnectionColumn('name'), 'chartLabels');
        }
        if ('vmr' === $view) {
            $this->statistic_data['rooms'] = db_query($queries->getQuery('rooms'))->fetch_all(MYSQLI_ASSOC);
            $this->statistic_data['vmr'] = db_query($queries->getQuery('total'))->fetch_all(MYSQLI_ASSOC);
            $build->vmr();
            $format_data->setArrayData($build->getVmr());
        }
        return $format_data->getFormat();
    }
    public function get($name)
    {
        return $this->statistic_data[$name];
    }
}
