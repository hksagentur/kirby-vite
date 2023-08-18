# Kirby Vite

Backend integration for [Vite.js](https://vitejs.dev/guide/backend-integration.html).

## Requirements

Kirby CMS (`>=3.9`)  
PHP (`>= 8.1`)

## Installation

### Composer

```sh
composer require hksagentur/kirby-vite
```

### Download

Download the project archive and copy the files to the plugin directory of your kirby installation. By default this directory is located at `/site/plugins`.

## Configuration

### Vite

Create a `vite.config.js` file in your project root and make sure to generate a [manifest](https://vitejs.dev/config/build-options.html#build-manifest) with your production build. You can use the configuration template provided with the repository as a starting point (see [stubs/vite.config.stub](./stubs/vite.config.stub)).

### Kirby

Embed your Vite assets in your website. Add the following snippet to the header of your site:

```php
<?= vite(['site/assets/index.css', 'site/assets/index.js']) ?>
```

Make sure that the path of your scripts and styles match those in your Vite configuration.

## License

ISC License. Please see [License File](LICENSE.txt) for more information.

## Frequently asked questions

### Is the development server supported by this Kirby Plugin?

You can use the Vite development server in your testing environment. The provided [configuration template](./stubs/vite.config.stub) creates a temporary file in Kirby’s cache directory once the development server is startet. The Kirby plugin is looking for this file and loads the required Vite client instead of the generated production build. If the development server is stopped the file is automatically removed from the cache directory and the files are loaded from the manifest again.
