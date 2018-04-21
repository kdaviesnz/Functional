<?php
declare(strict_types=1);

// Checked for PSR2 compliance 21/4/2018

namespace kdaviesnz\functional;

/**
 * Class FunctionalController
 * @package kdaviesnz\functional
 */
class FunctionalController
{
    /**
     * @var FunctionalModel
     */
    private $functionalModel;

    /**
     * FunctionalController constructor.
     *
     * @param FunctionalModel $model
     */
    public function __construct(FunctionalModel $model)
    {
        $this->functionalModel = $model;
    }

    /**
     * @param string $sourceDir
     */
    public function setSourceDir(string $sourceDir)
    {
        $this->functionalModel->sourceDir = $sourceDir;
    }
}
