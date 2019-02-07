<?php
/**
 * Plugin Name: Beaver Builder Custom Attributes
 * Plugin URI: https://github.com/JasonTheAdams/BBCustomAttributes
 * Description: Adds the ability to set custom attributes for all modules
 * Version: 1.0.0
 * Author: Jason Adams
 * Author URI: https://github.com/jasontheadams
 * Requires PHP: 5.6
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

namespace JasonTheAdams\BBCustomAttributes;

class BBCustomAttributes
{
    /**
     * Connects to the needed hooks
     */
    public function load()
    {
        add_action('plugins_loaded', [$this, 'registerForm']);
        add_filter('fl_builder_register_settings_form', [$this, 'filterAdvancedModule'], 10, 2);
        add_filter('fl_builder_module_attributes', [$this, 'filterAttributes'], 10, 2);
    }

    /**
     * Registers the custom attributes form
     */
    public function registerForm()
    {
        if ( ! class_exists('\FLBuilder')) {
            return;
        }

        \FLBuilder::register_settings_form('custom_attributes', [
            'title' => 'Custom Attributes',
            'tabs'  => [
                'attributes' => [
                    'title'    => 'Attributes',
                    'sections' => [
                        'general' => [
                            'title'  => '',
                            'fields' => [
                                'key'      => [
                                    'type'    => 'text',
                                    'label'   => 'Key',
                                    'help'    => 'Attribute key',
                                    'preview' => ['type' => 'none']
                                ],
                                'value'    => [
                                    'type'    => 'text',
                                    'label'   => 'Value',
                                    'help'    => 'Attribute value',
                                    'preview' => ['type' => 'none']
                                ],
                                'override' => [
                                    'type'    => 'select',
                                    'label'   => 'Override Attribute',
                                    'help'    => 'If the attribute already exists from another source, override or yield. Selecting no is safer and will avoid conflicts.',
                                    'default' => 'no',
                                    'options' => [
                                        'no'  => 'No',
                                        'yes' => 'Yes'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Adds the custom attributes field to the CSS section of the advanced section
     *
     * @param array  $form
     * @param string $id
     *
     * @return array
     */
    public function filterAdvancedModule($form, $id)
    {
        if ('module_advanced' === $id) {
            $form['sections']['css_selectors']['fields']['custom_attributes'] = [
                'type'         => 'form',
                'form'         => 'custom_attributes',
                'label'        => 'Attributes',
                'help'         => 'Adds custom attributes to the module',
                'multiple'     => true,
                'preview_text' => 'key'
            ];
        }

        return $form;
    }

    /**
     * Adds the custom attributes to the module being rendered
     *
     * @param array            $attributes
     * @param \FLBuilderModule $module
     *
     * @return array
     */
    public function filterAttributes($attributes, $module)
    {
        if (isset($module->settings->custom_attributes)) {
            foreach ($module->settings->custom_attributes as $attribute) {
                $key = esc_attr($attribute->key);
                if ('yes' === $attribute->override || ! isset($attributes[$key])) {
                    $attributes[$key] = esc_attr($attribute->value);
                }
            }
        }

        return $attributes;
    }
}

(new BBCustomAttributes())->load();
