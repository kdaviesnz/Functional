<?php
declare(strict_types = 1);

// Checked for PSR2 compliance 22/4/2018

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
     * @var string
     */
    public $sourceDir = ".";

    /**
     * FunctionalModel constructor.
     */
    public function __construct()
    {
        // Do nothing
    }

    /**
     * @return array
     */
    public function getFunctionsWithMutatedVariables(): array
    {
        $current_directory = $this->sourceDir;

        $this->functionsWithMutatedVariables = array();

        // Look for functions with mutated variables
        $callback = function (string $source_file) use ($current_directory) {

            // Get content of file.
            // Get functions/methods in file
            $functions = $this->getFunctions(file_get_contents($source_file));

            // For each function check for mutated variables.
            array_walk($functions, function ($functionInfo, $index) use ($current_directory, $source_file) {

                $function_code = $functionInfo["code"];

                if (strpos($function_code, "++") !== false ||
                    strpos($function_code, "--") !== false ||
                    strpos($function_code, ".=") !== false
                ) {
                    $this->functionsWithMutatedVariables[] = array(
                        "srcFile" => $source_file,
                        "name"    => $functionInfo["name"]
                    );
                } else {
                    preg_match_all("/(\\$[a-zA-Z\_])*\s*\=\s*.+;/", $function_code, $matches);
                    if (!empty($matches[1])) {
                        $variable_names = array_filter($matches[1], function ($item) {
                            return !empty(trim($item));
                        });
                        if (count($variable_names) != count(array_unique($variable_names))) {
                            $this->functionsWithMutatedVariables[] = array(
                                "srcFile" => $source_file,
                                "name"    => $functionInfo["name"]
                            );
                        }
                    }
                }

            });

        };

        new \kdaviesnz\callbackfileiterator\CallbackFileIterator($current_directory, $callback, true);

        return $this->functionsWithMutatedVariables;
    }

    /**
     * @return array
     */
    public function getFunctionsWithLoops(): array
    {
        $current_directory = $this->sourceDir;

        $this->functionsWithLoops = array();

        // Look for functions with loops
        $callback = function (string $source_file) use ($current_directory) {

            // Get content of file.
            // Get functions/methods in file
            $functions = $this->getFunctions(file_get_contents($source_file));

            // For each function check for loops.
            array_walk($functions, function ($functionInfo, $index) use ($current_directory, $source_file) {

                $function_code = $functionInfo["code"];

                if (strpos($function_code, "do(") !== false ||
                    strpos($function_code, "do (") !== false ||
                    strpos($function_code, "endwhile") !== false ||
                    strpos($function_code, "for (") !== false ||
                    strpos($function_code, "for(") !== false ||
                    strpos($function_code, "foreach(") !== false ||
                    strpos($function_code, "foreach (") !== false ||
                    strpos($function_code, "while") !== false
                ) {
                    $this->functionsWithLoops[] = array(
                        "srcFile" => $source_file,
                        "name"    => $functionInfo["name"]
                    );
                }

            });
        };

        new \kdaviesnz\callbackfileiterator\CallbackFileIterator($current_directory, $callback, true);

        return $this->functionsWithLoops;
    }

    /**
     * @return array
     */
    public function getSimilarFunctions(): array
    {
        $current_directory = $this->sourceDir;

        $this->similarFunctions = array();

        // Check functions for common code.
        // If found then recommend code be passed in as a function parameter.
        $callback = function (string $source_file) use ($current_directory) {

            // Get content of file.
            // Get functions/methods in file
            $functions_to_compare = $this->getFunctions(file_get_contents($source_file));

            // For each function check other files for similar functions.
            // If similar function found then inform user.
            array_walk($functions_to_compare, function ($functionInfo, $index) use ($current_directory, $source_file) {

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
                                } elseif ($this->isSimilar($function_code_to_compare, $functionInfo["code"])) {
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

                new \kdaviesnz\callbackfileiterator\CallbackFileIterator(
                    $current_directory,
                    $callback($function_to_compare_name, $function_code_to_compare, $source_file),
                    true
                );

            });

        };

        new \kdaviesnz\callbackfileiterator\CallbackFileIterator($current_directory, $callback, true);

        return $this->similarFunctions;

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
        return $this->getCodeFromReflection($method, new \ReflectionMethod($class, $method));
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
    private function getCodeFromReflection(string $method_or_function_name, $reflection):array
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
    private function addToken(array $tokenised_content, array $tokens): array
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


                    $methods_with_code = array_map(
                        function ($method) use ($class) {
                            return $this->getMethodCode($class, $method);
                        },
                        $methods
                    );

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

                    if (function_exists($function_name)) {
                        $tokenised_content["functions"][] = $this->getFunctionCode($function_name);
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
        $tokenised_content = $this->addToken($tokenised_content, $tokens);
        return $tokenised_content;

    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getFunctions(string $content): array
    {

        $tokenised_content = $this->getTokenisedContent($content);
        $functions = array();

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
}
