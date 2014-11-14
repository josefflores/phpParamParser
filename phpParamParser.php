<?php
    /**
     *  @file driver.php
     *  @author Jose Flores
     *
     *  Copyright (C) 2014 Jose F Flores
     *
     *  This program is free software: you can redistribute it and/or modify
     *  it under the terms of the GNU General Public License as published by
     *  the Free Software Foundation, either version 3 of the License, or
     *  (at your option) any later version.
     *
     *  This program is distributed in the hope that it will be useful,
     *  but WITHOUT ANY WARRANTY; without even the implied warranty of
     *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *  GNU General Public License for more details.
     *
     *  You should have received a copy of the GNU General Public License
     *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
     */

    /**
     *  @name   phpParamParser
     *
     *  This class parses command line arguments, generates a manual and
     *  validates the arguments.
     *
     *  Strings     Arguments that take strings are validated to make sure
     *              that the string is not formatted as a parameter.
     *  Bools       Are treated as flags that are by default off or
     *              hardcoded on.
     *  Enums       Are treated as a switch for multiple flags leaving
     *              only one on. Or if the default value is true then the
     *              enum will chose all or one option.
     */
    class phpParamParser {

    //  VARIABLES

        private $buckets    = array() ;     // An array of parameters and their target argv
        private $parameters = array() ;     // The parameters allowed array
        private $errors     = array() ;     // The generated errors
        private $out        = array() ;     // The output values

        private $successFlag = false ;      // If arguments should be returned

        private $argc ;                     // The argument count
        private $argv ;                     // The argument array
        private $description ;              // The project description


    //  FUNCTIONS

        /**
         *  @name   __construct
         *
         *  The constructor, it stores the command line arguments
         *
         *  @param  $argc   The argument count
         *  @param  $argv   The argument array
         */
        public function __construct( $argc , $argv , $desc ) {

            $this->argc = $argc ;
            $this->argv = $argv ;
            $this->description = $desc ;

            // Add default verbose
            $this->addParam( array( 'mandatory' => false ,
                                    'type' => 'bool' ,
                                    'description' => "Make project verbose" ,
                                    'value' => false ,
                                    'key' => array( 'v' , 'verbose' ) ) ) ;

            // Add default help page
            $this->addParam( array( 'mandatory' => false ,
                                    'type' => 'bool' ,
                                    'description' => "Show manual." ,
                                    'value' => false ,
                                    'key' => array( 'h' , 'help' ) ) ) ;

        }
        /**
         *  @name   addParam
         *
         *  This function adds an allowable param to the parameter list
         *
         *  @param  $arr    The $parameter array
         */
        public function addParam( $arr ) {

            // Check if all keys are present
            if ( !array_key_exists ( 'mandatory' , $arr ) ||
                 !array_key_exists ( 'type' , $arr ) ||
                 !array_key_exists ( 'description' , $arr ) ||
                 !array_key_exists ( 'value' , $arr ) ||
                 !array_key_exists ( 'key' , $arr ) )
                 return 1 ;

            // Add to list
            array_push( $this->parameters , $arr ) ;
            $size = count( $this->parameters ) ;

            // Add bucket target
            foreach( $this->parameters[ $size - 1 ][ 'key' ] as $p ) {
                    $this->buckets[ $p ] = $size - 1 ;
                    $this->out[ $p ] = $this->parameters[ $size - 1 ][ 'value' ] ;
            }

        }
        /**
         *  @name   validate
         *
         *  This function validates the arguments
         *
         *  @return 0       Valid arguments
         *  @return 1       Invalid arguments
         */
        public function validate() {

            // Check all inputted arguments
            for( $i = 1 ; $i < $this->argc ; ++$i ) {
                $str = $this->argv[ $i ] ;

                /*  Check to see if an argument is formatted as a parameter
                 *  or if it is not
                 */
                if ( substr( $str , 0 , 2 ) == "--" ||
                     substr( $str , 0 , 1 ) == "-" ) {

                    /*  Initializing the parameter and checks if it has
                     *  been used
                     */
                    $p = $this->initParam( $str ) ;

                    // Extract value
                    if ( array_key_exists( $p , $this->buckets ) ) {

                        // find the parameter target
                        $target = $this->buckets[ $p ] ;

                        // Determine type action
                        $i = $this->typeHandler( $this->parameters[ $target ][ 'type' ] , $target , $i , $p , $str ) ;
                    }
                    else {

                        // Record Error
                        if ( !array_key_exists( $p , $this->out ) ) {
                            $err = "Unknown parameter." ;
                            $this->errorMessage( 3 , $err , $str ) ;
                        }
                    }
                }
                else {
                    // Record Error
                    $err = "Argument is not properly marked with - or -- , or marker is missing before string." ;
                    $this->errorMessage( 4 , $err , $str ) ;
                }
            }

            // Check all mandatory parameters have been filled
            $this->mandatory( ) ;

            // Generate output
            $this->genOutput( ) ;

            // Return Failure
            if( count( $this->errors ) > 0 ) {
                $this->successFlag = false ;
                return 1 ;
            }

            // Return Success
            $this->successFlag = true ;
            return 0 ;

        }
        /**
         *  @name   initParam
         *
         *  This function extracts the expected dashes from an inputed
         *  parameter
         *
         *  @param $str     The inputed string
         *
         *  @return $p      What the paramter should have been recorded
         *                  as.
         */
        public function initParam( $str ) {
            $p = $str ;

            // A long parameter string
            if ( substr( $str , 0 , 2 ) == "--" &&
                 strlen( $str ) > 3 ) {
                $p = substr( $str , 2 ) ;
            }
            // A short parameter string
            else if ( substr( $str , 0 , 1 ) == "-" &&
                      strlen( $str ) == 2 ) {
                $p = substr( $str , 1 ) ;
            }

            // check if a parameter is already used
            $modified = false ;
            foreach( $this->parameters[ $this->buckets[ $p ] ][ 'key' ] as $key  ) {
                if ( $this->out[ $p ] != $this->parameters[ $this->buckets[ $p ] ][ 'value' ] )
                    $modified = true ;
            }

            // Record Error
            if ( $modified  ) {
                $err = "Parameter already used." ;
                $this->errorMessage( 2 , $err , $str ) ;
            }

            return $p ;
        }
        /**
         *  @name typeHandler
         *
         *  This handler prcesses the arguments by type instructions,
         *  custom types can be added here with their own processing
         *  functions.
         *
         *  @param  $type   The type of the argument
         *  @param  $target The argument index
         *  @param  $i      The argument currently being worked on
         *  @param  $p      The determined parameter
         *
         *  @return $i      argument number to proceed on
         */
        public function typeHandler( $type , $target , $i , $p ) {
            // determine available types
            switch ( $type ) {
                case 'string' :
                        $i = $this->typeString( $target , $i ) ;
                    break ;

                case 'enum' :
                        $this->typeEnum( $target , $p ) ;
                    break ;

                case 'bool' :
                        $this->typeBool( $target ) ;
                    break ;

                default :
                        // Record Error
                        $str = "Unknown option type in parameter declaration." ;
                        $this->errorMessage( 1 , $str , $this->parameters[ $target ][ 'type' ] ) ;
                    break ;
            }
            return $i ;
        }
        /**
         *  @name   typeString
         *
         *  This function handles the string type
         *
         *  @param  $target The argument index
         *  @param  $i      The argument currently being worked on
         *
         *  @return $i      argument number to proceed on
         */
        public function typeString( $target , $i ) {
            // Find the parameter string target
            ++$i ;

            //  Determine if string is valid or a mistaken parameter
            if ( substr( $this->argv[ $i ] , 0 , 1 ) == "-" ) {
                $str = "Invalid string." ;
                $this->errorMessage( 5 , $str , $this->argv[ $i ] ) ;
                --$i ;
            }
            else {
                    // Extract string
                foreach( $this->parameters[ $target ][ 'key' ] as $item ) {
                    // store values in out array
                    $this->out[ $item ] = $this->argv[ $i ] ;
                }
            }
            // return correct index to proceed on
            return $i ;
        }
        /**
         *  @name typeEnum
         *
         *  This function handles the enum type
         *
         *  @param  $target The argument index
         *  @param  $p      The enum option to enable
         */
        public function typeEnum( $target , $p ) {
            // Turn on option, disable others

            foreach ( $this->parameters[ $target ][ 'key' ] as $item ) {
                // store values in out array
                $this->out[ $item ] = false ;
            }

            $this->out[ $p ] = true ;

        }
        /**
         *  @name typeBool
         *
         *  This function handles the bool type
         *
         *  @param  $target The argument index
         */
        public function typeBool( $target ) {
            // Set flag
            foreach ( $this->parameters[ $target ][ 'key' ] as $item ) {
                // store values in out array
                $this->out[ $item ] = true ;
            }
        }
        /**
         *  @name   mandatory
         *
         *  This function returns the number of mandatory arguments that
         *  have not been filled as well as generate error messages for them
         *
         *  @return $val    The number of mandatory arguments not filled
         */
        public function mandatory( ) {
            $i = 0 ;
            $val = 0 ;

            foreach( $this->parameters as $item ) {

                if ( $item[ 'mandatory' ] ) {
                    // set flag
                    $modified = false ;

                    // check for changes
                    foreach( $item[ 'key' ] as $key  )
                        if ( $this->out[ $key ] != $item[ 'value' ] )
                            $modified = true ;

                    // error if modified
                    if( !$modified ) {
                        $err = "Argument is required and has not been filled." ;
                        $this->errorMessage( 6 , $err , $this->getKeys( $i ) ) ;
                        ++$val ;
                    }
                }
                ++$i ;
            }
            return $val ;
        }
        /**
         *  @name   genOutput
         *
         *  This function generates the output of the parser
         *
         *  @param  $store      True, return the output. false print
         *                      the output.
         *
         *  @return     $str    The output string
         *
         */
         public function genOutput( $store = false ) {
            $str = "" ;
            //  Display Errors
            if( count( $this->errors ) > 0  &&
                $this->out[ 'v' ] ) {
                    $str .= $this->getEntry( 'errors' , $this->errors , true ) ;
            }

            //  Show manual
            if ( $this->out[ 'h' ] ||
                 ( count( $this->errors ) > 0  &&
                   $this->out[ 'v' ] ) )  {
                $str .= $this->manual() ;
            }

            //  Print Derived arguments
            if ( $this->out[ 'v' ] ) {
                $str .= $this->getEntry( 'arguments' , $this->formatArguments( true ) , true ) ;
            }

            if ( !$store )
                echo $str ;

            return $str ;
        }
        /**
         *  @name errorMessage
         *
         *  This function generates error message strings
         *
         *  @param  $val    The error code
         *  @param  $str    The error message
         *  @param  $opt    The reference point
         */
        private function errorMessage( $val , $str = "" , $opt = "" ) {
            if ( $opt != "" )
                $opt = "[ " . $opt . "] " ;

            $str = $val . " - " . $str . " " . $opt ;
            array_push( $this->errors , $str ) ;
        }
        /**
         *  @name manual
         *
         *  This function displays the manual for the arguments.
         *
         *  @return     $str        The manual
         */
        private function manual() {

            // Initialize the options arrays
            $out[ 'OPTIONS' ][ 'mandatory' ] = array() ;
            $out[ 'OPTIONS' ][ 'not-mandatory' ] = array() ;

            // Determine the options
            for( $i = 0 ; $i < count( $this->parameters ) ; ++$i ) {
                // Process all mandatory parameters
                if ( $this->parameters[ $i ][ 'mandatory' ] ) {
                    array_push( $out[ 'OPTIONS' ][ 'mandatory' ] , $this->getKeys( $i ) ) ;
                    array_push( $out[ 'OPTIONS' ][ 'mandatory' ] , "  [ REQ ] " . $this->parameters[ $i ][ 'description' ] . "\n" ) ;
                }
                //  Process all optional parameters
                else {
                    array_push( $out[ 'OPTIONS' ][ 'not-mandatory' ] , $this->getKeys( $i ) ) ;
                    array_push( $out[ 'OPTIONS' ][ 'not-mandatory' ] , "  " . $this->parameters[ $i ][ 'description' ] . "\n" ) ;
                }
            }

            //  Print Manual
            $str .= $this->getEntry( "name" , array( $this->argv[ 0 ] ) , true ) ;
            $str .= $this->getEntry( "description" , array( $this->description )  , true ) ;

            $tmp = array_merge( $out[ 'OPTIONS' ][ 'mandatory' ] , $out[ 'OPTIONS' ][ 'not-mandatory' ] ) ;
            $str .= $this->getEntry( "options" , $tmp , true ) ;

            return $str ;

        }
        /**
         *  @name   getKeys
         *
         *  This function gets the keys for a paramater
         *
         *  @param  $index  The parameter index
         *  @return array() The keys
         */
        public function getKeys( $index ) {
            // add divider
            $tmp = null ;
            $flag = false ;

            foreach( $this->parameters[ $index ][ 'key' ] as $i ) {

                if( $flag ) {
                    $tmp .= " | " ;
                }
                else {
                    $flag = true ;
                    $tmp .= "" ;
                }

                if ( strlen( $i ) == 1 ) {
                    $i = "-" . $i ;
                }
                else {
                    $i = "--" . $i ;
                }

                $tmp .= $i ;
            }

            return $tmp ;
        }
        /**
         *  @name getArgs()
         *
         *  This function returns the arguments to the user
         *
         *  @return array()     The derived parameters
         *  @return null        The parameters could not be retrieved
         */
        public function getArgs() {
            if ( $this->successFlag ) return $this->out ;
            return null ;
        }
        /**
         *  @name formatArguments
         *
         *  Gets and formats the arguments that have been gathered
         *
         *  @param  $store      True, return the arguments. false print
         *                      the arguments.
         *
         *  @return $str        The generated arguments
         */
        public function formatArguments( $store = false ) {
            $tmp = array() ;

            foreach ( $this->out as $key => $val )
                array_push( $tmp , "[ " . $key . " ] {" . $val . "}" );

            if ( !$store )
                echo $tmp ;

            return $tmp ;
        }
        /**
         *  @name getEntry
         *
         *  @param  $label      The label to be used
         *  @param  $content    The content of the entry
         *  @param  $store      True, return the entry. false print the entry
         *
         *  @return $str        The generated entry
         */
        public function getEntry( $label , $content , $store = false ) {

            // Structure Entry
            if ( trim( $this->getContent( $content , true ) ) != "" ) {
                $str = $this->getLabel( $label , true ) . $this->getContent( $content , true ) ;

                if ( !$store )
                    echo $str ;

                return $str ;
            }

            return "" ;
        }
        /**
         *  @name getContent
         *
         *  @param  $content    The content to be used
         *  @param  $store      True, return the content. false print the content
         *
         *  @return $format     The generated content
         */
        public function getContent( $content , $store = false ) {

            $format = "" ;

            // Format Content
            foreach( $content as $item )
                $format .= "    " . $item . "\n" ;

            $format .= "\n" ;

            if ( !$store )
                echo $format ;

            return $format ;
        }
        /**
         *  @name getLabel
         *
         *  @param  $label      The label to be used
         *  @param  $store      True, return the label. false print the label
         *
         *  @return $format     The generated label
         */
        public function getLabel( $label , $store = false ) {

            // Format Label
            $format = "  " . strtoupper( $label ) . "\n" ;

            if ( !$store )
                echo $format ;

            return $format ;
        }
    }

?>
