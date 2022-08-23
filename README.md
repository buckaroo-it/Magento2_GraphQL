<p align="center">
  <img src=" " width="200px" position="center">
</p>

# Buckaroo Magento2 GraphQL extension
We created a additional GraphQL extension for our [Buckaroo Magento 2 Payments Plugin](https://github.com/buckaroo-it/Magento2).<br>
This was needed for another additional extension that we released, and that is the [Hÿva React checkout extension](https://github.com/buckaroo-it/Magento2_Hyva).<br>

<b>What is GraphQL?</b><br>GraphQL is a query language for APIs and a runtime for fulfilling those queries with your existing data. GraphQL provides a complete and understandable description of the data in your API, gives clients the power to ask for exactly what they need and nothing more, makes it easier to evolve APIs over time, and enables powerful developer tools.

### Index
- [Installation](#installation)
- [Requirements](#requirements)
- [Additional information](#additional-information)
- [Contribute](#contribute)
- [Versioning](#versioning)
- [Additional information](#additional-information)
---

### Installation
  - Install the module with composer by running `composer require buckaroo/module-magento2graphql`
  - Enable the module by running `php bin/magento module:enable Buckaroo_Magento2Graphql`
  - Apply the database updates by running `php bin/magento setup:upgrade`\*
  - Flush the cache by running `php bin/magento cache:flush`

### Requirements
To use the plugin you must use: 
- Magento Open Source version 2.3.x or 2.4.x
- [Buckaroo Magento 2 Payments plugin](https://github.com/buckaroo-it/Magento2) 1.39.0 or greater.

### Additional information
For more information on Buckaroo GraphQL please visit:
https://support.buckaroo.nl/categorieen/plugins/magento-2

### Contribute
We really appreciate it when developers contribute to improve the Buckaroo plugins.
If you want to contribute as well, then please follow our [Contribution Guidelines](CONTRIBUTING.md).

### Versioning 
<p align="left">
  <img src="https://www.buckaroo.nl/media/3480/magento_versioning.png" width="500px" position="center">
</p>

### Additional information
- **Support:** https://support.buckaroo.eu/contact
- **Contact:** [support@buckaroo.nl](mailto:support@buckaroo.nl) or [+31 (0)30 711 50 50](tel:+310307115050)
