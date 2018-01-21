<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// non-hard-coded way,
// $autoloader = require \Drupal::root() . '/autoload.php';
// doesn't work because it can't load the class Drupal, naturally.

// This next bit was done without Internet and apparently i don't have a PHP
// manual locally.  @TODO at least make sure i'm using safe globals.

$script_name = trim($GLOBALS['_SERVER']['SCRIPT_NAME'], '/');
$count = substr_count($script_name, '/');
$chdir = '';
for ($i=1; $i < $count; $i++) {
  $chdir .= '../';
}
$chdir .= '..';

chdir($chdir);

$autoloader = require_once 'autoload.php';

$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

$log = $container
  ->get('config.factory')
  ->get('give.settings')
  ->get('log_problems');

print $log;

if ($views) {
  $nid = filter_input(INPUT_POST, 'nid', FILTER_VALIDATE_INT);
  if ($nid) {
    $container->get('request_stack')->push(Request::createFromGlobals());
    $container->get('statistics.storage.node')->recordView($nid);
  }
}
