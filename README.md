# Product Labels Plugin for Craft CMS

Product Labels is a Craft Commerce plugin for managing promotional labels for products.

## License

This plugin requires a commercial license purchasable through the [Craft Plugin
Store](https://plugins.craftcms.com/productlabels).


## Requirements

This plugin requires Craft CMS 4.0.0 or later and Craft Commerce 4.0.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for
“Product Labels”. Then click on the “Install” button in its modal window.

### With Composer

Open your terminal and run the following commands in your project directory:

```
# tell Composer to load the plugin
composer require thepixelage/craft-productlabels

# tell Craft to install the plugin
./craft install/plugin productlabels
```

## Setup

You can customise the field layout for Product Labels by going to Product Labels > Settings and configuring the field layout, just like for Entries.

## Managing Product Labels

Product labels come with a set of these setting fields that control when a product label should be displayed:

| Setting       | Description                                                                                                                            |
|---------------|----------------------------------------------------------------------------------------------------------------------------------------|
| Start Date    | The date/time to start displaying this product label. If not specified, it displays immediately.                                       |
| End Date      | The date/time to stop displaying this product label. If not specified, it displays indefinitely.                                       |
| Match Product | The condition rules that a product must match in order for the product label to display. If not specified, it will match all products. |


### Product Sales Condition Rule

A Product Sales condition rule is available for specifying a list of Sales that a product should match.

### Products Condition Rule

A Products condition rule is available for specifying a list of Products to match. This is useful if you need a way to specify a list of products that are not related by a category or other attributes.

## Displaying Product Labels for Products

This plugin adds a `productLabels` property to the `Product` element type which returns an array of `ProductLabel` that are valid for the product. Once a list of product labels are queried, it can be displayed just like your entries.

Example using Twig:

```twig
{% set products = craft.products.all() %}
{% for product in products %}
    <h1>{{ product.title }}</h1>
    {% for label in product.productLabels %}
        {{ label.myCustomField }}
    {% endfor %}
{% endfor %}
```

Example using GraphQL:

```graphql
{
  products {
    title
    productLabels {
      title
      ... on ProductLabel {
        myCustomField
      }
    }
  }
}
```



---

Created by [ThePixelAge](https://www.thepixelage.com)
