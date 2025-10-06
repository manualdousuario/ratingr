const gulp = require('gulp');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
const sass = require('gulp-sass')(require('sass'));

const paths = {
    jsPublic: {
        src: 'source/js/**/*.js',
        dest: 'dist/js/'
    },
    scssPublic: {
        src: 'source/scss/**/*.scss',
        dest: 'dist/css/'
    },
    jsAdmin: {
        src: 'admin/source/js/**/*.js',
        dest: 'admin/dist/js/'
    },
    scssAdmin: {
        src: 'admin/source/css/**/*.scss',
        dest: 'admin/dist/css/'
    }
};

function processjsPublic() {
    return gulp.src(paths.jsPublic.src)
        .pipe(concat('ratingr.jsPublic'))
        .pipe(gulp.dest(paths.jsPublic.dest))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.jsPublic.dest));
}

function processSCSSPublic() {
    return gulp.src(paths.scssPublic.src)
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('ratingr.css'))
        .pipe(gulp.dest(paths.scssPublic.dest))
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.scssPublic.dest));
}

function processJSAdmin() {
    return gulp.src(paths.jsAdmin.src)
        .pipe(concat('ratingr-admin.js'))
        .pipe(gulp.dest(paths.jsAdmin.dest))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.jsAdmin.dest));
}

function processSCSSAdmin() {
    return gulp.src(paths.scssAdmin.src)
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('ratingr-admin.css'))
        .pipe(gulp.dest(paths.scssAdmin.dest))
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.scssAdmin.dest));
}

function watchFiles() {
    gulp.watch(paths.jsPublic.src, processjsPublic);
    gulp.watch(paths.scssPublic.src, processSCSSPublic);
    gulp.watch(paths.jsAdmin.src, processJSAdmin);
    gulp.watch(paths.scssAdmin.src, processSCSSAdmin);
}

const build = gulp.parallel(processjsPublic, processSCSSPublic, processJSAdmin, processSCSSAdmin);
const watch = gulp.series(build, watchFiles);

exports.processjsPublic = processjsPublic;
exports.processSCSSPublic = processSCSSPublic;
exports.processJSAdmin = processJSAdmin;
exports.processSCSSAdmin = processSCSSAdmin;
exports.build = build;
exports.watch = watch;
exports.default = watch;