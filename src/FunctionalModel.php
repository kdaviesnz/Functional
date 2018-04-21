<?php
declare(strict_types = 1);

namespace kdaviesnz\functional;

/**
 * Class FunctionalModel
 * @package kdaviesnz\functional
 */
class FunctionalModel {
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
    public function __construct() {
        // Do nothing
    }

    /**
     * @return array
     */
    public function getFunctionsWithMutatedVariables(): array {
        $currentDirectory = $this->sourceDir;

        $this->functionsWithMutatedVariables = array();

        // Look for functions with mutated variables
        $callback = function ( string $sourceFile ) use ( $currentDirectory ) {

            // Get content of file.
            // Get functions/methods in file
            $functions = $this->getFunctions( file_get_contents( $sourceFile ) );

            // For each function check for mutated variables.
            array_walk( $functions, function ( $functionInfo, $index ) use ( $currentDirectory, $sourceFile ) {

                $functionCode = $functionInfo["code"];

                if ( strpos( $functionCode, "++" ) !== false ||
                     strpos( $functionCode, "--" ) !== false ||
                     strpos( $functionCode, ".=" ) !== false
                ) {
                    $this->functionsWithMutatedVariables[] = array(
                        "srcFile" => $sourceFile,
                        "name"    => $functionInfo["name"]
                    );
                } else {
                    preg_match_all( "/(\\$[a-zA-Z\_])*\s*\=\s*.+;/", $functionCode, $matches );
                    if ( ! empty( $matches[1] ) ) {
                        $variableNames = array_filter( $matches[1], function ( $item ) {
                            return ! empty( trim( $item ) );
                        } );
                        if ( count( $variableNames ) != count( array_unique( $variableNames ) ) ) {
                            $this->functionsWithMutatedVariables[] = array(
                                "srcFile" => $sourceFile,
                                "name"    => $functionInfo["name"]
                            );
                        }
                    }
                }

            } );

        };

        $iterator = new \kdaviesnz\callbackfileiterator\CallbackFileIterator( $currentDirectory, $callback, true );

        return $this->functionsWithMutatedVariables;
    }

    /**
     * @return array
     */
    public function getFunctionsWithLoops(): array {
        $currentDirectory = $this->sourceDir;

        $this->functionsWithLoops = array();

        // Look for functions with loops
        $callback = function ( string $sourceFile ) use ( $currentDirectory ) {

            // Get content of file.
            // Get functions/methods in file
            $functions = $this->getFunctions( file_get_contents( $sourceFile ) );

            // For each function check for loops.
            array_walk( $functions, function ( $functionInfo, $index ) use ( $currentDirectory, $sourceFile ) {

                $functionCode = $functionInfo["code"];

                if ( strpos( $functionCode, "do(" ) !== false ||
                     strpos( $functionCode, "do (" ) !== false ||
                     strpos( $functionCode, "endwhile" ) !== false ||
                     strpos( $functionCode, "for (" ) !== false ||
                     strpos( $functionCode, "for(" ) !== false ||
                     strpos( $functionCode, "foreach(" ) !== false ||
                     strpos( $functionCode, "foreach (" ) !== false ||
                     strpos( $functionCode, "while" ) !== false
                ) {
                    $this->functionsWithLoops[] = array(
                        "srcFile" => $sourceFile,
                        "name"    => $functionInfo["name"]
                    );
                }

            } );
        };

        $iterator = new \kdaviesnz\callbackfileiterator\CallbackFileIterator( $currentDirectory, $callback, true );

        return $this->functionsWithLoops;
    }

    /**
     * @return array
     */
    public function getSimilarFunctions(): array {
        $currentDirectory = $this->sourceDir;

        $this->similarFunctions = array();

        // Check functions for common code.
        // If found then recommend code be passed in as a function parameter.
        $callback = function ( string $sourceFile ) use ( $currentDirectory ) {

            // Get content of file.
            // Get functions/methods in file
            $functionsToCompare = $this->getFunctions( file_get_contents( $sourceFile ) );

            // For each function check other files for similar functions.
            // If similar function found then inform user.
            array_walk( $functionsToCompare, function ( $functionInfo, $index ) use ( $currentDirectory, $sourceFile ) {

                $functionToCompareName = $functionInfo["name"];
                $functionCodeToCompare = $functionInfo["code"];

                $callback = function (
                    string $functionToCompareName,
                    string $functionCodeToCompare,
                    string $currentDirectory,
                    string $sourceFile
                ) {
                    return function (
                        string $targetFile
                    ) use ( $functionToCompareName, $functionCodeToCompare, $sourceFile ) {

                        // Get functions
                        $functions = $this->getFunctions( file_get_contents( $targetFile ) );

                        // For each function compare with comparison function
                        array_walk( $functions,
                            function ( $functionInfo, $index ) use (
                                $functionToCompareName,
                                $functionCodeToCompare,
                                $targetFile,
                                $sourceFile
                            ) {


                                if ( $this->isSimilar( $functionCodeToCompare, $functionInfo["code"] ) ) {
                                    $this->similarFunctions[] =
                                        array(
                                            "srcFile"        => $sourceFile,
                                            "targetFile"     => $targetFile,
                                            "srcFunction"    => $functionToCompareName,
                                            "targetFunction" => $functionInfo["name"]
                                        );
                                }

                            } );


                    };
                };

                $iterator = new \kdaviesnz\callbackfileiterator\CallbackFileIterator(
                    $currentDirectory,
                    $callback( $functionToCompareName, $functionCodeToCompare, $currentDirectory, $sourceFile ),
                    true );

            } );

        };

        $iterator = new \kdaviesnz\callbackfileiterator\CallbackFileIterator( $currentDirectory, $callback, true );

        return $this->similarFunctions;

    }

    /**
     * @param string $class
     * @param string $method
     *
     * @return array
     * @throws \ReflectionException
     */
    private function getMethodCode( string $class, string $method ): array {
        $reflection = new \ReflectionMethod( $class, $method );
        $file       = new \SplFileObject($reflection->getFileName());
        $file->seek( $reflection->getStartLine() );
        $code = "";
        do {
            $code .= $file->current();
            $file->next();
        } while ( $file->key() < $reflection->getEndLine() );

        return array(
            "name" => $method,
            "code" => $this->stripComments( $code )
        );
    }

    /**
     * @param array $tokenisedContent
     * @param array $tokens
     *
     * @return array
     */
    private function addToken( array $tokenisedContent, array $tokens ): array {

        $i = 0;
        for ( $i = 0; $i < count( $tokens ); $i ++ ) {
            $token = $tokens[ $i ];
            switch ( $token[0] ) {
                case T_CLASS:
                    $class = "";
                    do {
                        $i ++;
                        if ( ! is_string( $tokens[ $i ][0] ) && ! empty( trim( $tokens[ $i ][1] ) ) ) {
                            $class .= $tokens[ $i ][1];
                        }
                    } while ( ! is_string( $tokens[ $i ][0] ) );

                    $class                     = $tokenisedContent["namespace"] . "\\" . $class;
                    $tokenisedContent["class"] = $class;
                    $methods                   = get_class_methods( $class );

                    $methodsWithCode = array_map(
                        function ( $method ) use ( $class ) {
                            return $this->getMethodCode( $class, $method );
                        },
                        $methods
                    );

                    $tokenisedContent["methods"] = $methodsWithCode;

                    break;

                case T_NAMESPACE:
                    $namespace = "";
                    do {
                        $i ++;
                        if ( ! is_string( $tokens[ $i ][0] ) && ! empty( trim( $tokens[ $i ][1] ) ) ) {
                            $namespace .= $tokens[ $i ][1];
                        }
                    } while ( ! is_string( $tokens[ $i ][0] ) );
                    $tokenisedContent["namespace"] = $namespace;
                    break;
            }
        }

        return $tokenisedContent;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getTokenisedContentFromClass( string $content ) {
        $temp = array();
        // die();
        $tokens = token_get_all( $content );


        $tokenisedContent = array();
        $tokenisedContent = $this->addToken( $tokenisedContent, $tokens );

        return $tokenisedContent;

    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getFunctions( string $content ): array {

        // Class methods
        $tokenisedClassContent = $this->getTokenisedContentFromClass( $content );

        // Check for $tokenisedContent["methods"];
        if ( isset( $tokenisedClassContent["methods"] ) ) {
            $functions = $tokenisedClassContent["methods"];

            return $tokenisedClassContent["methods"];
        } else {
            // File of just functions
            $functions = array();
        }

        return $functions;

    }

    /**
     * @param string $comparisonFunctionContent
     * @param string $currentFunctionContent
     *
     * @return bool
     */
    private function isSimilar( string $comparisonFunctionContent, string $currentFunctionContent ): bool {
        similar_text( $comparisonFunctionContent, $currentFunctionContent, $perc );

        return $perc > 60 && $perc < 100;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function stripComments( string $content ): string {
        $tokens = token_get_all( $content );

        $contentSansComments = array_reduce(
            $tokens,
            function ( $carry, $token ) {
                if ( is_string( $token ) ) {
                    // simple 1-character token
                    return $carry . $token;
                } else {
                    // token array
                    list( $id, $text ) = $token;
                    switch ( $id ) {
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
        return $contentSansComments;
    }
}
