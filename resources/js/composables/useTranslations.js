import { usePage } from '@inertiajs/vue3';

export function useTranslations() {
    const page = usePage();
    
    const trans = (key, replacements = {}) => {
        const translations = page.props.translations || {};
        const keys = key.split('.');
        let value = translations;
        
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return key; // Return the key if translation not found
            }
        }
        
        // Handle replacements
        if (typeof value === 'string' && Object.keys(replacements).length > 0) {
            return value.replace(/:(\w+)/g, (match, key) => {
                return replacements[key] !== undefined ? replacements[key] : match;
            });
        }
        
        return value;
    };
    
    return { trans, t: trans };
}
