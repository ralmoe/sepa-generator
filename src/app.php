<?php

    class app
    {
        var $allowedMimeTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        var $simpleXLS;

        /**
         * APP CONSTRUCTOR
         */
        public function __construct()
        {
            $this->simpleXLS = new SimpleXLSX();
        }

        /**
         * HANDLE REQUESTS
         *
         * @return array
         * @throws Exception
         */
        public function handleRequest()
        {
            $data = [];

            if ($_SERVER['REQUEST_METHOD'] === "POST") {
                $this->checkMimeType($data, $_FILES['xlsx']);

                if (!$data['incorrectMimeType']) {
                    $this->processExcel($data, $_FILES['xlsx']);
                }
            }

            $data['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

            return ($data);
        }

        /**
         * LOAD TEMPLATE
         *
         * @param $tempate
         * @return false|string
         */
        public function loadTemplate($tempate){
            return(file_get_contents('templates/'.$tempate));
        }

        /**
         * REPLACE PLACEHOLDERS IN TEMPLATE
         *
         * @param $template
         * @param $replacements
         * @return string|string[]|null
         */
        public function parseTemplate($template, $replacements){
            foreach($replacements as $key => $replacement){
                $template = preg_replace('/###'.$key.'###/',$replacement,$template);
            }

            return($template);
        }

        /**
         * CHECK IF MIME-TYPE IS ALLOWED
         *
         * @param $data
         * @param $file
         */
        private function checkMimeType(&$data, $file)
        {
            $data['incorrectMimeType'] = true;
            if (in_array($file['type'], $this->allowedMimeTypes)) {
                $data['incorrectMimeType'] = false;
            }
        }

        /**
         * PROCESS EXCEL-FILE
         *
         * @param $data
         * @param $file
         * @throws Exception
         */
        private function processExcel(&$data, $file)
        {
            $xls = $this->simpleXLS::parse($file['tmp_name']);
            $this->processExcelData($data, $xls->rows(0));
            $this->processExcelSettings($data, $xls->rows(1));
        }

        /**
         * PROCESS DATA-SHEET
         *
         * @param $data
         * @param $rawData
         * @throws Exception
         */
        private function processExcelData(&$data, $rawData)
        {
            $xlsData        = [];
            $xlsViewData    = [];

            $sum = 0;
            $numberOfTransactions = 0;
            for ($i = 0; $i < count($rawData); $i++) {
                $rowData        = [];
                $rowViewData    = [];
                if ($i === 0) {
                    continue;
                }
                $numberOfTransactions++;
                for ($j = 0; $j < count($rawData[$i]); $j++) {
                    if ($j === 4) {
                        $date = new DateTime($rawData[$i][$j]);
                        $rowViewData[]  = $date->format("d.m.Y");
                        $rowData[]      = $date->format("d.m.Y");
                    } elseif ($j === 3) {
                        $rowViewData[]  = "&euro; ".number_format($rawData[$i][$j],2,",",".");
                        $rowData[]      = $rawData[$i][$j];
                        $sum += $rawData[$i][$j];
                    } else {
                        $rowViewData[]  = $rawData[$i][$j];
                        $rowData[]      = $rawData[$i][$j];
                    }
                }
                $xlsData[]      = $rowData;
                $xlsViewData[]  = $rowViewData;
            }

            $data['xlsData']                = $xlsData;
            $data['xlsViewData']            = $xlsViewData;
            $data['transactionSum']         = $sum;
            $data['numberOfTransactions']   = $numberOfTransactions;
        }

        /**
         * PROCESS SETTINGS-SHEET
         *
         * @param $data
         * @param $rawData
         * @throws Exception
         */
        private function processExcelSettings(&$data, $rawData)
        {
            $xlsSettings = [];

            foreach ($rawData as $row) {
                switch ($row[0]) {
                    case "Kreditor":
                        $xlsSettings['creditor'] = $row[1];
                        break;
                    case "Gläubiger-ID":
                        $xlsSettings['mandatorId'] = $row[1];
                        break;
                    case "Referenz":
                        $xlsSettings['reference'] = $row[1];
                        break;
                    case "IBAN":
                        $xlsSettings['iban'] = $row[1];
                        break;
                    case "BIC":
                        $xlsSettings['bic'] = $row[1];
                        break;
                    case "Institut":
                        $xlsSettings['bank'] = $row[1];
                        break;
                    case "Einzugsdatum":
                        $date = new DateTime($row[1]);
                        $xlsSettings['collectionDate'] = $date->format("Y-m-d");
                        break;
                }
            }

            if (isset($xlsSettings['reference']) && isset($xlsSettings['iban']) && isset($xlsSettings['bic']) && isset($xlsSettings['bank'])) {
                $data['xlsSettings'] = $xlsSettings;
                $data['incorrectSettings'] = false;
            } else {
                $data['incorrectSettings'] = true;
            }
        }

    }
