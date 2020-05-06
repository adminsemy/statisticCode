<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 12.10.2018
 * Time: 11:52
 */

namespace Statistics;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelFormatData implements InterfaceFormatData
{
    private $array_data = [];
    private $settings;
    private $spreadsheet;


    public function __construct(InterfaceNormalizationSettings $settings, Spreadsheet $spreadsheet)
    {
        $this->settings = $settings;
        $this->spreadsheet = $spreadsheet;
    }

    /**
     * @return mixed
     */
    public function getFormat()
    {
        $view = $this->settings->getSettings('view');
        $orientation = $this->settings->getSettings('orientation');
        if ('general' === $view)
            $this->createFileGeneral();
        if ('horizontal' === $orientation && ('detail' === $view || 'devices' === $view))
            $this->createFileHorizont();
        if ('horizontal' === $orientation && 'vmr' === $view)
            $this->createFileHorizontVmr();
        if ('vertical' === $orientation && ('detail' === $view || 'devices' === $view))
            $this->createFileVertical();
        if ('vertical' === $orientation && 'vmr' === $view)
            $this->createFileVerticalVmr();
        if ('connections' === $view)
            $this->createFileVertical();
        $format_file = $this->settings->getSettings('file');
        if('csv' === $format_file) {
            $writer = new Csv($this->spreadsheet);
            $writer->setDelimiter(';');
            $writer->setEnclosure('');
            $writer->setLineEnding("\r\n");
            $writer->setUseBOM(true);
            $writer->save('php://output');
        } elseif ('xlsx' === $format_file) {
            $writer = new Xlsx($this->spreadsheet);
            $writer->save('php://output');
        }
        return true;
    }

    public function setArrayData($data, $name = '')
    {
        if (empty($name)) {
            $this->array_data = $this->array_data + $data;
        } else {
            $this->array_data[$name] = $data;
        }
    }

    private function createFileGeneral()
    {
        $format_duration = $this->settings->getSettings('format_duration');
        $dictionary = $this->settings->getSettings('dictionary');
        $format_out_data = $this->settings->getSettings('format_out_data');
        $columns = $this->array_data[$this->settings->getSettings('columns')];
        $stat = $this->array_data[$this->settings->getSettings('dataSource')];
        $count_columns = count($columns);
        $count_stat = count($stat) + 1;
        if (1 === $format_out_data){
            $columns_upper = [];
            foreach ($columns as $column){
                if ($column === $dictionary['stat_duration'] . $format_duration)
                    $column = empty($dictionary['stat_duration_upper']) ? $column : $dictionary['stat_duration_upper'];
                if ($column === $dictionary['stat_meetings'])
                    $column = empty($dictionary['stat_meetings_upper']) ? $column : $dictionary['stat_meetings_upper'];
                $columns_upper[] = mb_strtoupper($column, 'UTF-8');
            }
            unset($columns);
            $columns = $columns_upper;
        }
        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $columns,  // The data to set
                NULL,        // Array values with this value will not be set
                'A1'         // Top left coordinate of the worksheet range where
            //    we want to set these values (default is A1)
            );
        $this->spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)->getAlignment()->setWrapText(true);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $stat,  // The data to set
                NULL,        // Array values with this value will not be set
                'A2',
                true
            );
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getAlignment()->setWrapText(true);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2,2,$count_columns, $count_stat)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        return true;
    }

    function createFileHorizont()
    {
        $duration = $this->settings->getSettings('duration');
        $participant = $this->settings->getSettings('participant');
        $dictionary = $this->settings->getSettings('dictionary');
        $format_out_data = $this->settings->getSettings('format_out_data');
        $columns = $this->array_data[$this->settings->getSettings('columns')];
        $stat = $this->array_data[$this->settings->getSettings('dataSource')];
        $m = 'm';
        $d = 'd';
        $p = 'p';
        $start_line = 2;
        $start_cells = 'A3';
        $merge_cells = 1;
        $a1 = $dictionary['stat_date_device'];

        if (1 === $format_out_data ) {
            $a1 = $dictionary['End_points'];
            $columns_upper = [];
            foreach ($columns as $column){
                $columns_upper[] = mb_strtoupper($column, 'UTF-8');
            }
            unset($columns);
            $columns = $columns_upper;
            $a1 = strtoupper($a1);
            $m = $dictionary['stat_meetings_upper'];
            $d = $dictionary['stat_duration_upper'];
            $p = strtoupper($dictionary['stat_participants']);
            $start_cells = 'A2';
            $merge_cells = 2;
        }

        $count_columns = count($columns);
        $count_stat = count($stat)+2;

        if (1 === $format_out_data)
            array_pop($stat);

        $this->spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);

        if (1 !== $format_out_data)
            $this->spreadsheet->getActiveSheet()->mergeCells('A1:A2');

        $this->spreadsheet->getActiveSheet()->getCell('A1')->setValue($a1);

        if ( 1 === $format_out_data ){
            $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow(2, 1)->setValue($m);
            $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow(3, 1)->setValue($d);
        } else {
            if (1 === $duration || 1 === $participant) {
                if (1 === $duration)
                    $merge_cells += 1;
                if (1 === $participant)
                    $merge_cells += 1;
                $column_key = 0;
                for ( $i = $start_line; $i <= $count_columns * $merge_cells; $i += $merge_cells ) {
                    $two_rows = $i;
                    $this->spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow($i, 1, $i + ($merge_cells-1), 1);
                    $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i, 1)->setValueExplicit($columns[$column_key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($two_rows, 2)->setValue($m);
                    if (1 === $duration)
                        $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($two_rows+1, 2)->setValue($d);
                    if (1 === $participant)
                        $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($two_rows+2, 2)->setValue($p);
                    $column_key++;
                }
            } else {
                $column_key = 0;
                for ( $i = $start_line; $i <= $count_columns * $merge_cells+1; $i += $merge_cells ){
                    $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i,1)->setValueExplicit($columns[$column_key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i,2)->setValue($m);
                    $column_key++;
                }
            }
        }



        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)
            ->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns*$merge_cells+1, 1)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns*$merge_cells+1, 1)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $stat,  // The data to set
                NULL,        // Array values with this value will not be set
                $start_cells,
                true
            );
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2,2,$count_columns*$merge_cells+1, $count_stat)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return true;
    }

    function createFileHorizontVmr()
    {
        $duration = $this->settings->getSettings('duration');
        $participant = $this->settings->getSettings('participant');
        $dictionary = $this->settings->getSettings('dictionary');
        $columns = $this->array_data[$this->settings->getSettings('columns')];
        $stat = $this->array_data[$this->settings->getSettings('dataSource')];
        $m = 'm';
        $d = 'd';
        $p = 'p';
        $start_line = 3;
        $start_cells = 'A3';
        $merge_cells = 1;
        $a1 = $dictionary['stat_vmr_number'];
        $b2 = $dictionary['stat_vmr_name'];

        $count_columns = count($columns);
        $last_key_stat = count($stat)-1;
        $count_stat = count($stat)+3;

        $last_data = $stat[$last_key_stat];
        array_splice($last_data, 1, 0, array($this->settings->getSettings('alias_number') => ''));

        $stat[$last_key_stat] = $last_data;

        $this->spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);

        $this->spreadsheet->getActiveSheet()->mergeCells('A1:A2');
        $this->spreadsheet->getActiveSheet()->mergeCells('B1:B2');
        $this->spreadsheet->getActiveSheet()->getCell('A1')->setValue($a1);
        $this->spreadsheet->getActiveSheet()->getCell('B1')->setValue($b2);
        if (1 === $duration || 1 === $participant) {
            if (1 === $duration)
                $merge_cells += 1;
            if (1 === $participant)
                $merge_cells += 1;
            $column_key = 0;
            for ( $i = $start_line; $i <= $count_columns * $merge_cells; $i += $merge_cells ) {
                $two_rows = $i;
                $this->spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow($i, 1, $i + ($merge_cells-1), 1);
                $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i, 1)->setValueExplicit($columns[$column_key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($two_rows, 2)->setValue($m);
                if (1 === $duration)
                    $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($two_rows+1, 2)->setValue($d);
                if (1 === $participant)
                    $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($two_rows+2, 2)->setValue($p);
                $column_key++;
            }
        } else {
            $column_key = 0;
            for ( $i = $start_line; $i <= $count_columns * $merge_cells+1; $i += $merge_cells ){
                $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i,1)->setValueExplicit($columns[$column_key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i,2)->setValue($m);
                $column_key++;
            }
        }

        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)
            ->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns*$merge_cells+1, 1)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns*$merge_cells+1, 1)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $stat,  // The data to set
                '',        // Array values with this value will not be set
                $start_cells,
                true
            );
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,2, $count_stat)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,2, $count_stat)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return true;
    }

    function createFileVertical()
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_out_data = $this->settings->getSettings('format_out_data');
        $columns = $this->array_data[$this->settings->getSettings('columns')];
        $stat = $this->array_data[$this->settings->getSettings('dataSource')];
        $view = $this->settings->getSettings('view');
        $a1 = $dictionary['stat_device_date'];

        array_values($columns);
        array_unshift($columns, ''.$a1.'', ' ');
        array_push($columns, 'Total');

        if (1 === $format_out_data){
            $columns_upper = [];
            foreach ($columns as $column){
                $columns_upper[] = mb_strtoupper($column, 'UTF-8');
            }
            unset($columns);
            $columns = $columns_upper;
        }

        if (1 === $format_out_data && 'total_only' !== $view){
            for ($i=0;$i<11;$i++){
                array_pop($stat);
            }
        }

        $count_columns = count($columns);
        $count_stat = count($stat)+1;

        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $columns,  // The data to set
                NULL,        // Array values with this value will not be set
                'A1'         // Top left coordinate of the worksheet range where
            //    we want to set these values (default is A1)
            );
        if ( 1 === $format_out_data)
            $this->spreadsheet->getActiveSheet()->getCell('B1')->setValue(mb_strtoupper($dictionary['connection_type'], 'UTF-8'));
        $this->spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)->getAlignment()->setWrapText(true);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 1)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $stat,  // The data to set
                NULL,        // Array values with this value will not be set
                'A2',
                true
            );
        $key = 0;
        for ( $i=2; $i<$count_columns-1; $i++ )
        {
            $cell_data = isset($stat[$key]['dataRange']) ? $stat[$key]['dataRange'] : '';
            $this->spreadsheet->getActiveSheet()->getCell('A'.$i)->setValueExplicit($cell_data,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $key++;
        }
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getAlignment()->setWrapText(true);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2,2,$count_columns, $count_stat)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return true;
    }

    function createFileVerticalVmr()
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $columns = $this->array_data[$this->settings->getSettings('columns')];
        $number_vmr = array_shift($this->array_data[$this->settings->getSettings('dataSource')]);
        $stat = $this->array_data[$this->settings->getSettings('dataSource')];
        $stat_device_date = $dictionary['stat_device_date'];
        $vmr_name = $this->settings->getSettings('dictionary')['stat_vmr_number'];

        array_values($columns);
        array_unshift($columns, ''.$stat_device_date.'', ' ');
        array_push($columns, 'Total');

        $count_columns = count($columns);
        $count_stat = count($stat)+1;

        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $number_vmr,  // The data to set
                NULL,        // Array values with this value will not be set
                'A1'         // Top left coordinate of the worksheet range where
            //    we want to set these values (default is A1)
            );
        $this->spreadsheet->getActiveSheet()->getCell('B1')->setValue('', 'UTF-8');

        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $columns,  // The data to set
                NULL,        // Array values with this value will not be set
                'A2'         // Top left coordinate of the worksheet range where
            //    we want to set these values (default is A1)
            );

        $this->spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 2)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 2)->getAlignment()->setWrapText(true);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,$count_columns, 2)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $stat,  // The data to set
                NULL,        // Array values with this value will not be set
                'A3',
                true
            );
        $key = 0;
        for ( $i=3; $i<$count_columns-1; $i++ )
        {
            $cell_data = isset($stat[$key]['dataRange']) ? $stat[$key]['dataRange'] : '';
            $this->spreadsheet->getActiveSheet()->getCell('A'.$i)->setValueExplicit($cell_data,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $key++;
        }
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getFont()->setBold(1);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1,1,1, $count_stat)->getAlignment()->setWrapText(true);
        $this->spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2,2,$count_columns, $count_stat)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return true;
    }
}