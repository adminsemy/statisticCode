<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 26.07.2018
 * Time: 21:35
 */

namespace Statistics;


use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceHeader;

class Converter
{
    const DEFAULT_VALUE = 0;
    public static function weekString($format_date, $date, InterfaceNormalizationSettings $settings)
    {
        $string = $format_date;
        if ( 'week' === $settings->getSettings('display')) {
            $lastMonday = date("d.m", strtotime('this week monday', strtotime($date)));
            $nextSunday = date("d.m.Y", strtotime('this week sunday', strtotime($date)));
            $string = $format_date. ' ' . $settings->getSettings('dictionary')['Week'] . ' ' . $lastMonday . '-' . $nextSunday;
            if ( 1 === $settings->getSettings('format_out_data') )
                $string = $settings->getSettings('dictionary')['Week'] . ' ' . $format_date . ' ' . $lastMonday . '-' . $nextSunday;
        }
        return $string;
    }
    public static function convertToVerticalData(InterfaceBody $body, InterfaceHeader $header, InterfaceNormalizationSettings $settings)
    {
        $array = [];
        foreach ($body->getBody() as $stat_key=>$stat_row ){
            $arrayChild = [];
            if((strripos($stat_key,' - d') && $settings->getSettings('duration') === 0) ||
                (strripos($stat_key,' - p') && $settings->getSettings('participant') === 0) ||
                (strripos($stat_key,' - m') && $settings->getSettings('meeting') === 0))
                continue;
            if(!(strripos($stat_key, 'Total') === 0).''  && 'total_only' === $settings->getSettings('display'))
                continue;
            foreach ($stat_row as $key=>$row){
                if ($key === 'dataRange')
                    $arrayChild[$key] = $row;
                if ($key === 'category')
                    $arrayChild[$key] = $row;
                if (strripos($stat_key,' - d')) {
                    if ($key !== 'dataRange' && $key !== 'category' && $key !== 'total')
                        $arrayChild[$header->getIdHeader($key)] = self::convertDate($row, $settings);
                    if ($key === 'total') {
                        $arrayChild['total'] = self::convertDate($row, $settings);
                    }
                    continue;
                }
                if ($key !== 'dataRange' && $key !== 'category' && $key !== 'total')
                    $arrayChild[$header->getIdHeader($key)] = $row;
                if ($key === 'total')
                    $arrayChild['total'] = $row;
            }
            $array[] = $arrayChild;
        }
        return $array;
    }

    public static function convertToHorizontalData(InterfaceBody $body, InterfaceNormalizationSettings $settings)
    {
        $array = [];
        foreach ($body->getBody() as $stat_key=>$stat_row ){
            $arrayChild = [];
            foreach ($stat_row as $key=>$row){
                if(preg_match("/[mdp] \d/",$key) && 'total_only' === $settings->getSettings('display')){
                    continue;
                }
                if(preg_match("/d /",$key) && 0 === $settings->getSettings('duration') ||
                    ( preg_match("/p /", $key) && 0 === $settings->getSettings('participant')))
                    continue;
                if(preg_match("/d /",$key)) {
                    $arrayChild[$key] = self::convertDate($row, $settings);
                    continue;
                }
                $arrayChild[$key] = $row;
            }
            $array[] = $arrayChild;
        }
        return $array;
    }
    public static function convertDate($minute, InterfaceNormalizationSettings $settings)
    {
        $second = $minute * 60;

        $d = $settings->getSettings('dictionary')['stat_days'];
        $h = $settings->getSettings('dictionary')['stat_hours'];
        $m = $settings->getSettings('dictionary')['stat_minutes'];

        $second = abs($second);

        $days = (int)($second/(60*60*24));
        $hours = (int)(($second - ($days*60*60*24))/(60*60));
        $min = round((($second - ($days*60*60*24) - ($hours*60*60))/60), 0);

        if (60 === $min){
            $hours += 1;
            $min = 00;
        }
        if ($min<= 9)
            $min = '0'.$min;

        if (1 === $settings->getSettings('format_out_data')) {
            $hours = $hours + ($days*24);
            if ($hours<=9)
                $hours = '0'.$hours;
            $dateValue = "$hours:$min";
        } else {
            $dateValue = "$days$d $hours$h $min$m";
        }

        return $dateValue;
    }

    public static function convertToMinute($minute)
    {
        $minute = abs($minute);
        return $minute;
    }

    public static function convertToSecondChart($minute)
    {
        $minute = self::convertToMinute($minute);
        return $minute*60;
    }

    public static function convertToGeneral(InterfaceBody $body, InterfaceNormalizationSettings $settings)
    {
        $array = [];
        $display = $settings->getSettings('display');
        $meeting = $settings->getSettings('meeting');
        $duration = $settings->getSettings('duration');
        $participant = $settings->getSettings('participant');
        $dictionary = $settings->getSettings('dictionary');
        $format_duration = $settings->getSettings('format_duration');
        foreach ($body->getBody() as $keys=>$stat_row ){
            if(preg_match("/^[0-9]/",$keys) && $display == 'total_only'){
                continue;
            }
            $array_child = [];
            foreach ($stat_row as $key=>$value){
                if ($key == $dictionary['stat_meetings'] && 0 === $meeting)
                    continue;
                if ($key == $dictionary['stat_duration'] . $format_duration && 0 === $duration)
                    continue;
                if ($key == $dictionary['stat_participants'] && 0 === $participant)
                    continue;
                $array_child[$key] = $value;
                if ($key == $dictionary['stat_duration'] . $format_duration)
                    $array_child[$key] = self::convertDate($value, $settings);
            }
            $array[] = $array_child;
        }
        return $array;
    }

    public static function converterToConnections(InterfaceBody $body, InterfaceHeader $header, InterfaceNormalizationSettings $settings)
    {
        $array = [];
        foreach ($body->getBody() as $stat_key=>$stat_row ){
            $arrayChild = [];
            if(!(strripos($stat_key, 'Total') === 0).''  && 'total_only' === $settings->getSettings('display'))
                continue;
            foreach ($stat_row as $key=>$row){
                if ($key === 'dataRange')
                    $arrayChild[$key] = $row;
                if ($key === 'category')
                    $arrayChild[$key] = $row;
                if ($key !== 'dataRange' && $key !== 'category' && $key !== 'total')
                    $arrayChild[$header->getIdHeader($key)] = $row;
                if ($key === 'total')
                    $arrayChild['total'] = $row;
            }
            $array[] = $arrayChild;
        }
        return $array;
    }

    public static function convertToOrganizations(InterfaceBody $body, InterfaceNormalizationSettings $settings)
    {
        $array = [];
        $display = $settings->getSettings('display');
        $dictionary = $settings->getSettings('dictionary');
        $format_duration = $settings->getSettings('format_duration');
        foreach ($body->getBody() as $keys=>$stat_row ){
            if(preg_match("/^[0-9]/",$keys) && 'total_only' ===  $display){
                continue;
            }
            $array_child = [];
            foreach ($stat_row as $key=>$value){
                if ($key === $dictionary['stat_meetings'] && 0 === $settings->getSettings('meeting'))
                    continue;
                if ($key === $dictionary['stat_duration'] . $format_duration && 0 === $settings->getSettings('duration'))
                    continue;
                if ($key === $dictionary['stat_participants'] && 0 === $settings->getSettings('participant'))
                    continue;
                $array_child[$key] = $value;
                if ($key === $dictionary['stat_duration'] . $format_duration)
                    $array_child[$key] = self::convertDate($value, $settings);
            }
            $array[] = $array_child;
        }
        return $array;
    }

    public  static function convertToAudioGeneral(InterfaceBody $body, InterfaceNormalizationSettings $settings)
    {
        $dictionary = $settings->getSettings('dictionary');
        $result = array_map(function ($array) use ($settings, $dictionary) {
            return [
                $dictionary['stat_date'] => $array[$dictionary['stat_date']],
                $dictionary['stat_meetings'] => $array[$dictionary['stat_meetings']],
                $dictionary['stat_participants'] => $array[$dictionary['stat_participants']],
                $dictionary['stat_duration'] => empty($array[$dictionary['stat_duration']]) ? self::DEFAULT_VALUE : self::convertDate($array[$dictionary['stat_duration']], $settings)
            ];
        }, $body->getBody());
        return $result;
    }
}