var gulp = require('gulp');
var postcss = require('gulp-postcss');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var autoprefixer = require('autoprefixer');
var cleanCSS = require('gulp-clean-css');

var paths = {
	styles: {
		src: 'css/notificationx-*.css',
		dest: 'css/'
	},
	scripts: {
		src: 'js/nx-admin.js',
		dest: 'js/'
	},
	pStyles: {
		src: '../../public/assets/css/notificationx-*.css',
		dest: '../../public/assets/css/'
	},
	pScripts: {
		src: '../../public/assets/js/notificationx-public.js',
		dest: '../../public/assets/js/'
	}
};

function styles() {
	return gulp.src(paths.styles.src)
		// pass in options to the stream
		.pipe(postcss( [ autoprefixer() ] ))
		.pipe(cleanCSS())
		.pipe(concat('nx-admin.min.css'))
		.pipe(gulp.dest(paths.styles.dest));
}

function scripts() {
	return gulp.src(paths.scripts.src, { sourcemaps: true })
		.pipe(uglify())
		.pipe(concat('nx-admin.min.js'))
		.pipe(gulp.dest(paths.scripts.dest));
}

function pStyles() {
	return gulp.src(paths.pStyles.src)
		// pass in options to the stream
		.pipe(postcss( [ autoprefixer() ] ))
		.pipe(cleanCSS())
		.pipe(concat('notificationx-public.min.css'))
		.pipe(gulp.dest(paths.pStyles.dest));
}

function pScripts() {
	return gulp.src(paths.pScripts.src, { sourcemaps: true })
		.pipe(uglify())
		.pipe(concat('notificationx-public.min.js'))
		.pipe(gulp.dest(paths.pScripts.dest));
}

function watch() {
	gulp.watch(paths.scripts.src, scripts);
	gulp.watch(paths.styles.src, styles);

	gulp.watch(paths.pScripts.src, pScripts);
	gulp.watch(paths.pStyles.src, pStyles);
}

/*
 * Specify if tasks run in series or parallel using `gulp.series` and `gulp.parallel`
 */
var build = gulp.parallel(styles, scripts, pStyles, pScripts);

/*
 * You can still use `gulp.task` to expose tasks
 */
gulp.task('build', build);
gulp.task('watch', watch);
/*
 * Define default task that can be called by just running `gulp` from cli
 */
gulp.task('default', build);