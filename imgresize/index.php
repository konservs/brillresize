<?php
error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
define('BEXEC', 1);
define('BROOTPATH', realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
define('BLIBRARIESPATH', BROOTPATH.'libraries'.DIRECTORY_SEPARATOR);

jimport('images.general');
$bimages=BImages::getInstance();
if((!empty($_SERVER['DOCUMENT_URI']))&&($_SERVER['DOCUMENT_URI']!='index.php')&&($_SERVER['DOCUMENT_URI']!='/index.php')){
	$bimages->run($_SERVER['DOCUMENT_URI']);
	}else{
	$bimages->run($_SERVER['REQUEST_URI']);
	}
