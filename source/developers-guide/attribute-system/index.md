---
layout: default
title: Attribute system
github_link: developers-guide/attribute-system/index.md
shopware_version: 5.2.0
indexed: true
tags:
  - attributes
---

The attribute system allows developers and users to configure additional fields for different entities in shopware. Users can simply define new fields over the free text fields backend module.

Developers can define new attributes over the database or use the corresponding service `Shopware\Bundle\AttributeBundle\Service\CrudService`.

# Services
The `Shopware\Bundle\AttributeBundle` contains the following services:

| Class        | Service id           | Description  |
| ------------- |:-------------:| -----:|
| `Shopware\Bundle\AttributeBundle\Service\CrudService`     | shopware_attribute.crud_service | Allows to change the table schema and persist a backend configuration |
| `Shopware\Bundle\AttributeBundle\Service\DataLoader`      | shopware_attribute.data_loader | Loads the attribute data for a provided table and foreign key |
| `Shopware\Bundle\AttributeBundle\Service\DataPersister`   | shopware_attribute.data_persister | Persists the attribute data for a provided table and foreign key |
| `Shopware\Bundle\AttributeBundle\Service\SchemaOperator`  | shopware_attribute.schema_operator | Handles all schema operations for a provided table |
| `Shopware\Bundle\AttributeBundle\Service\TableMapping`    | shopware_attribute.table_mapping | Contains a mapping of all defined shopware attribute tables, their identifier or core columns and depending tables |
| `Shopware\Bundle\AttributeBundle\Service\TypeMapping`     | shopware_attribute.type_mapping | Contains all defined data types which can be used for table columns |


# Attribute data types
The attribute data types are store in the `Shopware\Bundle\AttributeBundle\Service\TypeMapping` class. Each type is defined in a unified format and the corresponding sql data type.
Following types are supported:

| Unified type        | SQL type           | Backend view  |
| ------------- |:-------------:| -----:|
| string            | VARCHAR(500)  | Ext.form.field.Text
| text              | TEXT          | Ext.form.field.TextArea
| html              | MEDIUMTEXT    | Shopware.form.field.TinyMCE
| integer           | INT(11)       | Ext.form.field.Number
| float             | DOUBLE        | Ext.form.field.Number
| boolean           | INT(1)        | Ext.form.field.Checkbox
| date              | DATE          | Shopware.apps.Base.view.element.Date
| datetime          | DATETIME      | Shopware.apps.Base.view.element.DateTime
| combobox          | MEDIUMTEXT    | Ext.form.field.ComboBox
| single_selection  | VARCHAR(500)  | Shopware.form.field.SingleSelection
| multi_selection   | MEDIUMTEXT    | Shopware.form.field.Grid

The single and multi selection backend view depends on the configured entity, for example if `Shopware\Models\Article\Article` configured as entity shopware displays the `Shopware.form.field.ProductGrid` class

# Change attribute schema
To change the database schema of an attribute table, the `Shopware\Bundle\AttributeBundle\Service\CrudService` can be used. The service contains three functions to change the schema:

## Create an attribute
`\Shopware\Bundle\AttributeBundle\Service\CrudService::create($table, $column, $unifiedType, array $data = [], $considerDependencies = false)`
Creates a new column in the provided table with the unified type.
```
$service = Shopware()->Container()->get('shopware_attribute.crud_service');
$service->create('s_articles_attributes', 'my_attribute', 'string');
```
The above example only creates a new attribute column in the database but doesn't creates a configuration for the backend view. The fourth parameter `$data` allows to configure this.
```
$service = Shopware()->Container()->get('shopware_attribute.crud_service');
$service->create(
    's_articles_attributes',
    'my_attribute',
    'string',
    [
        'label' => 'backend field label',
        'supportText' => 'backend support text',
        'helpText' => 'backend help text',
        'position' => 10,
        'displayInBackend' => true,     //field has to be displayed in the backend module of the main entity
        'translatable' => true          //field can be translated over the backend translation module
    ]
);
```
Attribute columns which created over a plugin or directly over an `ALTER TABLE` statement in the database can't be modified over the backend module.

## Update an attribute
`\Shopware\Bundle\AttributeBundle\Service\CrudService::update($table, $originalColumnName, $newColumnName, $unifiedType, array $data = [], $considerDependencies = false)` allows to update an existing attribute column with a new name, type and backend configuration.

```
$service = Shopware()->Container()->get('shopware_attribute.crud_service');
$service->update(
    's_articles_attributes',
    'my_attribute'
    'my_new_attribute',
    'text',
    ['label' => 'new label',]
);
```

## Delete an attribute
`\Shopware\Bundle\AttributeBundle\Service\CrudService::delete($table, $column, $considerDependencies = false)`
Deletes the provided column in the provided table.

```
$service = Shopware()->Container()->get('shopware_attribute.crud_service');
$service->delete('s_articles_attributes', 'my_attribute');
```

# Attribute dependencies
Some attribute tables in shopware has dependencies to other attribute tables. For example the `s_user_addresses_attributes` table which contains all addresses of customers.
Attributes which generated which generated in this table should generated in most cases in all other address tables (`s_oder_*` and `s_user_billingaddress` and `s_user_shippingaddress`)
To consider this table dependencies, all public functions of the `CrudService` support an optional parameter `$considerDependencies` which defined with default `false`.
The following example shows how to generate or update an attribute for all address tables:
```
$service = Shopware()->Container()->get('shopware_attribute.crud_service');
$service->create(
    's_user_addresses_attributes',
    'my_attribute'
    'text',
    ['label' => 'backend label'],
    true    //flag to update depending tables
);

$service = Shopware()->Container()->get('shopware_attribute.crud_service');
$service->update(
    's_user_addresses_attributes',
    'my_attribute'
    'my_new_attribute',
    'text',
    ['label' => 'new label'],
    true    //flag to update depending tables
);
```

## Load and save attribute data
All data loaded and saved over the corresponding services of the `AttributeBundle`.
The following example shows a code snippet from the shopware core, where the basket attributes should be transported to the order table

```
$loader = Shopware()->Container()->get('shopware_attribute.data_loader');
$persister = Shopware()->Container()->get('shopware_attribute.data_persister');

$data = $loader->load('s_order_basket_attributes', $basketRow['id']);
$persister->persist($data, 's_order_details_attributes', $orderDetailsId);
```
This example works even for the case, that both tables has different attribute columns. The data persister only persists attribute data which are defined in the database schema. All other values will be stripped.


### EntitySearch
Through the new attribute system a new search controller was implemented, which allows to search for any entity in Shopware.

* Required parameters:
    * `model` - Class name of the model to search, e.g. `\Shopware\Models\Article\Supplier`
* Extra parameters:
    * `ids` - If provided, only selects the given IDs and ignores all other parameters
* Optional parameters:
    * `limit` - Limits the result set
    * `offset` - Sets an offset to the result set
    * `term` - Term to search for in any column of entity
    * `sortings` - Sort results using the Doctrine sorting syntax
    * `conditions` - Filter results using the Doctrine filter syntax

Each entity can configure their own data providers and search gateways. All search repositories are stored in the `\Shopware\Bundle\AttributeBundle\Repository\Registry`.

