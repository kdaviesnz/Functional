<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<?php

		require_once("vendor/autoload.php");
		require_once("src/FunctionalController.php");
        require_once("src/FunctionalModel.php");
        require_once("src/FunctionalView.php");

        $model = new \kdaviesnz\functional\FunctionalModel();
        $controller = new \kdaviesnz\functional\FunctionalController($model);

        $controller->setSourceDir("src");

        $view = new \kdaviesnz\functional\FunctionalView($controller, $model);
        echo $view->outputFunctionsWithMutatedVariables();
        echo $view->outputFunctionsWithLoops();
        echo $view->outputSimilarFunctions();

?>
</body>
</html>