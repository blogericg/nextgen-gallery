'use strict';

/*
 *   **** REQUIRES GULP 4.0 ***
 *
 * This file require node.js and node package manager. If you don't have those installed, please
 * see https://nodejs.org/en/download/ or install via homebrew.
 *
 * This file also requires Gulp 4.0. Gulp 4.0 is included locally within the node_modules folder in the
 * repo. But you will also need to install the Gulp 4.0+ CLI globally on your machine. If you don't have
 * Gulp installed yet, just type the following on the command line from any location/directory:
 *
 * npm install gulpjs/gulp-cli -g
 *
 * If you already have Gulp 3.x installed, type the following commands in sequence:
 *
 * npm uninstall gulp -g
 * npm install gulpjs/gulp-cli -g
 *
 * This file also uses certain node_modules. Once you have gulp installed globally and have a working
 * copy of the repo, just type the following from within the repo root folder, and node will
 * will install the needed dependencies from the package.json file.
 *
 * npm install
 *
 */

// REQUIRE PLUGINS
const gulp 		= require('gulp');
const shell 	= require('gulp-shell');
const del 		= require('del');
const argv		= require('yargs').argv;
const zip		= require('gulp-zip');
const children	= require('child_process');
const fs		= require('fs');
const uglify 	= require('gulp-uglify');
const cleancss  = require('gulp-clean-css');
const rename 	= require('gulp-rename');
const webpack 	= require('webpack-stream');
const babel 	= require('gulp-babel');
const exec 		= children.exec;

const gutil = {
	log: require('fancy-log'),
	colors: require('ansi-colors')
}

/*
 *
 * COMPILE PLUGIN IN BUILD FOLDER, DEPLOY TO TESTING INSTANCE, AND GENERATE ZIP
 *
 * To build a compiled and minified working copy of plugin files (files are created and compiled in a
 * build/ folder within the plugin repo folder), type:
 *
 * gulp build
 *
 * To deploy the same updated plugin files automaticaly to a local testing instance, first setup an environmental
 * variable for DEPLOY_PATH. This is the path the wp-content folder of your local WordPress instance. You can
 * add this by adding the following to your .bash_profile. Be sure to replace path/to/test-instance with the
 * path to your local testing instance, but ensure path goes exactly to wp-content/ folder just as below:
 *
 * export DEPLOY_PATH=$HOME/path/to/test-instance/wp-content/
 *
 * After saving, you will need to reload your .bash_profile by typing:
 *
 * source .bash_profile
 *
 * You can confirm that your DEPLOY_PATH variable is set by typing:
 *
 * printenv DEPLOY_PATH
 *
 * Once your deploy path is set, you can deploy updated plugin files to that path with each build by
 * adding '-d' to the build command:
 *
 * gulp build -d
 *
 * Finally, you can generate a zip file when building by adding -z [version] to the build command. The
 * zip will be created in your systems temp file (ie your TMPDIR environmental variable). Note that a
 * version number is required to use the -z command.
 *
 * gulp build -z 1.0.0
 *
 * You can also use both arguments above together:
 *
 * gulp build -d -z 1.0.0
 *
 *
 * REMOTE DEPLOYMENT USING LFTP
 * -----------------
 * Using gulp and LFTP, you can also deploy your build remotely.
 *
 *
 * Ensure that you have LFTP installed:
 *
 * >	apt-get install lftp # Linux
 *
 * OR
 *
 * > brew install lftp # macOS
 *
 * OR
 *
 * > choco install lftp # Windows
 *
 *
 * Specify the -rd options to remotely deploy using LFTP
 *
 * gulp build -rd
 *
 *
 * NOTE: Before we generate a build, we always delete the "build" folder first. We do that in case any files were
 * actually deleted as part of a changeset. But in practice, we rarely delete files. To make LFTP deployments faster
 * you can use the following which will omit deleting the build folder before a deployment:
 *
 * gulp build -rdk
 */

// Define vars
var product 		= 'nextgen-gallery';
var deploy_path		= null;
var deploy_files 	= './build/' + product + '/**/*';
var do_zip 			= argv.hasOwnProperty('z');
var do_deploy 		= argv.hasOwnProperty('d');
var do_keep_build	= argv.hasOwnProperty('k');
var do_use_lftp		= do_deploy && argv.hasOwnProperty('r');
var ftp_host		= process.env.hasOwnProperty('FTP_HOST') ?		process.env.FTP_HOST : null;
var ftp_path		= process.env.hasOwnProperty('FTP_PATH') ?		process.env.FTP_PATH : null;
var ftp_user		= process.env.hasOwnProperty('FTP_USER') ?		process.env.FTP_USER : null;
var ftp_pass		= process.env.hasOwnProperty('FTP_PASS') ?		process.env.FTP_PASS : null;
var ftp_proto		= process.env.hasOwnProperty('FTP_PROTOCOL')?	process.env.FTP_PROTOCOL : 'ftp';
var lftp_bin		= process.env.hasOwnProperty('LFTP_BIN') ?		process.env.LFTP_BIN : null;
var version	= do_zip ? argv.z : '';


// Set the deploy path
if (process.env.hasOwnProperty('DEPLOY_PATH')) {
	deploy_path = process.env.DEPLOY_PATH;
	deploy_path = deploy_path.replace(/['"]/g, '');
	if (!deploy_path.endsWith('/')) deploy_path += '/';
	deploy_path += 'plugins/' + product;
}

/*** INTERNAL FUNCTIONS ***/
function which_lftp()
{
	var retval	= '';

	return new Promise(function(resolve, reject){
		exec('which lftp', {}, function(err, stdout, stderr){
			retval = stdout ? stdout : lftp_bin;
			if (retval) resolve(retval.trim());
			else reject("Could not find LFTP. Perhaps try setting the LFTP_BIN environment variable");
		});
	});
}

function exec_lftp(bin)
{
	return new Promise(function(resolve, reject){
		if (ftp_host && ftp_path && ftp_user) {
			var creds = ftp_pass ? ftp_user + ",'" + ftp_pass + "'" : ftp_user
			var remote_path = (ftp_path + '/wp-content/plugins/') .replace('//', '/');
			var cmds = [
				"<< EOF",
				"set ssl:check-hostname no",
				"set ftp:sync-mode off",
				'set ftp:list-options -a',
				"cd " + remote_path,
				"mirror -R -c -e -v -P 5 ./build/" + product + ' ' + product,
				"EOF"
			];
			gutil.log(gutil.colors.underline.yellow('WARNING - This might take a while'));
			exec(bin + " -u " + creds + " " + ftp_proto + '://' + ftp_host + ' ' + cmds.join("\n"), function(err, stdout, stderr){
				if (err) 		  			reject(err);
				else if (!stdout && stdout) reject(stderr);
				else 						resolve(stdout);
			});
		}
		else reject("Please set FTP_HOST, FTP_PATH, FTP_USER, and FTP_PASS environment variables");
	});
}


/*** GULP TASKS BELOW ***/
gulp.task('showdeploydir', function(done){
	gutil.log(gutil.colors.yellow(deploy_path));
	done();
});


// Create the deploy path directory if it doesn't exist
gulp.task('mkdeploydir', shell.task('mkdir -p "'+deploy_path+'" || true', {
	quiet: true
}));

// Delete contents of build and deploy folders
gulp.task('deldevdeps', shell.task('composer --no-dev update', {
	cwd: './build/' + product
}));

// Delete contents of build and deploy folders
gulp.task('delbuild', function() {
	return del('build/**/*');
});

// Delete the contents of your deployed directory
gulp.task('deldeploy', function() {
	if (deploy_path)
		return del(deploy_path + '/**/*',{force: true});
	else
		console.error("Please define the DEPLOY_PATH environment variable.");
});

// Copy files to build folder and delete unneeded files
gulp.task('copybuild', function() {
	return gulp.src([
		'./**/*',
		'!.*',
		'!./{*.orig,**/*.orig}',
		'!gulpfile.js',
		'!package.json',
		'!package-lock.json',
		'!./{node_modules,node_modules/**}',
		'!./{pope/tests,pope/tests/**,pope/run_tests.php}',
		'!./{build,build/**}',
		'!./{tests,tests/**}',
		'!./{zips,zips/**}',
		'!./{autoupdate_config,autoupdate_config/**}',
		'!.env',
		'!README.md',
		'!./{config,config/**}',
		'!docker-compose.yml',
		'!export.sh',
		'!./{wp-app,wp-app/**}',
		'!./{wp-data,wp-data/**}'])
		.pipe(gulp.dest('./build/' + product));
});

gulp.task('webpack-block', function() {
  return gulp.src('./build/nextgen-gallery/products/photocrati_nextgen/modules/nextgen_block/static/src/block.jsx')
    .pipe(webpack({
		mode: "production",
		devtool: "source-map",	
		module: {
			rules: [
				{ test: /\.js[x]?$/, loader: 'babel-loader', query: { presets: ['@babel/preset-env', '@babel/preset-react'], plugins: ['@babel/plugin-proposal-class-properties'] } },
			],
		},
		output: {
			filename: 'block.min.js',
		},
    }))
    .pipe(gulp.dest("./build/nextgen-gallery/products/photocrati_nextgen/modules/nextgen_block/static/build"));
});

gulp.task('webpack-others', function() {
	return gulp.src('./build/nextgen-gallery/products/photocrati_nextgen/modules/nextgen_block/static/src/post-thumbnail.jsx')
	.pipe(webpack({
		mode: "production",
		devtool: "source-map",	
		module: {
			rules: [
			{ test: /\.js[x]?$/, loader: 'babel-loader', query: { presets: ['@babel/preset-env', '@babel/preset-react'], plugins: ['@babel/plugin-proposal-class-properties'] } },
			],
		},
		output: {
			filename: 'post-thumbnail.min.js',
		},
    }))
    .pipe(gulp.dest("./build/nextgen-gallery/products/photocrati_nextgen/modules/nextgen_block/static/build"));
})

gulp.task('minify-css', function() {
	return gulp.src(["./build/" + product + "/**/*.css", "!./build/"+product+"/**/*.min.css*"])
		.pipe(cleancss({debug: true}))
	  	.pipe(rename({
		  	suffix: ".min"
		}))
	  	.pipe(gulp.dest("./build/" + product));
});

gulp.task('minify-js', function() {  
  return gulp.src(["./build/" + product + "/**/*.js", "!./build/"+product+"/**/*.min.js*", "!./build/"+product+"/**/*.packed.js*"])
		.pipe(babel({
			presets: ['@babel/env']
		}))
		.pipe(uglify())
	  	.pipe(rename({
		  	suffix: ".min"
		}))
	  	.pipe(gulp.dest("./build/" + product));
});

gulp.task('minify', gulp.series('minify-css','minify-js') );

gulp.task('compile', shell.task('php bin/compile_modules.php', {
	cwd: './build/' + product
}));

// After minification/compiling, delete bin
gulp.task('cleanbin', function() {
	return del('./build/' + product + '/bin');
});

// If requested, push minifed/compiled files to local testing instance
gulp.task('deploybuild', function() {
	if (deploy_path) {
		return gulp.src(deploy_files, {base: './build/' + product })
			.pipe(gulp.dest(deploy_path));
	}
	else console.error("Please define the DEPLOY_PATH environment variable.");
});

// If requested, ensures that the DEPLOY_PATH directory exists, and then runs the deployment
gulp.task('deploy', gulp.series(['mkdeploydir', 'deploybuild']));

// If requested, generate a distributable zip file
gulp.task('zip', function() {
	var filename = version ? product + '.' + version + '.zip' : product + '.zip';
	return gulp.src(deploy_files, {base: "./build"})
		.pipe(zip(filename))
		.pipe(gulp.dest('./build/zips/distributables'));
});

/**
 * Deploy the plugin to a remote location using FTP or SFTP
 */
gulp.task('rdeploy', function(){
	return which_lftp().then(exec_lftp);
});

/*
 * Finally, one command to do it all in sequence
 */
var build_tasks 					= ['copybuild','webpack-block', 'webpack-others', 'minify', 'compile', 'deldevdeps', 'cleanbin'];
if (!do_keep_build)					build_tasks.unshift('delbuild');
if (do_zip)							build_tasks.push('zip');
if (do_deploy && !do_use_lftp)		build_tasks = build_tasks.concat(['deldeploy', 'deploy']);
else if (do_deploy && do_use_lftp)	build_tasks.push('rdeploy');
gulp.task('build', 	gulp.series(build_tasks));
