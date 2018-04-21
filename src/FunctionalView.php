<?php
declare(strict_types=1); // must be first line

// Checked for PSR2 compliance 21/4/2018

namespace kdaviesnz\functional;

/**
 * Class FunctionalView
 * @package kdaviesnz\functional
 */
class FunctionalView
{
    /**
     * @var FunctionalModel
     */
    private $functionalModel;
    /**
     * @var FunctionalController
     */
    private $functionalController;

    /**
     * FunctionalView constructor.
     *
     * @param FunctionalController $controller
     * @param FunctionalModel $model
     */
    public function __construct(FunctionalController $controller, FunctionalModel $model)
    {
        $this->functionalController = $controller;
        $this->functionalModel = $model;
    }


    /**
     *
     */
    public function output()
    {
        $functionsWithMutatedVariablesHTML = $this->outputFunctionsWithMutatedVariables();
        $functionsWithLoopsHTML = $this->outputFunctionsWithLoops();
        $similarFunctionsHTML = $this->outputSimilarFunctions();
        require_once($this->functionalModel->template);
    }

    /**
     *
     */
    public function outputFunctionsWithMutatedVariables():string
    {
        ob_start();
        $functionsWithMutatedVariables = $this->functionalModel->getFunctionsWithMutatedVariables();
        $this->render($functionsWithMutatedVariables, $this->functionsWithMutatedVariablesHTML());
        return ob_get_clean();
    }

    /**
     *
     */
    public function outputFunctionsWithLoops():string
    {
        ob_start();
        $functionsWithLoops = $this->functionalModel->getFunctionsWithLoops();
        $this->render($functionsWithLoops, $this->functionsWithLoopsHTML());
        return ob_get_clean();
    }

    /**
     *
     */
    public function outputSimilarFunctions():string
    {
        ob_start();
        $similar_functions = $this->functionalModel->getSimilarFunctions();
        $this->render($similar_functions, $this->similarFunctionsHTML());
        return ob_get_clean();
    }

    /**
     * @return Callable
     */
    private function similarFunctionsHTML():Callable
    {
        return function (array $item) {
            ?>
            <p>Method/Function <?php echo $item["srcFunction"]; ?>() in <?php echo $item["srcFile"]; ?> is very similar
                to <?php echo $item["targetFunction"]; ?>() in <?php echo $item["targetFile"]; ?> </p>
            <?php
        };
    }

    /**
     * @return Callable
     */
    private function functionsWithLoopsHTML():Callable
    {
        return function (array $item) {
            ?>
            <p>Method/Function <?php echo $item["name"]; ?>() in
                <?php echo $item["srcFile"]; ?> contains at least one loop construct.
                Consider replacing loop constructs with array_*</p>
            <?php
        };
    }

    /**
     * @return Callable
     */
    private function functionsWithMutatedVariablesHTML():Callable
    {
        return function (array $item) {
            ?>
            <p>Method/Function <?php echo $item["name"]; ?>() in
                <?php echo $item["srcFile"]; ?> contains at least mutated variable.
                Consider assigning to a new variable when variable is mutated.</p>
            <?php
        };
    }

    /**
     * @param array $items
     * @param callable $html_callback
     */
    private function render(array $items, callable $html_callback)
    {
        array_walk(
            $items,
            function (array $item) use ($html_callback) {
                $html_callback($item);
            }
        );
    }
}
