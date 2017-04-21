<?php
abstract class iVariation {
  abstract public function getDependencies();

  abstract public function beforeImport($base, $token);
  abstract public function import($base, $token, $line, $deps, $dir);
  abstract public function afterImport($base, $token);
  abstract public function headers($line);

  public function getCodesFromHeaders($headers, $selected) {
    return array_map(function($id) use ($headers) { return $headers[$id]; }, 
		     array_filter(array_keys($headers), 
				  function ($x) use ($selected) { 
				    return strtolower($selected[$x]) == 'x'; 
				  }));    
  }
  public function getIdsFromDeps($codes, $deps) {
    return array_map(function($code) use ($deps) { return $deps[$code]; }, $codes);
  }
}
