'use strict';

var SRC_PATH = './src';
var DIST_PATH = './dist';
// var TMP_PATH = './.tmp';
var TMP_PATH = DIST_PATH;

// TODO:
// js vendor

var gulp = require('gulp');
var g = require('gulp-load-plugins')();

var runSequence = require('run-sequence').use(gulp);
var memRev = require('./utils/gulp-memrev');
var del = require('del');
var babelify = require('babelify');

var DIST = false;
var CURRENT_PATH = TMP_PATH;

// for VM stuff
var WAIT = 2000;


gulp.task('clean', function (done) {
  del([TMP_PATH, DIST_PATH], done);
});

gulp.task('html', function () {
  var stream = gulp.src(SRC_PATH + '/**/*.html');
  if (DIST) {
    stream.pipe(memRev.replace())
      .pipe(gulp.dest(CURRENT_PATH));
  } else {
    stream.pipe(g.livereload())
      // .pipe(g.wait(WAIT))
      .pipe(g.livereload());
  }
  return stream;
});

gulp.task('views', function () {
  var stream = gulp.src([
    SRC_PATH + '/views/**/*.twig',
    SRC_PATH + '/index.php',
    SRC_PATH + '/.htaccess'
  ],
    { base: SRC_PATH });

  if (DIST) {
    stream.pipe(memRev.replace());
  }

  stream.pipe(gulp.dest(CURRENT_PATH));

  if (!DIST) {
    stream.pipe(g.wait(WAIT))
      .pipe(g.livereload());
  }

  return stream;
});

gulp.task('images', function () {
 var s = gulp.src([SRC_PATH + '/images/**/*']);
  if (DIST) {
    s.pipe(g.imagemin());
  }
  s.pipe(gulp.dest(CURRENT_PATH + '/images'));
  return s;
});

gulp.task('assets', function () {
  return gulp.src([
    SRC_PATH + '/fonts/**/*',
    SRC_PATH + '/locales/**/*'
    // , ... more
  ], { base : SRC_PATH })
  .pipe(gulp.dest(CURRENT_PATH));
});


gulp.task('watch', function () {
  gulp.watch([
    SRC_PATH + '/**/*.php',
    SRC_PATH + '/**/*.twig' ], ['views']);

  // gulp.watch(SRC_PATH + '/**/*.html', ['html']);
  gulp.watch(SRC_PATH + '/**/*.scss', ['sass']);
  gulp.watch(SRC_PATH + '/**/*.js',   ['browserify']);
});

gulp.task('serve', function (done) {
  g.livereload.listen();
  runSequence('clean', 'lint', ['browserify', 'sass', 'assets', 'images'], 'views', 'watch', done);
});

gulp.task('sass', function () {
  var stream = gulp.src(SRC_PATH + '/sass/main.scss')
    .pipe(g.plumber({errorHandler: g.notify.onError('Sass: <%= error.message %>')}))
    .pipe(g.sourcemaps.init())
    .pipe(g.sass({
      includePaths: [
        './bower_components',
        './node_modules/foundation-sites/scss'
      ]
    }))
     // will probably break sourcemaps?
    .pipe(g.autoprefixer({
        browsers: ['last 2 versions'],
        cascade: false,
        remove: true
      }));

  if (DIST) {
    stream
      .pipe(g.rev())
      .pipe(memRev())
      .pipe(g.minifyCss());
  } else {
    stream
      .pipe(g.sourcemaps.write())
      .pipe(g.livereload());
  }

  return stream.pipe(gulp.dest(CURRENT_PATH + '/css/'));
});

gulp.task('browserify', ['lint'], function() {
  var stream = gulp.src(SRC_PATH + '/js/index.js')
    .pipe(g.plumber({errorHandler: g.notify.onError('Browserify: <%= error.message %>')}))
    .pipe(g.browserify2({
      fileName: 'bundle.js',
      transform: babelify,
      options: {
        debug: !DIST
      }
    }));

  if (DIST) {
    stream
      .pipe(g.rev())
      .pipe(memRev())
      .pipe(g.uglify());
  }

  stream.pipe(gulp.dest(CURRENT_PATH + '/js/'));

  if (!DIST) {
    stream.pipe(g.wait(WAIT))
      .pipe(g.livereload());
  }
  return stream;
});

gulp.task('lint', function () {
  var stream = gulp.src(SRC_PATH + '/js/**/*.js')
    .pipe(g.plumber({errorHandler: g.notify.onError('<%= error.message %>')}))
    .pipe(g.cached('linting'))
    .pipe(g.jshint())
    .pipe(g.jshint.reporter('jshint-stylish'))
    .pipe(g.jshint.reporter('fail'));
  return stream;
});

gulp.task('dist', function (done) {
  DIST = true;
  CURRENT_PATH = DIST_PATH;
  runSequence('clean', 'lint', ['browserify', 'sass', 'images', 'assets'], 'views', done);
});

gulp.task('default', ['serve']);
