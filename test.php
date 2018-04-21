<?php

require_once("vendor/autoload.php");
require_once("src/FunctionalController.php");
require_once("src/FunctionalModel.php");
require_once("src/FunctionalView.php");
require_once("src/functions.php");

$model = new \kdaviesnz\functional\FunctionalModel();
$controller = new \kdaviesnz\functional\FunctionalController($model);

$controller->setSourceDir("src");
$controller->setTemplate("src/templates/main.html.php");

$view = new \kdaviesnz\functional\FunctionalView($controller, $model);
$view->output();
