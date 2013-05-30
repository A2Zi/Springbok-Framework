<?php

if(!isset($folderTests)) $folderTests=APP.'tests/';
$t=microtime(true);
$tests=new RecursiveDirectoryIterator($folderTests,FilesystemIterator::SKIP_DOTS); $l=strlen($folderTests); $total=$totalFailed=0;
UPhp::recursive(function($callback,$tests) use($l,$total,$totalFailed){
	foreach($tests as $path=>$file){
		if($file->isDir())
			$callback($callback,new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS));
		else{
			echo cliColor(substr($path,$l),CliColors::white)."\n";
			$results=STest::runFile($path);
			$stats=STest::cliDisplay($results);
			$total+=$stats['total'];
			$totalFailed+=$stats['failed'];
		}
	}
},$tests);

$t=microtime(true) - $t;
echo "\n";
if($total===0) echo 'No tests';
else if($totalFailed===0) echo cliColor('OK',CliColors::green).' '.$total.'/'.$total.' in '.$t.' ms';
else echo cliColor('FAILED',CliColors::green).' '.($total-$totalFailed).'/'.$total.' in '.$t.' ms';
