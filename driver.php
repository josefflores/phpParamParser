<?php
	/**
	 * @file driver.php 
	 * @author	Jose Flores
	 * 
	 * This file is an example usage of the phpParmParser
	 * 
	 *  Copyright (C) 2014 Jose F Flores
	 *
	 *	This program is free software: you can redistribute it and/or modify
	 *	it under the terms of the GNU General Public License as published by
	 *	the Free Software Foundation, either version 3 of the License, or
	 *	(at your option) any later version.
	 *
	 *	This program is distributed in the hope that it will be useful,
	 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *	GNU General Public License for more details.
     *
	 * 	You should have received a copy of the GNU General Public License
	 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */
	
	//	Requiring parser class
	require "phpParamParser.php" ;
	
	//	Example Starts here 
	$desc = "This is an argument parser, A project description goes here." ;
	
	// 	Creating instance of class
	$P = new phpParamParser( $argc , $argv , $desc ) ;
	
	// 	Adding Parameters
	$P->addParam( array( 'req' => true , 
						 'type' => 'string' , 
						 'description' => "A required string." ,
						 'value' => "" ,
						 'key' => array( 'r' , 'req-string' ) ) ) ;
 			     			     
	$P->addParam( array( 'req' => false , 
						 'type' => 'string' , 
						 'description' => "An optional string." ,
						 'value' => "" ,
						 'key' => array( 'o' , 'optional-string' ) ) ) ;
	
	$P->addParam( array( 'req' => true , 
						 'type' => 'enum' , 
						 'description' => "A req enumerated value." ,
						 'value' => false ,
						 'key' => array( 'build' , 'deploy' , 'test' ) ) ) ; 

	$P->addParam( array( 'req' => true , 
						 'type' => 'enum' , 
						 'description' => "An optional enumerated value." ,
						 'value' => false ,
						 'key' => array( 'now' , '5-mins' , '10-mins' ) ) ) ;
	
	// 	Error Check			     
	if ( $P->validate() ) 
		exit( 1 ) ;
	
	// 	Get validated arguments
	var_dump( $P->getArgs() ) ;	
	
?>
