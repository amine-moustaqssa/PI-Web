<?php
require 'vendor/autoload.php';
$k = new App\Kernel('dev', true);
$k->boot();
$c = $k->getContainer()->get('doctrine')->getConnection();
echo json_encode($c->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Disponibilite'")->fetchAllAssociative(), JSON_PRETTY_PRINT);
