var gulp = require('gulp'),
	sass = require('gulp-sass'),
	cleanCss = require('gulp-clean-css'),
	concat = require('gulp-concat'),
	sourcemaps = require('gulp-sourcemaps');
var paths = {
	pAssets: 'public/assets/',
	pStyles: {
		src: 'public/assets/scss/*.scss',
		dest: 'public/assets/css/'
	}
};
gulp.task('pSass', function(){
	return gulp.src( paths.pStyles.src )
		.pipe(sourcemaps.init())
		.pipe( sass())
		.pipe(cleanCss({format: 'beautify',level: 0}))
		.pipe(sourcemaps.write(''))
		.pipe( gulp.dest( paths.pStyles.dest ));
});
gulp.task('pConcat',function () {
	return gulp.src(paths.pAssets + 'css/notificationx-public.css')
		.pipe(sourcemaps.init())
		.pipe(concat('notificationx-public.min.css'))
		.pipe(cleanCss())
		.pipe(sourcemaps.write(''))
		.pipe( gulp.dest( paths.pStyles.dest ));
});
gulp.task('watch', function() {
	gulp.watch( paths.pAssets + 'scss/**/*.scss',  gulp.series('pSass'));
	gulp.watch( paths.pAssets + 'css/notificationx-public.css',  gulp.series('pConcat'));
});