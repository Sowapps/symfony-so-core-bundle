# SoCore Symfony Bundle

SoCore is a Symfony Bundle to bring basic features to your Sowapps App

## Standards

Project is done for the following requirements:

- We use latest Symfony standards as mush as possible
- We use Symfony 6.1+
- We use php 8.1+
- We use Webpack
- We use SCSS
- We use native JS ES6+ (no jQuery)
- We use stimulus as
- We use bootstrap 5+ as front framework
- We use FontAwesome 6+ for icons
- JS file compile directly only JS file, full component plugins and include SCSS entry point
- SCSS contains all SCSS from project, bundles and frameworks

## Install

### Requirements

WARNING: REMOVING WEBPACK FOR SYMFONY ASSET MAPPER

All front dependencies must be added to your project

```
yarn add bootstrap startbootstrap-sb-admin @fortawesome/fontawesome-free @popperjs/core sass sass-loader simple-datatables
```

### Configuration

We don't configure packages in our bundle to let you customize it for your needs.
You could see one of our project as example [SoIngenious](https://github.com/Sowapps/symfony-so-ingenious-demo/tree/main/config/packages).
Below the configuration the bundle is requiring to work properly.

#### Configure Messenger

Configure or disable configuration in `config/packages/messenger.yaml`.

For local classic usage, we comment the whole file.

#### Configure Doctrine

Configure `config/packages/doctrine.yaml`.

Set resolve_target_entities

#### Configure Security

Configure `config/packages/security.yaml`.

Set app_user_provider
Set form_login in firewall
Set remember_me in firewall
Set logout in firewall
Set role_hierarchy
Set access_control

#### Configure Translations

Configure `config/packages/translation.yaml`.

#### Configure Twig

Configure `config/packages/twig.yaml`.

Set globals

#### Configure SoCore

Configure `config/packages/so_core.yaml`.

#### Configure Routing

Configure `config/routes.yaml`.

Include SoCoreBundle routes.

### Import Fixtures

```php
bin/console doctrine:fixtures:load
```

TODO Separate initialization fixtures and sample fixtures

## Override

First, have a look on this page: https://symfony.com/doc/current/bundles/override.html

### Controllers

Extends our controller and write route to your own

### Templates

Put your template in /templates/bundles/SoCoBundle/ by respecting given hierarchy, you may extend our template to replace blocks only.

### Doctrine Custom types

Replace type in your doctrine configuration by your class. Extends our class and add more values for an enum.

### Stimulus controllers

Add controllers to the `assets/controllers.json` file under `@sowapps/so-core`

## Develop

### Create Fixtures

Some features are required to initialize the app with basic data, this is why the package `doctrine/doctrine-fixtures-bundle` is not dev only.  
For initialization fixtures : `config/fixtures/fixtures-init.yaml`  
For demo sample fixtures : `config/fixtures/fixtures-sample.yaml`

### Webpack

WARNING: REMOVING WEBPACK FOR SYMFONY ASSET MAPPER

For now, only `yarn add file:../so-core-bundle/assets;` works, but updating source requires to restart watch.
The package is in assets folder to prevent embedding all the bundle in the node module.

H:\Workspaces\git\so-core-bundle

/!\ It does not work, packages sources does not find any module

Link your package using yarn
See https://benjaminwfox.com/blog/tech/why-isnt-npm-link-working

In the package assets/ folder, run

`yarn link`

Now, your package is available in any project but as a symlink instead of a remote repository.

In your project folder, run

``yarn link @sowapps/so-core``

### Stimulus controllers

Create your controllers in `assets/controllers`

Declare them in `assets/package.json`

Use it as `@sowapps--so-core--name` with name the given name in package.json

