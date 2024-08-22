<?php
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
        add_action('wp_footer', [$this, 'enqueueCustomAttributesScript']);
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
                                'target'   => [
                                    'type'    => 'text',
                                    'label'   => __('Target Selector'),
                                    'help'    => __('CSS selector of the inner element to apply the attribute to (leave blank to add to wrapper)'),
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
     * If there is a target value set, then add the attr to data-custom-attributes attr so js can add it to the inner element
     *
     * @param array    $attributes
     * @param object   $element
     *
     * @return array
     */
    public function filterAttributes($attributes, $element)
    {
        if (isset($element->settings->custom_attributes)) {
            $innerElementAttributes = [];
            $wrapperAttributes = [];

            foreach ($element->settings->custom_attributes as $attribute) {
                if (!empty($attribute->key) && !empty($attribute->value)) {
                    $attr = [
                        'key'      => esc_attr($attribute->key),
                        'value'    => do_shortcode(esc_attr($attribute->value)),
                        'override' => esc_attr($attribute->override)
                    ];

                    if (!empty($attribute->target)) {
                        $attr['target'] = esc_attr($attribute->target);
                        $innerElementAttributes[] = $attr;
                    } else {
                        $wrapperAttributes[$attr['key']] = $attr['value'];
                    }
                }
            }

            // Add direct attributes to the wrapper
            foreach ($wrapperAttributes as $key => $value) {
                if (isset($attributes[$key]) && $attr['override'] === 'no') {
                    continue;
                }
                $attributes[$key] = $value;
            }

            // Add inner element custom attrs to data-custom-attributes attribute on the wrapper in prep for js processing
            if (!empty($innerElementAttributes)) {
                $attributes['data-custom-attributes'] = esc_attr(json_encode($innerElementAttributes));
            }
        }

        return $attributes;
    }

    /**
     * Enqueues the JavaScript for processing custom attributes for inner elements
     */
    public function enqueueCustomAttributesScript()
    {
        ?>
        <script id='bb-custom-attrs-script'>
            document.addEventListener('DOMContentLoaded', function() {
                const elsWithInnerCustomAttrs = document.querySelectorAll('[data-custom-attributes]');
                
                elsWithInnerCustomAttrs.forEach(function(element) {
                    const customAttributes = JSON.parse(element.getAttribute('data-custom-attributes'));
                    
                    customAttributes.forEach(function(attribute) {
                        const targetElements = element.querySelectorAll(attribute.target);
                        
                        targetElements.forEach(function(targetElement) {
                            if (attribute.override === 'yes' || !targetElement.hasAttribute(attribute.key)) {
                                targetElement.setAttribute(attribute.key, attribute.value);
                            }
                        });
                    });
                    
                    // Remove the data-custom-attributes attribute after processing
                    element.removeAttribute('data-custom-attributes');
                });
                // Dispatch a custom event and set a flag after processing custom attributes
                document.dispatchEvent(new Event('customAttrsProcessed'));
                window.customAttrsProcessingComplete = true;
            });
        </script>
        <?php
    }
}