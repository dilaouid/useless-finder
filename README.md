# useless-finder
PHP script to find unused javascript dependencies in directories according to a specified package.json

## How to use
First, git clone of course
Then, just launch the script like
```shell
php ./script.php PATH_TO_YOUR_PACKAGE.JSON [folder | file] [folder | file] ...
```
In [folder | subfolder] you have to specify the files or the folders you want to check. For instance:

```shell
php ./script.php ./myApp/package.json ./myApp/src/ ./myApp/src/file.js
```

The script can read **.ts** and **.js** files. If you specify a folder, the script will also recursively read all the subfolders inside of it.

## Flags
You can use the `--help` flag to understand how this works
```shell
php ./script.php --help
```

You can also use the `--skip-used` at the end of the script to skip the count of the used packages, like this
```shell
php ./script.php ./myApp/package.json ./myApp/src/ ./myApp/src/file.js --skip-used
```

Please note that some dependencies may be important for your framework to work properly.
Some are already ignored by the parsing.
