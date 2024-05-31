<?php
    require_once('app.php');
    require_once('SimpleXLSX.php');

    $app = new app;
    try {
        $data = $app->handleRequest();
    } catch (Exception $e) {
    }
?>
<html>
    <head>
        <title>SEPA-Generator</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    </head>
    <body>
        <nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <div class="col-8">
                    <h2>SEPA-Generator</h2>
                </div>
                <div class="col-4">
                    <?php if ($_SERVER['REQUEST_METHOD'] === "POST") { ?>
                        <span class="float-right ml-2"><a href="/" class="btn btn-danger">Abbrechen</a></span>
                    <?php } ?>
                    <span class="float-right"><a href="example.xlsx" class="btn btn-primary">Download Beispieldatei</a></span>

                </div>
            </div>

            <hr>
            <?php if($data['incorrectMimeType']){ ?>
                <div class="alert alert-danger" role="alert">
                    Falsches Dateiformat. Nur Excel-Dateien erlaubt (.xls / .xslx)
                </div>
            <?php } ?>
            <?php if($data['incorrectSettings']){ ?>
                <div class="alert alert-danger" role="alert">
                    Bankdaten unvollständig / falsch
                </div>
            <?php } ?>


                <?php if ($_SERVER['REQUEST_METHOD'] === "GET") { ?>
                    <div class="row align-items-center">
                        <div class="col"></div>
                        <div class="col-6">
                            <form name="upload" method="post" enctype="multipart/form-data">
                            <div class="card border-dark" style="min-height: 210px;">
                                <div class="card-body">
                                    <h3 class="card-title">Excel hochladen</h3>
                                    <div class="row mt-5">
                                        <div class="col-lg-12">
                                            <input type="file" name="xlsx" id="xlsx">
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-lg-12">
                                            <input type="submit" class="btn btn-success form-control" value="Datei hochladen">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </form>
                        </div>
                        <div class="col"></div>
                    </div>
                <?php } ?>


            <div class="row">
                <?php if (isset($data['xlsSettings'])){ ?>
                    <div class="col-12">
                        <div class="card border-dark">
                            <div class="card-body">
                                <h3 class="card-title">Einstellungen</h3>
                                <div class="row">
                                    <div class="col-3">
                                        Kreditor: <pre><?php echo $data['xlsSettings']['creditor']; ?></pre>
                                        IBAN: <pre><?php echo $data['xlsSettings']['iban']; ?></pre>
                                    </div>
                                    <div class="col-3">
                                        Referenz-Text: <pre><?php echo $data['xlsSettings']['reference']; ?></pre>
                                        BIC: <pre><?php echo $data['xlsSettings']['bic']; ?></pre>

                                    </div>
                                    <div class="col-3">
                                        Datum: <pre><?php echo $data['xlsSettings']['collectionDate']; ?></pre>
                                        Bank: <pre><?php echo $data['xlsSettings']['bank']; ?></pre>

                                    </div>
                                    <div class="col-3">
                                        Gläubiger-ID: <pre><?php echo $data['xlsSettings']['mandatorId']; ?></pre>
                                        <a href="#" class="btn btn-success form-control mt-3" id="downloadSepa"><i class="fa fa-download"></i> Download SEPA-Datei</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="exportSepa.php" method="POST" id="downloadForm">
                        <input type="hidden" name="sepaData" value='<?php echo serialize($data['xlsData']); ?>'>
                        <input type="hidden" name="sepaSettings" value='<?php echo serialize($data['xlsSettings']); ?>'>
                        <input type="hidden" name="sepaTransactionNumber" value='<?php echo $data['numberOfTransactions']; ?>'>
                        <input type="hidden" name="sepaAmount" value='<?php echo $data['transactionSum']; ?>'>
                    </form>
                <?php } ?>
            </div>

            <!-- OUTPUT -->
            <?php if (isset($data['xlsViewData'])) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-dark">
                            <div class="card-body">
                                <h3 class="card-title">Buchungssätze</h3>

                                <table class="table table-striped">
                                    <tr class="thead-dark">
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>IBAN</th>
                                        <th>BIC</th>
                                        <th>Betrag</th>
                                        <th>Datum des SEPA-Mandates</th>
                                        <th>Mandatsreferenz</th>
                                    </tr>
                                    <?php for($i = 0; $i< count($data['xlsViewData']); $i++) { ?>
                                        <tr>
                                            <td><?php echo $i+1; ?></td>
                                            <?php foreach($data['xlsViewData'][$i] as $col) { ?>
                                                <td><?php echo $col; ?></td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                    <tr class="thead-dark">
                                        <th>&nbsp;</th>
                                        <th colspan="3">Anzahl Datensätze: <?php echo $data['numberOfTransactions']; ?></th>
                                        <th>&euro; <?php echo number_format($data['transactionSum'],2,",","."); ?></th>
                                        <th colspan="2">&nbsp;</th>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
        <script>
            var $downloadForm = $('#downloadForm');

            $('#downloadSepa').on('click', function(){
                $downloadForm.submit();
            });
        </script>
    </body>
</html>

