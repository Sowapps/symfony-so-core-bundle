# Standards

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

# Install

## Requirements

All front dependencies must be added to your project

```
yarn add bootstrap startbootstrap-sb-admin @fortawesome/fontawesome-free @popperjs/core sass sass-loader
```

## Configuration

We don't configure packages in our bundle to let you customize it for your needs.
You could see one of our project as example [SoIngenious](https://github.com/Sowapps/symfony-so-ingenious-demo/tree/main/config/packages).
Below the configuration the bundle is requiring to work properly.

### Configure Messenger

Configure or disable configuration in `config/packages/messenger.yaml`.

For local classic usage, we comment the whole file.

### Configure Doctrine

Configure `config/packages/doctrine.yaml`.

Set resolve_target_entities

### Configure Security

Configure `config/packages/security.yaml`.

Set app_user_provider
Set form_login in firewall
Set remember_me in firewall
Set logout in firewall
Set role_hierarchy
Set access_control

### Configure Translations

Configure `config/packages/translation.yaml`.

### Configure Twig

Configure `config/packages/twig.yaml`.

Set globals

### Configure SoCore

Configure `config/packages/so_core.yaml`.

### Configure Routing

Configure `config/routes.yaml`.

Include SoCoreBundle routes.



