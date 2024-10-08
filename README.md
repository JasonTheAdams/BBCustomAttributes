# Beaver Builder Custom Attributes

## What does this do?
It adds the ability to add custom attributes to modules, columns, and rows within Beaver Builder the same way you would add
a class or ID. In fact, you'll find the new field right below those fields in the Advanced tab.

## How does it work?
After installing, to add an attribute to modules, columns, and rows, you navigate to the advanced tab. There will now be a field below the Label input where you can add your attributes.
When you click into a custom attribute, you will have inputs for Key, Value, Target Selector, and Override Attribute.
- The **Key** and **Value** inputs are your attribute name/value pairs like: `key="value"`
- The **Target Selector** is a way to add your custom attribute to an inner element of the current element. By default, all attributes are added to the outer wrapper of a module/column/row. For example, you could add an attribute like `id`, `class`, `title` or an `aria-` attribute to an `<a>` inside a Button Module by typing in a Target Selector of `a` or `a.fl-button`. See below for more info about this feature.
- The **Override Attribute** select input allows you to override the attribute if that attribute already exists from another source. Selecting 'No' is safer and will avoid conflicts.

![custom-attribute-form](https://github.com/user-attachments/assets/9f60dea4-c149-4533-8c52-10c1c6227fe5)

## Advanced use of the Target Selector
The Target Selector allows you to apply custom attributes to inner elements within the current element. Here are some advanced usage tips:
- If your Target Selector matches more than one inner element, all matches will receive the attribute.
- These inner attributes are added with JavaScript, so if you are trying to access this data with your own JavaScript, you will need to make sure it runs after this JavaScript runs.
### Ensuring custom JavaScript runs after attributes are applied
The JavaScript to add the inner element attributes runs on `DOMContentLoaded` in the footer. So if you need to run JavaScript on those attributes, to ensure it runs after the custom attributes have been applied, you can listen for the `customAttrsProcessed` event. Additionally, you should check if the processing has already completed by checking the `window.customAttrsProcessingComplete` flag. Here is an example:
```
function runCustomJs() {
    // Your custom JavaScript here
}

if (window.customAttrsProcessingComplete) {
    // If the custom attributes processing is already complete
    runCustomJs();
} else {
    // Otherwise, wait for the customAttrsProcessed event
    document.addEventListener('customAttrsProcessed', runCustomJs);
}
```

## How do I install it? What about updates?
1. Download the zip file of this plugin
2. Upload it to your WordPress install
3. Activate the plugin.

### How Updates Work

Even though the plugin is not listed in the WordPress repository, you’ll still receive updates through the WordPress dashboard like any other plugin. The GitHub updater built into the plugin ensures that whenever there’s a new version available on GitHub, it will appear in your WordPress updates section.

This means you don’t have to manually check GitHub for updates—the plugin will notify you of new releases and allow you to update in one click, just like with plugins from the WordPress plugin repository.

## Is this supported?
This is intended to be a very simple and light-weight plugin. Ideally it shouldn't really require much support.
That said, if you create an issue or pull request I'll get to it when I'm able. Please don't expect too much as this
is just a freebie intended to help others.
