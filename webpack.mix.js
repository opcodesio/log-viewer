let mix = require('laravel-mix');

mix.sass('resources/css/app.scss', 'public');
mix.js('resources/js/app.js', 'public');

mix.js('resources/js/appnew.js', 'public').vue();
