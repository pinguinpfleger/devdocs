
//{block name="backend/base/attribute/form"}

//{$smarty.block.parent}

//{include file="backend/swag_attribute/SwagAttribute.form.field.OwnType.js"}

//{include file="backend/swag_attribute/SwagAttribute.FieldHandler.js"}

Ext.define('Shopware.attribute.Form-SwagAttribute', {
    override: 'Shopware.attribute.Form',

    registerTypeHandlers: function() {
        var handlers = this.callParent(arguments);

        return Ext.Array.insert(handlers, 0, [ Ext.create('SwagAttribute.FieldHandler') ]);
    }
});

//{/block}
