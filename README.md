# TheLanguageQUIZ #

# Installing the development environment#

## 1. Grunt ##
This project uses Grunt.
All the dependencies are included and tracked by Git
Make sure you have installed Grunt on your development machine. For more information on Grunt, read the following documentation page: http://gruntjs.com/getting-started

### Installing Grunt ###
To install Grunt, you must first [download and install node.js](https://nodejs.org/download/) (which includes npm). npm stands for node packaged modules and is a way to manage development dependencies through node.js.

Then, from the command line:

1. ``Install grunt-cli globally with npm install -g grunt-cli``.
2. Navigate to the project root directory (eg. ~/home/the-language-quiz/), then run npm install. npm will look at the package.json file and automatically install the necessary local dependencies listed there.

When completed, you'll be able to run the various Grunt commands provided from the command line.

### Available Grunt tasks ###

* ``grunt dev``  - compile and build all files in development mode
* ``grunt prod`` - compile and build all files in production mode
* ``grunt watch``  - run predefined tasks whenever watched files change. It is highly recommended to leave this task running in background when you are working in development mode in order to watch and automatically regenerate all the requested files

## 2. Composer ##
Composer is a tool for dependency management in PHP. It allows you to declare the dependent libraries your project needs and it will install them in your project for you.
Check out the official [Composer](https://getcomposer.org/) website and the [Getting start](https://getcomposer.org/doc/00-intro.md) documentation page

### Installing Composer ###
Composer needs to run from inside your API project directory (src/api).
First of all, make sure you have installed Composer on your machine and Grunt watch is running in the background:
1. Open a terminal window and navigate inside your API folder (eg. /user/root/the-language-quiz/src/api )
2. Now launch: ``composer install``
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
In your src/ directory there are several files in dev mode. These files will be automatically converted in the correct file names when copied inside the distribution folder by Grunt.
All you need to do is running the Grunt task according to the environment mode you want to deploy the code to.

Running:

```
grunt dev
```

will copy all the files containing the .dev. text in the file name, from the source directory inside the distribution one
(eg. src/index.dev.html will be moved and renamed to dist/index.html and so on).

The files that will change between the 2 environments are the followings:
```
src/
|- .htaccess ( dev.htaccess / prod.htaccess )
|- index.html ( index.dev.html / index.prod.html )
|- api
    |- .htaccess ( dev.htaccess / prod.htaccess )
    |- config
      |- db.config.php ( db.config.dev.php / db.config.prod.php )  
```

Note: the .prod. files don't exists and they are automatically ignored by Git. You can create them just cloning the .dev. files, rename them using the correct name according to the file tree above, and change the variable inside according to your production environment.

## 5. Compile the project
TheLanguageQUIZ uses Grunt to automate the build process.
All you need to do is running ``grunt dev`` the first time you compile the project from your project's root directory ( eg. /user/root/the-language-quiz/ ), then leaving the ``grunt watch`` watching after your files when you're in development mode. Grunt will take care of compiling the distribution directory according to your file changes so that you can see the code changing in real time from inside your browser.
Remember to always render the project in your browser from your dist folder ( eg. http://localhost:8888/TheLanguageQUIZ/dist/ )

## 6. Compile a production version ##
If you want to compile a production version of the code using the .prod. files created inside your src folder, all you need to do is stop the grunt watch task, if running in background, and launch ``grunt prod``. This Grunt task will compile the production version inside your dist folder. All you need to do is uploading this folder to your Live environment.

### Project variables ###
Make sure to set them properly the first time:

* api/config/db.config.php

```
define( 'DB_NAME', 'language_quiz' );
define( 'DB_USERNAME', 'root' );
define( 'DB_PASSWORD', 'root' );
```
These are the constant variables used for establish the database comunication.
If you use Mamp for PHP/MySQL you will probably leave them as is.
Make sure to set the db.config.prod.php ones according to your production environment.

```
define( 'APP_DIR', 'http://localhost:8888/TheLanguageQUIZ/dist/' );
define( 'APP_ENV', 'development' );
```
These are the application root folder and the development mode.
Make sure to set the ``'APP_ENV'`` to ``'production'`` when you create the db.config.prod.php


* index.html

```
appDir = "http://sonnywebdesign.com/languagequiz/";
apiDir = "http://sonnywebdesign.com/languagequiz/api/";
```
These are the JavaScript global variables used for generating all the Ajax request from the main App to the API. Make sure to set them properly according to your environment.

----
There is a Demo version running live according to the latest master branch on: http://sonnywebdesign.com/languagequiz/


That's it for now.
