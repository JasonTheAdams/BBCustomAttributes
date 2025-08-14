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
    
    // Set the global flag to indicate processing is complete
    window.customAttrsProcessingComplete = true;
    
    // Dispatch the custom event to notify other scripts
    const event = new CustomEvent('customAttrsProcessed');
    document.dispatchEvent(event);
});
