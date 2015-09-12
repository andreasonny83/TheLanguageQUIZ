module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		project: {
			dev: ['src'],
			dist: ['dist'],
			sass: ['<%= project.dev %>/sass/main.scss'],
			banner: '/*!\n * <%= pkg.name %> v<%= pkg.version %>\n * <%= grunt.template.today("yyyy-mm-dd hh:mm:ss TT") %>\n */\n'
		},

		clean: {
			all: {
				src: ['<%= project.dist %>']
			},
			css: {
				src: ['<%= project.dist %>/css/']
			},
			scripts: {
				src: ['<%= project.dist %>/js/']
			},
			img: {
				src: ['<%= project.dist %>/img/']
			},
			php: {
				src: ['<%= project.dist %>/*.*']
			},
			components: {
				src: ['<%= project.dist %>/components/']
			},
			api: {
				src: ['<%= project.dist %>/api/v1/']
			},
			composer: {
				src: ['<%= project.dist %>/api/vendor/']
			},
			components_after: {
				src: ['<%= project.dist %>/components/*/demo/', '<%= project.dist %>/components/*/test/']
			},
			elements: {
				src: ['<%= project.dist %>/components/lq-*']
			},
		},

		sass: {
			dev: {
				options: {
					style: 'expanded',
					lineNumbers: true,
					noCache: true
				},
				files: {
					'<%= project.dist %>/css/main.min.css':'<%= project.sass %>'
				}
			},
			dist: {
				options: {
					sourcemap: 'none',
					style: 'compressed',
					noCache: true
				},
				files: {
					'<%= project.dist %>/css/main.min.css':'<%= project.sass %>'
				}
			}
		},

		jshint: {
			build: ['Gruntfile.js', '<%= project.dev %>/js/**/*.js', '!<%= project.dev %>/js/vendor/**/*.js']
		},

		copy: {
			main: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/',
						src: ['elements.html', 'robots.txt', 'manifest.json'],
						dest: '<%= project.dist %>/',
					}
				]
			},
			img: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/img/',
						src: '*.*',
						dest: '<%= project.dist %>/img/'
					},
					{
						expand: true,
						cwd: '<%= project.dev %>/img/touch',
						src: '*.*',
						dest: '<%= project.dist %>/img/touch'
					},
					{
						expand: true,
						cwd: '<%= project.dev %>/img/avatars',
						src: ['*', '!*.htaccess'],
						dest: '<%= project.dist %>/img/avatars'
					}
				]
			},
			css: {
				files: [
					{
						expand: true,
						src: '<%= project.dev %>/css/**/*.css',
						dest: '<%= project.dist %>/css/',
						flatten: true,
						filter: 'isFile'
					}
				]
			},
			js: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/js/',
						src: '**',
						dest: '<%= project.dist %>/js/'
					}
				]
			},
			components: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/bower_components/',
						src : [ '*/**','!*/test/**/*', '!*/demo/**/*', '!*/index.html', '!*/bower.json', '!*/README.md' ],
						dest: '<%= project.dist %>/components/',
					}
				]
			},
			elements: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/elements/',
						src: '**',
						dest: '<%= project.dist %>/components/',
					}
				]
			},
			composer: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/api/vendor/',
						src: '**',
						dest: '<%= project.dist %>/api/vendor/',
					}
				]
			},
			api: {
				files: [
					{
						expand: true,
						cwd: '<%= project.dev %>/api/v1/',
						src: ['**/*'],
						dest: '<%= project.dist %>/api/v1/',
					}
				]
			},
			dev: {
				files: [
					{
						src: '<%= project.dev %>/index.dev.html',
						dest: '<%= project.dist %>/index.html',
					},
					{
						src: '<%= project.dev %>/dev.htaccess',
						dest: '<%= project.dist %>/.htaccess',
					},
					{
						src: '<%= project.dev %>/api/dev.htaccess',
						dest: '<%= project.dist %>/api/.htaccess',
					},
					{
						src: '<%= project.dev %>/api/v1/config/db.config.dev.php',
						dest: '<%= project.dist %>/api/v1/config/db.config.php',
					},
					{
						src: '<%= project.dev %>/img/avatars/dev.htaccess',
						dest: '<%= project.dist %>/img/avatars/.htaccess',
					},
				]
			},
			prod: {
				files: [
					{
						src: '<%= project.dev %>/index.prod.html',
						dest: '<%= project.dist %>/index.html',
					},
					{
						src: '<%= project.dev %>/prod.htaccess',
						dest: '<%= project.dist %>/.htaccess',
					},
					{
						src: '<%= project.dev %>/api/prod.htaccess',
						dest: '<%= project.dist %>/api/.htaccess',
					},
					{
						src: '<%= project.dev %>/api/v1/config/db.config.prod.php',
						dest: '<%= project.dist %>/api/v1/config/db.config.php',
					},
					{
						src: '<%= project.dev %>/img/avatars/prod.htaccess',
						dest: '<%= project.dist %>/img/avatars/.htaccess',
					},
				]
			},
		},
		watch: {
			options: {
				dateFormat: function(time) {
					grunt.log.writeln('The watch finished in ' + time + 'ms at ' + (new Date()).toString());
					grunt.log.writeln('Waiting for more changes...');
				}
			},
			gruntfile: {
				files: ['Gruntfile.js'],
				tasks: ['jshint']
			},
			img: {
				files: ['<%= project.dev %>/img/**/*'],
				tasks: ['clean:img', 'copy:img', 'copy:dev']
			},
			sass: {
				files: ['<%= project.dev %>/sass/**/*', '<%= project.dev %>/css/**/*'],
				tasks: ['clean:css', 'sass:dev', 'copy:css']
			},
			scripts: {
				files: ['<%= project.dev %>/js/**/*'],
				// tasks: ['clean:scripts', 'jshint', 'copy:js']
				tasks: ['clean:scripts', 'copy:js']
			},
			files: {
				files: ['<%= project.dev %>/*'],
				tasks: ['clean:php', 'copy:main', 'copy:dev']
			},
			components: {
				files: ['<%= project.dev %>/bower_components/**/*'],
				tasks: ['clean:components', 'copy:components', 'copy:elements', 'clean:components_after']
			},
			elements: {
				files: ['<%= project.dev %>/elements/**/*'],
				tasks: ['clean:elements', 'copy:elements']
			},
			api: {
				files: ['<%= project.dev %>/api/v1/**/*', '<%= project.dev %>/api/*.htaccess'],
				tasks: ['clean:api', 'copy:api', 'copy:dev']
			},
			composer: {
				files: ['<%= project.dev %>/api/vendor/**/*'],
				tasks: ['clean:composer', 'copy:composer']
			},
		}
	});

	// Load the plugins
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-copy');

	grunt.log.writeln("");
	grunt.log.writeln( "  /$$$$$$$$/$$               /$$                                                                      /$$$$$$ /$$   /$$/$$$$$$/$$$$$$$$  " );
	grunt.log.writeln( " |__  $$__| $$              | $$                                                                     /$$__  $| $$  | $|_  $$_|_____ $$   " );
	grunt.log.writeln( "    | $$  | $$$$$$$  /$$$$$$| $$       /$$$$$$ /$$$$$$$  /$$$$$$ /$$   /$$ /$$$$$$  /$$$$$$  /$$$$$$| $$  l $| $$  | $$ | $$      /$$/   " );
	grunt.log.writeln( "    | $$  | $$__  $$/$$__  $| $$      |____  $| $$__  $$/$$__  $| $$  | $$|____  $$/$$__  $$/$$__  $| $$  | $| $$  | $$ | $$     /$$/    " );
	grunt.log.writeln( "    | $$  | $$  l $| $$$$$$$| $$       /$$$$$$| $$  l $| $$  l $| $$  | $$ /$$$$$$| $$  l $| $$$$$$$| $$  | $| $$  | $$ | $$    /$$/     " );
	grunt.log.writeln( "    | $$  | $$  | $| $$_____| $$      /$$__  $| $$  | $| $$  | $| $$  | $$/$$__  $| $$  | $| $$_____| $$/$$ $| $$  | $$ | $$   /$$/      " );
	grunt.log.writeln( "    | $$  | $$  | $|  $$$$$$| $$$$$$$|  $$$$$$| $$  | $|  $$$$$$|  $$$$$$|  $$$$$$|  $$$$$$|  $$$$$$|  $$$$$$|  $$$$$$//$$$$$$/$$$$$$$$  " );
	grunt.log.writeln( "    |__/  |__/  |__/l_______|________/l_______|__/  |__/l____  $$l______/ l_______/l____  $$l_______/l____ $$$l______/|______|________/  " );
	grunt.log.writeln( "                                                        /$$  l $$                  /$$  l $$ "+" /$$         l__/                           ".cyan );
	grunt.log.writeln( "                                                       |  $$$$$$/                 |  $$$$$$/ "+"| $$                                        ".cyan );
	grunt.log.writeln( ""+"                                             /$$$$$$  /$$$$$$__/$$$$$$ /$$ /$$$$$$ l/$$$$$$$/$$$$$$                                      ".cyan );
	grunt.log.writeln( ""+"                                            /$$__  $$/$$__  $$/$$__  $|__//$$__  $$/$$_____|_  $$_/                                      ".cyan );
	grunt.log.writeln( ""+"                                           | $$  l $| $$  l__| $$  l $$/$| $$$$$$$| $$       | $$                                        ".cyan );
	grunt.log.writeln( ""+"                                           | $$  | $| $$     | $$  | $| $| $$_____| $$       | $$ /$$                                    ".cyan );
	grunt.log.writeln( ""+"                                           | $$$$$$$| $$     |  $$$$$$| $|  $$$$$$|  $$$$$$$ |  $$$$/                                    ".cyan );
	grunt.log.writeln( ""+"                                           | $$____/|__/      l______/| $$l_______/l_______/  l___/                                      ".cyan );
	grunt.log.writeln( ""+"                                           | $$                  /$$  | $$                                                               ".cyan );
	grunt.log.writeln( ""+"                                           | $$                 |  $$$$$$/                                                               ".cyan );
	grunt.log.writeln( ""+"                                           |__/                  l______/                                                                ".cyan );
	grunt.log.writeln("");
	grunt.log.writeln("                                                    https://www.thelanguagequiz.com");
	grunt.log.writeln("                                                        info@thelanguagequiz.com");
	grunt.log.writeln("");
	grunt.log.writeln("");


	// Default task(s).
	grunt.registerTask( 'copy:all', ['copy:main', 'copy:img', 'copy:css', 'copy:js', 'copy:composer', 'copy:components', 'copy:elements', 'copy:api'] );
	grunt.registerTask( 'dev',      ['clean:all', 'sass:dev', 'copy:all', 'copy:dev', 'clean:components_after'] );
	grunt.registerTask( 'prod',     ['clean:all', 'sass:dist', 'jshint', 'copy:all', 'copy:prod', 'clean:components_after'] );

};
