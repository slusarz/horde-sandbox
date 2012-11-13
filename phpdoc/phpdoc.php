<?php
// Usage: phpdoc.php [git branch] [Horde version]

// CONFIG
//$checkout = '/horde/checkout/horde.git';
$checkout = '/disk2/src/horde';
$phpdoc = '/usr/local/php/bin/phpdoc';
$output_dir = '/httpd/sites/testing.curecanti.org/phpdoc';
// ENDCONFIG

if (count($argv) != 3) {
    exit("FATAL: Need branch and horde version.\n");
}

$branch = $argv[1];
$hversion = $argv[2];

chdir($checkout);
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', $checkout . '/horde');
}

$output_dir . '/' . $branch;

exec('git checkout ' . escapeshellcmd($branch));
$apps = $libs = array();

// Applications
$dir_ob = new DirectoryIterator($checkout);
foreach ($dir_ob as $val) {
    if ($val->isDir() && !$val->isDot()) {
        $app = $val->getBasename();

        if ($app == 'horde') {
            $apps[] = 'horde';
        } else {
            $app_file = $val->getPathname(). '/lib/Application.php';
            if (!file_exists($app_file)) {
                continue;
            }

            try {
                include $app_file;
            } catch (Exception $e) {
                continue;
            }
            $reflection_class = new ReflectionClass($app . '_Application');
            $properties = $reflection_class->getDefaultProperties();
            if (strpos($properties['version'], $hversion . ' ') === 0) {
                $apps[] = $app;
            }
        }
    }
}

foreach ($apps as $app) {
    exec('nice -n 10 ' .
        escapeshellcmd($phpdoc) .
        ' -d ' . escapeshellarg($checkout . '/' . $app) .
        ' -t ' . escapeshellarg($output_dir . '/' . $app) .
        ' --template abstract' .
//        ' > ' . escapeshellarg($output_dir . '/' . $app . '.log')
        ' > /dev/null'
    );
    exec('tar czf ' . escapeshellarg($output_dir . '/' . $app . '/' . $app . '.tar.gz') . ' ' . escapeshellarg($checkout . '/' . $app));
}

// Framework
$dir_ob = new DirectoryIterator($checkout . '/framework');
foreach ($dir_ob as $val) {
    if ($val->isDir() && !$val->isDot()) {
        $package = $val->getPathname() . '/package.xml';
        if (file_exists($package)) {
            $libs[] = $val->getBasename();
        }
    }
}

foreach ($libs as $lib) {
    exec('nice -n 10 ' .
        escapeshellcmd($phpdoc) .
        ' -d ' . escapeshellarg($checkout . '/framework/' . $lib) .
        ' -t ' . escapeshellarg($output_dir . '/' . $lib) .
        ' --template abstract' .
//        ' > ' . escapeshellarg($output_dir . '/' . $lib . '.log')
        ' > /dev/null'
    );
    exec('tar czf ' . escapeshellarg($output_dir . '/' . $app . '/' . $app . '.tar.gz') . ' ' . escapeshellarg($checkout . '/framework/' . $app));
}

if ($branch != 'master') {
    exec('git checkout master');
}
