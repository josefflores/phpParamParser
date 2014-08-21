<?php
	/**
	 * 	@name 	phpParamParser
	 * 
	 * 	This class parses command line arguments, generates a manual and 
	 * 	validates the arguments.
	 * 
	 * 	Strings		Arguments that take strings are validated to make sure 
	 * 				that the string is not formatted as a parameter.
	 * 	Bools		Are treated as flags that are by default off or 
	 * 				hardcoded on.
	 * 	Enums 		Are treated as a switch for multiple flags leaving 
	 * 				only one on. Or if the default value is true then the
	 * 				enum will chose all or one option.
	 */
	class phpParamParser {
	 
	//	VARIABLES
	
		private $buckets 	= array() ;		// An array of parameters and their target argv
		private $parameters = array() ;		// The parameters allowed array
		private $errors 	= array() ;		// The generated errors
		private $out 		= array() ;		// The output values
	
		private $successFlag = false ;		// If arguments should be returned
		
		private $argc ;						// The argument count
		private $argv ;						// The argument array
		private $desc ;						// The project description
	
		
		/**
		 * 	@name	__construct
		 * 
		 * 	The constructor, it stores the command line arguments
		 * 
		 * 	@param 	$argc	The argument count
		 * 	@param	$argv	The argument array
		 */
		public function __construct( $argc , $argv , $desc ) {
			
			$this->argc = $argc ;
			$this->argv = $argv ;
			$this->desc = $desc ;
			
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
		 * 	@name	addParam
		 * 
		 * 	This function adds an allowable param to the parameter list
		 *
		 * 	@param 	$arr	The $parameter array
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
		 *	@name	validate
		 * 
		 * 	This function validates the arguments
		 */
		public function validate() {
			// intialize manual off
			
			for( $i = 1 ; $i < $this->argc ; ++$i ) {
				$str = $this->argv[ $i ] ;
				
				// Check to see if an argument is a parameter or if it is not
				if ( substr( $str , 0 , 2 ) == "--" || 
					 substr( $str , 0 , 1 ) == "-" ) { 
					
					// Initializing the parameter
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
						$this->errorMessage( 2 , $err , "[" . $str . "]" ) ;
					}
					
					// Extract value
					if ( array_key_exists ( $p , $this->buckets ) ) { 
						
						// find the parameter target
						$target = $this->buckets[ $p ] ;

						// determine available types
						switch ( $this->parameters[ $target ][ 'type' ] ) { 
							case 'string' :
									// Find the parameter string target
									++$i ;
									
									if ( substr( $this->argv[ $i ] , 0 , 1 ) == "-" ) {
										$str = "Invalid string." ;
										$this->errorMessage( 5 , $str , "[" . $this->argv[ $i ] . "]" ) ;
										--$i ;
									}
									else {	
										foreach( $this->parameters[ $target ][ 'key' ] as $item ) {
											// store values in out array
											$this->out[ $item ] = $this->argv[ $i ] ;
										}
									}
								break ;
							
							case 'enum' :
									// Turn on option, disable others
									foreach ( $this->parameters[ $target ][ 'key' ] as $item ) {
										// store values in out array
										$this->out[ $item ] = false ;
									}
									$this->out[ $p ] = true ;
								break ;
							
							case 'bool' :
									// Set flag
									foreach ( $this->parameters[ $target ][ 'key' ] as $item ) {
										// store values in out array
										$this->out[ $item ] = true ;
									}
								break ;
								
							default :
									// Record Error
									$str = "Unknown option type in parameter declaration." ;
									$this->errorMessage( 1 , $str , "[" . $this->parameters[ $target ][ 'type' ] . "]" ) ;
								break ;
						}
					}
					else { 
						
						// Record Error	
						if ( !array_key_exists ( $p , $this->out ) ) {
							$err = "Unknown parameter." ;
							$this->errorMessage( 3 , $err , "[" . $str . "]" ) ;
						}
					}
				}
				else { 
					// Record Error
					$err = "Argument is not properly marked with - or -- , or marker is missing before string." ;
					$this->errorMessage( 4 , $err , "[" . $str . "]" ) ;
				}
			}
			
			// Check all mandatory parameters have been filled
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
						$err = "Argument is mandatory and has not been filled." ;
						$this->errorMessage( 6 , $err , "[" . $key . "]" ) ;
					}
				}
			}
			
			// Display Errors
			if( count( $this->errors ) > 0  && 
				$this->out[ 'v' ] ) {
					$this->printErrors() ;
			}
			
			// Show manual
			if ( $this->out[ 'h' ] ||
				 ( count( $this->errors ) > 0  && 
				   $this->out[ 'v' ] ) )  {
				$this->manual() ;
			} 
			
			// Print Derived arguments
			if ( $this->out[ 'v' ] ) {
				echo " ARGUMENTS\n" ;
				foreach( $this->out as $key => $val )
					echo "\t[ " , $key , " ] {" , $val , "}\n" ;
			}
			
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
		 * 	@name errorMessage
		 * 
		 * 	This function generates error message strings
		 * 
		 * 	@param	$val	The error code		
		 * 	@param	$str	The error message
		 * 	@param	$opt	The reference point
		 */
		private function errorMessage( $val , $str , $opt = "" ) {
			$str = $val . " - " . $str . " " . $opt ;
			array_push( $this->errors , $str ) ;		
		}
		
		/**
		 * 	@name	printErrors
		 * 
		 * 	This function prints errors.
		 */
		private function printErrors() {
			echo "\n ERRORS\n" ;
			foreach( $this->errors as $e ) 
				echo "\t" , $e , "\n" ;	 
			echo "\n" ;
		}
		/**
		 * 	@name manual	
		 * 	
		 * 	This function displays the manual for the arguments.
		 */
		private function manual() {
			
			//	Get a copy of the parameters
			$tmp = $this->parameters ;
				
			//	Generate the name of the file being run	
			$out[ 'NAME' ] = $this->argv[ 0 ] ;
			
			// Retrieve the project description
			$out[ 'DESCRIPTION' ] = $this->desc ;			
			
			// Process all mandatory parameters first
			foreach( $tmp as $item ) {
				
				if ( $item[ 'mandatory' ] ) {
					foreach( $item[ 'key' ] as $i ) {
						if ( strlen( $i ) == 1 )
							$i = "-".$i ;
						else
							$i = "--".$i ;
							
						$out[ 'OPTIONS' ] .= "  " . $i . " " ; 
					
					}
					if ( $item[ 'mandatory' ] ) 
						$out[ 'OPTIONS' ] .= "\n\t[ REQ ] " . $item[ 'description' ] . "\n\n" ;
				}
			}
			
			//	Process all optional parameters
			foreach( $tmp as $item ) {
				if ( !$item[ 'mandatory' ] ) {
					foreach( $item[ 'key' ] as $i ) {
						if ( strlen( $i ) == 1 )
							$i = "-".$i ;
						else
							$i = "--".$i ;
						$out[ 'OPTIONS' ] .= "  " . $i . " " ; 
					}
					$out[ 'OPTIONS' ] .= "\n\t" . $item[ 'description' ] . "\n\n" ;
				}
			}
			
			//	Print Manual
			echo " NAME\n  " , $out[ 'NAME' ] , "\n\n" ;
			echo " DESCRIPTION\n  " , $out[ 'DESCRIPTION' ] , "\n\n" ;
			echo " OPTIONS\n" , $out[ 'OPTIONS' ] , "\n" ;
			
		}

		/**
		 * 	@name 	getArgs
		 * 
		 * 	This function returns the arguments array if possible
		 * 
		 * 	@return null	Arguments could not be retrieved
		 * 	@return array	Derived arguments
		 */ 	
		public function getArgs() {
			if( $this->successFlag )
				return $out ;	
			else return null ;
		}
	}
	
?>
