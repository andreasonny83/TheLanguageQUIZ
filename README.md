# TheLanguageQUIZ #

# Installing the development environment#

## 1. Grunt ##
This project uses Grunt.
All the dependencies are included and tracked by Git
Make sure you have installed Grunt on your development machine. For more information on Grunt, read the following documentation page: http://gruntjs.com/getting-started

### Installing Grunt ###
To install Grunt, you must first [download and install node.js](https://nodejs.org/download/) (which includes npm). npm stands for node packaged modules and is a way to manage development dependencies through node.js.

Then, from the command line:

1. Install grunt-cli globally with npm install -g grunt-cli.
2. Navigate to the project root directory (eg. ~/home/the-language-quiz/), then run npm install. npm will look at the package.json file and automatically install the necessary local dependencies listed there.

When completed, you'll be able to run the various Grunt commands provided from the command line.

### Available Grunt tasks ###

* grunt  - compile and build all files in development mode
* grunt build - compile and build all files in production mode
* grunt watch  - run predefined tasks whenever watched files change. is highly recommended to leave this task running in background when you are working in development mode in order to watch and automatically regenerate all the requested files

## 2. Composer ##
Composer is a tool for dependency management in PHP. It allows you to declare the dependent libraries your project needs and it will install them in your project for you.
Check out the official [Composer](https://getcomposer.org/) website and the [Getting start](https://getcomposer.org/doc/00-intro.md) documentation page

### Installing Composer ###
Composer needs to run from inside your development project directory (src).
First of all, make sure you have installed Composer on your machine and Grunt watch is running in the background:
1. From inside your terminal window, reach the project root directory (eg. /user/root/the-language-quiz/ )
2. Then run: grunt watch
3. Open a new terminal window/tab and navigate inside your API folder (eg. /user/root/the-language-quiz/src/api )
4. Now launch: composer install
This will create the composer.lock and will install all the libraries required in the project

## 3. Bower ##
This project uses Polymer. The recommended way to install TheLanguageQuiz is through Bower. To install Bower, see the [Bower web site](http://bower.io/)

### Project setup ###
From a terminal windows, navigate inside the "src" folder (eg. ~/username/thelanguagequiz/src/ ) and install all the bower dependencies running:

```
bower update
```
This updates all packages in bower_components/ to the latest stable version.

## 4. Setting the correct environment variables ##
In your src/config/db.config.php files, make sure the PATH is pointing to the correct project folder before proceeding

## 5. Render the project ##
Now you should be able to render the project from inside your dist/ folder

## 6. Develop new code ##

### Project variables ###
There are several Production/Development variables around the project.
Make sure to set them properly every time you switch environment:

* api/api.php

```
$app->config( 'debug', false );
```
Make sure to set the status to true while in development mode to ensure displaying any error message related to the API. A generic error message will generate while this status is set to false.

* config/db_config.php

This file contains the database information
make sure to set it properly according to your database information

* index.html

```
appDir = "http://sonnywebdesign.com/languagequiz/";
apiDir = "http://sonnywebdesign.com/languagequiz/api/";
```
This is a JavaScript variable used to generate all the Ajax request from the main App to the API. Make sure to set it properly according to your environment URL

----
There is a Demo version running live according to the latest master branch on: http://sonnywebdesign.com/languagequiz/


That's it for now.
