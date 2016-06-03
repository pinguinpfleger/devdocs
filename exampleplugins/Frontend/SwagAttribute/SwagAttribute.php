<?php

namespace SwagAttribute;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\PluginContext;
use Shopware\Models\Article\Article;

class SwagAttribute extends Plugin
{
    public function install(PluginContext $context)
    {
        $attributeService = $this->container->get('shopware_attribute.crud_service');

        $attributeService->update(
            's_articles_attributes',
            'my_column',
            'string',
            ['label' => 'Backend field label', 'displayInBackend' => true, 'custom' => true],
            null,
            true
        );

        $attributeService->update(
            's_articles_attributes',
            'my_own_validation',
            'string',
            ['label' => 'My own validation', 'displayInBackend' => true, 'translatable' => true],
            null,
            true
        );

        $attributeService->update(
            's_articles_attributes',
            'my_own_type',
            'text',
            ['label' => 'My own extjs type', 'displayInBackend' => true, 'translatable' => true],
            null,
            true
        );

        $attributeService->update(
            's_articles_attributes',
            'my_article_selection',
            'multi_selection',
            ['label' => 'My article selection', 'entity' => Article::class, 'translatable' => true],
            null,
            true
        );

        $this->generateEntities();

        $context->scheduleClearCache(PluginContext::CACHE_LIST_DEFAULT);
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Base' => 'extendExtJS'
        ];
    }

    public function extendExtJS(\Enlight_Event_EventArgs $arguments)
    {
        /** @var \Enlight_View_Default $view */
        $view = $arguments->getSubject()->View();
        $view->addTemplateDir($this->getPath() . '/Views/');
        $view->extendsTemplate('backend/swag_attribute/Shopware.attribute.Form.js');
    }

    private function generateEntities()
    {
        $this->container->get('models')->generateAttributeModels(
            ['s_articles_attributes']
        );

        //update all depending tables of s_articles_attributes
        $mapping = $this->container->get('shopware_attribute.table_mapping');
        $this->container->get('models')->generateAttributeModels(
            $mapping->getDependingTables('s_articles_attributes')
        );
    }
}