<?php
	/**
	 * @file driver.php
	 * 
	 * @author	Jose Flores
	 * 
	 * This file is an example usage of the phpParmParser
	 */
	
	//	Requiring parser class
	require "phpParamParser.php" ;
	
//	Example Starts here 
	$desc = "This is an argument parser, A project description goes here." ;
	
	// Creating instance of class
	$P = new phpParamParser( $argc , $argv , $desc ) ;
	
	// Adding Parameters
	$P->addParam( array( 'mandatory' => true , 
					 'type' => 'string' , 
					 'description' => "A required string." ,
				     'value' => "" ,
 				     'key' => array( 'r' , 'req-string' ) ) ) ;
 			     			     
	$P->addParam( array( 'mandatory' => false , 
				     'type' => 'string' , 
				     'description' => "An optional string." ,
				     'value' => "" ,
				     'key' => array( 'o' , 'optional-string' ) ) ) ;
	
	$P->addParam( array( 'mandatory' => true , 
				     'type' => 'enum' , 
				     'description' => "A req enumerated value." ,
				     'value' => false ,
				     'key' => array( 'build' , 'deploy' , 'test' ) ) ) ; 

	$P->addParam( array( 'mandatory' => true , 
				     'type' => 'enum' , 
				     'description' => "An optional enumerated value." ,
				     'value' => false ,
				     'key' => array( 'now' , '5-mins' , '10-mins' ) ) ) ;
	
	// 	Error Check			     
	if ( $P->validate() ) 
		exit( 1 ) ;
	
	// 	Get validated arguments
	$arguments = $P->getArgs() ;
	
	//	Display output
	var_dump( $arguments ) ;	
	
?>
