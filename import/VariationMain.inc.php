<?php
class VariationMain {
  const CSV_SEPARATOR = ',';

  private $dir;

  private $dependencies;
  private $objects;
  private $codes;

  private $base;
  private $token;

  public function __construct($dir, $base) {
    $this->dir = $dir;
    $this->base = $base;
  }

  public function collectObjects($topdir, $subdirs) {
    $this->dependencies = [];
    foreach ($subdirs as $subdir) {
      $files = glob($topdir . $subdir . '*.class.php');
      foreach ($files as $file) {
	require($file);
	list($name) = explode('.', substr ($file, strlen($topdir)));
	$this->dependencies[$name] = [];
	$classname = "Variation".ucfirst(substr ($name, strlen($subdir)));
	if (in_array('iVariation', class_parents($classname))) {
	  $obj = $this->objects[$name] = new $classname();
	  $deps = $obj->getDependencies();
	  foreach ($deps as $dep) {
	    $this->dependencies[$name][] = $dep;
	  }
	} else {
	  echo 'error'."\n";
	}
      }
    }
  }

  public function callObjects() {
    while (true) {
      $nodeps = array_keys(array_filter($this->dependencies, function($d) { return count($d) == 0; }));
      if (count($nodeps) == 0)
	break;
      foreach ($nodeps as $nodep) {
	$this->log('call '.$nodep);
	$this->callObject($nodep);
	unset($this->dependencies[$nodep]);
      }
      foreach($this->dependencies as $k => $deps) {
	$this->dependencies[$k] = array_diff($deps, $nodeps);
      }      
    }
  }

  public function callObject($name) {
    $obj = $this->objects[$name];
    $filename = $this->dir . DIRECTORY_SEPARATOR . $name . '.csv';
    if (!file_exists($filename))
      return;
    $f = fopen($filename, 'r');
    if ($f === FALSE)
      return;
    $this->codes[$name] = [];
    $nlines = 0;
    $obj->beforeImport($this->base, $this->token);
    $headers_set = false;
    while ( ($line = fgetcsv($f, 0, self::CSV_SEPARATOR)) !== FALSE) {
      if ($line[0][0] == '#') {
	if (!$headers_set) {
	  $obj->headers($line);
	  $headers_set = true;
	}
	continue;
      }
      $deps = [];
      foreach ($obj->getDependencies() as $dep) {
	$deps[$dep] = $this->ids[$dep];
      }
      list($code, $id) = $obj->import($this->base, $this->token, $line, $deps, $this->dir);
      if ($code)
	$this->codes[$name][$id] = $code;
	$this->ids[$name][$code] = $id;
      $nlines ++;
    }
    fclose($f);  
    $obj->afterImport($this->base, $this->token);
    $this->log('  -> imported '.$nlines.' lines');
  } 

  public function start() {
    $this->base->startTransaction();
    $variationUser = $this->base->login->user_login('variation', 'variation', '{users,organization,structure}', null);
    $this->token = $variationUser['usr_token'];
  }

  public function end() {
    $this->base->commit();    
  }

  private function log($txt) {
    echo $txt."\n";
  }
}
