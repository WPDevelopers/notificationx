var gulp = require('gulp'),
	sass = require('gulp-sass'),
	cleanCSS = require('gulp-clean-css'),
	concat = require('gulp-concat'),
	autoprefixer = require('autoprefixer'),
	postcss = require('gulp-postcss'),
	uglify = require('gulp-uglify'),
	sourcemaps = require('gulp-sourcemaps');
const { src, dest, series } = require('gulp');
const zip = require('gulp-zip');
const clean = require('gulp-clean');
const run = require('gulp-run');

var paths = {
	pAssets: 'public/assets/',
	adminStyles: {
		src: 'admin/assets/css/notificationx-admin.css',
		dest: 'admin/assets/css/'
	},
	gAdminStyles: {
		src: 'admin/assets/css/notificationx-admin-global.css',
		dest: 'admin/assets/css/'
	},
	adminScripts: {
		src: 'admin/assets/js/nx-admin.js',
		dest: 'admin/assets/js/'
	},
	pScripts: {
		src: 'public/assets/js/notificationx-public.js',
		dest: 'public/assets/js/'
	},
	pStyles: {
		src: 'public/assets/scss/*.scss',
		dest: 'public/assets/css/'
	}
};
function adminStyles(){
	return gulp
		.src(paths.adminStyles.src)
		.pipe(postcss( [ autoprefixer() ] ))
		.pipe(cleanCSS())
		.pipe(concat('nx-admin.min.css'))
		.pipe(gulp.dest(paths.adminStyles.dest));
}
function gAdminStyles(){
	return gulp.src(paths.gAdminStyles.src)
		.pipe(postcss( [ autoprefixer() ] ))
		.pipe(cleanCSS())
		.pipe(concat('nx-admin-global.min.css'))
		.pipe(gulp.dest(paths.gAdminStyles.dest));
}
function adminScripts(){
	return gulp.src(paths.adminScripts.src, { sourcemaps: true })
		.pipe(uglify())
		.pipe(concat('nx-admin.min.js'))
		.pipe(gulp.dest(paths.adminScripts.dest));
}
function pSass(){
	return gulp.src( paths.pStyles.src )
		.pipe(sourcemaps.init())
		.pipe(postcss( [ autoprefixer() ] ))
		.pipe( sass().on('error', sass.logError))
		.pipe(cleanCSS({format: 'beautify',level: 0}))
		.pipe(sourcemaps.write(''))
		.pipe( gulp.dest( paths.pStyles.dest ));
}
function pConcat() {
	return gulp.src(paths.pAssets + 'css/notificationx-public.css')
		.pipe(sourcemaps.init())
		.pipe(concat('notificationx-public.min.css'))
		.pipe(cleanCSS())
		.pipe(sourcemaps.write(''))
		.pipe( gulp.dest( paths.pStyles.dest ));
}
function pScripts() {
	return gulp.src(paths.pScripts.src, { sourcemaps: true })
		.pipe(uglify())
		.pipe(concat('notificationx-public.min.js'))
		.pipe(gulp.dest(paths.pScripts.dest));
}
function watch() {
	gulp.watch( paths.adminStyles.src, adminStyles);
	gulp.watch( paths.gAdminStyles.src, gAdminStyles);
	gulp.watch( paths.adminScripts.src, adminScripts);
	gulp.watch( paths.pAssets + 'scss/**/*.scss', pSass);
	gulp.watch( paths.pAssets + 'css/notificationx-public.css', pConcat);
	gulp.watch( paths.pScripts.src, pScripts);
}


var build = gulp.parallel(adminStyles, gAdminStyles, adminScripts, pSass, pConcat, pScripts);
gulp.task('build', build);
/*
 * watch task
 */
gulp.task('watch', watch);
/*
 * Define default task that can be called by just running `gulp` from cli
 */
gulp.task('default', build);

function buildJS(){
    return run('gulp build').exec();
}

function cleanDist() {
    return src( './dist', { read: false, allowEmpty: true } ).pipe( clean() );
}

function makeDist() {
    return src([
        './**/*.*',
        '!./dist/**/*.*',
        '!./node_modules/**/*.*',
        '!./**/*.zip',
        '!./Gruntfile.js',
        '!./gulpfile.js',
        '!./.gitignore',
        '!./package-lock.json',
        '!./package.json',
    ]).pipe( dest( 'dist/notificationx/' ) );
}

function cleanZip() {
    return src( './notificationx.zip', { read: false, allowEmpty: true } ).pipe( clean() );
}

function makeZip() {
    return src( './dist/**/*.*' ).pipe( zip( '../notificationx.zip' ) ).pipe( dest( './' ) );
}

gulp.task('makeZip', series( cleanDist, cleanZip, buildJS, makeDist, makeZip, cleanDist ));