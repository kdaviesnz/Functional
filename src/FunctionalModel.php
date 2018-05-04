<?php
declare(strict_types = 1);

// Checked for PSR2 compliance 26/4/2018

namespace kdaviesnz\functional;

/**
 * Class FunctionalModel
 * @package kdaviesnz\functional
 */
class FunctionalModel
{

    /**
     * @var string
     */
    public $template = "";

    /**
     * @var array
     */
    public $similarFunctions = array();
    /**
     * @var array
     */
    public $functionsWithLoops = array();
    /**
     * @var array
     */
    public $functionsWithMutatedVariables = array();

    /**
     * @var array
     */
    public $functionsWithVariablesUsedOnlyOnce = array();

    /**
     * @var array
     */
    public $functionsThatAreTooBig = array();

    /**
     * @var array
     */
    public $functionsThatAreNotPure = array();

    /**
     * @var string
     */
    public $sourceDir = ".";


    public $iterator;

    /**
     * FunctionalModel constructor.
     */
    public function __construct()
    {
        $this->iterator = new \kdaviesnz\callbackfileiterator\CallbackFileIterator();
    }

    /**
     * @return array
     */
    public function getFunctionsWithIssues(): array
    {
        $current_directory = $this->sourceDir;

        $this->functionsWithMutatedVariables = array();

        // Look for functions with mutated variables
        $callback = function (string $source_file) use ($current_directory) {

            // Get content of file.
            // Get functions/methods in file
            $functions = $this->getFunctions(file_get_contents($source_file));

            // For each function check for mutated variables, loops.
            array_walk($functions, function ($functionInfo, $index) use ($current_directory, $source_file) {

                if (isset($functionInfo["code"])) {
                    $functionsWithMutatedVariables = $this->checkForMutatedVariables( $functionInfo, $source_file );
                    if ( $functionsWithMutatedVariables ) {
                        $this->functionsWithMutatedVariables[] = $functionsWithMutatedVariables;
                    }

                    $functionsWithLoops = $this->checkForLoops( $functionInfo, $source_file );
                    if ( $functionsWithLoops ) {
                        $this->functionsWithLoops[] = $functionsWithLoops;
                    }

                    // This sets $this->similarFunctions
                    $this->checkForSimilarCode( $functionInfo, $current_directory, $source_file );

                    $variablesOnlyUsedOnce = $this->checkForVariablesUsedOnlyOnce( $functionInfo, $source_file );
                    if ( $variablesOnlyUsedOnce ) {
                        $this->functionsWithVariablesUsedOnlyOnce[] = $variablesOnlyUsedOnce;
                    }

                    $tooBigFunction = $this->checkForFunctionsThatAreTooBig( $functionInfo, $source_file );
                    if ( $tooBigFunction ) {
                        $this->functionsThatAreTooBig[] = $tooBigFunction;
                    }

                    $impureFunction = $this->checkForFunctionsThatAreNotPure( $functionInfo, $source_file );
                    if ( $impureFunction ) {
                        $this->functionsThatAreNotPure[] = $impureFunction;
                    }

                }
            });

        };

        $this->iterator->run($current_directory, $callback, true, false);

        return array(
            "mutatedVariables" => $this->functionsWithMutatedVariables,
            "loops" => $this->functionsWithLoops,
            "similarFunctions" => $this->similarFunctions,
            "functionsWithVariablesOnlyUsedOnce" => $this->functionsWithVariablesUsedOnlyOnce,
            "functionsThatAreTooBig" => $this->functionsThatAreTooBig,
            "functionsThatAreNotPure" => $this->functionsThatAreNotPure
        );

    }

    /**
     * @param array $functionInfo
     * @param string $source_file
     */
    private function checkForMutatedVariables(array $functionInfo, string $source_file)
    {
        if (strpos($functionInfo["code"], "++") !== false ||
            strpos($functionInfo["code"], "--") !== false ||
            strpos($functionInfo["code"], ".=") !== false
        ) {
            return array(
                "srcFile" => $source_file,
                "name"    => $functionInfo["name"]
            );
        } else {
            preg_match_all("/(\\$[a-zA-Z\_])*\s*\=\s*.+;/", $functionInfo["code"], $matches);
            if (!empty($matches[1])) {
                $variable_names = array_filter($matches[1], function ($item) {
                    return !empty(trim($item));
                });
                if (count($variable_names) != count(array_unique($variable_names))) {
                    return array(
                        "srcFile" => $source_file,
                        "name"    => $functionInfo["name"]
                    );
                }
            }
        }
        return false;
    }

    /**
     * @param array $functionInfo
     * @param string $source_file
     */
    private function checkForVariablesUsedOnlyOnce(array $functionInfo, string $source_file)
    {
        preg_match_all("/(\\$[a-zA-Z\_])*\s*\=\s*.+;/", $functionInfo["code"], $matches);

        if (!empty($matches[1])) {

            // Remove empty variables.
            $variable_names = array_filter($matches[1], function ($item) {
                return !empty(trim($item));
            });

            // Check for variables used only once or less.
            foreach ($variable_names as $variable_name) {
                if (substr_count($functionInfo["code"], $variable_name) < 3) {
                    return array(
                        "srcFile" => $source_file,
                        "name"    => $functionInfo["name"],
                        "variable" => $variable_name
                    );
                }
            }
        }

        return false;
    }

    /**
     * @param array $functionInfo
     * @param string $source_file
     */
    private function checkForFunctionsThatAreTooBig(array $functionInfo, string $source_file)
    {
        if (count(explode("\n", $functionInfo["code"])) > 25) {
            return array(
                "srcFile" => $source_file,
                "name"    => $functionInfo["name"],
            );
        }
        return false;
    }

    /**
     * @param array $functionInfo
     * @param string $source_file
     */
    public function checkForFunctionsThatArePure(array $functionInfo, string $source_file)
    {
        // If code has word "global" in it then it's not pure.
        if (strpos($functionInfo["code"], "global")!==false) {
            return false;
        }

        // If constructor then not pure.
        if ($functionInfo["name"] == "__construct") {
            return false;
        }

        // If code has "->" then not pure as referencing a class property.
        if (strpos($functionInfo["code"], "->")!==false) {
            return false;
        }

        // If code has $this->x = $y or class::x = $y then not pure.
        preg_match_all( "/this\-\>[a-zA-Z\_]*\s*\=\s*.+;/", $functionInfo["code"], $matches );
        if ( empty( $matches[0] ) ) {
            preg_match_all( "/\:\:[a-zA-Z\_]*\s*\=\s*.+;/", $functionInfo["code"], $matches );
        }
        if ( ! empty( $matches[0] ) ) {
            return false;
        }

        return true;
    }


    /**
     * @param array $functionInfo
     * @param string $source_file
     */
    private function checkForFunctionsThatAreNotPure(array $functionInfo, string $source_file)
    {

        // If code has word "global" in it then it's not pure.
        if (strpos($functionInfo["code"], "global")!==false) {
            return array(
                "srcFile" => $source_file,
                "name"    => $functionInfo["name"],
            );
        }

        // Ignore constructors.
        if ($functionInfo["name"] !== "__construct") {
            // If code has $this->x = $y or class::x = $y then not pure.
            preg_match_all( "/this\-\>[a-zA-Z\_]*\s*\=\s*.+;/", $functionInfo["code"], $matches );
            if ( empty( $matches[0] ) ) {
                preg_match_all( "/\:\:[a-zA-Z\_]*\s*\=\s*.+;/", $functionInfo["code"], $matches );
            }
            if ( ! empty( $matches[0] ) ) {
                return  array(
                    "srcFile" => $source_file,
                    "name"    => $functionInfo["name"],
                );
            }
        }

        return false;
    }

    /**
     * @param array $functionInfo
     * @param string $source_file
     */
    private function checkForLoops(array $functionInfo, string $source_file)
    {
        if (strpos($functionInfo["code"], "do(") !== false ||
            strpos($functionInfo["code"], "do (") !== false ||
            strpos($functionInfo["code"], "endwhile") !== false ||
            strpos($functionInfo["code"], "for (") !== false ||
            strpos($functionInfo["code"], "for(") !== false ||
            strpos($functionInfo["code"], "foreach(") !== false ||
            strpos($functionInfo["code"], "foreach (") !== false ||
            strpos($functionInfo["code"], "while") !== false
        ) {
            return array(
                "srcFile" => $source_file,
                "name"    => $functionInfo["name"]
            );
        }

        return false;
    }

    /**
     * @param array $functionInfo
     * @param string $current_directory
     * @param string $source_file
     */
    private function checkForSimilarCode(array $functionInfo, string $current_directory, string $source_file)
    {
        $function_to_compare_name = $functionInfo["name"];
        $function_code_to_compare = $functionInfo["code"];

        $callback = function (
            string $function_to_compare_name,
            string $function_code_to_compare,
            string $source_file
        ) {
            return function (
                string $target_file
            ) use ($function_to_compare_name, $function_code_to_compare, $source_file) {

                // Get functions
                $functions = $this->getFunctions(file_get_contents($target_file));

                // For each function compare with comparison function
                array_walk($functions,
                    function ($functionInfo, $index) use (
                        $function_to_compare_name,
                        $function_code_to_compare,
                        $target_file,
                        $source_file
                    ) {

                        if ($source_file == $target_file
                            && $function_to_compare_name == $functionInfo["name"]
                        ) {
                            // Do nothing.
                        } elseif (isset($functionInfo["code"]) && $this->isSimilar($function_code_to_compare, $functionInfo["code"])) {
                            $this->similarFunctions[] =
                                array(
                                    "srcFile"        => $source_file,
                                    "targetFile"     => $target_file,
                                    "srcFunction"    => $function_to_compare_name,
                                    "targetFunction" => $functionInfo["name"]
                                );
                        }

                    }
                );

            };
        };

        $this->iterator->run(
            $current_directory,
            $callback($function_to_compare_name, $function_code_to_compare, $source_file),
            true,
            false
        );

    }

    /**
     * @param string $class
     * @param string $method
     *
     * @return array
     * @throws \ReflectionException
     */
    private function getMethodCode(string $class, string $method): array
    {
        return $this->getCodeFromReflection($method, new \ReflectionMethod($class, $method), $class);
    }

    /**
     * @param string $function_name
     *
     * @return array
     * @throws \ReflectionException
     */
    private function getFunctionCode(string $function_name): array
    {
        return $this->getCodeFromReflection($function_name,  new \ReflectionFunction($function_name));
    }

    /**
     * @param string $method_or_function_name
     * @param $reflection
     *
     * @return array
     */
    private function getCodeFromReflection(string $method_or_function_name, $reflection, string $className=""):array
    {
        if (is_bool($reflection->getFileName())) {
            return array();
        }

        $file       = new \SplFileObject($reflection->getFileName());
        $file->seek($reflection->getStartLine());
        $code = "";
        do {
            $code .= $file->current();
            $file->next();
        } while ($file->key() < $reflection->getEndLine());

        return array(
            "className" => $className,
            "name" => $method_or_function_name,
            "code" => $this->stripComments($code)
        );
    }

    /**
     * @param array $tokenised_content
     * @param array $tokens
     *
     * @return array
     */
    private function addToken(array $tokenised_content, array $tokens, string $code): array
    {
        for ($i = 0; $i < count($tokens); $i ++) {
            $token = $tokens[$i];
            switch ($token[0]) {
                case T_CLASS:
                    $class = "";
                    do {
                        $i ++;
                        if (!is_string($tokens[$i][0]) && !empty(trim($tokens[$i][1]))) {
                            $class .= $tokens[$i][1];
                        }
                    } while (!is_string($tokens[$i][0]));

                    // Check for "extends"
                    preg_match("/(.*?)extends/", $class, $matches);
                    if (!empty($matches)) {
                        $class = $matches[1];
                    }

                    // Check for "implements"
                    preg_match("/(.*?)implements/", $class, $matches);
                    if (!empty($matches)) {
                        $class = $matches[1];
                    }

                    $class                     = $tokenised_content["namespace"] . "\\" . $class;
                    $tokenised_content["class"] = $class;
                    $methods                   = get_class_methods($class);
                    // $methods will be null if the class hasn't been loaded.
                    if (empty($methods)) {
                        $methods_with_code = $this->parseClassCode($code, $class);
                    } else {

                        $methods_with_code = array_map(
                            function ( $method ) use ( $class ) {
                                return $this->getMethodCode( $class, $method );
                            },
                            $methods
                        );
                    }

                    $tokenised_content["methods"] = $methods_with_code;

                    break;

                case T_NAMESPACE:
                    $namespace = "";
                    do {
                        $i ++;
                        if (!is_string($tokens[$i][0]) && !empty(trim($tokens[$i][1]))) {
                            $namespace .= $tokens[$i][1];
                        }
                    } while (!is_string($tokens[$i][0]));
                    $tokenised_content["namespace"] = $namespace;
                    break;

                case T_FUNCTION:
                    $function_name = "\\";
                    do {
                        $i ++;
                        if (!is_string($tokens[$i][0]) && !empty(trim($tokens[$i][1]))) {
                            $function_name .= $tokens[$i][1];
                        }
                    } while (!is_string($tokens[$i][0]));

                    if (function_exists($function_name)) { //
                        $tokenised_content["functions"][] = $this->getFunctionCode($function_name);
                    } else {
                        $tokenised_content["functions"][] = $this->parseFunctionCode($function_name, $code);
                    }
            }
        }


        return $tokenised_content;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getTokenisedContent(string $content)
    {

        $tokens = token_get_all($content);


        $tokenised_content = array();
        $tokenised_content = $this->addToken($tokenised_content, $tokens, $content);

        return $tokenised_content;

    }

    /**
     * @param string $content
     *
     * @return array
     */
    public function getFunctions(string $content): array
    {

        $functions = array();

        $tokenised_content = $this->getTokenisedContent($content);

        // Check for $tokenised_content["methods"];
        if (isset($tokenised_content["methods"])) {
            $functions = $tokenised_content["methods"];
        } elseif (isset($tokenised_content["functions"])) {
            $functions = $tokenised_content["functions"];
        }

        return $functions;

    }

    /**
     * @param string $comparisonFunctionContent
     * @param string $currentFunctionContent
     *
     * @return bool
     */
    private function isSimilar(string $comparisonFunctionContent, string $currentFunctionContent): bool
    {
        $numberOfcomparisonFunctionContentLines = array_filter(
            explode("\n", $comparisonFunctionContent),
            function($line){
                return !empty(trim($line));
            }
        );

        $currentFunctionContentLines = array_filter(
            explode("\n", $currentFunctionContent),
            function($line){
                return !empty(trim($line));
            }
        );

        if (count($numberOfcomparisonFunctionContentLines) <= 5 || count($currentFunctionContentLines) <= 5 ) {
            return false;
        }

        similar_text($comparisonFunctionContent, $currentFunctionContent, $perc);

        return $perc > 60;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function stripComments(string $content): string
    {
        $tokens = token_get_all($content);

        $content_sans_comments = array_reduce(
            $tokens,
            function ($carry, $token) {
                if (is_string($token)) {
                    // simple 1-character token
                    return $carry . $token;
                } else {
                    // token array
                    list($id, $text) = $token;
                    switch ($id) {
                        case T_COMMENT:
                        case T_DOC_COMMENT:
                            // no action on comments
                            break;
                        default:
                            // anything else -> output "as is"
                            return $carry . $text;
                            break;
                    }
                }

                return $carry;
            },
            ""
        );
        return $content_sans_comments;
    }

    /**
     * @param string $code
     *
     * @return array
     */
    private function parseClassCode(string $code, string $className):array
    {
        $codeSansComments = $this->stripComments($code);
        $lines = array_map("trim", explode("\n", $codeSansComments));
        preg_match_all("/p[a-zA-Z]*\sfunction\s+([a-zA-Z\_]*)\(.*?\).*/ui", $code, $matches);

        if (!empty($matches[0])) {

            $functions = array_map(function ($item, $key) use ($lines, $matches, $className) {

                $startLineNumber = array_search($item, $lines);
                if ($startLineNumber) {
                    if (isset($matches[0][$key+1])) {
                        $endLineNumber =  array_search($matches[0][$key+1], $lines);
                    }
                    if (isset($endLineNumber) && is_int($endLineNumber)) {
                        $code = trim(implode("\n", array_slice( $lines, $startLineNumber, $endLineNumber - $startLineNumber )));
                    } else {
                        $code = trim(implode("\n", array_slice( $lines, $startLineNumber )));
                    }
                }

                return array(
                    "className" => $className,
                    "name" => $matches[1][$key],
                    "code" => isset($code)?$code:""
                );

            }, $matches[0], array_keys($matches[0]));

            return $functions;

        }

        return array();

    }

    private function parseFunctionCode(string $function_name, string $code):array
    {
        $codeSansComments = $this->stripComments($code);
        $lines = array_map("trim", explode("\n", $codeSansComments));
        preg_match_all("/function\s+([a-zA-Z\_]*)\(.*?\).*/ui", $code, $matches);

        if (!empty($matches[0])) {

            // Get all functions from the file.
            $functions = array_map(function ($item, $key) use ($lines, $matches) {

                $startLineNumber = array_search($item, $lines);
                if ($startLineNumber) {
                    if (isset($matches[0][$key+1])) {
                        $endLineNumber =  array_search($matches[0][$key+1], $lines);
                    }
                    if (isset($endLineNumber) && is_int($endLineNumber)) {
                        $code = trim(implode("\n", array_slice( $lines, $startLineNumber, $endLineNumber - $startLineNumber )));
                    } else {
                        $code = trim(implode("\n", array_slice( $lines, $startLineNumber )));
                    }
                }

                return array(
                    "name" => $matches[1][$key],
                    "code" => isset($code)?$code:""
                );

            }, $matches[0], array_keys($matches[0]));

            // Return the matching function.
            $selectedFunctions = array_filter($functions, function($item) use ($function_name){
                return "\\" . $item["name"] == $function_name;
            });

            if (empty($selectedFunctions)) {
                return array();
            } else {
                return array_pop($selectedFunctions);
            }
        }

        return array();

    }
}
