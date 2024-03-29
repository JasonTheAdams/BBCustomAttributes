<?php
/**
 * Plugin Name: Beaver Builder Custom Attributes
 * Plugin URI: https://github.com/JasonTheAdams/BBCustomAttributes
 * Description: Adds the ability to set custom attributes for modules, columns, and rows
 * Version: 1.1.0
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
        add_filter('fl_builder_register_settings_form', [$this, 'filterAdvancedTabAttr'], 10, 2);
        add_filter('fl_builder_module_attributes', [$this, 'filterAttributes'], 10, 2);
        add_filter('fl_builder_column_attributes', [$this, 'filterAttributes'], 10, 2);
        add_filter('fl_builder_row_attributes', [$this, 'filterAttributes'], 10, 2);
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
            'title' => __('Custom Attributes'),
            'tabs'  => [
                'attributes' => [
                    'title'    => __('Attribute'),
                    'sections' => [
                        'general' => [
                            'title'  => '',
                            'fields' => [
                                'key'      => [
                                    'type'    => 'text',
                                    'label'   => __('Key'),
                                    'help'    => __('Attribute key'),
                                    'preview' => ['type' => 'none']
                                ],
                                'value'    => [
                                    'type'    => 'text',
                                    'label'   => __('Value'),
                                    'help'    => __('Attribute value'),
                                    'preview' => ['type' => 'none']
                                ],
                                'override' => [
                                    'type'    => 'select',
                                    'label'   => __('Override Attribute'),
                                    'help'    => __('If the attribute already exists from another source, override or yield. Selecting no is safer and will avoid conflicts.'),
                                    'default' => 'no',
                                    'options' => [
                                        'no'  => __('No'),
                                        'yes' => __('Yes')
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
    public function filterAdvancedTabAttr($form, $id)
    {
        if ('module_advanced' === $id) {
            $form['sections']['css_selectors']['fields']['custom_attributes'] = [
                'type'         => 'form',
                'form'         => 'custom_attributes',
                'label'        => __('Attribute'),
                'help'         => __('Adds custom attributes to the module'),
                'multiple'     => true,
                'preview_text' => 'key'
            ];
        }
        
        if('col' === $id ) {
            $form['tabs']['advanced']['sections']['css_selectors']['fields']['custom_attributes'] = [
                'type'         => 'form',
                'form'         => 'custom_attributes',
                'label'        => __('Attribute'),
                'help'         => __('Adds custom attributes to the column'),
                'multiple'     => true,
                'preview_text' => 'key'
            ];
        }
		
        if('row' === $id ) {
            $form['tabs']['advanced']['sections']['css_selectors']['fields']['custom_attributes'] = [
                'type'         => 'form',
                'form'         => 'custom_attributes',
                'label'        => __('Attribute'),
                'help'         => __('Adds custom attributes to the row'),
                'multiple'     => true,
                'preview_text' => 'key'
            ];
        }

        return $form;
    }

    /**
     * Adds the custom attributes to the row/column/module being rendered
     *
     * @param array    $attributes
     * @param object   $element
     *
     * @return array
     */
    public function filterAttributes($attributes, $element)
    {
        if (isset($element->settings->custom_attributes)) {
            foreach ($element->settings->custom_attributes as $attribute) {
                $key = esc_attr($attribute->key);
                if ('yes' === $attribute->override || !isset($attributes[$key])) {
                    $value = do_shortcode(esc_attr($attribute->value));
                    $attributes[$key] = $value;
                }
            }
        }

        return $attributes;
    }
}

(new BBCustomAttributes())->load();
