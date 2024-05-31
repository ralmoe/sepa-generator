<?php
    require_once('app.php');
    require_once('SimpleXLSX.php');

    $app = new app();

    $now                    = new DateTime();
    $sepaData               = unserialize($_POST['sepaData']);
    $sepaSettings           = unserialize($_POST['sepaSettings']);
    $sepaTransactionNumber  = $_POST['sepaTransactionNumber'];
    $sepaAmount             = $_POST['sepaAmount'];

    $globalTemplate = $app->loadTemplate('sepa.xml.tpl');
    $recordTemplate = $app->loadTemplate('sepa.transaction.xml.tpl');

    $recordsContent = '';

    foreach($sepaData as $sepaRecord){
        $sepaDate = DateTime::createFromFormat("d.m.Y",$sepaRecord[4]);

        $recordReplacements = [
            'ENDTOEND'      => 'NOTPROVIDED',
            'CUSTOMERID'    => $sepaRecord[5],
            'MANDATEID'     => $sepaRecord[5],
            'AMOUNT'        => number_format($sepaRecord[3],2,".",""),
            'DEBITORBIC'    => $sepaRecord[2],
            'DEBITORIBAN'   => $sepaRecord[1],
            'SEPADATE'      => $sepaDate->format("Y-m-d"),
            'DEBITORNAME'   => $sepaRecord[0],
            'DESCRIPTION'   => $sepaSettings['reference']
        ];

        $recordsContent .= $app->parseTemplate($recordTemplate,$recordReplacements);
    }

    $globalReplacements = [
        'TIMESTAMP'         => $now->getTimestamp(),
        'DATETIME'          => $now->format("Y-m-d\TH:i:s"),
        'CREDITOR'          => $sepaSettings['creditor'],
        'NUMOFTRANSACTIONS' => $sepaTransactionNumber,
        'SUMOFTRANSACTIONS' => number_format($sepaAmount,2,".",""),
        'COLLECTIONDATE'    => $sepaSettings['collectionDate'],
        'CREDITORIBAN'      => $sepaSettings['iban'],
        'CREDITORBIC'       => $sepaSettings['bic'],
        'MANDATEID'         => $sepaSettings['mandatorId'],
        'TRANSACTIONS'      => $recordsContent
    ];

    $globalTemplate = $app->parseTemplate($globalTemplate,$globalReplacements);

    $filename = $now->format("Y-m-d_H-i-s")."_SEPA.xml";

    header("Content-Type: text/xml");
    header("Content-disposition: attachment; filename=$filename");
    ob_clean();
    flush();
    echo $globalTemplate;
