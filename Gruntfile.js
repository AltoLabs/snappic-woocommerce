module.exports = function(grunt) {

  // load most all grunt tasks
  require('load-grunt-tasks')(grunt);

  // Project configuration.
  grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),

	clean: {
		//Clean up build folder
		main: ['deploy']
	},

	copy: {
		// Copy the plugin to a versioned release directory
		main: {
			src:  [
				'**',
				'!node_modules/**',
				'!deploy/**',
				'!.git/**',
				'!Gruntfile.js',
				'!package.json',
				'!gitcreds.json',
				'!.gitcreds',
				'!.gitignore',
				'!.gitmodules',
				'!*~',
				'!*.sublime-workspace',
				'!*.sublime-project',
				'!*.transifexrc',
				'!deploy.sh',
				'!wp-assets/**',
				'!readme.md',
				'!*.bak'
			],
			dest: 'deploy/'
		},
	
	},

	wp_readme_to_markdown: {
		convert:{
			files: {
				'readme.md': 'readme.txt'
			},
		},
	},

	// # Internationalization 

	// Add text domain
	addtextdomain: {
		textdomain: '<%= pkg.name %>',
		target: {
			files: {
				src: ['*.php', '**/*.php', '!node_modules/**', '!deploy/**']
			}
		}
	},

	// Generate .pot file
	makepot: {
		target: {
			options: {
				domainPath: '/languages', // Where to save the POT file.
				exclude: ['deploy'], // List of files or directories to ignore.
				mainFile: '<%= pkg.name %>.php', // Main project file.
				potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
				type: 'wp-plugin' // Type of project (wp-plugin or wp-theme).
			}
		}
	},

	// bump version numbers
	replace: {
		Version: {
			src: [
				'readme.txt',
				'readme.md',
				'<%= pkg.name %>.php'
			],
			overwrite: true,
			replacements: [
				{
					from: /Stable tag:.*$/m,
					to: "Stable tag: <%= pkg.version %>"
				},
				{ 
					from: /Version:.*$/m,
					to: "Version: <%= pkg.version %>"
				},
				{ 
					from: /public \$version = \'.*.'/m,
					to: "public $version = '<%= pkg.version %>'"
				},
				{ 
					from: /static \$version = \'.*.'/m,
					to: "static $version = '<%= pkg.version %>'"
				},
				{ 
					from: /const VERSION = \'.*.'/m,
					to: "const VERSION = '<%= pkg.version %>'"
				}
			]
		}
	},

	// make a zipfile
	compress: {
	  main: {
	    options: {
	      archive: 'deploy/<%= pkg.name %>-<%= pkg.version %>.zip'
	    },
	    expand: true,
	    src: ['**/*', '!*.zip'],
	    dest: '/',
	    cwd: 'deploy'
	  }
	}


});





grunt.registerTask( 'docs', [ 'wp_readme_to_markdown'] );

grunt.registerTask( 'build', [ 'replace', 'addtextdomain', 'makepot', 'wp_readme_to_markdown' ] );

grunt.registerTask( 'zip', [ 'build', 'clean', 'copy', 'compress' ] );

};
