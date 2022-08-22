<?php

    define('INGNORED_PACKAGE', array('@angular/compiler', 'tslib', 'zone.js', 'rxjs', 'react', 'react-native', 'react-dom'));

    function printColoredText($text, $color = "GREEN") {
        $colors = ['RED', 'GREEN', 'YELLOW', 'BLUE'];
        $prefix = "\e[" . (array_search($color, $colors) + 31) . "m";
        $suffix = "\e[39m\n";
        echo $prefix . $text . $suffix;
    }

    function trimAndFilter($str) {
        return (str_replace(' ', '', $str));
    }
    
    function parseFilename($path) {
        $filenameExplode = explode('\\', $path);
        $filename = $filenameExplode[count($filenameExplode) - 1];
        return ($filename);
    }

    function checkArgumentsLength($argc, $flag) {
        if ($argc == 1)
            die(printColoredText("❌ You need to specify at least a file or a folder!", "RED"));
        else if ($argc == 2) {
            if ($flag !== "--help")
                die(printColoredText("❌ You need to specify at least a file or a folder to check!", "RED"));
        }
    }

    function parsePackage($path) {
        $file = json_decode(file_get_contents($path), true);
        if ($file === null || !array_key_exists('dependencies', $file))
            die(printColoredText("❌ No 'dependencies' property found in the package.json file!", "RED"));
        $dependencies = $file['dependencies'];
        if (count($dependencies) == 0)
            die(printColoredText("❌ No dependencies found in the package.json file!", "RED"));
        printColoredText("✔️  " . count($dependencies) . " dependencies found in the package.json!", 'GREEN');
        return (array_keys($dependencies));
    }

    function checkFileExtension($filename) {
        $parseFileName = explode('.', $filename);
        return in_array($parseFileName[count($parseFileName) - 1], ['ts', 'js']);
    }

    function checkForDirOrFiles($paths) {
        foreach ($paths as $value) {
            if (!file_exists($value) && !is_dir($value) && $value !== "--skip-used")
                die(printColoredText("❌ '$value' is nor a file nor a directory!", "RED"));
            else if (!is_dir($value)) {
                if (!checkFileExtension($value))
                    die(printColoredText("❌ '$value' is nor a ts file nor a js file!", "RED"));
            }
        }
    }

    function checkFirstArgument($arg) {
        $firstArg = parseFilename($arg);
        if ($firstArg == '--help')
            die(printColoredText("🔅 You have to use the script the following way:\nphp useless-finder.php [--help | package.json] [...your file | your folder]", "YELLOW"));
        else if ($firstArg !== 'package.json')
            die(printColoredText("❌ You need to specify a package.json file as a first argument!", "RED"));
        else if (!file_exists($arg))
            die(printColoredText("❌ The specified package.json does not exists!", "RED"));
    }

    function checkSkipUsed($arg) {
        return $arg === '--skip-used';
    }

    function parseFile($file, $packages, $occurence) {
        foreach ($packages as $package) {
            $potential = [
                "require(\"$package\")",
                "require('$package')",
                "from '$package",
                "from \"$package",
                "import '$package",
                "import \"$package",
            ];
            for ($i=0; $i < count($potential); $i++) { 
                if (str_contains($file, $potential[$i])) {
                    if (array_key_exists($package, $occurence))
                        $occurence[$package]++;
                    else
                        $occurence[$package] = 1;
                    continue;
                }
            }
        }
        return ($occurence);
    }

    function scanElement($path, $packages, $occurence, &$checkedFiles) {
        if (is_dir($path)) {
            if (substr($path, -1) !== "\\") $path .= "\\";
            $scan = scandir($path);
            $scan = array_slice($scan, 2);
            foreach ($scan as $element) {
                $newPath = $path . $element;
                $pathFile = $path . "\\" . $element;
                if (is_dir($newPath))
                    $occurence = scanElement($newPath, $packages, $occurence, $checkedFiles);
                else if (file_exists($pathFile)) {
                    $filename = parseFilename($pathFile);
                    if (checkFileExtension($filename) && !in_array($pathFile, $checkedFiles)) {
                        $file = file_get_contents($pathFile);
                        $occurence = parseFile($file, $packages, $occurence);
                        array_push($checkedFiles, $pathFile);
                    }
                }
            }
        } else {
            $file = file_get_contents($path);
            $occurence = parseFile($file, $packages, $occurence);
        }
        return ($occurence);
    }

    function goingThroughDirAndFiles($paths, $packages, $occurence) {
        $checkedFiles = array();
        foreach ($paths as $path)
            $occurence = scanElement($path, $packages, $occurence, $checkedFiles);
        return ($occurence);
    }

    function printUsedPackages($occurence, $package, $skip = false) {
        $neverUsed = false;
        foreach ($package as $dependency) {
            if (!array_key_exists($dependency, $occurence) && !in_array($dependency, INGNORED_PACKAGE)) {
                printColoredText("❌ '$dependency' is never imported in your code!", "RED");
                $neverUsed = true;
            }
        }
        if (!$skip) {
            foreach ($package as $dependency) {
                if (array_key_exists($dependency, $occurence) && !in_array($dependency, INGNORED_PACKAGE))
                    printColoredText("✔️ '$dependency' is used $occurence[$dependency] times!", "GREEN");
            }
        }
        if ($neverUsed) {
            printColoredText("⚠️  DISCLAIMER: Some unused dependencies may be necessary for your framework to works properly!!", "YELLOW");
        }
    }

    unset($argv[0]);
    $argv = array_map('trimAndFilter', $argv);
    
    $skip = checkSkipUsed($argv[$argc - 1]);
    if ($skip) array_pop($argv);
    
    checkArgumentsLength($argc, $argv[1]);
    checkFirstArgument($argv[1]);
    $package = parsePackage($argv[1]);
    $paths = array();
    for ($i=2; $i < count($argv)+1; $i++)
        array_push($paths, $argv[$i]);
    checkForDirOrFiles($paths);
    $occurence = array();
    $occurence = goingThroughDirAndFiles($paths, $package, $occurence);
    printUsedPackages($occurence, $package, $skip);
