<?php

spl_autoload_register(function($class)
{
	$prefix = "Strud\\";
	
	$prefixLength = strlen($prefix);
	
	if(strncmp($prefix, $class, $prefixLength) !== 0)
	{
		return;
	}
	
	$baseDirectory = defined("STRUD_DIRECTORY") ? STRUD_DIRECTORY : __DIR__;
	
	$classPathWithPrefixRemoved = str_replace("\\", DIRECTORY_SEPARATOR, substr($class, $prefixLength));
	
	$filePath = "";
	$filePath .= rtrim($baseDirectory, DIRECTORY_SEPARATOR);
	$filePath .= DIRECTORY_SEPARATOR;
	$filePath .= $classPathWithPrefixRemoved;
	$filePath .= ".php";
	
	if(file_exists($filePath))
	{
		require $filePath;
	}
	
});
