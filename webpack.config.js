const Encore = require("@symfony/webpack-encore");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath("public/build/")
  // public path used by the web server to access the output path
  .setPublicPath("/shareEquipe/build")
  // only needed for CDN's or subdirectory deploy
  .setManifestKeyPrefix("build/")

  /*
   * ENTRY CONFIG
   *
   * Each entry will result in one JavaScript file (e.g. app.js)
   * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
   */
  .addEntry("app", "./assets/js/app.js") // Main app entry
  .addEntry("captcha", "./assets/js/components/captcha.js") // Entrée pour le script CAPTCHA
  .addEntry("statinscrits", "./assets/js/components/statinscrits.js")
  .addEntry('jourconnexion', './assets/js/components/jourconnexion.js')
  .addEntry("categoriestats", "./assets/js/components/categoriestats.js")
  .addEntry('strengthbar', './assets/js/components/strengthbar.js')
  .addEntry('checkboxcategorystate', './assets/js/checkboxCategoryState.js')

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()

  // will require an extra script tag for runtime.js
  // but, you probably want this, unless you're building a single-page app
  .enableSingleRuntimeChunk()

  /*
   * FEATURE CONFIG
   *
   * Enable & configure other features below. For a full
   * list of features, see:
   * https://symfony.com/doc/current/frontend.html#adding-more-features
   */
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  // configure Babel
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = "usage";
    config.corejs = "3.38";
  });

// enables Sass/SCSS support
// Uncomment if you are using SCSS in your project
//.enableSassLoader()

// uncomment if you use TypeScript
//.enableTypeScriptLoader()

// uncomment if you use React
//.enableReactPreset()

// uncomment to get integrity="..." attributes on your script & link tags
// requires WebpackEncoreBundle 1.4 or higher
//.enableIntegrityHashes(Encore.isProduction())

// uncomment if you're having problems with a jQuery plugin
//.autoProvidejQuery()

module.exports = Encore.getWebpackConfig();
